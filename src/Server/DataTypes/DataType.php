<?php
namespace PublicUHC\MinecraftAuth\Server\DataTypes;

abstract class DataType {

    private $value, $encoded, $dataLength;

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