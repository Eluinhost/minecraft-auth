<?php
namespace PublicUHC\MinecraftAuth\Server\DataTypes;

use PublicUHC\MinecraftAuth\Server\InvalidDataException;
use PublicUHC\MinecraftAuth\Server\NoDataException;

class String extends DataType {

    /**
     * Reads a string from the stream
     *
     * @param $connection resource the stream to read from
     * @throws NoDataException if not data ended up null in the stream
     * @throws InvalidDataException if not valid varint
     * @return String
     */
    public static function fromStream($connection)
    {
        $lengthInt = VarInt::fromStream($connection);

        $stringLength = $lengthInt->getValue();
        $stringData = '';
        $read = 0;
        while($read < $stringLength) {
            $data = @fread($connection, 1);
            $read++;
            if(!$data) {
                throw new NoDataException();
            }
            $stringData .= $data;
        }

        return new String($stringData, $stringLength + $lengthInt->getDataLength());
    }
} 