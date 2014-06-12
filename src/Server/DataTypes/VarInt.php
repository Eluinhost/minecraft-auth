<?php
namespace PublicUHC\MinecraftAuth\Server\DataTypes;

use PublicUHC\MinecraftAuth\Server\InvalidDataException;
use PublicUHC\MinecraftAuth\Server\NoDataException;

class VarInt extends DataType {

    /**
     * Reads a varint from the stream
     *
     * @param $connection resource the stream to read from
     * @throws NoDataException if not data ended up null in the stream
     * @throws InvalidDataException if not valid varint
     * @return VarInt
     */
    public static function fromStream($connection)
    {
        $result = $i = 0;

        while(true) {
            $data = @fread($connection, 1);
            if(!$data) {
                throw new NoDataException();
            }
            $data = ord($data);
            $result |= ($data & 0x7F) << $i++ * 7;
            if( $i > 5 ) {
                throw new InvalidDataException();
            }
            if(($data & 0x80) != 128) {
                break;
            }
        }
        return new VarInt($result, $i);
    }
} 