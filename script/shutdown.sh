#!/usr/bin/env bash

filename=./bin/producer.pid
for line in `cat $filename`
do
  kill -15 ${line}
done