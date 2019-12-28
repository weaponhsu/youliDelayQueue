<?php


namespace server;


use Swoole\Client as SwClient;
use Exception;

class Remote
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
     * 调用远程接口
     * @param array $param
     * @throws Exception
     */
    public function callRemote($param = []) {
        if (! is_array($param) || empty($param))
            throw new Exception("无效param参数");
        if (! isset($param['data']['url']))
            throw new Exception("url不存在");
        if (! isset($param['data']['data']))
            throw new Exception("data数据不存在");
        if (! isset($param['data']['method']))
            throw new Exception("method不存在");

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