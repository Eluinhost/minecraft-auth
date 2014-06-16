<?php
namespace PublicUHC\MinecraftAuth\Protocol\Packets;

use PublicUHC\MinecraftAuth\Protocol\Constants\Direction;
use PublicUHC\MinecraftAuth\ReactServer\DataTypes\VarInt;

abstract class ClientboundPacket extends Packet {

    /**
     * Get the direction it is headed
     * @return Direction
     */
    public function getDirection()
    {
        return Direction::CLIENTBOUND();
    }

    /**
     * Get the encoded contents of the packet (minus packetID/length)
     * @return String
     */
    protected abstract function encodeContents();

    public function encodePacket() {
        $packetIDVarInt = VarInt::writeUnsignedVarInt($this->getPacketID());

        $encodedPacket = $this->encodeContents();
        $packetLengthVarInt = VarInt::writeUnsignedVarInt(strlen($encodedPacket) + $packetIDVarInt->getDataLength());

        return $packetLengthVarInt->getEncoded() . $packetIDVarInt->getEncoded() . $encodedPacket;
    }
}