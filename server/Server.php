<?php
/**
 *
 *            ┏┓　　  ┏┓+ +
 *           ┏┛┻━━━━━┛┻┓ + +
 *           ┃         ┃ 　
 *           ┃   ━     ┃ ++ + + +
 *          ████━████  ┃+
 *           ┃　　　　　 ┃ +
 *           ┃　　　┻　　┃
 *           ┃　　　　　 ┃ + +
 *           ┗━┓　　　┏━┛
 *             ┃　　　┃　　　　
 *             ┃　　　┃ + + + +
 *             ┃　　　┃    Code is far away from bug with the alpaca protecting
 *             ┃　　　┃ + 　　　　        神兽保佑,代码无bug
 *             ┃　　　┃
 *             ┃　　　┃　　+
 *             ┃     ┗━━━┓ + +
 *             ┃         ┣┓
 *             ┃ 　　　　　┏┛
 *             ┗┓┓┏━━┳┓┏━┛ + + + +
 *              ┃┫┫  ┃┫┫
 *              ┗┻┛  ┗┻┛+ + + +
 * Created by PhpStorm.
 * User: weaponhsu
 * Date: 2019/2/2
 * Time: 10:19 AM
 */

namespace server;

use src\EmailHandler;
use Swoole\Server as SwServer;
use src\RedisHandler;
use src\RemoteHandler;
use conf\Config;
use Exception;
use youliPhpLib\Common\RsaOperation;


class Server
{
    public $server;
    public $type;
    public $pid_file;
    public $log_path;
    public $redis_handler = null;
    public $remote_handler = null;
    public $email_handler = null;

    /**
     * Server constructor.
     * @param $host
     * @param $port
     * @param $type
     * @throws Exception
     */
    public function __construct($host, $port, $type)
    {
        date_default_timezone_set("PRC");
        if (! in_array($type, Config::ALLOWED_SERVER_TYPE))
            throw new Exception("无效type参数");

        $this->type = $type;
        $this->pid_file = realpath(__DIR__) . '/../bin/' . $this->type . '.pid';
        $this->log_path = realpath(__DIR__) . '/../log/' . $this->type. '-' . date('Y-m-d', time()) . '.log';
        $this->server = new SwServer($host, $port);
        $this->server->set([
            'worker_num' => 1,
            'daemonize' => 1,
            'max_request' => 10000,
            'task_worker_num' => 1,
            'task_ipc_mode' => 1,
            'log_file' => $this->log_path
        ]);

        if ($this->type == 'remote')
            $this->remote_handler = RemoteHandler::getInstance();
        if ($this->type == 'producer')
            $this->redis_handler = RedisHandler::getInstance();

        $this->server->on('Receive', [$this, 'onReceive']);
        // bind callback
        $this->server->on('Task', [$this, 'onTask']);
        $this->server->on('Finish', [$this, 'onFinish']);
        // 进程启动与关闭的callback
        $this->server->on("Start", [$this, 'onStart']);
        $this->server->on("Shutdown", [$this, 'onShutdown']);

        $this->server->start();
    }

    /**
     * 进程启动的callback
     * @param SwServer $server
     */
    public function onStart(\Swoole\Server $server) {
        // 进程启动 将master_pid写入./bin/proeucer.pid中，便于shutdown.sh脚本读取进程号并kill -15
        file_put_contents($this->pid_file, $this->server->master_pid);
        self::log($this->log_path,
            "INFO - " . $this->type . " Server Start!!! master_pid: " . $this->server->master_pid .
            ", manager_pid: " . $this->server->manager_pid);

        if ($this->type == 'producer') {
            $current_time = time();
            $start_time = strtotime(date("Y-m-d H:i:00", $current_time)) + 60;
            self::log($this->log_path, "INFO - Current time {$current_time} - Start time {$start_time}");
//            self::log($this->log_path, "INFO - Consumer will be started after " . ($start_time - $current_time)  . " seconds");
        }
    }

    /**
     * 进程结束时的callback
     * @param SwServer $server
     */
    public function onShutdown(\Swoole\Server $server) {
        // 删除进程号文件
        if (file_exists($this->pid_file))
            unlink($this->pid_file);

        // 关闭进程 将关闭信息写入日志
        self::log($this->log_path,
            'INFO - ' . $this->type . 'Server is shutdown now. master_pid: ' . $this->server->master_pid);
    }

    /**
     * 接收到客户端请求的callback
     * @param SwServer $server
     * @param $fd
     * @param $from_id
     * @param $data
     */
    public function onReceive(\Swoole\Server $server, $fd, $from_id, $data ) {
        self::log($this->log_path, "INFO - Get Message From Client {$fd}:{$data}");
        $data_arr = json_decode($data, true);
        self::log($this->log_path, "INFO - Timeout {$fd}:{" . (isset($data_arr['timeout']) ? $data_arr['timeout'] : 'Nah') . "}");

        if (! isset($data_arr['command']))
            self::log($this->log_path, "ERROR - command not in data");

        // send a task to task worker.
        if ($data_arr['command'] == 'pop') {
            self::log($this->log_path, "INFO - Consumer will be executed in 60 seconds later");
            $server->tick(60000, function () use (&$server, &$data, &$fd) {
                $server->task($data);
            });
        } else
            $server->task($data);
    }

    /**
     * 接收到投递任务的callback
     * @param SwServer $server
     * @param $task_id
     * @param $from_id
     * @param $data
     * @return int
     * @throws Exception
     */
    public function onTask(\Swoole\Server $server, $task_id, $from_id, $data) {
        self::log($this->log_path, "INFO - taskId: $task_id from_id: $from_id");
        $data = json_decode($data, true);

        $func_name = $data['command'];
        if ($this->type == 'producer') {
            if (! method_exists($this->redis_handler, $func_name))
                self::log($this->log_path, "ERROR - $func_name not exists");

            if (isset($data['data'])) {
                $param = $data['data'];
                self::log($this->log_path, "INFO - CALL " . $func_name . ". PARAM: " . json_encode($param));
                $this->redis_handler->$func_name($param);
            } else {
                self::log($this->log_path, "INFO - CALL " . $func_name);
                $this->redis_handler->$func_name();
            }

            return true;
        } else {
            $param = $data['data'];
            self::log($this->log_path, "INFO - CALL " . $func_name . ". PARAM: " . json_encode($param));
            try {
                $callback = null;
                if ($data['platform'] == 'pdd' && $data['action'] == 'check_order_status') {
                    // 调用拼多多订单状态查询接口
                    list($request_res, $request) = $this->remote_handler->$func_name(
                        $param['url'], $param['data'], $param['method'], $param['headers'],
                        isset($param['options']) ? $param['options'] : null, 'pddOrderStatus');

                    self::log($this->log_path, "INFO - pdd check_order_status: {$request_res}, request: " . json_encode($request_res));

                    if (isset($data['callback']) && (isset($data['callback']['url']) && !empty($data['callback']['url'])) &&
                        isset($data['callback']['method']) && !empty($data['callback']['method'])) {
                        $callback_data = ['result' => $request_res];
                        if (isset($data['callback']['data'])) {
                            $callback_data = array_merge($callback_data, $data['callback']['data']);
                        }
                        self::log($this->log_path, "INFO - notify callback data: {" . json_encode($callback_data) . "}");

                        // 将拼多多订单状态查询接口的返回结果发回客户端指定的服务器
                        $request_res = $this->remote_handler->$func_name(
                            $data['callback']['url'], http_build_query($callback_data), $data['callback']['method'],
                            isset($data['callback']['header']) ? $data['callback']['header'] : null,
                            isset($data['callback']['option']) ? $data['callback']['option'] : null,
                            'notifyClient'
                        );

                        self::log($this->log_path, "INFO - notify default: {" . json_encode($request_res) . "}");
                    }

                    return $request_res;
                } else if ($data['platform'] == 'pdd' && $data['action'] == 'check_user_address') {
                    $res = $this->remote_handler->$func_name(
                        $param['url'], $param['data'], $param['method'], $param['headers'],
                        isset($param['options']) ? $param['options'] : null, 'parsePddAddress');

                    // 将拼多多收货地址接口的返回结果发回客户端指定的服务器
                    if (strpos($data['callback']['url'], Config::SECRET_DOMAIN) !== false && is_array($data)) {

                        self::log($this->log_path, "INFO - pdd check_user_address: {" . json_encode($res) . "}");

                        if ($data['callback']['data'] && is_array($data['callback']['data']))
                            $param = array_merge($data['callback']['data'], $res);
                        else
                            $param = $res;

                        self::log($this->log_path, "INFO - pdd check_user_address param: " . json_encode($param) . "");

                        $rsa = RsaOperation::getInstance(Config::PUBLIC_PEM, Config::PRIVATE_PEM);

                        $param = 'secret=' . urlencode($rsa->publicEncrypt($param));
                    } else {
                        self::log($this->log_path, "INFO - pdd check_user_address: {$res}");
                        $param = $data['callback']['data'] . '&' . $res;
                    }
                    $r = $this->remote_handler->$func_name(
                        $data['callback']['url'], $param, $data['callback']['method'],
                        isset($data['callback']['headers']) ? $data['callback']['headers'] : [],
                        isset($data['callback']['options']) ? $data['callback']['options'] : null,
                        'notifyClient');

                    return $r;
                } else if ($data['platform'] == 'pdd' && $data['action'] == 'check_goods_info') {
                    $res = $this->remote_handler->$func_name(
                        $param['url'], $param['data'], $param['method'], $param['headers'],
                        isset($param['options']) ? $param['options'] : null, 'parsePddGoods');

                    // 将拼多多收货地址接口的返回结果发回客户端指定的服务器
                    if (strpos($data['callback']['url'], Config::SECRET_DOMAIN) !== false && is_array($data)) {

                        self::log($this->log_path, "INFO - pdd check_goods_info: {" . json_encode($res) . "}");

                        if ($data['callback']['data'] && is_array($data['callback']['data']))
                            $param = array_merge($data['callback']['data'], $res);
                        else
                            $param = $res;

                        self::log($this->log_path, "INFO - pdd check_goods_info param: " . json_encode($param) . "");

                        $rsa = RsaOperation::getInstance(Config::PUBLIC_PEM, Config::PRIVATE_PEM);

                        $param = 'secret=' . urlencode($rsa->publicEncrypt($param));
                    } else {
                        self::log($this->log_path, "INFO - pdd check_user_address: {$res}");
                        $param = $data['callback']['data'] . '&' . $res;
                    }
                    $r = $this->remote_handler->$func_name(
                        $data['callback']['url'], $param, $data['callback']['method'],
                        isset($data['callback']['headers']) ? $data['callback']['headers'] : [],
                        isset($data['callback']['options']) ? $data['callback']['options'] : null,
                        'notifyClient');

                    return $r;
                } else {
                    $res = $this->remote_handler->$func_name(
                        $param['url'], $param['data'], $param['method'],
                        isset($param['headers']) ? $param['headers'] : [],
                        isset($param['options']) ? $param['options'] : null, 'notifyClient');

                    if (isset($data['callback'])) {
                        if ((isset($data['callback']['url']) && !empty($data['callback']['url'])) &&
                            isset($data['callback']['method']) && !empty($data['callback']['method'])) {

                            $param_data = $data['callback']['data'];
                            self::log($this->log_path, "INFO - notify callback data: {" . json_encode($param_data) . "}");
                            $param_data['result'] = $res[0];
                            self::log($this->log_path, "INFO - notify callback data: {" . json_encode($param_data) . "}");

                            $res1 = $this->remote_handler->$func_name(
                                $data['callback']['url'], http_build_query($param_data), $data['callback']['method'],
                                isset($data['callback']['headers']) ? $data['callback']['headers'] : [],
                                isset($data['callback']['options']) ? $data['callback']['options'] : null, 'notifyClient');
                            self::log($this->log_path, "INFO - notify callback default: {" . json_encode($res1) . "}");
                        }
                    }

                    self::log($this->log_path, "INFO - notify default: {" . json_encode($res) . "}");

                    return $res;
                }

            } catch (Exception $exception) {
                self::log($this->log_path, "ERROR - CALL REMOTE ERROR" . $exception->getMessage());
            }
        }
    }

    public function onFinish(\Swoole\Server $server, $task_id, $data) {
        self::log($this->log_path, "INFO - Task {$task_id} finish");
        self::log($this->log_path, "INFO - Result: {$data}");
    }

    static protected function log($log_path = null, $content = '') {
        if (!is_null($log_path)) {
            error_log('[' . date('Y-m-d H:i:s', time()) . '] - '.$content . "\r\n", 3, $log_path);
        }
    }

}
