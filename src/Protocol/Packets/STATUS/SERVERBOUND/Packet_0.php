<?php
namespace PublicUHC\MinecraftAuth\Protocol\Packets\STATUS\SERVERBOUND;

use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\Packets\ServerboundPacket;

/**
 * Class Packet_0
 * @package PublicUHC\MinecraftAuth\Protocol\Packets\STATUS\SERVERBOUND
 *
 * Represents a status request packet. http://wiki.vg/Protocol#Request
 */
class Packet_0 extends ServerboundPacket {

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
        return STAGE::STATUS();
    }

    /**
     * Parse the raw data into the packet
     * @param $data String the raw data to parse (minus packet ID and packet length
     * @return Packet_0
     */
    public function fromRawData($data)
    {
        return new Packet_0();
    }
}