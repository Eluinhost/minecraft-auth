<?php
namespace PublicUHC\MinecraftAuth\ReactServer;

use Exception;
use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\HandshakePacket;
use PublicUHC\MinecraftAuth\Protocol\StatusResponsePacket;
use PublicUHC\MinecraftAuth\Server\DataTypes\VarInt;
use PublicUHC\MinecraftAuth\Server\InvalidDataException;
use React\Socket\Connection;

class Client {

    private $socket;
    private $stage;

    public function __construct(Connection $socket)
    {
        $this->socket = $socket;
        $socket->on('data', [$this, 'onData']);
        $this->stage = Stage::HANDSHAKE();
    }

    public function disconnect()
    {
        $this->socket->end();
    }

    public function onData($data)
    {
        try {
            $packetLengthVarInt = VarInt::readUnsignedVarInt($data);
            $data = substr($data, $packetLengthVarInt->getDataLength());

            $packetIDVarInt = VarInt::readUnsignedVarInt($data);
            $data = substr($data, $packetIDVarInt->getDataLength());
            echo "-> PACKET ID: {$packetIDVarInt->getValue()}, STAGE: {$this->stage->getName()}\n";

            switch ($this->stage) {
                case Stage::HANDSHAKE():
                    switch ($packetIDVarInt->getValue()) {
                        case 0:
                            $handshake = HandshakePacket::fromStreamData($data);

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

                            $this->socket->write($response->encode());
                            break;
                        default:
                            throw new InvalidDataException('Packet not implemented');
                    }
                    break;
                default:
                    throw new InvalidDataException('Unknown stage');
            }
        } catch (Exception $ex) {
            echo "Exception thrown: {$ex->getMessage()} disconnecting client\n";
            $this->disconnect();
        }
    }

    public function getSocket()
    {
        return $this->socket;
    }
} 