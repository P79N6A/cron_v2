#!/bin/sh
curDate=`date  +"%Y%m%d"`
curPath=`dirname $0`
basePath=$(cd `dirname $0`; pwd)
yiiPath=${curPath}'/../../yiic'
logPath=${curPath}'/../../../data'
rlogPath=${curPath}'/../../../log'
logDir=`basename $0 .sh`
mkdir ${logPath}/${logDir}_${curDate}
${yiiPath} Stat User >  ${logPath}/${logDir}_${curDate}/feeuser_${curDate}.xls
${yiiPath} Stat UserToday >  ${logPath}/${logDir}_${curDate}/feeusertoday_${curDate}.xls

cd $basePath
cd $logPath
filename=${logDir}_${curDate}".tar"
tar -cf $filename  ${logDir}_${curDate}
reciver=${logPath}/${filename}
cd $basePath
${yiiPath} Stat SendMail --at=${reciver} >>  ${rlogPath}/$(basename $0 .sh)_${curDate}.log

result=$?;
if [ $result -ne 0 ]
then
    cur_time=`date "+%Y-%m-%d %H:%M:%S"`;
    echo "==== ${cur_time} ====" >> ${rlogPath}/$(basename $0 .sh)_${curDate}.log
    ${yiiPath} common saveCronLog --cron_no=8002 --level='error' --msg="file:$(basename $0 .sh)_${curDate}.log time:${cur_time}" >>
${rlogPath}/$(basename $0 .sh)_${curDate}.log 2>&1 &
fi
