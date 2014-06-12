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
            //ascii char
            $data = ord($data);

            //if only I knew...
            $result |= ($data & 0x7F) << $i++ * 7;

            //if it's too large
            if( $i > 5 ) {
                throw new InvalidDataException();
            }

            //also probably pretty important
            if(($data & 0x80) != 128) {
                break;
            }
        }
        return new VarInt($result, $i);
    }

    public static function fromInt($int)
    {
        $string = decbin($int);
        if (strlen($string) < 8)
        {
            $hexstring = dechex(bindec($string));
            if (strlen($hexstring) % 2 == 1)
                $hexstring = '0' . $hexstring;
            return self::hex_to_str($hexstring);
        }

        // split it and insert the mb byte
        $string_array = array();
        $pre = '1';
        while (strlen($string) > 0)
        {
            if (strlen($string) < 8)
            {
                $string = substr('00000000', 0, 7 - strlen($string) % 7) . $string;
                $pre = '0';
            }
            $string_array[] = $pre . substr($string, strlen($string) - 7, 7);
            $string = substr($string, 0, strlen($string) - 7);
            $pre = '1';
            if ($string == '0000000')
                break;
        }

        $hexstring = '';
        foreach ($string_array as $string)
        {
            $hexstring .= sprintf('%02X', bindec($string));
        }


        return self::hex_to_str($hexstring);
    }

    /**
     * Converts hex 2 ascii
     * @param String $hex - the hex string
     * @return string
     */
    private static function hex_to_str($hex)
    {
        $str = '';

        for($i = 0; $i < strlen($hex); $i += 2)
        {
            $str .= chr(hexdec(substr($hex, $i, 2)));
        }
        return $str;
    }
} 