<?php
namespace PublicUHC\MinecraftAuth\Protocol\Packets;

use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\DataTypeEncoders\StringType;

/**
 * Represents a login start packet. http://wiki.vg/Protocol#Login_Start
 */
class LoginStartPacket extends ServerboundPacket {

    private $username;

    /**
     * @return String the username of the client
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param $username String the client username
     * @return LoginStartPacket
     */
    protected function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Get the ID of this packet
     * @return int
     */
    public function getPacketID()
    {
        return 0x00;
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
        $username = StringType::read($data);
        $this->setUsername($username->getValue());
    }
}