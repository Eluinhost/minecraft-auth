<?php
namespace PublicUHC\MinecraftAuth\Server\DataTypes;

use PublicUHC\MinecraftAuth\Server\InvalidDataException;
use PublicUHC\MinecraftAuth\Server\NoDataException;

class VarInt extends DataType {

    /**
     * Writes to the stream
     *
     * @param $fd resource the stream to write to
     * @param $data String the data to write
     * @param $length int the length of the data
     * @throws \PublicUHC\MinecraftAuth\Server\InvalidDataException
     */
    private static function write($fd, $data, $length)
    {
        $written = fwrite($fd, $data, $length);
        if ($written !== $length) {
            throw new InvalidDataException();
        }
    }

    /**
     * Reads from the stream
     *
     * @param $fd resource the stream to read from
     * @param $length int the length to read
     * @return string the read bytes
     * @throws \PublicUHC\MinecraftAuth\Server\InvalidDataException
     */
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
    public static function readUnsignedVarInt($data)
    {
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

    /**
     * Writes a VarInt
     *
     * @param $data int the value to write
     * @param null $connection if null nothing happens, if set will write the data to the stream
     * @return int the encoded value
     * @throws \PublicUHC\MinecraftAuth\Server\InvalidDataException
     */
    public static function writeUnsignedVarInt($data, $connection = null) {
        if($data < 0) {
            throw new InvalidDataException('Cannot write negative values');
        }

        //single bytes don't need encoding
        if ($data < 0x80) {
            if($connection != null)
                self::write($connection, chr($data), 1);
            return $data;
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

        if($connection != null)
            self::write($connection, $bytes, strlen($bytes));

        return $bytes;
    }
} 