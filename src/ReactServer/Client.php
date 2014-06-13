<?php
namespace PublicUHC\MinecraftAuth\ReactServer;

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

    public function onData($data)
    {
        $hexData = bin2hex($data);
        echo "-> CLIENT DATA: $hexData\n";

        $packetLengthVarInt = VarInt::readUnsignedVarInt($data);
        $data = substr($data, $packetLengthVarInt->getDataLength());
        echo "-> PACKET LENGTH: {$packetLengthVarInt->getValue()}\n";
        $hexData = bin2hex($data);
        echo "-> CLIENT DATA: $hexData\n";

        $packetIDVarInt = VarInt::readUnsignedVarInt($data);
        $data = substr($data, $packetIDVarInt->getDataLength());
        echo "-> PACKET ID: {$packetIDVarInt->getValue()}\n";
        $hexData = bin2hex($data);
        echo "-> CLIENT DATA: $hexData\n";

        switch($this->stage) {
            case Stage::HANDSHAKE():
                switch($packetIDVarInt->getValue()) {
                    case 0:
                        $versionVarInt = VarInt::readUnsignedVarInt($data);
                        $data = substr($data, $packetIDVarInt->getDataLength());
                        echo "-> VERSION: {$versionVarInt->getValue()}\n";
                        $hexData = bin2hex($data);
                        echo "-> CLIENT DATA: $hexData\n";

                        $addressStringLength = VarInt::readUnsignedVarInt($data);
                        $data = substr($data, $addressStringLength->getDataLength());
                        echo "-> ADDRESS LENGTH: {$addressStringLength->getValue()}\n";
                        $hexData = bin2hex($data);
                        echo "-> CLIENT DATA: $hexData\n";

                        $address = substr($data, 0, $addressStringLength->getValue());
                        $data = substr($data, $addressStringLength->getValue());
                        echo "-> ADDRESS: $address\n";
                        $hexData = bin2hex($data);
                        echo "-> CLIENT DATA: $hexData\n";

                        $portShort = unpack('nshort', substr($data, 0, 2))['short'];
                        $data = substr($data, 2);
                        echo "-> PORT: {$portShort}\n";
                        $hexData = bin2hex($data);
                        echo "-> CLIENT DATA: $hexData\n";

                        $nextVarInt = VarInt::readUnsignedVarInt($data);
                        $data = substr($data, $nextVarInt->getDataLength());
                        echo "-> NEXT: {$nextVarInt->getValue()}\n";
                        $hexData = bin2hex($data);
                        echo "-> CLIENT DATA: $hexData\n";

                        $this->stage = Stage::get($nextVarInt->getValue());
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
    }

    public function getSocket()
    {
        return $this->socket;
    }
} 