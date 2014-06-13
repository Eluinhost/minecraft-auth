<?php
namespace PublicUHC\MinecraftAuth\ReactServer;

use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\HandshakePacket;
use PublicUHC\MinecraftAuth\Protocol\StatusResponsePacket;
use PublicUHC\MinecraftAuth\ReactServer\DataTypes\VarInt;
use React\Socket\Connection;

class Client {

    private $stage;
    private $buffer = '';

    public function __construct(Connection $socket)
    {
        $socket->on('data', [$this, 'onData']);
        $this->stage = Stage::HANDSHAKE();
    }

    public function onData($data, Connection $connection)
    {
        echo "NEW DATA: ".bin2hex($data)."\n";

        $this->buffer .= $data;

        do {

            echo "START PROCESS BUFFER " . bin2hex($this->buffer) . "\n";

            $packetLengthVarInt = VarInt::readUnsignedVarInt($data);
            echo "-> PACKET LENGTH: {$packetLengthVarInt->getValue()}\n";

            //if we don't have enough data to read the entire packet wait for more data to enter
            if (false == $packetLengthVarInt || strlen($this->buffer) < ($packetLengthVarInt->getDataLength() + $packetLengthVarInt->getValue())) {
                echo "NOT ENOUGH DATA TO READ PACKET\n";
                return;
            }

            $this->buffer = substr($this->buffer, $packetLengthVarInt->getDataLength());

            $packetIDVarInt = VarInt::readUnsignedVarInt($this->buffer);
            $this->buffer = substr($this->buffer, $packetIDVarInt->getDataLength());
            echo "-> PACKET ID: {$packetIDVarInt->getValue()}, STAGE: {$this->stage->getName()}\n";

            switch ($this->stage) {
                case Stage::HANDSHAKE():
                    switch ($packetIDVarInt->getValue()) {
                        case 0:
                            $handshake = HandshakePacket::fromStreamData($this->buffer);

                            //switch stage
                            echo "  -> SWITCHING TO STAGE: {$handshake->getNextStage()->getName()}\n";
                            $this->stage = $handshake->getNextStage();
                            break;
                        default:
                            throw new InvalidDataException('Packet not implemented');
                    }
                    break;
                case Stage::STATUS():
                    switch ($packetIDVarInt->getValue()) {
                        case 0:
                            //status request packet, no data
                            $response = new StatusResponsePacket();
                            $response->setDescription('Test Server')
                                ->setMaxPlayers(10)
                                ->setOnlineCount(0)
                                ->setProtocol(5)
                                ->setVersion('1.7.9');

                            $connection->write($response->encode());
                            break;
                        case 1:
                            //ping
                            echo "PING DATA: ".bin2hex($this->buffer)."\n";
                            break;
                        default:
                            throw new InvalidDataException('Packet not implemented');
                    }
                    break;
                default:
                    throw new InvalidDataException('Unknown stage');
            }

            $len = strlen($this->buffer);
            $data = bin2hex($this->buffer);
            echo "FINISHED PACKET PROCESSING, EXTRA DATA: $data LEN $len\n";
        }while(strlen($this->buffer) > 0);
    }
} 