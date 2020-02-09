#!/usr/bin/env bash

filename=/Users/huangxu/PhpstormProjects/bin/remote.pid
for line in `cat $filename`
do
  kill -15 ${line}
done
