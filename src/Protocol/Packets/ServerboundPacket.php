<?php
namespace PublicUHC\MinecraftAuth\Protocol\Packets;

use PublicUHC\MinecraftAuth\Protocol\Constants\Direction;

abstract class ServerboundPacket extends Packet {

    /**
     * Get the direction it is headed
     * @return Direction
     */
    public function getDirection()
    {
        return Direction::SERVERBOUND();
    }

    /**
     * Parse the raw data into the packet
     * @param $data String the raw data to parse (minus packet ID and packet length
     */
    public abstract function fromRawData($data);
}