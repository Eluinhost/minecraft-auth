<?php
namespace PublicUHC\MinecraftAuth\Protocol;

use PublicUHC\MinecraftAuth\Protocol\DataTypeEncoders\VarInt;

class DisconnectPacket {

    const PACKET_ID = 0;
    private $JSONData;

    /**
     * Create a disconnect packet (clientbound->disconnect 0x00)
     * @param $JSONData ? don't know yet
     */
    public function __construct($JSONData)
    {
        $this->JSONData = $JSONData;
    }

    /**
     * @return ? JSONData
     */
    public function getJSONData()
    {
        return $this->JSONData;
    }

    /**
     * @param $JSONData ? JSONData
     * @return $this
     */
    public function setJSONData($JSONData)
    {
        $this->JSONData = $JSONData;
        return $this;
    }

    public function encode()
    {
        $jsonString = '{"extra":["Not authenticated with ",{"underlined":false,"clickEvent":{"action":"open_url","value":"http://Minecraft.net"},"text":"Minecraft.net"}],"text":""}';
        $jsonStringLen = strlen($jsonString);
        echo " <- DISCONNECT - JSON UTF8 DATA (LEN - $jsonStringLen: $jsonString\n";
        echo " <- DISCONNECT - JSON UTF8 DATA (HEX): 0x".bin2hex($jsonString)."\n";

        $jsonStringLengthVarInt = VarInt::writeUnsignedVarInt($jsonStringLen);
        echo " <- DISCONNECT - STRING LENGTH (O): ".$jsonStringLengthVarInt->getValue()."\n";
        echo " <- DISCONNECT - STRING LENGTH (E): 0x".bin2hex($jsonStringLengthVarInt->getEncoded())."\n";

        $jsonObjectEncoded = $jsonStringLengthVarInt->getEncoded() . $jsonString;
        echo " <- ENCODED JSON OBJECT (RAW): ".$jsonObjectEncoded."\n";
        echo " <- ENCODED JSON OBJECT (HEX): 0x".bin2hex($jsonObjectEncoded)."\n";

        $packetIDVarInt = VarInt::writeUnsignedVarInt(self::PACKET_ID);
        echo " <- DISCONNECT - PACKET ID: ".self::PACKET_ID."\n";
        echo " <- DISCONNECT - PACKET ID VARINT (O): ".$packetIDVarInt->getValue()."\n";
        echo " <- DISCONNECT - PACKET ID VARINT (E): 0x".bin2hex($packetIDVarInt->getEncoded())."\n";

        $packetLengthVarInt = VarInt::writeUnsignedVarInt($packetIDVarInt->getDataLength() + strlen($jsonObjectEncoded));
        echo " <- DISCONNECT - PACKET LENGTH: ".($packetIDVarInt->getDataLength() + strlen($jsonObjectEncoded))."\n";
        echo " <- DISCONNECT - PACKET LENGTH (O): ".$packetLengthVarInt->getValue()."\n";
        echo " <- DISCONNECT - PACKET LENGTH (E): 0x".bin2hex($packetLengthVarInt->getEncoded())."\n";

        $encoded = $packetLengthVarInt->getEncoded() . $packetIDVarInt->getEncoded() . $jsonObjectEncoded;
        echo 'ENCODED RESPONSE (HEX): 0x' . bin2hex($encoded) . "\n";
        echo "ENCODED RESPONSE (RAW): $encoded\n";
        return $encoded;
    }
} 