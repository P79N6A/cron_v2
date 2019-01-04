#!/bin/sh
clear
lib=./lib
classpath=.
for file in ${lib}/*.jar; 
    do classpath=${classpath}:$file; 
done
# export SERVICE_PORT=21230
export SERVICE_IPv4dd=127.0.0.1
# export CHARSET=UTF-8
java -classpath $classpath lajpsocket.PhpJava
# nohup java -classpath $classpath lajpsocket.PhpJava &
