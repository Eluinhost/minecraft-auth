<?php
namespace PublicUHC\MinecraftAuth\Protocol;

use PublicUHC\MinecraftAuth\Server\DataTypes\String;
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
     * @param int $nextState next state, 1 (HandshakePacketNextState::STATUS) for status and 2 (Stage::HANDSHAKE) for starting login
     */
    public function __construct($protocolVersion, $serverAddress, $serverPort, $nextState = Stage::HANDSHAKE)
    {
        $this->protocolVersion = $protocolVersion;
        $this->serverAddress = $serverAddress;
        $this->serverPort = $serverPort;
        $this->nextState = $nextState;
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
     * @return int the next stage, uses HandshakePacketNextState constants
     */
    public function getNextStage()
    {
        return $this->nextStage;
    }

    /**
     * @param $nextStage int the next
     * @return $this
     */
    public function setNextStage($nextStage)
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
        $serverAddress = String::fromStream($connection);
        $serverPort = UnsignedShort::fromStream($connection);
        $nextState = VarInt::fromStream($connection);

        return new HandshakePacket($protocolVersion->getValue(), $serverAddress->getValue(), $serverPort->getValue(), $nextState->getValue());
    }
} 