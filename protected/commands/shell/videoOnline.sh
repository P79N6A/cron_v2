#!/bin/sh
curDate=`date  +"%Y%m%d"`;
curPath=`dirname $0`;
yiiPath=${curPath}'/../../yiic';
logPath=${curPath}'/../../../log';

step=5;
for((i=0;i<12;i++))
do
${yiiPath} Video online 2001 >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1
result=$?;
if [ $result -ne 0 ]
then
    cur_time=`date "+%Y-%m-%d %H:%M:%S"`;
    echo "==== ${cur_time} ====" >> ${logPath}/$(basename $0 .sh)_${curDate}.log
    ${yiiPath} common saveCronLog --cron_no=9001 --level='error' --msg="file:$(basename $0 .sh)_${curDate}.log time:${cur_time}" >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1 &
fi
sleep $step
done

