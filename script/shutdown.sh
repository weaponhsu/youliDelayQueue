#!/usr/bin/env bash

filename=/Users/huangxu/PhpstormProjects/bin/producer.pid
for line in `cat $filename`
do
  kill -15 ${line}
done
