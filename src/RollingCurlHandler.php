<?php


namespace src;

use src\RequestHelper\RollingCurl;
use src\RequestHelper\RollingCurlException;
use src\RequestHelper\RollingCurlRequest;

class RollingCurlHandler
{

    static public $instance = null;
    private $log_path = null;
    private $rc = null;
    private $job_id = null;

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
            case 'parsePddAddress':
                $callback_func = [$this, 'parsePddAddress'];
                break;
            case 'parsePddGoods':
                $callback_func = [$this, 'parsePddGoods'];
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
    public function __construct() {
        $this->log_path = realpath(__DIR__) . '/../log/curl_handler-' . date('Y-m-d', time()) . '.log';
        $this->rc = new RollingCurl();
    }

    /**
     * @param null $job_id
     */
    public function setJobId($job_id)
    {
        $this->job_id = $job_id;
    }

    /**
     * @param array $urls
     * @param array $methods
     * @param array $data
     * @param array $headers
     * @param array $options
     * @param array $callback
     * @return array|bool
     * @throws RollingCurlException
     */
    public function multiRun($urls = [], $methods = [], $data = [], $headers = [], $options = []) {
        foreach ($urls as $idx => $url) {
            $request = new RollingCurlRequest($url,
                isset($methods[$idx]) ? $methods[$idx] : 'GET',
                isset($data[$idx]) ? $data[$idx] : [],
                isset($headers[$idx]) ? $headers[$idx] : [],
                isset($options[$idx]) ? $options[$idx] : null);
            $this->rc->add($request);
        }
        return $this->rc->execute();
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
        if (is_null($this->job_id))
            return [false, $request, $this->job_id];

        self::log($this->log_path, "INFO - call notifyClient");
        self::log($this->log_path, "INFO - job_id " . $this->job_id);
        self::log($this->log_path, "INFO - info " . json_encode($info));
        self::log($this->log_path, "INFO - request " . json_encode($request));
        self::log($this->log_path, "INFO - response " . $response);

        if ($info['http_code'] != '200')
            self::log($this->log_path, "ERROR - error: http code不为200");

        return in_array($response, ['error', 'success']) === true ? [$response, $request, $this->job_id] : [false, $request, $this->job_id];
    }

    public function parsePddGoods($response, $info, $request) {
        if (is_null($this->job_id))
            return [false, $request];

        self::log($this->log_path, "INFO - call parsePddGoods");
        self::log($this->log_path, "INFO - job_id " . $this->job_id);
        self::log($this->log_path, "INFO - info " . json_encode($info));
        self::log($this->log_path, "INFO - request " . json_encode($request));
        self::log($this->log_path, "INFO - response " . json_encode($response));

        if ($info['http_code'] != '200')
            self::log($this->log_path, "ERROR - error: http code不为200");
//            throw new RollingCurlException("http code不为200");

        if (preg_match('/window.rawData=(.*)"}}};/', $response, $matches)) {
            self::log($this->log_path, "INFO - matches " . json_encode($matches));
            $data = $matches[1] . '"}}}';
            self::log($this->log_path, "INFO - raw data " . $data);
            if (false !== $data = json_decode($data, true)) {
                $goods_data = [
                    'goods_id' => $data['store']['initDataObj']['goods']['goodsID'],
                    'goods_name' => $data['store']['initDataObj']['goods']['goodsName'],
                    'group_id' => $data['store']['initDataObj']['goods']['groupTypes'][0]['groupID'],
                    'sku_id' => $data['store']['initDataObj']['goods']['skus'][0]['skuID'],
                    'normal_price' => $data['store']['initDataObj']['goods']['skus'][0]['normalPrice']
                ];
                self::log($this->log_path, "INFO - goods data " . json_encode($goods_data));
//                return $goods_data;
                return [$goods_data, $request];
            } else
                self::log($this->log_path, "ERROR - error: 无法登陆并获取商品信息");
//                throw new RollingCurlException("无法登陆并获取收货地址");
        } else
            self::log($this->log_path, "ERROR - error: 未知状态");
//            throw new RollingCurlException("未知状态");

        return [false, $request];
    }

    public function parsePddAddress($response, $info, $request) {
        if (is_null($this->job_id))
            return [false, $request];

        self::log($this->log_path, "INFO - call parsePddAddress");
        self::log($this->log_path, "INFO - job_id " . $this->job_id);
        self::log($this->log_path, "INFO - info " . json_encode($info));
        self::log($this->log_path, "INFO - request " . json_encode($request));
        self::log($this->log_path, "INFO - response " . json_encode($response));

        if ($info['http_code'] != '200')
            self::log($this->log_path, "ERROR - error: http code不为200");
//            throw new RollingCurlException("http code不为200");

        if (preg_match('/window.rawData=(.*)"}};/', $response, $matches)) {
            $data = $matches[1] . '"}}';
            if (false !== $data = json_decode($data, true)) {
                $address_arr = [];
                foreach ($data['store']['addressList'] as $address) {
                    if ($address['isDefault'] == 1) {
                        $address_arr = [
                            'address_id' => $address['addressId'],
                            'address' => $address['province'] . $address['city'] . $address['district'] . $address['address'],
                            'uid' => $address['uid']
                        ];
                    }
                }
//                return $address_arr;
                return [$address_arr, $request];
            } else
                self::log($this->log_path, "ERROR - error: 无法登陆并获取收货地址");
//                throw new RollingCurlException("无法登陆并获取收货地址");
        } else
            self::log($this->log_path, "ERROR - error: 未知状态");
//            throw new RollingCurlException("未知状态");

        return [false, $request];
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
        if (is_null($this->job_id))
            return [false, $request, null];

        self::log($this->log_path, "INFO - call parsePddOrderStatus");
        self::log($this->log_path, "INFO - job_id " . $this->job_id);
        self::log($this->log_path, "INFO - info " . json_encode($info));
        self::log($this->log_path, "INFO - request " . json_encode($request));
        self::log($this->log_path, "INFO - response " . json_encode($response));

        if ($info['http_code'] != '200') {
            self::log($this->log_path, "ERROR - error: http code不为200");
            return [false, $request, $this->job_id];
//            throw new RollingCurlException("http code不为200");
        }

        if (! preg_match('/"chatStatusPrompt":"([^"]*)"/', $response, $matches)) {
            self::log($this->log_path, "ERROR - matches " . json_encode($matches));
            self::log($this->log_path, "ERROR - error: 未知状态");
//            throw new RollingCurlException("未知状态");
            return [false, $request, $this->job_id];
        }

        return [$matches[1], $request, $this->job_id];
    }

    static protected function log($log_path = null, $content = '') {
        if (!is_null($log_path)) {
            error_log('[' . date('Y-m-d H:i:s', time()) . '] - '.$content . "\r\n", 3, $log_path);
        }
    }
}
