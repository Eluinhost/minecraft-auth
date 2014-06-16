<?php
use PublicUHC\MinecraftAuth\AuthServer\AuthServer;

require_once __DIR__ . '/../../vendor/autoload.php';

$server = new AuthServer(25565);

$server->on('login_success', function($username, $uuid){
    echo "USERNAME: $username, UUID: $uuid\n";
});

$server->start();