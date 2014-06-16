<?php
namespace PublicUHC\MinecraftAuth\Protocol\Packets\HANDSHAKE\SERVERBOUND;


use InvalidArgumentException;
use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Protocol\Packets\ServerboundPacket;
use PublicUHC\MinecraftAuth\ReactServer\DataTypes\VarInt;
use PublicUHC\MinecraftAuth\ReactServer\InvalidDataException;

/**
 * Class Packet_0
 *
 * Represents an incoming Handshake packet. http://wiki.vg/Protocol#Handshake
 *
 * @package PublicUHC\MinecraftAuth\Protocol\Packets\HANDSHAKE\SERVERBOUND
 */
class Packet_0 extends ServerboundPacket {

    private $protocolVersion;
    private $serverAddress;
    private $serverPort;
    private $nextStage;

    /**
     * @return int the protocol version
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * @param $version int the version to set to
     * @return Packet_0
     */
    protected function setProtocolVersion($version)
    {
        $this->protocolVersion = $version;
        return $this;
    }

    /**
     * @return String the server address connecting to
     */
    public function getServerAddress()
    {
        return $this->serverAddress;
    }

    /**
     * @param $serverAddress String the server address
     * @return Packet_0
     */
    protected function setServerAddress($serverAddress)
    {
        $this->serverAddress = $serverAddress;
        return $this;
    }

    /**
     * @return int the port connecting on
     */
    public function getServerPort()
    {
        return $this->serverPort;
    }

    /**
     * @param $serverPort int the server port
     * @return Packet_0
     */
    protected function setServerPort($serverPort)
    {
        $this->serverPort = $serverPort;
        return $this;
    }

    /**
     * @return Stage the next stage, uses HandshakePacketNextState constants
     */
    public function getNextStage()
    {
        return $this->nextStage;
    }

    /**
     * @param $nextStage Stage the next
     * @return Packet_0
     */
    protected function setNextStage(Stage $nextStage)
    {
        $this->nextStage = $nextStage;
        return $this;
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
    function getStage()
    {
        return Stage::HANDSHAKE();
    }

    /**
     * Parse the raw data
     * @param $data String the raw data to parse (minus packet ID and packet length
     * @throws InvalidDataException
     */
    function fromRawData($data)
    {
        $versionVarInt = VarInt::readUnsignedVarInt($data);
        $data = substr($data, $versionVarInt->getDataLength());

        $addressStringLength = VarInt::readUnsignedVarInt($data);
        $data = substr($data, $addressStringLength->getDataLength());

        $address = substr($data, 0, $addressStringLength->getValue());
        $data = substr($data, $addressStringLength->getValue());

        $portShort = unpack('nshort', substr($data, 0, 2))['short'];
        $data = substr($data, 2);

        $nextVarInt = VarInt::readUnsignedVarInt($data);

        try {
            $nextStage = Stage::get($nextVarInt->getValue());

            //disconnect if not a valid stage
            if ($nextStage != Stage::LOGIN() && $nextStage != Stage::STATUS()) {
                throw new InvalidDataException('Stage must be LOGIN or STATUS on handshake');
            }

            //set all of the data
            $this->setNextStage($nextStage)
                ->setServerPort($portShort)
                ->setProtocolVersion($versionVarInt->getValue())
                ->setServerAddress($address);
        } catch (InvalidArgumentException $ex) {
            throw new InvalidDataException('Stage is not a valid number');
        }
    }
}