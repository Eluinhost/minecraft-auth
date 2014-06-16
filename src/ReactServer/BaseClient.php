<?php
namespace PublicUHC\MinecraftAuth\ReactServer;

use Evenement\EventEmitter;
use PublicUHC\MinecraftAuth\Protocol\DataTypeEncoders\VarInt;
use PublicUHC\MinecraftAuth\Protocol\Packets\ClientboundPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\DisconnectPacket;
use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\Packets\ServerboundPacket;
use React\Socket\Connection;

class BaseClient extends EventEmitter {

    /** @var $stage Stage the current stage of the client */
    private $stage;

    /** @var string $buffer the current input buffer from the stream */
    private $buffer = '';

    /** @var $packetClassMap Array an array that stores stage+packetID -> class mappings for incoming packets */
    private $packetClassMap;

    /** @var $currentConnection Connection The latest reference for the connection */
    private $currentConnection;

    /** @var resource mcrypt module for stream encryption */
    private $encryptor;

    /** @var String the secret/IV to use for stream encryption */
    private $secret;

    public function __construct(Connection $socket)
    {
        $this->stage = Stage::HANDSHAKE();

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

        $socket->on('data', [$this, 'onData']);
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

    public function onData($data, Connection $connection)
    {
        $this->currentConnection = $connection;

        if($this->secret != null) {
            mcrypt_generic_init($this->encryptor, $this->secret, $this->secret);

            $data = mdecrypt_generic($this->encryptor, $data);

            mcrypt_generic_deinit($this->encryptor);
        }

        try {
            $this->buffer .= $data;

            do {
                $packetLengthVarInt = VarInt::readUnsignedVarInt($this->buffer);
                if($packetLengthVarInt === false) {
                    return;
                }
                $totalLength = $packetLengthVarInt->getDataLength() + $packetLengthVarInt->getValue();

                $bufferLength = strlen($this->buffer);
                //if we don't have enough data to read the entire packet wait for more data to enter
                if ($bufferLength < $totalLength) {
                    return;
                }

                //cut the packet length varint out
                $this->buffer = substr($this->buffer, $packetLengthVarInt->getDataLength());

                //the data with the packet ID
                $packetDataWithPacketID = substr($this->buffer, 0, $packetLengthVarInt->getValue());

                //cut out the packet data from the buffer
                $this->buffer = substr($this->buffer, $packetLengthVarInt->getValue());

                $packetID = VarInt::readUnsignedVarInt($packetDataWithPacketID);

                //just the data
                $packetData = substr($packetDataWithPacketID, $packetID->getDataLength());

                $this->processPacket($packetID->getValue(), $packetData, $connection);
            } while (strlen($this->buffer) > 0);
        } catch (\Exception $ex) {
            echo "EXCEPTION IN PACKET PARSING {$ex->getMessage()}\n";
            echo $ex->getTraceAsString();
            $dis = new DisconnectPacket();
            $dis->setReason('Internal Server Error: '.$ex->getMessage());
            $connection->end($dis->encodePacket());
        }
    }

    private function processPacket($id, $data, Connection $connection) {
        $stageMap = $this->packetClassMap[$this->stage->getValue()];
        if(null == $stageMap) {
            throw new InvalidDataException('Invalid Stage');
        }

        $packetClass = $stageMap[$id];
        if(null == $packetClass) {
            throw new InvalidDataException("Unknown packet ID $id for stage {$this->stage->getName()}");
        }

        /** @var $packet ServerboundPacket */
        $packet = new $packetClass();

        $packet->fromRawData($data);
        var_dump($packet);

        $className = join('', array_slice(explode('\\', $packetClass), -1));
        $this->emit("{$packet->getStage()->getName()}.$className", [$packet, $connection]);
    }

    /**
     * Send a packet to the client, will use cipher if cipher is enabled TODO cipher
     *
     * @param ClientboundPacket $packet the packet to send to the client
     */
    public function sendPacket(ClientboundPacket $packet)
    {
        var_dump($packet);
        $packetData = $packet->encodePacket();

        if($this->secret != null) {
            mcrypt_generic_init($this->encryptor, $this->secret, $this->secret);

            $packetData = mcrypt_generic($this->encryptor, $packetData);

            mcrypt_generic_deinit($this->encryptor);
        }
        $this->currentConnection->write($packetData);
    }

    /**
     * Disconnects the client with an optional packet
     *
     * @param ClientboundPacket $packet the packet to send if needed
     */
    public function disconnectClient(ClientboundPacket $packet = null)
    {
        $packetData = $packet->encodePacket();

        if($this->secret != null) {
            mcrypt_generic_init($this->encryptor, $this->secret, $this->secret);

            $packetData = mcrypt_generic($this->encryptor, $packetData);

            mcrypt_generic_deinit($this->encryptor);
        }
        $this->currentConnection->end($packetData);
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