<?php
namespace PublicUHC\MinecraftAuth\AuthServer;

use Exception;
use PublicUHC\MinecraftAuth\Protocol\DataTypeEncoders\VarInt;
use PublicUHC\MinecraftAuth\Protocol\InvalidDataException;
use PublicUHC\MinecraftAuth\Protocol\Packets\ClientboundPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\DisconnectPacket;
use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\Packets\ServerboundPacket;
use React\EventLoop\LoopInterface;
use React\Socket\Connection;

class BaseClient extends Connection {

    /** @var $stage Stage the current stage of the client */
    private $stage;

    /** @var string $buffer the current unread data from the stream */
    private $packetBuffer = '';

    /** @var $packetClassMap Array an array that stores stage+packetID -> class mappings for incoming packets */
    private $packetClassMap;

    /** @var resource mcrypt module for stream encryption */
    private $encryptor;

    /** @var String the secret/IV to use for stream encryption, null when not in encryption mode */
    private $secret;

    public function __construct($stream, LoopInterface $loop)
    {
        parent::__construct($stream, $loop);

        //default stage is handshake
        $this->stage = Stage::HANDSHAKE();

        //setup the default stage=>packetID=>class map TODO allow passing values in in constructor
        $this->packetClassMap = [
            STAGE::HANDSHAKE()->getValue() => [
                0x00 => 'PublicUHC\MinecraftAuth\Protocol\Packets\HandshakePacket'
            ],
            STAGE::STATUS()->getValue() => [
                0x00 => 'PublicUHC\MinecraftAuth\Protocol\Packets\StatusRequestPacket',
                0x01 => 'PublicUHC\MinecraftAuth\Protocol\Packets\PingRequestPacket'
            ],
            STAGE::LOGIN()->getValue() => [
                0x00 => 'PublicUHC\MinecraftAuth\Protocol\Packets\LoginStartPacket',
                0x01 => 'PublicUHC\MinecraftAuth\Protocol\Packets\EncryptionResponsePacket'
            ]
        ];

        //call $this->onData whenever there is data available
        $this->on('data', [$this, 'onData']);
    }

    /**
     * @return Stage the current client stage
     */
    public function getStage()
    {
        return $this->stage;
    }

    /**
     * @param Stage $stage the stage to set into
     */
    public function setStage(Stage $stage)
    {
        $this->stage = $stage;
    }

    /**
     * Called whenever there is data available on the stream
     * @param $data String the raw data
     */
    public function onData($data)
    {
        //if we're in encryption stage decrypt it first
        if($this->secret != null) {
            mcrypt_generic_init($this->encryptor, $this->secret, $this->secret);
            $data = mdecrypt_generic($this->encryptor, $data);
            mcrypt_generic_deinit($this->encryptor);
        }

        //attempt to parse the data as a packet
        try {

            //add the data to the current buffer
            $this->packetBuffer .= $data;

            //for as long as we have data left in the buffer
            do {

                //read the packet length from the stream
                $packetLengthVarInt = VarInt::readUnsignedVarInt($this->packetBuffer);

                //not enough data to read the packet length, wait for more data
                if($packetLengthVarInt === false) {
                    return;
                }

                //total length of the packet is the length of the varint + it's value
                $totalLength = $packetLengthVarInt->getDataLength() + $packetLengthVarInt->getValue();

                //if we don't have enough data to read the entire packet wait for more data to enter
                $bufferLength = strlen($this->packetBuffer);
                if ($bufferLength < $totalLength) {
                    return;
                }

                //remove the packet length varint from the buffer
                $this->packetBuffer = substr($this->packetBuffer, $packetLengthVarInt->getDataLength());

                //read the packet ID from the buffer
                $packetDataWithPacketID = substr($this->packetBuffer, 0, $packetLengthVarInt->getValue());

                //remove the rest of the packet from the buffer
                $this->packetBuffer = substr($this->packetBuffer, $packetLengthVarInt->getValue());

                //read the packet ID
                $packetID = VarInt::readUnsignedVarInt($packetDataWithPacketID);

                //get the raw packet data
                $packetData = substr($packetDataWithPacketID, $packetID->getDataLength());

                //trigger packet processing
                $this->processPacket($packetID->getValue(), $packetData);

                //if we have buffer left run again
            } while (strlen($this->packetBuffer) > 0);

            //if any exceptions are thrown (error parsing the packets e.t.c.) send a disconnect packet
        } catch (Exception $ex) {
            echo "EXCEPTION IN PACKET PARSING {$ex->getMessage()}\n";
            echo $ex->getTraceAsString();
            $dis = new DisconnectPacket();
            $dis->setReason('Internal Server Error: '.$ex->getMessage());
            $this->end($dis->encodePacket());
        }
    }

    /**
     * @param $id int the packet ID
     * @param $data string the raw packet data
     * @throws \PublicUHC\MinecraftAuth\Protocol\InvalidDataException on packet parsing failing
     */
    private function processPacket($id, $data) {
        //attempt to get all the packets for the stage we are on
        $stageMap = $this->packetClassMap[$this->stage->getValue()];
        if(null == $stageMap) {
            throw new InvalidDataException('Invalid Stage');
        }

        //attempt to get the class for the current packet ID
        $packetClass = $stageMap[$id];
        if(null == $packetClass) {
            throw new InvalidDataException("Unknown packet ID $id for stage {$this->stage->getName()}");
        }

        /**
         * Attempt to create the new packet
         * @var $packet ServerboundPacket
         */
        $packet = new $packetClass();

        //parse the raw data into the packet
        $packet->fromRawData($data);

        //send an event of style HANDSHAKE.HandshakePacket with the packet data
        $className = join('', array_slice(explode('\\', $packetClass), -1));

        $this->emit("{$packet->getStage()->getName()}.$className", [$packet]);
    }

    /**
     * Send a packet to the client, will use cipher if cipher is enabled
     *
     * @param ClientboundPacket $packet the packet to send to the client
     */
    public function sendPacket(ClientboundPacket $packet)
    {
        $this->write($packet->encodePacket());
    }

    /**
     * Disconnects the client with an optional packet
     *
     * @param ClientboundPacket $packet the packet to send if needed
     */
    public function disconnectClient(ClientboundPacket $packet = null)
    {
        $this->end($packet->encodePacket());
    }

    /**
     * Override the base write to add encryption if we're in an encypted stage
     *
     * @param $data String the data to write
     * @return bool
     * @Override
     */
    public function write($data)
    {
        //encrypt the data if needed
        if($this->secret != null) {
            mcrypt_generic_init($this->encryptor, $this->secret, $this->secret);
            $data = mcrypt_generic($this->encryptor, $data);
            mcrypt_generic_deinit($this->encryptor);
        }
        return parent::write($data);
    }

    /**
     * Override the base end to add encryption if we're in an encypted stage
     *
     * @param $data String the data to write on ending
     * @Override
     */
    public function end($data = null)
    {
        //encrypt the data if needed
        if($data != null && $this->secret != null) {
            mcrypt_generic_init($this->encryptor, $this->secret, $this->secret);
            $data = mcrypt_generic($this->encryptor, $data);
            mcrypt_generic_deinit($this->encryptor);
        }
        parent::end($data);
    }

    /**
     * Enables encryption of the stream
     *
     * @param $secret String the secret generated by the client, used to encrypt the stream
     */
    public function enableAES($secret)
    {
        $this->secret = $secret;
        $this->encryptor = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CFB, '');
    }

    /**
     * Disable encryption of the stream
     */
    public function disableAES()
    {
        mcrypt_module_close($this->encryptor);
        $this->encryptor = $this->secret = null;
    }
} 