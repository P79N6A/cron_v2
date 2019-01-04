#!/bin/sh
#---------------------------
# 将IM聊天投顾信息同步到CRM系统
# @description 每分钟运行一次，如果有正在运行的进程则退出不做操作
# @datetime 2017-12-23 22:08:12
# @author lining<alexphpengineer@163.com>
#---------------------------
curDate=`date  +"%Y%m%d"`;
curPath=`dirname $0`;
yiiPath=${curPath}'/../../yiic';
logPath=${curPath}'/../../../log';
processName=SyncImCrm;

#如果进程在运行则退出
isRunning=`ps aux |grep $processName |grep -v grep`
if [ -n "$isRunning" ]  #非空
then
    echo "is running ";
    exit 0;
else
    echo "is not running";
    #再次启动
    ${yiiPath} SyncImCrm SyncIm
    #>> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1 &
fi