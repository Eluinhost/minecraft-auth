<?php
namespace PublicUHC\MinecraftAuth\Protocol\Packets;

use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\DataTypeEncoders\StringType;

/**
 * Represents an encryption request. http://wiki.vg/Protocol#Encryption_Request
 */
class EncryptionRequestPacket extends ClientboundPacket {

    private $serverID;
    private $publicKey;
    private $token;

    /**
     * @return String The server ID
     */
    public function getServerID()
    {
        return $this->serverID;
    }

    /**
     * @param String $serverID The server ID
     * @return EncryptionRequestPacket
     */
    protected function setServerID($serverID)
    {
        $this->serverID = $serverID;
        return $this;
    }

    /**
     * @return String generate a random server ID
     */
    public static function getRandomServerID()
    {
        $range_start = 0x21;
        $range_end   = 0x7E;
        $id = '';
        $length = 20;

        for ($i = 0; $i < $length; $i++) {
            $id .= chr(round(mt_rand($range_start, $range_end)));
        }

        return $id;
    }

    /**
     * @return String The verification token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param String $token The verification token
     * @return EncryptionRequestPacket
     */
    protected function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return String the public key
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @param String $publicKey the public key
     * @return EncryptionRequestPacket
     */
    protected function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;
        return $this;
    }

    /**
     * Get the encoded contents of the packet (minus packetID/length)
     * @return String
     */
    protected function encodeContents()
    {
        $serverIDEncoded = StringType::write($this->serverID);

        $encodedPublicKey = base64_decode($this->getPublicKey());
        $publicKeyLength = pack('n', strlen($encodedPublicKey));

        $encodedToken = base64_decode($this->getToken());
        $tokenLength = pack('n', strlen($encodedToken));

        return $serverIDEncoded->getEncoded() . $publicKeyLength . $encodedPublicKey . $tokenLength . $encodedToken;
    }

    /**
     * Get the ID of this packet
     * @return int
     */
    public function getPacketID()
    {
        return 0x01;
    }

    /**
     * Get the stage this packet is for
     * @return Stage
     */
    public function getStage()
    {
        return STAGE::LOGIN();
    }
}