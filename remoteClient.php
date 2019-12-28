<?php
require realpath(dirname(__FILE__)) . '/vendor/autoload.php';

use conf\Config;
use server\Remote;

$query_param = [
    'command' => 'callRemote',
    'data' => [
        'url' => 'http://120.78.190.34/nba/games',
        'method' => 'POST',
        'data' => json_encode(['tid' => '583ecdfb-fb46-11e1-82cb-f4ce4684ea4c', 'page' => 1, 'page_size' => 5]),
        'headers' => ['Content-type: application/json']
    ]
];

$client = Remote::getInstance();
try {
    $client->connect(Config::REMOTE_CLIENT_HOST, Config::REMOTE_PORT, Config::REMOTE_CLIENT_TIMEOUT);
    $client->callRemote($query_param);
} catch (Exception $e) {
    echo $e->getMessage();
} finally {
    $client->close();
    echo 'done';
}
