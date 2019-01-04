#!/bin/sh
#---------------------------
# 同步用户数据到CRM(上海)系统
# @description 每分钟运行一次，如果有正在运行的进程则退出不做操作
# @datetime 2017-08-18 10:10:10
# @author lixiaocheng<xiaocheng3@ggt.sian.com.cn>
#---------------------------
curDate=`date  +"%Y%m%d"`;
curPath=`dirname $0`;
yiiPath=${curPath}'/../../yiic';
logPath=${curPath}'/../../../log';
processName=SyncUser2CRMCommand;

#如果进程在运行则退出
isRunning=`ps aux |grep $processName |grep -v grep`
if [ -n "$isRunning" ]  #非空
then
    echo "is running ";
    exit 0;
else
    echo "is not running";
    #再次启动
    ${yiiPath} SyncUser2CRM SyncUser >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1 &
fi
