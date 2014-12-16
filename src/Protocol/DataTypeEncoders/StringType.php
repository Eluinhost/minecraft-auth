<?php
namespace PublicUHC\MinecraftAuth\Protocol\DataTypeEncoders;


class StringType extends DataType {

    public static function read($data)
    {
        $original = $data;
        $stringLengthVarInt = VarInt::readUnsignedVarInt($data);

        $data = substr($data, $stringLengthVarInt->getDataLength());

        $actualString = substr($data, 0, $stringLengthVarInt->getValue());
        return new StringType($actualString, $original, $stringLengthVarInt->getDataLength() + $stringLengthVarInt->getValue());
    }

    public static function write($data)
    {
        $stringLength = strlen($data);
        $stringLengthVarInt = VarInt::writeUnsignedVarInt($stringLength);

        return new StringType($data, $stringLengthVarInt->getEncoded() . $data, $stringLengthVarInt->getDataLength() + $stringLength);
    }
}
