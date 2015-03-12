<?php
namespace PublicUHC\MinecraftAuth\Protocol\Packets;

use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\DataTypeEncoders\VarInt;

/**
 * Represents an encryption response. http://wiki.vg/Protocol#Encryption_Response
 */
class EncryptionResponsePacket extends ServerboundPacket {

    private $secret;
    private $token;

    /**
     * @return String the verify token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param String $token the verify token
     * @return EncryptionResponsePacket
     */
    protected function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return String the shared secret
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @param String $secret the shared serect
     * @return EncryptionResponsePacket
     */
    protected function setSecret($secret)
    {
        $this->secret = $secret;
        return $this;
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

    /**
     * Parse the raw data into the packet
     * @param $data String the raw data to parse (minus packet ID and packet length
     */
    public function fromRawData($data)
    {
        $secretLength = VarInt::readUnsignedVarInt($data);
        $data = substr($data, $secretLength->getDataLength());

        $secretData = substr($data, 0, $secretLength->getValue());
        $data = substr($data, $secretLength->getValue());

        $verifyLength = VarInt::readUnsignedVarInt($data);
        $data = substr($data, $verifyLength->getDataLength());

        $verifyData = substr($data, 0, $verifyLength->getValue());

        $this->setToken($verifyData)->setSecret($secretData);
    }
}
