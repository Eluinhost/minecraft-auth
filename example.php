<?php

require('./vendor/autoload.php');

use PublicUHC\MinecraftAuth\AuthServer\AuthServer;
use PublicUHC\MinecraftAuth\Protocol\Packets\DisconnectPacket;
use PublicUHC\MinecraftAuth\Protocol\Packets\StatusResponsePacket;

$server = new AuthServer(25565, '0.0.0.0');

$server->on('login_success', function($username, $uuid, DisconnectPacket $packet){
    echo "USERNAME: $username, UUID: $uuid\n";
    $packet->setReason("USERNAME: $username, UUID: $uuid");
});

$server->on('status_request', function(StatusResponsePacket $packet) {
    $packet->setDescription('test server')
        ->setMaxPlayers(10)
        ->setOnlineCount(1000)
        ->setVersion('1.7.6')
        ->setProtocol(5);
});

$server->start();