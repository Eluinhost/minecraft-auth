<?php
namespace PublicUHC\MinecraftAuth\Protocol\Packets;

use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\DataTypeEncoders\StringType;

/**
 * Represents a login stage disconnect packet. http://wiki.vg/Protocol#Disconnect_2
 */
class DisconnectPacket extends ClientboundPacket {

    private $reasonJSON = null;

    /**
     * @return String the JSON encoded disconnect message
     */
    public function getReasonJSON()
    {
        return $this->reasonJSON;
    }

    /**
     * @param $jsonString String the JSON encoded disconnect message
     * @return DisconnectPacket
     */
    public function setReasonJSON($jsonString)
    {
        $this->reasonJSON = $jsonString;
        return $this;
    }

    /**
     * @param $reason String a disconnect message that will be JSON encoded
     * @return DisconnectPacket
     */
    public function setReason($reason)
    {
        $this->reasonJSON = json_encode($reason);
        return $this;
    }

    /**
     * Get the encoded contents of the packet (minus packetID/length)
     * @return String
     */
    protected function encodeContents()
    {
        return StringType::write($this->reasonJSON)->getEncoded();
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
}
