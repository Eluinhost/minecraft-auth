<?php
namespace PublicUHC\MinecraftAuth\Protocol\Packets;

use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;

/**
 * Represents a status request packet. http://wiki.vg/Protocol#Request
 */
class StatusRequestPacket extends ServerboundPacket {

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
     */
    public function fromRawData($data)
    {
        //no contents :)
    }
}
