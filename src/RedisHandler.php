<?php


namespace src;


use Redis;
use Exception;
use conf\Config;
use src\RequestHelper\RollingCurl;
use youliPhpLib\Common\RsaOperation;

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

    public function get($name = '') {
        return isset($this->$name) ? $this->$name : null;
    }

    public function setString($key, $value) {
        $this->redis->set($key, $value);
    }

    public function pop() {
        self::log($this->consumer_log_path, 'INFO - PARAM: consumer' . time());

        $key = strtotime(date('Y-m-d H:i:00', time()));

        $this->connect();
        $res = $this->redis->hGetAll($key);
        self::log($this->consumer_log_path, "INFO - key: " . $key . ', res: ' . json_encode($res));

        $job_id_arr = $urls = $data = $headers = $options = $methods = $callback = $curl_res = $notify_info = [];
        if ($res) {
            foreach ($res as $job_id => $data_json_str) {
                $job_data = json_decode($data_json_str, true);
                self::log($this->consumer_log_path, 'INFO - job data: ' . json_encode($job_data));
                array_push($urls, $job_data['url']);
                array_push($methods, $job_data['method']);
                array_push($data, $job_data['data']);
                array_push($headers, isset($job_data['headers']) ? $job_data['headers'] : []);
                array_push($options, isset($job_data['options']) ? $job_data['options'] : null);
                array_push($callback, isset($job_data['callback']) ? $job_data['callback'] : null);
                array_push($notify_info, isset($job_data['notify_info']) ? $job_data['notify_info']: null);
            }
            $job_id_arr = array_keys($res);
            self::log($this->consumer_log_path, "INFO - job_id_arr: " . json_encode($job_id_arr));
            self::log($this->consumer_log_path, "INFO - callback: " . json_encode($callback));
            self::log($this->consumer_log_path, "INFO - urls: " . json_encode($urls));
            self::log($this->consumer_log_path, "INFO - methods: " . json_encode($methods));
            self::log($this->consumer_log_path, "INFO - data: " . json_encode($data));
            self::log($this->consumer_log_path, "INFO - headers: " . json_encode($headers));
            self::log($this->consumer_log_path, "INFO - options: " . json_encode($options));
            self::log($this->consumer_log_path, "INFO - notify_info: " . json_encode($notify_info));
        }

        if ($job_id_arr) {
            $this->redis->multi();
            $this->redis->del($key);

            $rc = new RollingCurlHandler();
//            $rc->setCallback("notifyClient");
            $curl_res = null;

            foreach ($urls as $idx => $url) {
                $rc->setJobId($job_id_arr[$idx]);
                $rc->setCallback($callback[$idx] ? $callback[$idx] : 'notifyClient');
                $curl_res[] = $rc->run($url, $methods[$idx], $data[$idx], !empty($headers) ? $headers[$idx] : $headers, $options[$idx]);
            }

//            if (count($job_id_arr) == 1) {
//                $rc->setJobId($job_id_arr[0]);
//                $rc->setCallback($callback[0] ? $callback[0] : 'notifyClient');
//                $curl_res[] = $rc->run($urls[0], $methods[0], $data[0], !empty($headers) ? $headers[0] : $headers, $options[0]);
//            } else if (count($job_id_arr) >= 2) {
//                $curl_res = $rc->multiRun($urls, $methods, $data, $headers, $options);
//            }
            self::log($this->consumer_log_path, "INFO - request_result: " . json_encode($curl_res));
            self::log($this->consumer_log_path, "DEBUG - curl_res length: " . count($curl_res));
        }

        if ($curl_res) {
            foreach ($curl_res as $idx => $request_result) {
                list($request_res, $request, $job_id) = $request_result;
                self::log($this->consumer_log_path, "INFO - idx: " . $idx . ". request: " . json_encode($request) . ". response: " . json_encode($request_res));
//                $data_idx = array_search($request->post_data, $data);
                $job_id_idx = array_search($job_id, $job_id_arr);
                self::log($this->consumer_log_path, "INFO - job_id_idx: " . json_encode($job_id_idx));
                self::log($this->consumer_log_path, "INFO - remote return " . $request_res);
                // 远程接口返回的结果需要转发到另一台服务器
                if (false !== $job_id_idx && ($request_res == 'success' || preg_match("/[\x7f-\xff]/", $request_res)/*preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $request_res)*/)) {
                    $notify = $notify_info[$job_id_idx];
                    self::log($this->consumer_log_path, "INFO - notify info: " . json_encode($notify));
                    self::log($this->consumer_log_path, "INFO - notify info: " . json_encode([isset($notify['url']) && !empty($notify['url']),
                            isset($notify['method']) && !empty($notify['method'])]));
                    if ((isset($notify['url']) && !empty($notify['url'])) &&
                        (isset($notify['method']) && !empty($notify['method']))) {

                        $data = [];
                        if (isset($notify['data'])) {
                            $data = $notify['data'];
                            $data['result'] = $request_res;
                            $param = http_build_query($data);
                        }
                        self::log($this->consumer_log_path, "INFO - notify data: " . json_encode($data));

                        if (strpos($notify['url'], '/v3/order/edit') !== false) {
                            $rsa = RsaOperation::getInstance(Config::PUBLIC_PEM, Config::PRIVATE_PEM);
                            $param = 'secret=' . urlencode($rsa->publicEncrypt($data));
                            self::log($this->consumer_log_path, "INFO - encrypt data: " . $param);
                        }

                        list($remote_result, $remote_request, $notify_client_job_id) = RemoteHandler::getInstance()->callRemote($notify['url'], $param,
                            $notify['method'],
                            isset($notify['header']) ? $notify['header'] : [],
                            isset($notify['option']) ? $notify['option'] : null,
                            isset($notify['callback']) ? $notify['callback'] : 'notifyClient',
                            $job_id_arr[$job_id_idx]);


                        self::log($this->consumer_log_path, "INFO - remote result: " . json_encode([$remote_result, $remote_request, $notify_client_job_id]));
                        self::log($this->consumer_log_path, "INFO - notify result: " . json_encode($remote_result));

                        if ($remote_result !== false)
                            continue;
                    }

//                    if (! preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $request_res))
//                        continue;
                }

                self::log($this->consumer_log_path, "INFO - urls: " . json_encode([$urls[$job_id_idx], $request->url, $urls[$job_id_idx] == $request->url]));

                if ($job_id_idx === false && $urls[$job_id_idx] !== $request->url) {
                    self::log($this->consumer_log_path, "ERROR - remote return success");
                    continue;
                }

//                $job_id = isset($job_id_arr[$job_id_idx]) ? $job_id_arr[$job_id_idx] : false;
                self::log($this->consumer_log_path, "INFO - job_id: " . $job_id_arr[$job_id_idx]);
                if ($job_id === false) {
                    self::log($this->consumer_log_path, "ERROR - job_id not exists");
                    continue;
                }

                self::log($this->consumer_log_path, "INFO - order_data: " . $res[$job_id]);
                if (isset($res[$job_id])) {
                    $order_data = json_decode($res[$job_id], true);

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
                }
            }
        } else
            self::log($this->consumer_log_path, "ERROR - curl_res length: " . count($curl_res));
        $result = $this->redis->exec();
        $this->close();

        self::log($this->consumer_log_path, "INFO - RESULT: " . json_encode($result));
    }

    public function add($param = []) {
        self::log($this->log_path, 'INFO - PARAM: ' . json_encode($param));
        $this->connect();
        $this->redis->multi();
        $key = strtotime(date('Y-m-d H:i:00', time()));
        if ($param['delay'] == 0)
            $key = $key + 60;
        else
            $key = $key + $param['delay'];

        $this->redis->HSetnx($key, $param['job_id'], json_encode($param));
        $result = $this->redis->exec();
        $this->close();
        self::log($this->log_path, "INFO - RES: " . json_encode([$result]));
    }

    public function after($param = []) {
        self::log($this->consumer_log_path, 'INFO - PARAM: after' . time());
        $key = strtotime(date('Y-m-d H:i:00', time()));
        self::log($this->consumer_log_path, "INFO - key: " . $key . ', res: ' . json_encode($param));

        self::log($this->consumer_log_path, "INFO - job_id_arr: " . $param['job_id']);
        self::log($this->consumer_log_path, "INFO - callback: " . $param['callback']);
        self::log($this->consumer_log_path, "INFO - urls: " . $param['url']);
        self::log($this->consumer_log_path, "INFO - methods: " . $param['method']);
        self::log($this->consumer_log_path, "INFO - data: " . $param['data']);
        self::log($this->consumer_log_path, "INFO - headers: " . json_encode($param['header']));
        self::log($this->consumer_log_path, "INFO - options: " . json_encode($param['options']));
        self::log($this->consumer_log_path, "INFO - notify_info: " . json_encode($param['notify_info']));

        $rc = new RollingCurlHandler();

        $rc->setJobId($param['job_id']);
        $rc->setCallback($param['callback'] ? $param['callback'] : 'notifyClient');
        $res = $rc->run($param['url'], $param['method'], $param['data'],
            !empty($param['headers']) ? $param['headers'] : [],
            $param['options']);
        self::log($this->consumer_log_path, "INFO - res: " . json_encode($res));
        list($request_res, $request, $job_id) = $res;

        self::log($this->consumer_log_path, "INFO - job_id: " . $job_id);
        self::log($this->consumer_log_path, "INFO - request: " . json_encode($request));
        self::log($this->consumer_log_path, "INFO - request_res: " . $request_res);

        if ($request_res == 'success' || preg_match("/[\x7f-\xff]/", $request_res)) {

            $notify = $param['notify_info'];

            if (isset($notify['url']) && !empty($notify['url']) && isset($notify['method']) && !empty($notify['method'])) {
                $data = [];
                $param = '';
                if (isset($notify['data'])) {
                    $data = $notify['data'];
                    $data['result'] = $request_res;
                    $param = http_build_query($data);
                }
                self::log($this->consumer_log_path, "INFO - notify param: " . $param);

                if (strpos($notify['url'], '/v3/order/edit') !== false) {
                    $rsa = RsaOperation::getInstance(Config::PUBLIC_PEM, Config::PRIVATE_PEM);
                    $param = 'secret=' . urlencode($rsa->publicEncrypt($data));
                    self::log($this->consumer_log_path, "INFO - encrypt data: " . $param);
                }

                $rc->setJobId(date('Y-m-d H:i:s', time()));
                $rc->setCallback($notify['callback'] ? $notify['callback'] : 'notifyClient');
                list($notify_result, $notify_request, $notify_job_id) = $rc->run($notify['url'], $notify['method'], $param,
                    !empty($notify['headers']) ? $notify['headers'] : [],
                    $notify['options']);

                self::log($this->consumer_log_path, "INFO - notify_job_id: " . $notify_job_id);
                self::log($this->consumer_log_path, "INFO - notify_result: " . $notify_result);
                self::log($this->consumer_log_path, "INFO - notify_request: " . json_encode($notify_request));
            }
        }

        self::log($this->consumer_log_path, 'INFO - RES: after' . json_encode($res));

        return $key;
    }

    public function connect() {
        if (! $this->redis->isConnected())
            $this->redis->connect(Config::REDIS_HOST, Config::REDIS_PORT);
        $this->redis->auth(Config::REDIS_AUTH);
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
