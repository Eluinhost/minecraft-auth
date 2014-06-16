<?php
namespace Protocol\Packets\STATUS\CLIENTBOUND;

use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\Packets\ClientboundPacket;

/**
 * Class Packet_1
 * @package Protocol\Packets\STATUS\CLIENTBOUND
 *
 * Represents a ping response packet. http://wiki.vg/Protocol#Ping
 */
class Packet_1 extends ClientboundPacket {

    private $pingData;

    /**
     * The raw ping data to send
     *
     * @param $data
     * @return Packet_1
     */
    public function setPingData($data)
    {
        $this->pingData = $data;
        return $this;
    }

    public function getPingData()
    {
        return $this->pingData;
    }

    /**
     * Get the encoded contents of the packet (minus packetID/length)
     * @return String
     */
    protected function encodeContents()
    {
        return $this->pingData;
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
        return Stage::STATUS();
    }
}