<?php
use PublicUHC\MinecraftAuth\ReactServer\ReactServer;

require_once __DIR__ . '/../../vendor/autoload.php';

$server = new ReactServer(25565);

$server->on('login_success', function(\PublicUHC\MinecraftAuth\ReactServer\AuthClient $client){
    var_dump($client);
});

$server->start();