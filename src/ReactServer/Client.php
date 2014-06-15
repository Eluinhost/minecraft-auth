<?php
namespace PublicUHC\MinecraftAuth\ReactServer;

use PublicUHC\MinecraftAuth\Protocol\DisconnectPacket;
use PublicUHC\MinecraftAuth\Protocol\EncryptionRequestPacket;
use PublicUHC\MinecraftAuth\Protocol\PingPacket;
use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\HandshakePacket;
use PublicUHC\MinecraftAuth\Protocol\StatusResponsePacket;
use PublicUHC\MinecraftAuth\ReactServer\DataTypes\VarInt;
use PublicUHC\MinecraftAuth\ReactServer\Encryption\Certificate;
use React\Socket\Connection;

class Client {

    private $stage;
    private $buffer = '';
    private $certificate;

    public function __construct(Connection $socket, Certificate $certificate)
    {
        $socket->on('data', [$this, 'onData']);
        $this->stage = Stage::HANDSHAKE();
        $this->certificate = $certificate;
    }

    public function onData($data, Connection $connection)
    {
        try {
            echo "NEW DATA: " . bin2hex($data) . "\n";

            $this->buffer .= $data;

            do {
                echo "START PROCESS BUFFER " . bin2hex($this->buffer) . "\n";

                $packetLengthVarInt = VarInt::readUnsignedVarInt($this->buffer);
                if($packetLengthVarInt === false) {
                    echo "NOT ENOUGH DATA TO READ PACKET LENGTH\n";
                    return;
                }
                $totalLength = $packetLengthVarInt->getDataLength() + $packetLengthVarInt->getValue();
                echo "-> PACKET LENGTH: {$packetLengthVarInt->getValue()}/$totalLength\n";

                $bufferLength = strlen($this->buffer);
                //if we don't have enough data to read the entire packet wait for more data to enter
                if ($bufferLength < $totalLength) {
                    echo "NOT ENOUGH DATA TO READ PACKET ($bufferLength)\n";
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

                $len = strlen($this->buffer);
                $data = bin2hex($this->buffer);
                echo "FINISHED PACKET PROCESSING, EXTRA DATA: $data LEN $len\n";
            } while (strlen($this->buffer) > 0);
        } catch (\Exception $ex) {
            echo "EXCEPTION IN PACKET PARSING {$ex->getMessage()}\n";
            echo $ex->getTraceAsString();
            $dis = new DisconnectPacket('Internal Server Error: '.$ex->getMessage());
            $connection->end($dis->encode());
        }
    }

    private function processPacket($id, $data, Connection $connection)
    {
        echo "-> PACKET ID: $id, STAGE: {$this->stage->getName()}\n";
        switch ($this->stage) {
            case Stage::HANDSHAKE():
                switch ($id) {
                    case 0:
                        $handshake = HandshakePacket::fromStreamData($data);

                        //TODO check protocol e.t.c.

                        //switch stage
                        echo "  -> SWITCHING TO STAGE: {$handshake->getNextStage()->getName()}\n";
                        $this->stage = $handshake->getNextStage();
                        break;
                    default:
                        throw new InvalidDataException('Packet not implemented');
                }
                break;
            case Stage::STATUS():
                switch ($id) {
                    case 0:
                        //status request packet, no data
                        $response = new StatusResponsePacket();
                        $response->setDescription('§4▁§e▂§4▃§e▄§4▅§e▆§4▇§e█ §4§l   PHPAuthServer   §e█§4▇§e▆§4▅§e▄§4▃§e▂§4▁ §c▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔')
                            ->setMaxPlayers(-1)
                            ->setOnlineCount(-1)
                            ->setProtocol(5)
                            ->setVersion('1.7.6+');

                        $connection->write($response->encode());
                        break;
                    case 1:
                        //ping
                        echo "PING DATA: ".bin2hex($data)."\n";

                        $ping = new PingPacket($data);
                        $connection->end($ping->encode());
                        break;
                    default:
                        throw new InvalidDataException('Packet not implemented');
                }
                break;
            case Stage::LOGIN():
                switch ($id) {
                    case 0:
                        //login start packet
                        //TODO read the username
                        $request = new EncryptionRequestPacket();
                        $serverID = $request->getRandomServerID();
                        $verifyToken = $request->getRandomServerID();

                        $publicKey = $this->certificate->getPublicKey()->getPublicKey();
                        $publicKey = substr($publicKey, 28, -26);
                        $request->setPublicKey($publicKey)
                            ->setServerID($serverID)
                            ->setToken($verifyToken);

                        $connection->write($request->encode());
                }
                break;
            default:
                throw new InvalidDataException('Unknown stage');
        }
    }
} 