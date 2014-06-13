<?php
namespace PublicUHC\MinecraftAuth\Server\DataTypes;

use PublicUHC\MinecraftAuth\Server\InvalidDataException;
use PublicUHC\MinecraftAuth\Server\NoDataException;

class VarInt extends DataType {

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

    private static function read($fd, $length)
    {
        // Protect against 0 byte reads when an EOF
        if ($length < 1) return '';

        $bytes = fread($fd, $length);
        if (false === $bytes) {
            throw new InvalidDataException();
        }

        return $bytes;
    }

    /**
     * Read a varint from string or resource.
     *
     * @param $data String|resource the data
     * @throws InvalidDataException on invalid data
     * @return VarInt the parsed VarInt
     */
    public static function readUnsignedVarInt($data) {
        $fd = null;
        if (is_resource($data)) {
            $fd = $data;
        } else {
            $fd = fopen('data://text/plain,' . urlencode($data), 'rb');
        }
        $result = $shift = 0;
        do {
            $byte = ord(self::read($fd, 1));
            $result |= ($byte & 0x7f) << $shift++ * 7;

            if($shift > 5) {
                throw new InvalidDataException('VarInt greater than allowed range');
            }
        } while ($byte > 0x7f);

        return new VarInt($result, $shift);
    }
} 