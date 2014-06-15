<?php
namespace PublicUHC\MinecraftAuth\Protocol;

class EncryptionRequestPacket {

    const PACKET_ID = 1;

    private $serverID;
    private $keyLength;
    private $publicKey;
    private $tokenLength;
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
     * @return $this
     */
    public function setServerID($serverID)
    {
        $this->serverID = $serverID;
        return $this;
    }

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
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return int length of the public key
     */
    public function getKeyLength()
    {
        return $this->keyLength;
    }

    /**
     * @param int $keyLength length of the public key
     * @return $this
     */
    public function setKeyLength($keyLength)
    {
        $this->keyLength = $keyLength;
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
     * @return $this
     */
    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;
        return $this;
    }

    /**
     * @return int the length of the verification token
     */
    public function getTokenLength()
    {
        return $this->tokenLength;
    }

    /**
     * @param int $tokenLength the length of the verification token
     * @return $this
     */
    public function setTokenLength($tokenLength)
    {
        $this->tokenLength = $tokenLength;
        return $this;
    }
} 