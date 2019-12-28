<?php

require realpath(dirname(__FILE__)) . '/vendor/autoload.php';

use server\Consumer;
use conf\Config;
use Hashids\Hashids;

$hash_ids = new Hashids(Config::SALT, Config::MIN_HASH_LENGTH);
$job_id = $hash_ids->encode('234');

$data = ['tid' => '583ecdfb-fb46-11e1-82cb-f4ce4684ea4c', 'page' => 1, 'page_size' => 5];
$query_param = [
    'command' => 'add',
    'data' => [
        'time' => time(),
        'delay' => 0,
        'job_id' => $job_id,
        'url' => 'http://120.78.190.34/nba/games',
        'method' => 'POST',
        'data' => json_encode($data),
        'headers' => ['Content-type: application/json']
    ]
];

$client = Consumer::getInstance();
try {
    $client->connect(Config::CONSUMER_HOST, Config::PRODUCER_PORT, Config::TIMEOUT);
    $client->producer($query_param);
} catch (Exception $e) {
    echo $e->getMessage();
} finally {
    $client->close();
    echo 'done';
}
