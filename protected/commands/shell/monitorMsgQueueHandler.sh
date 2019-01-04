#!/bin/sh
curDate=`date  +"%Y%m%d"`;
curPath=`dirname $0`;

cur_time=`date +%H%M`;

#监控快速消息队列处理进程状态
f_p_max_num=3
if [ $cur_time -ge 925 ] && [ $cur_time -le 1500 ]
then
    f_p_max_num=5
fi
f_p_num=`ps -ef |grep "fastMsgQueueHandler 1308" |grep -v "grep" |wc -l`;
if [ $f_p_num -lt $f_p_max_num ]
then
    f_add_p_num=`expr $f_p_max_num - $f_p_num`;
    for((i=0;i<$f_add_p_num;i++))
    do
        ${curPath}/startCron.sh message fastMsgQueueHandler 1308 > /dev/null 2>&1 &
        sleep 1s;
    done
fi


#监控普通消息队列处理进程状态
c_p_max_num=3
c_p_num=`ps -ef |grep "commonMsgQueueHandler 1308" |grep -v "grep" |wc -l`;
if [ $c_p_num -lt $c_p_max_num ]
then
    c_add_p_num=`expr $c_p_max_num - $c_p_num`;
    for((i=0;i<$c_add_p_num;i++))
    do
        ${curPath}/startCron.sh message commonMsgQueueHandler 1308 > /dev/null 2>&1 &
        sleep 1s;
    done
fi


#新浪SPNS消息队列处理进程状态
spns_p_max_num=3
if [ $cur_time -ge 925 ] && [ $cur_time -le 1500 ]
then
    spns_p_max_num=5
fi
spns_p_num=`ps -ef |grep "sinaSpnsMessagePushQueueHandler 1307" |grep -v "grep" |wc -l`;
if [ $spns_p_num -lt $spns_p_max_num ]
then
    spns_add_p_num=`expr $spns_p_max_num - $spns_p_num`;
    for((i=0;i<$spns_add_p_num;i++))
    do
        ${curPath}/startCron.sh message sinaSpnsMessagePushQueueHandler 1307 > /dev/null 2>&1 &
        sleep 1s;
    done
fi

#微信消息队列处理进程状态
wx_p_max_num=3
if [ $cur_time -ge 925 ] && [ $cur_time -le 1500 ]
then
    wx_p_max_num=5
fi
wx_p_num=`ps -ef |grep "weiXinMessagePushQueueHandler 1305" |grep -v "grep" |wc -l`;
if [ $wx_p_num -lt $wx_p_max_num ]
then
    wx_add_p_num=`expr $wx_p_max_num - $wx_p_num`;
    for((i=0;i<$wx_add_p_num;i++))
    do
        ${curPath}/startCron.sh message weiXinMessagePushQueueHandler 1305 > /dev/null 2>&1 &
        sleep 1s;
    done
fi

#第三方微信消息队列处理进程状态
wxts_p_max_num=3
if [ $cur_time -ge 925 ] && [ $cur_time -le 1500 ]
then
    wxts_p_max_num=5
fi
wxts_p_num=`ps -ef |grep "weiXinThirdMessagePushQueueHandler 1313" |grep -v "grep" |wc -l`;
if [ $wxts_p_num -lt $wxts_p_max_num ]
then
    wxts_add_p_num=`expr $wxts_p_max_num - $wxts_p_num`;
    for((i=0;i<$wxts_add_p_num;i++))
    do
        ${curPath}/startCron.sh message weiXinThirdMessagePushQueueHandler 1313 > /dev/null 2>&1 &
        sleep 1s;
    done
fi

#个推消息队列处理进程状态
getui_p_max_num=5
if [ $cur_time -ge 925 ] && [ $cur_time -le 1500 ]
then
    getui_p_max_num=10
fi
getui_p_num=`ps -ef |grep "getuiMessagePushQueueHandler 1312" |grep -v "grep" |wc -l`;
if [ $getui_p_num -lt $getui_p_max_num ]
then
    getui_add_p_num=`expr $getui_p_max_num - $getui_p_num`;
    for((i=0;i<$getui_add_p_num;i++))
    do
        ${curPath}/startCron.sh message getuiMessagePushQueueHandler 1312 > /dev/null 2>&1 &
        sleep 1s;
    done
fi
