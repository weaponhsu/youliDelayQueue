#!/usr/bin/env bash

filename=./bin/remote.pid
for line in `cat $filename`
do
  kill -15 ${line}
done