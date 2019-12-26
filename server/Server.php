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

use Swoole\Server as SwServer;
use src\RedisDelayQueue;

class Server
{
    public $server;
    public $log_path;
    public $redis_delay_queue = null;

    public function __construct()
    {
        date_default_timezone_set("PRC");
        $this->log_path = realpath(__DIR__) . '/../log/swoole_server' . date('Y-m-d', time()) . '.log';

        $this->server = new SwServer(Config::SERVER_HOST, Config::PORT);
        $this->server->set([
            'worker_num' => 1,
            'daemonize' => 0,
            'max_request' => 10000,
            'task_worker_num' => 1,
            'task_ipc_mode' => 1,
            'log_file' => $this->log_path
        ]);

        $this->redis_delay_queue = RedisDelayQueue::getInstance();

        $this->server->on('Receive', [$this, 'onReceive']);
        // bind callback
        $this->server->on('Task', [$this, 'onTask']);
        $this->server->on('Finish', [$this, 'onFinish']);
        $this->server->start();

    }

    public function onReceive(\Swoole\Server $server, $fd, $from_id, $data ) {
        self::log($this->log_path, "INFO - Get Message From Client {$fd}:{$data}");
        $data_arr = json_decode($data, true);
        self::log($this->log_path, "INFO - Timeout {$fd}:{" . (isset($data_arr['timeout']) ? $data_arr['timeout'] : 'Nah') . "}");

        if (! isset($data_arr['command']))
            self::log($this->log_path, "ERROR - command not in data");

        // send a task to task worker.
        if ($data_arr['command'] == 'pop') {
            $server->tick(6000, function () use (&$server, &$data, &$fd) {
                $server->task($data);
            });
        } else
            $server->task($data);
        $server->task($data);
    }

    public function onTask(\Swoole\Server $server, $task_id, $from_id, $data) {
        self::log($this->log_path, "INFO - taskId: $task_id ");
        $data = json_decode($data, true);

        $func_name = $data['command'];
        if (! method_exists($this->redis_delay_queue, $func_name))
            self::log($this->log_path, "ERROR - $func_name not exists");

        if (isset($data['data'])) {
            $param = $data['data'];
            self::log($this->log_path, "INFO - CALL " . $func_name . ". PARAM: " . json_encode($param));
            $this->redis_delay_queue->$func_name($param);
        } else {
            self::log($this->log_path, "INFO - CALL " . $func_name);
            $this->redis_delay_queue->$func_name();
        }
        return true;
    }

    public function onFinish($server, $task_id, $data) {
        self::log($this->log_path, "INFO - Task {$task_id} finish");
        self::log($this->log_path, "INFO - Result: {$data}");
    }

    static protected function log($log_path = null, $content = '') {
        if (!is_null($log_path)) {
            error_log('[' . date('Y-m-d H:i:s', time()) . '] - '.$content . "\r\n", 3, $log_path);
        }
    }

}

//$server = new AsynServer();