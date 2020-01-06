<?php

require realpath(dirname(__FILE__)) . '/vendor/autoload.php';

use server\Consumer;
use conf\Config;
use Hashids\Hashids;

$hash_ids = new Hashids(Config::SALT, Config::MIN_HASH_LENGTH);
$job_id = $hash_ids->encode('123');

$data = ['tid' => '583ecdfb-fb46-11e1-82cb-f4ce4684ea4c', 'page' => 1, 'page_size' => 5];
$query_param = [
    'command' => 'add',
    'data' => [
        'time' => time(),
        'delay' => 0,
        'job_id' => $job_id,
//        'url' => 'http://120.78.190.34/nba/games',
        'url' => 'http://bbs.ananazq.com/v3/section/list',
        'method' => 'GET',
//        'data' => json_encode($data),
        'data' => urldecode(http_build_query(['secret' => 'IrGS989WfxIzDJPqHezxgWOEZ3a+RI2rcedpXMQfzFYi+AJuI59PzY1PuSRsm7LxRd+nSjUuz+exToe7NOSXlfyelFHisheB6R9eZLzcyvQrofiU22YUyLhnNEdnbpbIUwnzbV2K6u23FLj5dJwfgFHm3kFpn4bj0zfxNvU9MjMJbsQNukPDE+T89M3zigKz9vMDJm/wuF05aWdpW8LW4rcAPaIzpW4SpSuVNWyMpk0gzd2AzXiTM901HwVuqcgopS6qnEjagfYZztetS/chwTjuXDEF9IaOByf4BbQ2QtWXSkJi+wX2UrEwpSyxasWQX7q0/LZrJzV44HUWPBpKpeyk6WEW/mn9+uI7kXyDls8n1mA8jEIeUI8w/g13iHeCCxCk3SSDbC1pM8utHJAw/enVgX7MeBPcHBTrVZtyrPEJ5vDZA8qPgj4HgpxZdx1B1mfao5iUasYPgTq3919UNk/k3h95ybdtHFvzyG/48CoeCdjLjx/BqNHg9jZEsnZpjyhSlkxykm6z+7jf0sNQ/44NRw41RjEpEMXxqkEo3fbVFFZiFKE9m3UZsVDiUYDP0VvV5ZeP6vRwEjzPeSlk+UTyDy/nG9J9Vtzz9wKw3Bo8OwbH1c8bDc8CbXvhYpfHHv55QZxFBgH7dh6Ifuql1tY3qA9giqk1J6ZAsoyrmpk='])),
        'headers' => ['Content-type: application/json'],
//        'options' => [
//            CURLOPT_PROXY => '117.69.33.68',
//            CURLOPT_PROXYPORT => '57114'
//        ]
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
