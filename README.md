minecraft-auth
==============

PHP Library for running a 'fake' Minecraft server that checks auth and kicks on connect

HOW TO USE:

    use PublicUHC\MinecraftAuth\AuthServer\AuthServer;

    $server = new AuthServer(25565, '127.0.0.1');
    
    $server->on('login_success', function($username, $uuid){
        echo "USERNAME: $username, UUID: $uuid\n";
    });
    
    $server->start();
    
Make sure to do any processing/setting up before calling `$server-start();` as it will block until the server is stopped.
