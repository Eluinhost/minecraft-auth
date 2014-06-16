<?php
namespace PublicUHC\MinecraftAuth\ReactServer;

use Evenement\EventEmitter;
use PublicUHC\MinecraftAuth\ReactServer\Encryption\Certificate;
use React\EventLoop\Factory;
use React\Socket\Connection;
use React\Socket\Server;
use RuntimeException;

class ReactServer extends EventEmitter {

    private $clients = [];
    private $certificate;
    private $loop;

    public function __construct($port, $host = '127.0.0.1')
    {
        $this->loop = Factory::create();
        $socket = new Server($this->loop);

        $this->certificate = new Certificate();

        $socket->on('connection', [$this, 'onConnection']);

        $socket->on('error', function(RuntimeException $ex) {
            echo "Error with server connection: {$ex->getMessage()}\n";
        });

        $socket->listen($port, $host);
    }

    /**
     * Start the server up
     */
    public function start()
    {
        $this->loop->run();
    }

    public function echoOnlineCount()
    {
        echo count($this->clients) . " open connections.\n";
    }

    public function onConnection(Connection $connection)
    {
        $newClient = new AuthClient($connection, $this->certificate);

        //bubble the event up
        $newClient->on('login_success', function(AuthClient $client) {
            $this->emit('login_success', [$client]);
        });

        $connection->on('close', function(Connection $connection) use (&$newClient) {
            for($i = 0; $i<count($this->clients); $i++) {
                /** @var $checkclient AuthClient */
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