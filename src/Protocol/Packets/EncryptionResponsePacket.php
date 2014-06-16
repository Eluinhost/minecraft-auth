<?php
namespace PublicUHC\MinecraftAuth\Protocol\Packets;

use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;

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
        $secretLength = unpack('nshort', substr($data, 0, 2))['short'];
        $data = substr($data, 2);

        $secretData = substr($data, 0, $secretLength);
        $data = substr($data, $secretLength);

        $verifyLength = unpack('nshort', substr($data, 0, 2))['short'];
        $data = substr($data, 2);

        $verifyData = substr($data, 0, $verifyLength);

        $this->setToken($verifyData)->setSecret($secretData);
    }
}