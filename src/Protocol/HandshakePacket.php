<?php
namespace PublicUHC\MinecraftAuth\Protocol;

use InvalidArgumentException;
use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\ReactServer\DataTypes\VarInt;
use PublicUHC\MinecraftAuth\ReactServer\InvalidDataException;

class HandshakePacket {

    private $protocolVersion;
    private $serverAddress;
    private $serverPort;
    private $nextStage;

    /**
     * Create a new Handshake packet (serverbound->handshake 0x00)
     *
     * @param $protocolVersion int protocol version
     * @param $serverAddress String server address connecting to
     * @param $serverPort int port connecting to
     * @param Stage $nextStage next stage for the client
     */
    public function __construct($protocolVersion, $serverAddress, $serverPort, Stage $nextStage)
    {
        $this->protocolVersion = $protocolVersion;
        $this->serverAddress = $serverAddress;
        $this->serverPort = $serverPort;
        $this->nextStage = $nextStage;
    }

    /**
     * @return int the protocol version
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * @param $version int the version to set to
     * @return $this
     */
    public function setProtocolVersion($version)
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
     * @return $this
     */
    public function setServerAddress($serverAddress)
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
     * @return $this
     */
    public function setServerPort($serverPort)
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
     * @return $this
     */
    public function setNextStage(Stage $nextStage)
    {
        $this->nextStage = $nextStage;
        return $this;
    }

    /**
     * Reads a handshake packet data from the stream
     *
     * @param $data String
     * @throws InvalidDataException if not valid packet structure
     * @return HandshakePacket
     */
    public static function fromStreamData($data)
    {
        $versionVarInt = VarInt::readUnsignedVarInt($data);
        $data = substr($data, $versionVarInt->getDataLength());
        echo "  -> VERSION: {$versionVarInt->getValue()}\n";

        $addressStringLength = VarInt::readUnsignedVarInt($data);
        $data = substr($data, $addressStringLength->getDataLength());

        $address = substr($data, 0, $addressStringLength->getValue());
        $data = substr($data, $addressStringLength->getValue());
        echo "  -> ADDRESS: $address\n";

        $portShort = unpack('nshort', substr($data, 0, 2))['short'];
        $data = substr($data, 2);
        echo "  -> PORT: {$portShort}\n";

        $nextVarInt = VarInt::readUnsignedVarInt($data);
        echo "  -> NEXT STAGE #: {$nextVarInt->getValue()}\n";

        try {
            $nextStage = Stage::get($nextVarInt->getValue());

            //disconnect if not a valid stage
            if ($nextStage != Stage::LOGIN() && $nextStage != Stage::STATUS()) {
                throw new InvalidDataException('Stage must be LOGIN or STATUS on handshake');
            }

            return new HandshakePacket($versionVarInt->getValue(), $address, $portShort, $nextStage);
        } catch (InvalidArgumentException $ex) {
            throw new InvalidDataException('Stage is not a valid number');
        }
    }
} 