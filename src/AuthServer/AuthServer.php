<?php
namespace PublicUHC\MinecraftAuth\AuthServer;

use PublicUHC\MinecraftAuth\Protocol\Packets\DisconnectPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\StatusResponsePacket;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Socket\Connection;
use React\Socket\Server;
use RuntimeException;

class AuthServer extends Server {

    private $loop;
    private $certificate;
    private $clients;

    /**
     * Creates a new AuthServer. Start the server with start() (will block forever)
     * @param int $port the port to bind to - default 25565
     * @param string $host the host address to bind to - default 0.0.0.0
     * @param LoopInterface $loop the loop to use, default null causes Factory::create() to create one
     */
    public function __construct($port = 25565, $host = '0.0.0.0', LoopInterface $loop = null)
    {
        if(null === $loop) {
            $loop = Factory::create();
        }
        $this->loop = $loop;
        parent::__construct($loop);

        $this->certificate = new Certificate();

        $this->on('connection', [$this, 'onConnection']);

        $this->on('error', function(RuntimeException $ex) {
            echo "Error with server connection: {$ex->getMessage()}\n";
        });

        $this->listen($port, $host);
    }

    /**
     * Start the server up
     */
    public function start()
    {
        $this->loop->run();
    }

    /**
     * @param $socket
     * @return AuthClient
     * @Override
     */
    public function createConnection($socket)
    {
        return new AuthClient($this->certificate, $socket, $this->loop);
    }

    public function echoOnlineCount()
    {
        echo count($this->clients) . " open connections.\n";
    }

    /**
     * Called on event 'connection'
     * @param AuthClient $connection
     */
    public function onConnection(AuthClient $connection)
    {
        //bubble the events up
        $connection->on('login_success', function(AuthClient $client, DisconnectPacket $packet) {
            $this->emit('login_success', [$client->getUsername(), $client->getUUID(), $packet]);
        });

        $connection->on('status_request', function(StatusResponsePacket $packet) {
            $this->emit('status_request', [$packet]);
        });

        $connection->on('close', function() use (&$connection) {
            $amount = count($this->clients);
            for($i = 0; $i<$amount; $i++) {
                /** @var $checkclient AuthClient */
                $checkclient = $this->clients[$i];
                //if we found our connection
                if($checkclient === $connection) {
                    //remove from array and reset the keys
                    unset($this->clients[$i]);
                    $this->clients = array_values($this->clients);
                    echo "A client disconnected. Now there are total ". ($amount - 1) . " clients.\n";
                    return;
                }
            }
        });

        //if there is an error with the connection echo the error and end the connection
        $connection->on('error', function($error, Connection $connection) {
            echo "ERROR: $error\n";
            $connection->end();
        });

        //add the connection to the list of tracked connections
        $this->clients[] = $connection;

        //echo how many are now online
        $count = count($this->clients);
        echo "New client conected: {$connection->getRemoteAddress()}. Clients online: $count.\n";
    }
}
