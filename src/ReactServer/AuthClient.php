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
    private $username = null;
    private $uuid = null;

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

    public function getUsername()
    {
        return $this->username;
    }

    public function getUUID()
    {
        return $this->uuid;
    }

    public function onEncryptionResponsePacket(EncryptionResponsePacket $packet)
    {
        if(null == $this->verifyToken) {
            //packet received without a request sent
            $disconnect = new DisconnectPacket();
            $this->disconnectClient($disconnect->setReason('Packet received out of order'));
            return;
        }

        $verifyToken = $this->certificate->getPrivateKey()->decrypt($packet->getToken());

        if($verifyToken != $this->verifyToken) {
            $disconnect = new DisconnectPacket();
            $this->disconnectClient($disconnect->setReason('Invalid validation token'));
            return;
        }

        //decrypt the shared secret
        $secret = $this->certificate->getPrivateKey()->decrypt($packet->getSecret());

        //TODO check auth servers for the UUID

        $this->enableAES($secret);

        //trigger the login success
        $this->emit('login_success', [$this]);

        $disconnect = new DisconnectPacket();
        $this->disconnectClient($disconnect->setReason('AUTH COMPLETED'));
    }

    public function onLoginStartPacket(LoginStartPacket $packet)
    {
        $request = new EncryptionRequestPacket();
        $this->serverID = $request->getRandomServerID();
        $this->verifyToken = $request->getRandomServerID();
        $this->username = $packet->getUsername();

        $publicKey = $this->certificate->getPublicKey()->getPublicKey();
        $publicKey = substr($publicKey, 28, -26);
        $request->setPublicKey($publicKey)
            ->setServerID($this->serverID)
            ->setToken($this->verifyToken);

        $this->sendPacket($request);
    }

    public function onPingRequestPacket(PingRequestPacket $packet)
    {
        $ping = new PingResponsePacket();
        $ping->setPingData($packet->getPingData());

        $this->disconnectClient($ping);
    }

    public function onStatusRequestPacket(StatusRequestPacket $packet)
    {
        $response = new StatusResponsePacket();
        $response->setDescription('§4▁§e▂§4▃§e▄§4▅§e▆§4▇§e█ §4§l   PHPAuthServer   §e█§4▇§e▆§4▅§e▄§4▃§e▂§4▁ §c▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔')
            ->setMaxPlayers(-1)
            ->setOnlineCount(-1)
            ->setProtocol(5)
            ->setVersion('1.7.6+');

        $this->sendPacket($response);
    }

    public function onHandshakePacket(HandshakePacket $packet)
    {
        //only allow protocol 5 to connect (1.7.6+)
        if($packet->getProtocolVersion() != 5) {
            $disconnect = new DisconnectPacket();
            $disconnect->setReason('Invalid Minecraft Version');
            $this->disconnectClient($disconnect);
        }
        $this->setStage($packet->getNextStage());
    }
} 