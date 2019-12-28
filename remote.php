<?php
require realpath(dirname(__FILE__)) . '/vendor/autoload.php';

use server\Server;
use conf\Config;

$server = new Server(Config::REMOTE_HOST, Config::REMOTE_PORT, 'remote');
