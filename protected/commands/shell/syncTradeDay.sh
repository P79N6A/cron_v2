#!/bin/sh
curDate=`date  +"%Y%m%d"`
curPath=`dirname $0`
yiiPath=${curPath}'/../../yiic'
rlogPath=${curPath}'/../../../log'

${yiiPath} SyncCJData TradeDay  > ${rlogPath}/$(basename $0 .sh)_${curDate}.log

result=$?;
if [ $result -ne 0 ]
then
    cur_time=`date "+%Y-%m-%d %H:%M:%S"`;
    echo "==== ${cur_time} ====" >> ${rlogPath}/$(basename $0 .sh)_${curDate}.log
    ${yiiPath} common saveCronLog --cron_no=8101 --level='error' --msg="file:$(basename $0 .sh)_${curDate}.log time:${cur_time}" >> ${rlogPath}/$(basename $0 .sh)_${curDate}.log 2>&1 &
fi
