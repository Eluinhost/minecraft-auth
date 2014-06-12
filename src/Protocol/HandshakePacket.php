<?php
namespace PublicUHC\MinecraftAuth\Protocol;

use InvalidArgumentException;
use PublicUHC\MinecraftAuth\Protocol\Constants\Stage;
use PublicUHC\MinecraftAuth\Server\DataTypes\StringType;
use PublicUHC\MinecraftAuth\Server\DataTypes\UnsignedShort;
use PublicUHC\MinecraftAuth\Server\DataTypes\VarInt;
use PublicUHC\MinecraftAuth\Server\InvalidDataException;
use PublicUHC\MinecraftAuth\Server\NoDataException;

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
     * @param $connection resource the stream to read from
     * @throws NoDataException if not data ended up null in the stream
     * @throws InvalidDataException if not valid packet structure
     * @return HandshakePacket
     */
    public static function fromStream($connection)
    {
        $protocolVersion = VarInt::fromStream($connection);
        $serverAddress = StringType::fromStream($connection);
        $serverPort = UnsignedShort::fromStream($connection);
        $nextState = VarInt::fromStream($connection);

        try {
            $nextStateInt = $nextState->getValue();

            $stage = Stage::get($nextStateInt);
            if($stage != Stage::LOGIN() && $stage != Stage::STATUS()) {
                throw new InvalidDataException('Handshake packet has an invalid stage value');
            }

            return new HandshakePacket($protocolVersion->getValue(), $serverAddress->getValue(), $serverPort->getValue(), $stage);
        } catch(InvalidArgumentException $ex) {
            throw new InvalidDataException('Handshake packet has an invalid stage value');
        }
    }
} 