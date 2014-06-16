minecraft-auth
==============

PHP Library for running a 'fake' Minecraft server that checks authentications are correct with the session servers and 
kicks the player afterwards. Has a callback to change the disconnect message. I use if for verification codes to link minecraft
accounts without needing them to input their password.

Install
=======

Install via composer by adding `"publicuhc/minecraft-auth": "dev-master"` to your composer require.

Dependencies: Will be handled by composer on install, requires the PHP mcrypt extension to run. (You can install this on linux with `apt-get install php5-mcrypt` or similar)

Example Usage
=============

    use PublicUHC\MinecraftAuth\AuthServer\AuthServer;

    $server = new AuthServer(25565, '0.0.0.0');
    
    $server->on('login_success', function($username, $uuid, DisconnectPacket $packet){
        echo "USERNAME: $username, UUID: $uuid\n";
        $packet->setReason("USERNAME: $username, UUID: $uuid");
    });
    
    $server->start();
    
Explanation
===========

    new AuthServer(25565, '0.0.0.0');
    
Creates a new auth server to bind on the port 25565 and on all interfaces (0.0.0.0). 
If second parameter is ommited it will bind to 127.0.0.1 for localhost connections.

    $server->on('login_success', function($username, $uuid, DisconnectPacket $packet){
        echo "USERNAME: $username, UUID: $uuid\n";
        $packet->setReason("USERNAME: $username, UUID: $uuid");
    });
    
There is only 1 event to listen on (login_success). It is called whenever a successful connection has been made.
Username is the username of the client connecting, UUID is the minecraft UUID (without -s). $packet is a DisconnectPacket
object that will be sent to the client after the event. Use setReason (to set an array/string reason that will be json encoded before sending) 
or setReasonJSON (to set a pre-encoded json string). Due to the single thread nature of PHP this method will be blocking. If you run any
long running processes in here it will stop processing other connections until it is complete. Either keep computation low or fork
processes to run long running code.

    `$server->start()`
    
Starts the server, nothing after this method will be called as it blocking, make sure to run everything you need before this.