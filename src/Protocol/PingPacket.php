<?php
namespace PublicUHC\MinecraftAuth\Protocol;

use PublicUHC\MinecraftAuth\ReactServer\DataTypes\VarInt;

class PingPacket {

    const PACKET_ID = 1;

    private $pingData;

    public function __construct($pingData)
    {
        $this->pingData = $pingData;
    }

    public function encode()
    {
        $packetIDVarInt = VarInt::writeUnsignedVarInt(self::PACKET_ID);
        echo " <- STATUS RESPONSE - PACKET ID: ".self::PACKET_ID."\n";
        echo " <- STATUS RESPONSE - PACKET ID VARINT (O): ".$packetIDVarInt->getValue()."\n";
        echo " <- STATUS RESPONSE - PACKET ID VARINT (E): 0x".bin2hex($packetIDVarInt->getEncoded())."\n";

        $packetLengthVarInt = VarInt::writeUnsignedVarInt($packetIDVarInt->getDataLength() + strlen($this->pingData));
        echo " <- STATUS RESPONSE - PACKET LENGTH: ".($packetIDVarInt->getDataLength() + strlen($this->pingData))."\n";
        echo " <- STATUS RESPONSE - PACKET LENGTH (O): ".$packetLengthVarInt->getValue()."\n";
        echo " <- STATUS RESPONSE - PACKET LENGTH (E): 0x".bin2hex($packetLengthVarInt->getEncoded())."\n";

        return $packetLengthVarInt->getEncoded() . $packetIDVarInt->getEncoded() . $this->pingData;
    }
} 