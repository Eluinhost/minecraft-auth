<?php
namespace PublicUHC\MinecraftAuth\Protocol;

use PublicUHC\MinecraftAuth\ReactServer\DataTypes\VarInt;

class EncryptionRequestPacket {

    const PACKET_ID = 1;

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

    public function encode()
    {
        $packetIDVarInt = VarInt::writeUnsignedVarInt(self::PACKET_ID);
        echo " <- ENCRYPTION REQUEST - PACKET ID: ".self::PACKET_ID."\n";
        echo " <- ENCRYPTION REQUEST - PACKET ID VARINT (O): ".$packetIDVarInt->getValue()."\n";
        echo " <- ENCRYPTION REQUEST - PACKET ID VARINT (E): 0x".bin2hex($packetIDVarInt->getEncoded())."\n";

        $serverIDLength = strlen($this->getServerID());
        echo " <- ENCRYPTION REQUEST - DATA (LEN - $serverIDLength: {$this->getServerID()}\n";
        echo " <- ENCRYPTION REQUEST - DATA (HEX): 0x".bin2hex($this->getServerID())."\n";

        $serverIDLengthVarInt = VarInt::writeUnsignedVarInt($serverIDLength);
        echo " <- ENCRYPTION REQUEST - SERVER ID LENGTH (O): ".$serverIDLengthVarInt->getValue()."\n";
        echo " <- ENCRYPTION REQUEST - SERVER ID LENGTH (E): 0x".bin2hex($serverIDLengthVarInt->getEncoded())."\n";

        $serverIDEncoded = $serverIDLengthVarInt->getEncoded() . $this->getServerID();
        echo " <- SERVER ID (RAW): ".$this->getServerID()."\n";
        echo " <- ENCODED SERVER ID (RAW): ".$serverIDEncoded."\n";
        echo " <- ENCODED SERVER ID (HEX): 0x".bin2hex($serverIDEncoded)."\n";

        $encodedPublicKey = base64_decode($this->getPublicKey());
        $publicKeyLength = pack('n', strlen($encodedPublicKey));
        echo " <- PUBLIC KEY (0): {$this->getPublicKey()}\n";
        echo " <- PUBLIC KEY (E) 0x".bin2hex($encodedPublicKey)."\n";
        echo " <- PUBLIC KEY LENGTH (0): ".strlen($encodedPublicKey)."\n";
        echo " <- PUBLIC KEY LENGTH (E) 0x".bin2hex($publicKeyLength)."\n";

        $encodedToken = base64_decode($this->getToken());
        $tokenLength = pack('n', strlen($encodedToken));
        echo " <- TOKEN LENGTH (o): ".strlen($encodedToken)."\n";
        echo " <- TOKEN LENGTH (E) 0x".bin2hex($tokenLength)."\n";
        echo " <- TOKEN VALUE (E) 0x".bin2hex($encodedToken)."\n";

        $packetLength = $packetIDVarInt->getDataLength()
            + $serverIDLength + $serverIDLengthVarInt->getDataLength()
            + 2 + strlen($encodedPublicKey)
            + 2 + strlen($encodedToken);

        $packetLengthVarInt = VarInt::writeUnsignedVarInt($packetLength);

        return $packetLengthVarInt->getEncoded() . $packetIDVarInt->getEncoded() .
                $serverIDEncoded . $publicKeyLength . $encodedPublicKey .
                $tokenLength . $encodedToken;
    }
} 