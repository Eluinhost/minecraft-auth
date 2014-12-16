<?php
namespace PublicUHC\MinecraftAuth\Protocol\DataTypeEncoders;

abstract class DataType {

    private $value, $encoded, $dataLength;

    /**
     * @param $value String the decoded value
     * @param $encoded String the encoded value
     * @param $dataLength int the length of the encoded data
     */
    public function __construct($value, $encoded, $dataLength)
    {
        $this->value = $value;
        $this->encoded = $encoded;
        $this->dataLength = $dataLength;
    }

    /**
     * @return int the value read
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getEncoded()
    {
        return $this->encoded;
    }

    /**
     * @return int the length of the original data
     */
    public function getDataLength()
    {
        return $this->dataLength;
    }
}
