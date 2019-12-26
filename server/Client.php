<?php


namespace server;

use Exception;
use Swoole\Client as SwClient;


class client
{
    static public $instance = null;
    private $client;

    static public function getInstance() {
        if (is_null(self::$instance))
            self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        $this->client = new SwClient(SWOOLE_SOCK_TCP);
    }

    /**
     * @param array $param
     * @throws Exception
     */
    public function producer($param = []) {
        if (! is_array($param) || empty($param))
            throw new Exception("无效param参数");

        $this->client->send(json_encode($param, true));
    }

    public function consumer($param = []) {
        if (! is_array($param) || empty($param) || !isset($param['command']) || $param['command'] != 'pop')
            throw new Exception("无效param参数");

        $this->client->send(json_encode($param, true));
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function connect() {
        if (! $this->client->connect(Config::CLIENT_HOST, Config::PORT , Config::TIMEOUT))
            throw new Exception('无法链接服务器');

        return $this;
    }

    public function close() {
        if ($this->client->isConnected() === true)
            $this->client->close();

        return true;
    }
}