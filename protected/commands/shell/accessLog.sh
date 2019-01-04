#!/bin/sh

curDate=`date  +"%Y%m%d"`;
curPath=`dirname $0`;
cd $curPath/../../;
log_path='access_'$curDate'.txt';
org_log_path='org_access_'$curDate'.txt';

#监控访问日志处理进程
log_p_max_num=6
log_p_num=`ps -ef |grep "stat SaveAccessLog 7801" |grep -v "grep" |wc -l`;
if [ $log_p_num -lt $log_p_max_num ]
then
    log_add_p_num=`expr $log_p_max_num - $log_p_num`;
    for((i=0;i<$log_add_p_num;i++))
    do
        ./yiic stat SaveAccessLog 7801 >> ../log/userAccess/$log_path 2>/dev/null &
        sleep 2s;
    done
fi
