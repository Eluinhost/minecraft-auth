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
        $data = '';
        $read = 0;
        while($read < 2) {
            $data = @fread($connection, 1);
            $read++;
            if(!$data) {
                throw new NoDataException();
            }
            $data = ord($data);
        }
    }
} 