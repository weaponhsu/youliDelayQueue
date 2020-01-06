<?php


namespace src;

use src\RequestHelper\RollingCurl;
use src\RequestHelper\RollingCurlException;

class RollingCurlHandler
{

    static public $instance = null;
    private $log_path = null;
    private $rc = null;

    /**
     * 设置RollingCurl的callback函数
     * @param string $callback
     */
    public function setCallback($callback = 'parsePddOrderStatus') {
        switch ($callback) {
            // 调用指定服务器的只能返回success或error的接口
            case 'notifyClient':
                $callback_func = [$this, 'notifyClient'];
                break;
            // 默认调用pdd单比订单状态查询
            default:
                $callback_func = [$this, 'parsePddOrderStatus'];
                break;

        }
        $this->rc->__set("callback", $callback_func);
    }

    /**
     * @return RollingCurlHandler|null
     */
    static public function getInstance() {
        if (is_null(self::$instance))
            self::$instance = new self();

        return self::$instance;
    }

    /**
     * RollingCurlHandler constructor.
     */
    private function __construct() {
        $this->log_path = realpath(__DIR__) . '/../log/curl_handler-' . date('Y-m-d', time()) . '.log';
        $this->rc = new RollingCurl();
    }

    /**
     * @param $url
     * @param $method
     * @param $data
     * @param $headers
     * @param $option
     * @return bool|string
     * @throws RollingCurlException
     */
    public function run($url, $method, $data, $headers, $option) {
        self::log($this->log_path, "INFO - call run url: $url, method: $method, data:" . json_encode($data) . ", headers:" . json_encode($headers) . ", options: " . json_encode($option));

        $this->rc->request($url, $method, $data, $headers, $option);
        return $this->rc->execute();
    }

    /**
     * 给指定服务器发送数据后，必须接收到error或success的接口返回结果解析
     * @param $response
     * @param $info
     * @param $request
     * @return bool
     * @throws RollingCurlException
     */
    public function notifyClient($response, $info, $request) {
        self::log($this->log_path, "INFO - call notifyClient");

        if ($info['http_code'] != '200')
            throw new RollingCurlException("http code不为200");

        return in_array($response, ['error', 'success']) === true ? $response : false;
    }

    /**
     * 解析pdd接口返回结果
     * @param $response
     * @param $info
     * @param $request
     * @return mixed
     * @throws RollingCurlException
     */
    public function parsePddOrderStatus($response, $info, $request) {
        self::log($this->log_path, "INFO - call parsePddOrderStatus");

        if ($info['http_code'] != '200')
            throw new RollingCurlException("http code不为200");

        if (! preg_match('/"chatStatusPrompt":"([^"]*)"/', $response, $matches))
            throw new RollingCurlException("未知状态");

        return $matches[1];
    }

    static protected function log($log_path = null, $content = '') {
        if (!is_null($log_path)) {
            error_log('[' . date('Y-m-d H:i:s', time()) . '] - '.$content . "\r\n", 3, $log_path);
        }
    }
}