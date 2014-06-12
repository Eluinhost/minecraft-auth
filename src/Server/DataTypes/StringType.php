<?php
namespace PublicUHC\MinecraftAuth\Server\DataTypes;

use PublicUHC\MinecraftAuth\Server\InvalidDataException;
use PublicUHC\MinecraftAuth\Server\NoDataException;

class StringType extends DataType {

    /**
     * Reads a string from the stream
     *
     * @param $connection resource the stream to read from
     * @throws NoDataException if not data ended up null in the stream
     * @throws InvalidDataException if not valid varint
     * @return StringType
     */
    public static function fromStream($connection)
    {
        $lengthInt = VarInt::fromStream($connection);

        $stringLength = $lengthInt->getValue();

        $data = @fread($connection, $stringLength);
        if(!$data) {
            throw new NoDataException();
        }
        return new StringType($data, $stringLength + $lengthInt->getDataLength());
    }
} 