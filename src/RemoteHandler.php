<?php


namespace src;


use conf\Config;
use Exception;
use server\Remote;
use src\RequestHelper\RollingCurl;

class RemoteHandler
{
    static public $instance = null;
    public $rc = null;
    public $log_path = null;

    /**
     * @return RemoteHandler|null
     */
    static public function getInstance() {
        if (is_null(self::$instance))
            self::$instance = new self();

        return self::$instance;
    }

    /**
     * RemoteHandler constructor.
     */
    private function __construct() {
        date_default_timezone_set("PRC");
        $this->log_path = realpath(__DIR__) . '/../log/remote_handler-' . date('Y-m-d', time()) . '.log';
        $this->rc = new RollingCurlHandler();
    }

    /**
     * @param $url
     * @param $data
     * @param string $method
     * @param array $headers
     * @param null $option
     * @param null $callback
     * @return bool|string|null
     */
    public function callRemote($url, $data, $method = 'GET', $headers = [], $option = null, $callback = null) {
        self::log($this->log_path, "INFO - call remote: param: " . json_encode(func_get_args()));
        self::log($this->log_path, "INFO - call remote: url: $url, data:" . json_encode($data) . ", method: $method, headers:" . json_encode($headers));
        $email_address = $result = null;
        try {
            // 判断参数
            if (empty($url))
                throw new Exception("url不能为空");
            if (empty($data) && $method == 'POST')
                throw new Exception("参数不能为空");

            // 调用接口 获取返回结果
            $this->rc->setCallback($callback);
            $result = $this->rc->run($url, $method, $data, $headers, $option);
            self::log($this->log_path, "INFO - response status:" . json_encode($result));

            // 设置邮件内容
            $email_address = '234769003@qq.com';
            $subject = '数据采集成功';
            $body = '123';
        } catch (Exception $e) {
            self::log($this->log_path, "ERROR - curl failed: {$e->getMessage()}");
            $subject = '数据采集失败';
            $body = $e->getMessage();
        } finally {
            // 发送邮件
            /*if ($email_address)
                EmailHandler::getInstance()->mail($email_address, $subject, $body);*/

            return $result;
        }
    }

    static protected function log($log_path = null, $content = '') {
        if (!is_null($log_path)) {
            error_log('[' . date('Y-m-d H:i:s', time()) . '] - '.$content . "\r\n", 3, $log_path);
        }
    }
}