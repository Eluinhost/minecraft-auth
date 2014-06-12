<?php
namespace PublicUHC\MinecraftAuth\Server;

class Client {

    private $connection;

    /**
     * Create a new Client
     *
     * @param $resource resource the client connection
     */
    public function __construct($resource)
    {
        $this->connection = $resource;
    }

    /**
     * Get the connection handle
     *
     * @return resource the socket connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Closes the socket
     */
    public function close()
    {
        @fclose($this->connection);
        $this->connection = null;
    }

    /**
     * @return bool if the connection is opened
     */
    public function isOpen()
    {
        return $this->connection != null;
    }

    /**
     * Read a packet from the connection
     *
     * @throws NoDataException if there is no data supplied and closes the connection
     * @throws InvalidDataException if data didn't match the expected
     */
    public function readPacket()
    {
        //packet length - VarInt - length of the data + packetID
        $packetLength = VarInt::fromStream($this->connection);
        echo "Packet Length: {$packetLength->getValue()}\n";

        //packet ID - the ID of the packet, relevant to each stage?
        $packetID = VarInt::fromStream($this->connection);
        echo "Packet ID: {$packetID->getValue()}\n";

        //TODO parse the rest of the packet

        $data = @fread($this->connection, $packetLength->getValue() - $packetID->getDataLength());
        echo $data;
        @fwrite($this->connection, $data);
    }
} 