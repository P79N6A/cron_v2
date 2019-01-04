#!/bin/sh
curDate=`date  +"%Y%m%d"`;
curPath=`dirname $0`;

cur_time=`date +%H%M`;


#监控短信发送队列
sms_p_max_num=3
sms_p_num=`ps -ef |grep "ProcessSms 7701" |grep -v "grep" |wc -l`;
if [ $sms_p_num -lt $sms_p_max_num ]
then
    sms_add_p_num=`expr $sms_p_max_num - $sms_p_num`;
    for((i=0;i<$sms_add_p_num;i++))
    do
        ${curPath}/startCron.sh sms ProcessSms 7701 > /dev/null 2>&1 &
        sleep 2s;
    done
fi

#监控快速短信发送队列
sms_p_max_num=3
sms_p_num=`ps -ef |grep "ProcessFastSms 7701" |grep -v "grep" |wc -l`;
if [ $sms_p_num -lt $sms_p_max_num ]
then
    sms_add_p_num=`expr $sms_p_max_num - $sms_p_num`;
    for((i=0;i<$sms_add_p_num;i++))
    do
        ${curPath}/startCron.sh sms ProcessFastSms 7701 > /dev/null 2>&1 &
        sleep 2s;
    done
fi