<?php
namespace PublicUHC\MinecraftAuth\Server\DataTypes;

abstract class DataType {

    private $value, $dataLength;

    public function __construct($value, $dataLength)
    {
        $this->value = $value;
        $this->dataLength = $dataLength;
    }

    /**
     * @return int the value read
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return int the length of the original data
     */
    public function getDataLength()
    {
        return $this->dataLength;
    }
} 