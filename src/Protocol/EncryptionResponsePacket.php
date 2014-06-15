<?php
namespace PublicUHC\MinecraftAuth\Protocol;

class EncryptionResponsePacket {

    const PACKET_ID = 1;

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
     * @return $this
     */
    public function setToken($token)
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
     * @return $this
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
        return $this;
    }

    public static function fromStreamData($data)
    {
        $secretLength = unpack('nshort', substr($data, 0, 2))['short'];
        $data = substr($data, 2);
        echo " -> ENCRYPTION RESPONSE - SECRET LENGTH: $secretLength\n";

        $secretData = substr($data, 0, $secretLength);
        $data = substr($data, $secretLength);
        echo " -> ENCRYPTION RESPONSE - SECRET DATA (O): $secretData\n";
        echo " -> ENCRYPTION RESPONSE - SECRET DATA 0x".bin2hex($secretData)."\n";

        $verifyLength = unpack('nshort', substr($data, 0, 2))['short'];
        $data = substr($data, 2);
        echo " -> ENCRYPTION RESPONSE - VERIFY LENGTH: $verifyLength\n";

        $verifyData = substr($data, 0, $verifyLength);
        echo " -> ENCRYPTION RESPONSE - VERIFY DATA (O): $verifyData\n";
        echo " -> ENCRYPTION RESPONSE - VERIFY DATA (E) 0x".bin2hex($verifyData)."\n";

        $response = new EncryptionResponsePacket();
        return $response->setToken($verifyData)->setSecret($secretData);
    }
} 