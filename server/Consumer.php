<?php


namespace server;

use Exception;
use Swoole\Client as SwClient;


class Consumer
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
     * 生产者
     * @param array $param
     * @throws Exception
     */
    public function producer($param = []) {
        if (! is_array($param) || empty($param))
            throw new Exception("无效param参数");

        $this->client->send(json_encode($param, true));
    }

    /**
     * 开启消费者
     * @param array $param
     * @throws Exception
     */
    public function consumer($param = []) {
        if (! is_array($param) || empty($param) || ! isset($param['command']) || $param['command'] != 'pop')
            throw new Exception("无效param参数");

        $this->client->send(json_encode($param, true));
    }

    /**
     * @param $host
     * @param $port
     * @param $timeout
     * @return $this
     * @throws Exception
     */
    public function connect($host, $port, $timeout) {
        if (! $this->client->connect($host, $port , $timeout))
            throw new Exception('无法链接服务器');

        return $this;
    }

    public function close() {
        if ($this->client->isConnected() === true)
            $this->client->close();

        return true;
    }
}