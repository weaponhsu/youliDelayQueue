<?php


namespace src;


use conf\Config;
use Exception;
use server\Remote;

class RemoteHandler
{
    static public $instance = null;
    public $log_path = null;

    static public function getInstance() {
        if (is_null(self::$instance))
            self::$instance = new self();

        return self::$instance;
    }

    private function __construct() {
        date_default_timezone_set("PRC");
        $this->log_path = realpath(__DIR__) . '/../log/remote_handler-' . date('Y-m-d', time()) . '.log';
    }

    /**
     * @param $url
     * @param $data
     * @param string $method
     * @param array $headers
     * @throws Exception
     */
    public function callRemote($url, $data, $method = 'GET', $headers = []) {
        self::log($this->log_path, "INFO - call remote: url: $url, data:" . json_encode($data) . ", method: $method, headers:" . json_encode($headers));
        $email_address = null;
        try {
            // 判断参数
            if (empty($url))
                throw new Exception("url不能为空");
            if (empty($data))
                throw new Exception("参数不能为空");

            // 调用接口 获取返回结果
            list($resp_code, $header_size, $resp_body) =
                RequestHelper::curlRequest($url, $data, $method, $headers, false, 30, true);
            self::log($this->log_path, "INFO - response: response_code: $resp_code, response_body:" . $resp_code . ", header_size: $header_size");
            $resp_body = substr($resp_body, $header_size);
            self::log($this->log_path, "INFO - response body: " . trim($resp_body));

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
            if ($email_address)
                EmailHandler::getInstance()->mail($email_address, $subject, $body);
        }
    }

    static protected function log($log_path = null, $content = '') {
        if (!is_null($log_path)) {
            error_log('[' . date('Y-m-d H:i:s', time()) . '] - '.$content . "\r\n", 3, $log_path);
        }
    }
}