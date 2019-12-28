<?php


namespace src;


use Redis;
use Exception;
use conf\Config;
use server\Remote;

class RedisHandler
{
    static public $instance = null;
    public $redis = null;
    public $log_path = null;
    public $consumer_log_path = null;

    static public function getInstance() {
        if (is_null(self::$instance))
            self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        date_default_timezone_set("PRC");
        $this->log_path = realpath(__DIR__) . '/../log/redis_handler-' . date('Y-m-d', time()) . '.log';
        $this->consumer_log_path = realpath(__DIR__) . '/../log/consumer-' . date('Y-m-d', time()) . '.log';

        $this->redis = new Redis();
    }

    public function pop() {
        self::log($this->consumer_log_path, 'INFO - PARAM: consumer' . time());

        $key = strtotime(date('Y-m-d H:i:00', time()));

        $client = Remote::getInstance();
        $this->connect();
        $this->redis->multi();
        $this->redis->hGetAll($key);
        $this->redis->del($key);
        $result = $this->redis->exec();
        $this->close();

        $this->connect();
        $this->redis->multi();
        foreach ($result[0] as $job_id => $order_string) {
            $order_data = json_decode($order_string, true);

            try {
                throw new Exception('ddd');
                /*
                // 调用接口 获取返回结果
                list($resp_code, $header_size, $resp_body) =
                    RequestHelper::curlRequest($order_data['url'], $order_data['data'],
                        $order_data['method'], $order_data['headers'], false, 30, true);
                self::log($this->log_path, "INFO - response: response_code: $resp_code, response_body:" . $resp_code . ", header_size: $header_size");
                $resp_body = substr($resp_body, $header_size);
                self::log($this->log_path, "INFO - response body: " . trim($resp_body));
                */
            } catch (Exception $e) {
                // 设置邮件内容
                $email_address = '234769003@qq.com';
                $subject = '数据读取失败';
                $body = $e->getMessage();
                EmailHandler::getInstance()->mail($email_address, $subject, $body);

                switch ($order_data['delay']) {
                    case 0:
                        $delay = 60;
                        break;
                    case 60:
                        $delay = 300;
                        break;
                    case 300:
                        $delay = 600;
                        break;
                    case 900:
                        $delay = 1800;
                        break;
                    case 1800:
                        $delay = 3600;
                        break;
                    default:
                        $delay = false;
                        break;
                }

                if ($delay !== false) {
                    $order_data['delay'] = $delay;
                    self::log($this->consumer_log_path, "INFO - delay: $delay");
                    self::log($this->consumer_log_path, "INFO - key: " . (int)($key + $delay));
                    $this->redis->HSetnx((int)($key + $delay), $job_id, json_encode($order_data));
                    $this->redis->expire((int)($key + $delay), (string)($key + $delay + 120));
                }

            } finally {
                $result = $this->redis->exec();
                $this->close();
//                self::log($this->consumer_log_path, "ERROR - RESET DELAY FAILED: " . json_encode($query_param));
            }
        }

        self::log($this->consumer_log_path, "INFO - RES: " . json_encode($result));
    }

    public function add($param = []) {
        self::log($this->log_path, 'INFO - PARAM: ' . json_encode($param));
        $this->connect();
        $this->redis->multi();
        $key = strtotime(date('Y-m-d H:i:00', time()));
        if ($param['delay'] == 0)
            $key = $key + 60;
        $this->redis->HSetnx($key, $param['job_id'], json_encode($param));
        $result = $this->redis->exec();
        $this->close();
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