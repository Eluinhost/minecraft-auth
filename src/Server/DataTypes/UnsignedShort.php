<?php
namespace PublicUHC\MinecraftAuth\Server\DataTypes;

use PublicUHC\MinecraftAuth\Server\InvalidDataException;
use PublicUHC\MinecraftAuth\Server\NoDataException;

class UnsignedShort extends DataType {

    /**
     * Reads an unsigned short from the stream
     *
     * @param $connection resource the stream to read from
     * @throws NoDataException if not data ended up null in the stream
     * @throws InvalidDataException if not valid varint
     * @return UnsignedShort
     */
    public static function fromStream($connection)
    {
        $data = @fread($connection, 2);
        if(!$data) {
            throw new NoDataException();
        }
        //unsigned short big-endian
        $decodedData = unpack('nshort', $data)['short'];
        return new UnsignedShort($decodedData, $data, 2);
    }
} 