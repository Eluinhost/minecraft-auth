<?php
namespace PublicUHC\MinecraftAuth\Protocol\Packets;


use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\DataTypeEncoders\StringType;

/**
 * Represents a status response packet. http://wiki.vg/Protocol#Response
 */
class StatusResponsePacket extends ClientboundPacket {

    private $version = '1.7.6+';
    private $protocol = 6;
    private $max_players = -1;
    private $online_count = -1;
    private $online_players = [];
    private $description = '§4▁§e▂§4▃§e▄§4▅§e▆§4▇§e█ §4§l   PHPAuthServer   §e█§4▇§e▆§4▅§e▄§4▃§e▂§4▁ §c▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔';
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
     * @return StatusResponsePacket
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
     * @return StatusResponsePacket
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
     * @return StatusResponsePacket
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
     * @return StatusResponsePacket
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
     * @return StatusResponsePacket
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
     * @return StatusResponsePacket
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
     * @param String $favicon the favicon base64encoded image, leave null to set no favicon
     * @return StatusResponsePacket
     */
    public function setFavicon($favicon)
    {
        $this->favicon = $favicon;
        return $this;
    }

    /**
     * Get the encoded contents of the packet (minus packetID/length)
     * @return String
     */
    protected function encodeContents()
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
        ];
        if($this->favicon != null) {
            $payload['favicon'] = $this->favicon;
        }
        foreach($this->online_players as $player) {
            array_push($payload['players']['sample'], [
                'name'  => $player,
                'id'    => ''
            ]);
        }

        $jsonString = utf8_encode(json_encode($payload));

        return StringType::write($jsonString)->getEncoded();
    }

    /**
     * Get the ID of this packet
     * @return int
     */
    public function getPacketID()
    {
        return 0x00;
    }

    /**
     * Get the stage this packet is for
     * @return Stage
     */
    public function getStage()
    {
        return Stage::STATUS();
    }
}