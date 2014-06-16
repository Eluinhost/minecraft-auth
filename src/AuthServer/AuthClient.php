<?php
namespace PublicUHC\MinecraftAuth\AuthServer;

use PublicUHC\MinecraftAuth\Protocol\Packets\DisconnectPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\EncryptionRequestPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\EncryptionResponsePacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\HandshakePacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\LoginStartPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\PingRequestPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\PingResponsePacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\StatusRequestPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\StatusResponsePacket;
use PublicUHC\PhpYggdrasil\DefaultYggdrasil;
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

        $this->enableAES($secret);

        /*
         * TODO GENERATE LOGIN HASH
         * sha1 := Sha1()
         * sha1.update(ASCII encoding of the server id string from Encryption Request)
         * sha1.update(shared secret)
         * sha1.update(server's encoded public key from Encryption Request)
         * hash := sha1.hexdigest()  # String of hex characters
         *
         * sha1(Notch) :  4ed1f46bbe04bc756bcb17c0c7ce3e4632f06a48
         * sha1(jeb_)  : -7c9d5b0044c130109a5d7b5fb5c317c02b4e28c1
         * sha1(simon) :  88e16a1019277b15d58faf0541e11910eb756f6
         */
        $loginHash = '';

        $yggdrasil = new DefaultYggdrasil();

        try {
            $response = $yggdrasil->hasJoined($this->username, $loginHash);
            $this->uuid = $response->getUuid();

            //trigger the login success
            $this->emit('login_success', [$this]);

            $disconnect = new DisconnectPacket();
            $this->disconnectClient($disconnect->setReason('AUTH COMPLETED'));
        } catch (\Exception $ex) {
            $disconnect = new DisconnectPacket();
            $this->disconnectClient($disconnect->setReason($ex->getMessage()));
        }
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