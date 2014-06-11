<?php
namespace PublicUHC\MinecraftAuth\Protocol;

class DisconnectPacket {

    private $JSONData;

    /**
     * Create a disconnect packet (clientbound->disconnect 0x00)
     * @param $JSONData ? don't know yet
     */
    public function __construct($JSONData)
    {
        $this->JSONData = $JSONData;
    }

    /**
     * @return ? JSONData
     */
    public function getJSONData()
    {
        return $this->JSONData;
    }

    /**
     * @param $JSONData ? JSONData
     * @return $this
     */
    public function setJSONData($JSONData)
    {
        $this->JSONData = $JSONData;
        return $this;
    }
} 