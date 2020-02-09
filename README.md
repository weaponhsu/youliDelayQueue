# youliDelayQueue
delay queue

---

# 开机启动项
centos下为remote创建开机启动项
>[Unit]
>Description=remote_server
>After=network.target
>
>[Service]
>Type=forking
>ExecStart=/path/to/projects/script/remoteStart.sh
>ExecStop=/path/to/projects/script/remoteShutdown.sh
PrivateTmp=true
>
>[Install]
>WantedBy=multi-user.target

centos下为producer创建开机启动项
>[Unit]
>Description=delay_queue_producer
>After=network.target
>
>[Service]
>Type=forking
>ExecStart=/path/to/projects/script/start.sh
>ExecStop=/path/to/projects/script/shutdown.sh
>PrivateTmp=true
>
>[Install]
>WantedBy=multi-user.target

chmod -R 0755 /path/to/xxx.service
systemctl xxx redis.service
systemctl daemon-reload

