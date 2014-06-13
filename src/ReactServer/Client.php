<?php
namespace PublicUHC\MinecraftAuth\ReactServer;

use React\Socket\Connection;

class Client {

    private $socket;

    public function __construct(Connection $socket)
    {
        $this->socket = $socket;
        $socket->on('data', [$this, 'onData']);
    }

    public function onData($data)
    {
        echo "-> CLIENT DATA: $data\n";
    }

    public function getSocket()
    {
        return $this->socket;
    }
} 