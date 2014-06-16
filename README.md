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
    
    $server->on('status_request', function(StatusResponsePacket $packet) {
        $packet->setDescription('test server')
            ->setMaxPlayers(10)
            ->setOnlineCount(1000)
            ->setVersion('your mum')
            ->setProtocol(5);
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
    
This is the login_success event. It is called whenever a successful connection has been made.
Username is the username of the client connecting, UUID is the minecraft UUID (without -s). $packet is a DisconnectPacket
object that will be sent to the client after the event. Use setReason (to set an array/string reason that will be json encoded before sending) 
or setReasonJSON (to set a pre-encoded json string). 

    $server->on('status_request', function(StatusResponsePacket $packet) {
        $packet->setDescription('test')
            ->setMaxPlayers(10)
            ->setOnlineCount(1000)
            ->setVersion('your mum')
            ->setProtocol(5);
            ->setOnlinePlayers([
                [
                    'name' => 'Eluinhost',
                    'id'   => '000000000000-0000-0000-0000-00000000'
                ]
            ]);
    });
    
This is the status_request event. It is called whenever a client requests data for the server list.

->setDescription(string); - Sets the description that shows up in the server list  
->setMaxPlayers(int)      - the number after the / on the list  
->setOnlineCount(int)     - the number before the / on the list  
->setVersion(string)      - If the client is connecting on a different protocol this version number will show instead of min/max players  
->setProtocol(5)          - The protocol version to set to, we only accept 5 so either set this to 5 or leave it out   
->setOnlinePlayers(array) - An array of player names to show when hovering over the online count. For array format check the PHPDoc comment   
->setFavicon(string)      - The image in text format (i.e. data:image/png;base64,DATAHERE)     

All of the method can be ignored. If they are not set the following will be set instead:

->setDescription(string); - Default: §4▁§e▂§4▃§e▄§4▅§e▆§4▇§e█ §4§l   PHPAuthServer   §e█§4▇§e▆§4▅§e▄§4▃§e▂§4▁ §c▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔▔  
->setMaxPlayers(int)      - Default: -1  
->setOnlineCount(int)     - Default: -1  
->setVersion(string)      - Default: 1.7.6+  
->setProtocol(5)          - Default: 5  
->setOnlinePlayers(array) - Default: []  
->setFavicon(string)      - Default: null (no favicon)  
    
Due to the single thread nature of PHP all events will be blocking other code. If you run any
long running processes in here it will stop processing other connections until it is complete. Either keep computation low or fork
processes to run long running code.

    `$server->start()`
    
Starts the server, nothing after this method will be called as it blocking, make sure to run everything you need before this.