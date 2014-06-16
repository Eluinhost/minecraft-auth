<?php
namespace PublicUHC\MinecraftAuth\ReactServer;

use PublicUHC\MinecraftAuth\Protocol\Packets\DisconnectPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\EncryptionRequestPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\EncryptionResponsePacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\HandshakePacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\LoginStartPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\PingRequestPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\PingResponsePacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\StatusRequestPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\StatusResponsePacket;
use PublicUHC\MinecraftAuth\ReactServer\Encryption\Certificate;
use React\Socket\Connection;

class AuthClient extends BaseClient {

    private $certificate;
    private $verifyToken = null;
    private $serverID = null;

    public function __construct(Connection $socket, Certificate $certificate)
    {
        parent::__construct($socket);
        $this->certificate = $certificate;

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
        $this->setStage($packet->getNextStage());
    }
} 