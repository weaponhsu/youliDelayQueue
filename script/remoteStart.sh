#!/usr/bin/env bash

# 本地环境
exec_dir=/Users/huangxu/PhpstormProjects/youliDelayQueue
tool=php
# 线上环境
# exec_dir=/path/to/project
# tool=php

cd $exec_dir
file_name=remote.php
$tool $file_name
