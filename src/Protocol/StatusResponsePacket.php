<?php
namespace PublicUHC\MinecraftAuth\Protocol;

use PublicUHC\MinecraftAuth\Server\DataTypes\StringType;
use PublicUHC\MinecraftAuth\Server\DataTypes\VarInt;

class StatusResponsePacket {

    const PACKET_ID = 0;

    private $version = '0';
    private $protocol = 0;
    private $max_players = 0;
    private $online_count = 0;
    private $online_players = [];
    private $description = 'A Minecraft Server';
    private $favicon = null;

    /**
     * @return string the name of the Minecraft version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version the name of the Minecraft version
     * @return $this;
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return int the protocol number
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @param int $protocol the protocol number
     * @return $this;
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
        return $this;
    }

    /**
     * @return int the max players to show
     */
    public function getMaxPlayers()
    {
        return $this->max_players;
    }

    /**
     * @param int $max_players the max players to show
     * @return $this
     */
    public function setMaxPlayers($max_players)
    {
        $this->max_players = $max_players;
        return $this;
    }

    /**
     * @return int the online amount to show
     */
    public function getOnlineCount()
    {
        return $this->online_count;
    }

    /**
     * @param int $online_count the online amount to show
     * @return $this;
     */
    public function setOnlineCount($online_count)
    {
        $this->online_count = $online_count;
        return $this;
    }

    /**
     * @return array list of online player names
     */
    public function getOnlinePlayers()
    {
        return $this->online_players;
    }

    /**
     * @param array $online_players list of online player names
     * @return $this
     */
    public function setOnlinePlayers($online_players)
    {
        $this->online_players = $online_players;
    }

    /**
     * @return string the server description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description the server description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return String the favicon base64encoded image
     */
    public function getFavicon()
    {
        return $this->favicon;
    }

    /**
     * @param String $favicon the favicon base64encoded image
     * @return $this
     */
    public function setFavicon($favicon)
    {
        $this->favicon = $favicon;
        return $this;
    }

    public function encode()
    {
        $payload = [
            'version' => [
                'name'      => $this->version,
                'protocol'  => $this->protocol
            ],
            'players' => [
                'max'       => $this->max_players,
                'online'    => $this->online_count,
                'sample'    => []
            ],
            'description'   => [
                'text'  => $this->description
            ],
            'favicon'   => $this->favicon
        ];
        foreach($this->online_players as $player) {
            array_push($payload['players']['sample'], [
                'name'  => $player,
                'id'    => ''
            ]);
        }

        $jsonPayload = StringType::fromString(json_encode($payload));
        $packetID = VarInt::writeUnsignedVarInt(self::PACKET_ID);
        $dataLen = VarInt::writeUnsignedVarInt($jsonPayload->getDataLength() + $packetID->getDataLength());

        return $dataLen->getEncoded() . $packetID->getEncoded() . $jsonPayload->getEncoded();
    }
} 