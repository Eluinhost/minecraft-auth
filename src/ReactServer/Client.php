<?php
namespace PublicUHC\MinecraftAuth\ReactServer;

use Evenement\EventEmitter;
use PublicUHC\MinecraftAuth\Protocol\DataTypeEncoders\VarInt;
use PublicUHC\MinecraftAuth\Protocol\Packets\DisconnectPacket;
use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\Packets\EncryptionRequestPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\LoginStartPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\EncryptionResponsePacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\HandshakePacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\PingRequestPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\PingResponsePacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\ServerboundPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\StatusRequestPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\StatusResponsePacket;
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

    public function onEncryptionResponsePacket(EncryptionResponsePacket $packet, Connection $connection)
    {
        $verifyToken = $this->certificate->getPublicKey()->encrypt($this->verifyToken);

        echo "OUR TOKEN: ".bin2hex($verifyToken)."\n";
        echo "THEIR TOKEN ".bin2hex($packet->getToken())."\n";

        //TODO verify encryption success

        $secret = $this->certificate->getPrivateKey()->decrypt($packet->getSecret());

        echo "ENCRYPT SECRET ".bin2hex($packet->getSecret())."\n";
        echo "DECRYPT SECRET ".bin2hex($secret)."\n";

        //TODO check auth servers and fire the listeners

        $disconnect = new DisconnectPacket();
        $disconnect->setReason('AUTH COMPLETED');
        $connection->end(mcrypt_encrypt(
            MCRYPT_RIJNDAEL_128,
            $secret,
            $disconnect->encodePacket(),
            MCRYPT_MODE_CFB,
            $secret
        ));
    }

    public function onLoginStartPacket(LoginStartPacket $packet, Connection $connection)
    {
        $request = new EncryptionRequestPacket();
        $this->serverID = $request->getRandomServerID();
        $this->verifyToken = $request->getRandomServerID();

        $publicKey = $this->certificate->getPublicKey()->getPublicKey();
        $publicKey = substr($publicKey, 28, -26);
        $request->setPublicKey($publicKey)
            ->setServerID($this->serverID)
            ->setToken($this->verifyToken);

        $connection->write($request->encodePacket());
    }

    public function onPingRequestPacket(PingRequestPacket $packet, Connection $connection)
    {
        $ping = new PingResponsePacket();
        $ping->setPingData($packet->getPingData());

        $connection->end($ping->encodePacket());
    }

    public function onStatusRequestPacket(StatusRequestPacket $packet, Connection $connection)
    {
        $response = new StatusResponsePacket();
        $response->setDescription('§4▁§e▂§4▃§e▄§4▅§e▆§4▇§e█ §4§l   PHPAuthServer   §e█§4▇§e▆§4▅§e▄§4▃§e▂§4▁ §c▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔')
            ->setMaxPlayers(-1)
            ->setOnlineCount(-1)
            ->setProtocol(5)
            ->setVersion('1.7.6+');

        $connection->write($response->encodePacket());
    }

    public function onHandshakePacket(HandshakePacket $packet, Connection $connection)
    {
        if($packet->getProtocolVersion() != 5) {
            //TODO disconnect them
        }
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