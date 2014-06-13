<?php
namespace PublicUHC\MinecraftAuth\ReactServer;

use React\EventLoop\Factory;
use React\Socket\Connection;
use React\Socket\Server;
use RuntimeException;

class ReactServer {

    private $clients = [];

    public function __construct($port, $host = '127.0.0.1')
    {
        $loop = Factory::create();
        $socket = new Server($loop);

        $socket->on('connection', [$this, 'onConnection']);

        $socket->on('error', function(RuntimeException $ex) {
            echo "Error with server connection: {$ex->getMessage()}\n";
        });

       // $loop->addPeriodicTimer(2, [$this, 'echoOnlineCount']);

        $socket->listen($port, $host);
        $loop->run();
    }

    public function echoOnlineCount()
    {
        echo count($this->clients) . " open connections.\n";
    }

    public function onConnection(Connection $connection)
    {
        $newClient = new Client($connection);

        $connection->on('end', function(Connection $connection) use (&$newClient) {
            for($i = 0; $i<count($this->clients); $i++) {
                /** @var $checkclient Client */
                $checkclient = $this->clients[$i];
                if($checkclient == $newClient) {
                    unset($this->clients[$i]);
                    $this->clients = array_values($this->clients);
                    echo "A client disconnected. Now there are total ". count($this->clients) . " clients.\n";
                    return;
                }
            }
        });

        $connection->on('error', function($error, $connection) {
            /** @var $connection Connection */
            echo "ERROR: $error\n";
            $connection->end();
        });

        $this->clients[] = $newClient;
        $count = count($this->clients);
        echo "New client conected: {$connection->getRemoteAddress()}. Clients online: $count.\n";
    }
} 