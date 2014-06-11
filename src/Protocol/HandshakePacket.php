<?php
namespace PublicUHC\MinecraftAuth\Protocol;

class HandshakePacket {

    private $protocolVersion;
    private $serverAddress;
    private $serverPort;
    private $nextState;

    /**
     * Create a new Handshake packet (0x00)
     *
     * @param $protocolVersion int protocol version
     * @param $serverAddress String server address connecting to
     * @param $serverPort int port connecting to
     * @param int $nextState next state, 1 (HandshakePacketNextState::STATUS) for status and 2 (HandshakePacketNextState::LOGIN) for starting login
     */
    public function __construct($protocolVersion, $serverAddress, $serverPort, $nextState = GameState::STATUS)
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
    public function getNextState()
    {
        return $this->nextState;
    }

    /**
     * @param $nextState int the next
     * @return $this
     */
    public function setNextState($nextState)
    {
        $this->nextState = $nextState;
        return $this;
    }
} 