<?php
namespace PublicUHC\MinecraftAuth\ReactServer;

use Evenement\EventEmitter;
use PublicUHC\MinecraftAuth\Protocol\DataTypeEncoders\VarInt;
use PublicUHC\MinecraftAuth\Protocol\Packets\DisconnectPacket;
use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\Packets\LoginStartPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\EncryptionResponsePacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\HandshakePacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\PingRequestPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\ServerboundPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\StatusRequestPacket;
use PublicUHC\MinecraftAuth\ReactServer\Encryption\Certificate;
use React\Socket\Connection;

class Client extends EventEmitter {

    /** @var $stage Stage the current stage of the client */
    private $stage;

    /** @var string $buffer the current input buffer from the stream */
    private $buffer = '';

    /** @var $certificate Certificate the ceritificate to use for signing */
    private $certificate;

    /**
     * @var $packetClassMap Array an array that stores stage+packetID -> class mappings for incoming packets
     */
    private $packetClassMap;

    public function __construct(Connection $socket, Certificate $certificate)
    {
        $socket->on('data', [$this, 'onData']);
        $this->stage = Stage::HANDSHAKE();
        $this->certificate = $certificate;

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

        $this->on('HANDSHAKE.HandshakePacket', [$this, 'onHandshakePacket']);
        $this->on('STATUS.StatusRequestPacket', [$this, 'onStatusRequestPacket']);
        $this->on('STATUS.PingRequestPacket', [$this, 'onPingRequestPacket']);
        $this->on('LOGIN.LoginStartPacket', [$this, 'onLoginStartPacket']);
        $this->on('LOGIN.EncryptionResponsePacket', [$this, 'onEncryptionResponsePacket']);
    }

    public function onEncryptionResponsePacket(EncryptionResponsePacket $packet)
    {
        //TODO
    }

    public function onLoginStartPacket(LoginStartPacket $packet)
    {
        //TODO
    }

    public function onPingRequestPacket(PingRequestPacket $packet)
    {
        //TODO
    }

    public function onStatusRequestPacket(StatusRequestPacket $packet)
    {
        //TODO
    }

    public function onHandshakePacket(HandshakePacket $packet)
    {
        $this->stage = $packet->getNextStage();
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

                $len = strlen($this->buffer);
                $data = bin2hex($this->buffer);
            } while (strlen($this->buffer) > 0);
        } catch (\Exception $ex) {
            echo "EXCEPTION IN PACKET PARSING {$ex->getMessage()}\n";
            echo $ex->getTraceAsString();
            $dis = new DisconnectPacket('Internal Server Error: '.$ex->getMessage());
            $connection->end($dis->encode());
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
        $this->emit("{$packet->getStage()->getName()}.$className", [$packet]);
    }

    /*
    private function processPacket($id, $data, Connection $connection)
    {
        $packetClass = 'PublicUHC\MinecraftAuth\Protocol\Packets\\' . $this->stage->getName() . '\SERVERBOUND\Packet_' . dechex($id);

        if(!class_exists($packetClass)) {
            throw new InvalidDataException("Unknown packet received ($id)");
        }
        $packet = new $packetClass();


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
                        $this->serverID = $request->getRandomServerID();
                        $this->verifyToken = $request->getRandomServerID();

                        $publicKey = $this->certificate->getPublicKey()->getPublicKey();
                        $publicKey = substr($publicKey, 28, -26);
                        $request->setPublicKey($publicKey)
                            ->setServerID($this->serverID)
                            ->setToken($this->verifyToken);

                        $connection->write($request->encode());
                        break;
                    case 1:
                        //encryption response
                        $encryptionResponse = EncryptionResponsePacket::fromStreamData($data);

                        $verifyToken = $this->certificate->getPublicKey()->encrypt($this->verifyToken);

                        echo "OUR TOKEN: ".bin2hex($verifyToken)."\n";
                        echo "THEIR TOKEN ".bin2hex($encryptionResponse->getToken())."\n";

                        //TODO verify encryption success

                        $secret = $this->certificate->getPrivateKey()->decrypt($encryptionResponse->getSecret());

                        echo "ENCRYPT SECRET ".bin2hex($encryptionResponse->getSecret())."\n";
                        echo "DECRYPT SECRET ".bin2hex($secret)."\n";

                        //TODO check auth servers and fire the listeners

                        $disconnect = new DisconnectPacket('SOME FUCKING MESSAGE OR SOMETHING');
                        $connection->write(mcrypt_encrypt(
                            MCRYPT_RIJNDAEL_128,
                            $secret,
                            $disconnect->encode(),
                            MCRYPT_MODE_CFB,
                            $secret
                        ));
                        break;
                    default:
                        throw new InvalidDataException('Unknown packet ID for stage');
                }
                break;
            default:
                throw new InvalidDataException('Unknown stage');
        }
    }
    */
} 