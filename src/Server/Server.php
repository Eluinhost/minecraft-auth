<?php

use PublicUHC\MinecraftAuth\Server\MinecraftServer;

require_once __DIR__ . '/../../vendor/autoload.php';

$server = new MinecraftServer();
$server->start();

//TODO remove this class, temporary file