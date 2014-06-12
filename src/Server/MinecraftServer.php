<?php
namespace PublicUHC\MinecraftAuth\Server;

use UnexpectedValueException;

class MinecraftServer {

    private $server;
    private $connections = [];

    public function __construct($host = '0.0.0.0', $port = 25565)
    {
        $this->server = stream_socket_server("tcp://$host:$port", $errNo, $errMsg);

        if ($this->server === false) {
            throw new UnexpectedValueException('Could not bind to host socket: ' . $errMsg);
        }
    }

    public function start()
    {
        while(true) {
            //prepare readable sockets
            $read_socks = $this->connections;
            $read_socks[] = $this->server;

            if(!stream_select ( $read_socks, $write, $except, 5)) {
                continue;
            }

            //new client
            if(in_array($this->server, $read_socks)) {
                $new_client = stream_socket_accept($this->server);

                if ($new_client) {
                    //print remote client information, ip and port number
                    echo 'Connection accepted from ' . stream_socket_get_name($new_client, true) . "\n";

                    $this->connections[] = $new_client;
                    echo "Now there are total ". count($this->connections) . " clients.\n";
                }

                //delete the server socket from the read sockets
                unset($read_socks[ array_search($this->server, $read_socks) ]);
            }

            //message from existing client
            foreach($read_socks as $sock) {
                $data = fread($sock, 65535);
                if(!$data) {
                    unset($this->connections[ array_search($sock, $this->connections) ]);
                    @fclose($sock);
                    echo "A client disconnected. Now there are total ". count($this->connections) . " clients.\n";
                    continue;
                }
                //echo the message back to client
                fwrite($sock, $data);
            }
        }
    }
}