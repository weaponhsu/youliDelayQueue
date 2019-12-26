<?php


namespace src;


use Redis;

class RedisDelayQueue
{
    static public $instance = null;
    public $redis = null;
    public $log_path = null;

    static public function getInstance() {
        if (is_null(self::$instance))
            self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        date_default_timezone_set("PRC");
        $this->log_path = realpath(__DIR__) . '/../log/redis_delay_queue' . date('Y-m-d', time()) . '.log';

        $this->redis = new Redis();
    }

    public function pop() {
        self::log($this->log_path, 'INFO - PARAM: consumer' . time());

        $this->connect();
        $this->redis->multi();
        $key = '1577375927';
//        $key = time();
        $this->redis->hVals($key);
        $result = $this->redis->exec();

        self::log($this->log_path, "INFO - RES: " . $result[0][0]);

    }

    public function add($param = []) {
        self::log($this->log_path, 'INFO - PARAM: ' . json_encode($param));

        $this->connect();
        $this->redis->multi();
        $this->redis->HSetnx(time() + $param['delay'], $param['order_sn'], json_encode($param));
        $result = $this->redis->exec();
        self::log($this->log_path, "INFO - RES: " . json_encode([$result]));
    }

    public function connect() {
        if (! $this->redis->isConnected())
            $this->redis->connect(Config::REDIS_HOST, Config::REDIS_PORT);
    }

    public function close() {
        if ($this->redis->isConnected())
            $this->redis->close();
    }

    static protected function log($log_path = null, $content = '') {
        if (!is_null($log_path)) {
            error_log('[' . date('Y-m-d H:i:s', time()) . '] - '.$content . "\r\n", 3, $log_path);
        }
    }
}