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
     */
    public function readPacket()
    {
        $data = @fread($this->connection, 65535);

        if(!$data) {
            $this->close();
            throw new NoDataException();
        }
        echo("data = ".json_encode($data)."\n");

        @fwrite($this->connection, $data);
    }
} 