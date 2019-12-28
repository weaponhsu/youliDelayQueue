<?php

require realpath(dirname(__FILE__)) . '/vendor/autoload.php';

use server\Server;
use server\Consumer;
use conf\Config;

$server = new Server(Config::PRODUCER_HOST, Config::PRODUCER_PORT, 'producer');

// 启动consumer
$query_param = ['command' => 'pop'];

$client = Consumer::getInstance();
var_dump($client);

$current_time = time();
$start_time = strtotime(date("Y-m-d H:i:00", $current_time)) + 60;
if ($start_time % $current_time != 0) {
    $sleep_time = $start_time - $current_time;
    sleep($sleep_time);
    try {
        $client->connect(Config::CONSUMER_HOST, Config::PRODUCER_PORT, Config::TIMEOUT);
        $client->consumer($query_param);
    } catch (Exception $e) {
        echo $e->getMessage();
    } finally {
        $client->close();
        echo 'done';
    }
}
