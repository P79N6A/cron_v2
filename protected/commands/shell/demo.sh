#!/bin/sh
curDate=`date  +"%Y%m%d"`;
curPath=`dirname $0`;
yiiPath=${curPath}'/../../yiic';
logPath=${curPath}'/../../../log';
${yiiPath} common test --flag=0 >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1
result=$?;
if [ $result -ne 0 ]
then
    cur_time=`date "+%Y-%m-%d %H:%M:%S"`;
    echo "==== ${cur_time} ====" >> ${logPath}/$(basename $0 .sh)_${curDate}.log
    ${yiiPath} common saveCronLog --cron_no=1000 --level='error' --msg="file:$(basename $0 .sh)_${curDate}.log time:${cur_time}" >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1 &
fi


#监控微信发送通知消息的进程状态
cur_time=`date +%H%M`;
if [ $cur_time -ge 930 ] && [ $cur_time -le 1500 ]
then
    p_num=`ps -ef |grep "MessageWeixin" |grep -v "grep" |wc -l`;
    if [ $p_num -lt 20 ]
    then
        add_p_num=`expr 20 - $p_num`;
        for((i=0;i<$add_p_num;i++))
        do
	        nohup php ${curPath}/../../yiic.php MessageWeixin send >> ${curPath}/../../../cron_log/messageWeixin.log 2>&1 &
        done

        echo `date "+%Y-%m-%d %H:%M:%S"`" start ${add_p_num} MessageWeixin Process." >> $logFile;
    fi
fi
