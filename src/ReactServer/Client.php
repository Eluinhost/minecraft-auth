<?php
namespace PublicUHC\MinecraftAuth\ReactServer;

use Exception;
use InvalidArgumentException;
use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
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
                            $versionVarInt = VarInt::readUnsignedVarInt($data);
                            $data = substr($data, $packetIDVarInt->getDataLength());
                            echo "  -> VERSION: {$versionVarInt->getValue()}\n";

                            $addressStringLength = VarInt::readUnsignedVarInt($data);
                            $data = substr($data, $addressStringLength->getDataLength());

                            $address = substr($data, 0, $addressStringLength->getValue());
                            $data = substr($data, $addressStringLength->getValue());
                            echo "  -> ADDRESS: $address\n";

                            $portShort = unpack('nshort', substr($data, 0, 2))['short'];
                            $data = substr($data, 2);
                            echo "  -> PORT: {$portShort}\n";

                            $nextVarInt = VarInt::readUnsignedVarInt($data);
                            echo "  -> NEXT STAGE #: {$nextVarInt->getValue()}\n";

                            try {
                                $nextStage = Stage::get($nextVarInt->getValue());

                                //disconnect if not a valid stage
                                if ($nextStage != Stage::LOGIN() && $nextStage != Stage::STATUS()) {
                                    $this->disconnect();
                                }

                                $this->stage = $nextStage;
                            } catch (InvalidArgumentException $ex) {
                                //disconnect, not a stage number
                                $this->disconnect();
                            }
                            break;
                        default:
                            throw new InvalidDataException('Packet not implemented');
                    }
                    break;
                case Stage::STATUS():
                    throw new InvalidDataException('Not implemented');
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