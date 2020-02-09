#!/usr/bin/env bash

# 本地环境
exec_dir=/Users/huangxu/PhpstormProjects/youliDelayQueue
tool=php
# 线上环境
# exec_dir=/path/to/project
# tool=php

# 启动生产者进程
file_name=/Users/huangxu/PhpstormProjects/producer.php
$tool $file_name

# 启动消费者进程
consumer_name=/Users/huangxu/PhpstormProjects/consumer.php
$tool $consumer_name
