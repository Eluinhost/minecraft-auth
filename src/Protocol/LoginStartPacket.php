<?php
namespace PublicUHC\MinecraftAuth\Protocol;

class LoginStartPacket {

    private $name;

    /**
     * Create a new login start packet (serverbound->login start 0x00)
     * @param $name ? I don't know what this is supposed to be
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return String ? I dont know what this is supposed to be
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name String ? I dont know what this is supposed to be
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
} 