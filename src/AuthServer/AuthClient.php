<?php
namespace PublicUHC\MinecraftAuth\AuthServer;

use Exception;
use Math_BigInteger;
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
use React\EventLoop\LoopInterface;

class AuthClient extends BaseClient {

    private $certificate;
    private $verifyToken = null;
    private $serverID = null;
    private $username = null;
    private $uuid = null;

    /**
     * @param Certificate $certificate used for client->server key generation
     * @param $stream resource the stream to create the connection from
     * @param LoopInterface $loop the loop to create the connection with
     */
    public function __construct(Certificate $certificate, $stream, LoopInterface $loop)
    {
        parent::__construct($stream, $loop);
        $this->certificate = $certificate;

        //set up all the events
        $this->on('HANDSHAKE.HandshakePacket', [$this, 'onHandshakePacket']);
        $this->on('STATUS.StatusRequestPacket', [$this, 'onStatusRequestPacket']);
        $this->on('STATUS.PingRequestPacket', [$this, 'onPingRequestPacket']);
        $this->on('LOGIN.LoginStartPacket', [$this, 'onLoginStartPacket']);
        $this->on('LOGIN.EncryptionResponsePacket', [$this, 'onEncryptionResponsePacket']);
    }

    /**
     * @return null|string the client's username, or null if not set yet
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return null|string the client's uuid, or null if not set yet
     */
    public function getUUID()
    {
        return $this->uuid;
    }

    /**
     * Called on event LOGIN.EncryptionResponsePacket
     * @param EncryptionResponsePacket $packet
     */
    public function onEncryptionResponsePacket(EncryptionResponsePacket $packet)
    {
        //if we don't have a verifiyToken sent yet then the packet has been sent without us sending a request, disconnect client
        if(null == $this->verifyToken) {
            $this->disconnectClient((new DisconnectPacket())->setReason('Packet received out of order'));
            return;
        }

        //get the verify token from the packet it and decrypt it using our key
        $verifyToken = $this->certificate->getPrivateKey()->decrypt($packet->getToken());

        //if it isn't the same as our token then encryption failed, disconnect the client
        if($verifyToken != $this->verifyToken) {
            $this->disconnectClient((new DisconnectPacket())->setReason('Invalid validation token'));
            return;
        }

        //decrypt the shared secret from the packet and decrypt it using our key
        $secret = $this->certificate->getPrivateKey()->decrypt($packet->getSecret());

        //enable encryption using the client generated secret
        $this->enableAES($secret);

        //generate a login hash the same as the client for the session server request
        $publicKey = $this->certificate->getPublicKey()->getPublicKey();
        $publicKey = base64_decode(substr($publicKey, 28, -26)); //cut out the start and end lines of the key
        $loginHash = $this->serverID . $secret . $publicKey;
        $loginHash = self::sha1($loginHash);

        //create a new Yggdrasil for checking against Mojang
        $yggdrasil = new DefaultYggdrasil();

        try {
            //ask Mojang if the user has sent a join request, throws exception on failure
            $response = $yggdrasil->hasJoined($this->username, $loginHash);

            //get and set the clients UUID from the response
            $this->uuid = $response->getUuid();

            //trigger the login success with the disconnect packet
            $disconnect = new DisconnectPacket();
            $this->emit('login_success', [$this, $disconnect]);

            //if no reason was set after the event then set a default
            if($disconnect->getReasonJSON() == null) {
                $disconnect->setReason("No kick reason supplied");
            }

            //disconnect the client
            $this->disconnectClient($disconnect);
        } catch (Exception $ex) {
            echo "{$this->username} failed authentication with Mojang session servers\n";
            //exception occured checking against Mojang
            $this->disconnectClient((new DisconnectPacket())->setReason("Error Authenticating with Mojang servers"));
        }
    }

    /**
     * Called on event LOGIN.LoginStartPacket
     * @param LoginStartPacket $packet
     */
    public function onLoginStartPacket(LoginStartPacket $packet)
    {
        //set the users name as supplied in the packet
        $this->username = $packet->getUsername();

        //create a new encryption request
        $request = new EncryptionRequestPacket();

        //set our randomly generated server ID and verifyToken for this connection
        $this->serverID = $request->getRandomServerID();
        $this->verifyToken = $request->getRandomServerID();

        //the public key needs to not have the start and end lines on it
        $publicKey = $this->certificate->getPublicKey()->getPublicKey();
        $publicKey = substr($publicKey, 28, -26);

        //set the request data
        $request->setPublicKey($publicKey)
            ->setServerID($this->serverID)
            ->setToken($this->verifyToken);

        //send the packet to the client
        $this->sendPacket($request);
    }

    /**
     * Called on event STATUS.PingRequestPacket
     * @param PingRequestPacket $packet
     */
    public function onPingRequestPacket(PingRequestPacket $packet)
    {
        //a ping data contains a long, but because the client just expects the same data back no point in parsing/reencoding it
        $ping = new PingResponsePacket();
        $ping->setPingData($packet->getPingData());

        //disconnect the client with the ping response
        $this->disconnectClient($ping);
    }

    /**
     * Called on STATUS.StatusRequestPacket
     * @param StatusRequestPacket $packet
     */
    public function onStatusRequestPacket(StatusRequestPacket $packet)
    {
        //status request packet has no data, just expects a StatusResponse in return
        $response = new StatusResponsePacket();

        //call the status_request event for packet modification
        $this->emit('status_request', [$response]);

        //send the packet to the client
        $this->sendPacket($response);
    }

    /**
     * Called on HANDSHAKE.HandshakePacket
     * @param HandshakePacket $packet
     */
    public function onHandshakePacket(HandshakePacket $packet)
    {
        //only allow protocol 5 to connect (1.7.6+) TODO can probably allow greater range, needs testing
        if($packet->getProtocolVersion() != 5) {
            $this->disconnectClient((new DisconnectPacket())->setReason('Invalid Minecraft Version, use 1.7.6+'));
        }

        //move to the next stage as defined in the packet (STATUS or LOGIN)
        $this->setStage($packet->getNextStage());
    }

    public static function sha1($data) {
        $number = new Math_BigInteger(sha1($data, true), -256);
        $zero = new Math_BigInteger(0);
        return ($zero->compare($number) <= 0 ? "":"-") . ltrim($number->toHex(), "0");
    }
} 