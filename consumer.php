<?php

require realpath(dirname(__FILE__)) . '/vendor/autoload.php';

use server\Consumer;
use conf\Config;

$query_param = [
    'command' => 'pop'
];

$client = Consumer::getInstance();

$current_time = time();
$start_time = strtotime(date("Y-m-d H:i:00", $current_time)) + 60;
self::log($this->log_path, "INFO - Current time {$current_time} - Start time {$start_time} - {" . $start_time % $current_time . "}");
if ($start_time % $current_time != 0) {
    $sleep_time = $start_time - $current_time;
    self::log($this->log_path, "INFO - Sleep {$sleep_time} ");

    $server->after($sleep_time * 1000, function () use (&$server, &$data, &$fd) {
        $server->task($data);
    });
}

try {
    $client->connect(Config::CONSUMER_HOST, Config::PRODUCER_PORT, Config::TIMEOUT);
    $client->consumer($query_param);
} catch (Exception $e) {
    echo $e->getMessage();
} finally {
    $client->close();
    echo 'done';
}
