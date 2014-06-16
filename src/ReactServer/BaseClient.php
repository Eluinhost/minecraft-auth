<?php
namespace PublicUHC\MinecraftAuth\ReactServer;

use Evenement\EventEmitter;
use PublicUHC\MinecraftAuth\Protocol\DataTypeEncoders\VarInt;
use PublicUHC\MinecraftAuth\Protocol\Packets\DisconnectPacket;
use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\Packets\ServerboundPacket;
use React\Socket\Connection;

class BaseClient extends EventEmitter {

    /** @var $stage Stage the current stage of the client */
    private $stage;

    /** @var string $buffer the current input buffer from the stream */
    private $buffer = '';

    /**
     * @var $packetClassMap Array an array that stores stage+packetID -> class mappings for incoming packets
     */
    private $packetClassMap;

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
        echo 'Found packet '.get_class($packet)." ID: $id\n";

        $packet->fromRawData($data);
        var_dump($packet);

        $className = join('', array_slice(explode('\\', $packetClass), -1));
        echo "FIRING EVENT {$packet->getStage()->getName()}.$className\n";
        $this->emit("{$packet->getStage()->getName()}.$className", [$packet, $connection]);
    }
} 