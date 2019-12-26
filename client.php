<?php

require realpath(dirname(__FILE__)) . '/vendor/autoload.php';

use server\client;

$query_param = [
    'command' => 'add',
    'data' => [
        'time' => time(),
        'delay' => 60,
        'order_sn' => '123456',
        'notify_url' => 'http://www.baidu.com',
        'order_status' => true
    ]
];

$client = client::getInstance();
$client->connect();
$client->producer($query_param);
$client->close();
echo 'asdf';
