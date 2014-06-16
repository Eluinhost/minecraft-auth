<?php
namespace PublicUHC\MinecraftAuth\Protocol\Packets\STATUS\SERVERBOUND;

use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\Packets\ServerboundPacket;

/**
 * Class Packet_1
 * @package PublicUHC\MinecraftAuth\Protocol\Packets\STATUS\SERVERBOUND
 *
 * Represents a ping request packet. http://wiki.vg/Protocol#Ping_2
 */
class Packet_1 extends ServerboundPacket {

    private $pingData;

    /**
     * The raw ping data to send
     *
     * @param $data
     * @return Packet_1
     */
    protected function setPingData($data)
    {
        $this->pingData = $data;
        return $this;
    }

    /**
     * @return String the raw ping data
     */
    public function getPingData()
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
        return STAGE::STATUS();
    }

    /**
     * Parse the raw data into the packet
     * @param $data String the raw data to parse (minus packet ID and packet length
     * @return ServerboundPacket
     */
    public function fromRawData($data)
    {
        $this->pingData = $data;
    }
}