<?php
use PublicUHC\MinecraftAuth\AuthServer\AuthServer;
use PublicUHC\MinecraftAuth\Protocol\Packets\DisconnectPacket;

require_once __DIR__ . '/../../vendor/autoload.php';

$server = new AuthServer(25565);

$server->on('login_success', function($username, $uuid, DisconnectPacket $packet){
    echo "USERNAME: $username, UUID: $uuid\n";
    $packet->setReason("USERNAME: $username, UUID: $uuid");
});

$server->start();