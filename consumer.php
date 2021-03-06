<?php

require realpath(dirname(__FILE__)) . '/vendor/autoload.php';

use server\Consumer;
use conf\Config;

set_time_limit(60);
$query_param = ['command' => 'pop'];

$client = Consumer::getInstance();

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
        echo "sleep $sleep_time";
    }
}
