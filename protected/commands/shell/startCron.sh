#!/bin/sh
curDate=`date  +"%Y%m%d"`;
curPath=`dirname $0`;
yiiPath=${curPath}'/../../yiic';
logPath=${curPath}'/../../../log';
params='';

if [ $# -lt 3 ]
then
    echo '命令格式如下：startCron.sh :command :action :cron_no [:param1 :param2]';
    exit;
elif [ $# -gt 3 ]
then

    i=1;
    for arg in "$@"
    do
        if [ ${i} -ge 4 ]
        then
            params="${params} ${arg}";
        fi
        i=`expr ${i} + 1`;
    done
fi
#echo "${yiiPath} $1 $2 ${params}"
#exit;
${yiiPath} $1 $2 ${params} >> ${logPath}/$1_$2_${curDate}.log 2>&1
result=$?;
if [ ${result} -ne 0 ]
then
    cur_time=`date "+%Y-%m-%d %H:%M:%S"`;
    echo "${yiiPath} $1 $2 ${params}" >> ${logPath}/$1_$2_${curDate}.log
    echo "==== ${cur_time} ====" >> ${logPath}/$1_$2_${curDate}.log
    ${yiiPath} common saveCronLog --cron_no=$3 --level='error' --msg="file:$1_$2_${curDate}.log time:${cur_time}" >> ${logPath}/$1_$2_${curDate}.log 2>&1 &
fi
