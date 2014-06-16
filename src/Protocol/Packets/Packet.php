<?php
namespace PublicUHC\MinecraftAuth\Protocol\Packets;

use PublicUHC\MinecraftAuth\Protocol\Constants\Direction;
use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;

abstract class Packet {

    /**
     * Get the ID of this packet
     * @return int
     */
    public abstract function getPacketID();

    /**
     * Get the stage this packet is for
     * @return Stage
     */
    public abstract function getStage();

    /**
     * Get the direction it is headed
     * @return Direction
     */
    public abstract function getDirection();
} 