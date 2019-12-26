<?php

require realpath(dirname(__FILE__)) . '/vendor/autoload.php';

use server\client;

$query_param = [
    'command' => 'pop'
];

$client = client::getInstance();
$client->connect();
$client->consumer($query_param);
$client->close();
echo 'asdf';