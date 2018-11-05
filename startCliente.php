<?php


require_once 'Client.php';

$cliente = new Client("127.0.0.1", 6000);

$cliente->startClient();