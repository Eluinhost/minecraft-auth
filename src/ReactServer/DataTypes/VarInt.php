<?php
namespace PublicUHC\MinecraftAuth\ReactServer\DataTypes;

use PublicUHC\MinecraftAuth\ReactServer\InvalidDataException;

class VarInt extends DataType {

    /**
     * Reads from the stream
     *
     * @param $fd resource the stream to read from
     * @param $length int the length to read
     * @return string|false the read bytes or false if read failed
     */
    private static function read($fd, $length)
    {
        // Protect against 0 byte reads when an EOF
        if ($length < 1) return '';

        $bytes = fread($fd, $length);
        if (false === $bytes) {
            return false;
        }

        return $bytes;
    }

    /**
     * Read a varint from beginning of the string.
     *
     * @param $data String the data
     * @throws InvalidDataException on invalid data
     * @return VarInt|false the parsed VarInt if parsed, false if not enough data
     */
    public static function readUnsignedVarInt($data)
    {
        $fd = fopen('data://text/plain,' . urlencode($data), 'rb');

        $original = '';
        $result = $shift = 0;
        do {
            $readValue = self::read($fd, 1);
            if(false === $readValue) {
                return false;
            }
            $original .= $readValue;
            $byte = ord($readValue);
            $result |= ($byte & 0x7f) << $shift++ * 7;

            if($shift > 5) {
                throw new InvalidDataException('VarInt greater than allowed range');
            }
        } while ($byte > 0x7f);

        return new VarInt($result, $original, $shift);
    }

    /**
     * Writes a VarInt
     *
     * @param $data int the value to write
     * @return VarInt the encoded value
     * @throws InvalidDataException
     */
    public static function writeUnsignedVarInt($data) {
        if($data < 0) {
            throw new InvalidDataException('Cannot write negative values');
        }
        $orig = $data;

        //single bytes don't need encoding
        if ($data < 0x80) {
            return new VarInt($data, $data, 1);
        }

        $encodedBytes = [];
        while ($data > 0) {
            $encodedBytes[] = 0x80 | ($data & 0x7f);
            $data >>= 7;
        }

        //remove most sig bit from final value
        $encodedBytes[count($encodedBytes)-1] &= 0x7f;

        //build the actual bytes from the encoded array
        $bytes = call_user_func_array('pack', array_merge(array('C*'), $encodedBytes));;

        return new VarInt($orig, $bytes, strlen($bytes));
    }
} 