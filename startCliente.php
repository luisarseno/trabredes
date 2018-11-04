<?php
/**
 * Created by PhpStorm.
 * User: santana
 * Date: 03/11/18
 * Time: 19:37
 */

require_once 'Client.php';

$cliente = new Client("127.0.0.1", 6000);

$cliente->startClient();