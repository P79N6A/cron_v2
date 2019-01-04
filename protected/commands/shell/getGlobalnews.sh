#!/bin/sh
#---------------------------
# 获取 7*24小时全球实时财经新闻直播 数据
# @description 每分钟杀死进程然后再启动进程，防止进程意外僵死
# @datetime 2017-08-02 16:10:10
# @author lixiaocheng<xiaocheng3@ggt.sian.com.cn>
#---------------------------
curDate=`date  +"%Y%m%d"`;
curPath=`dirname $0`;
yiiPath=${curPath}'/../../yiic';
logPath=${curPath}'/../../../log';
processName=GetGlobalnews;

#如果进程在运行则杀掉
isRunning=`ps -aux |grep $processName |grep -v grep`
if [ -n "$isRunning" ]  #非空
then
    echo "is running && to kill";
    echo "$isRunning" | awk '{print $2}' | xargs kill
else
    echo "not running";
fi

#再次启动
${yiiPath} GetGlobalnews GetNews >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1 &

exit 0



