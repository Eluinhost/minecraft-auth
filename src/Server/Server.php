<?php

use PublicUHC\MinecraftAuth\Server\MinecraftServer;

require_once 'MinecraftServer.php';

$server = new MinecraftServer();
$server->start();

//TODO remove this class, temporary file