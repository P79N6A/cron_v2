#!/bin/sh
curDate=`date  +"%Y%m%d"`;
curPath=`dirname $0`;

cur_time=`date +%H%M`;

third_p_max_num=3
if [ $cur_time -ge 925 ] && [ $cur_time -le 1500 ]
then
    third_p_max_num=5
fi
third_p_num=`ps -ef |grep "thirdPartyTemplateMsg 1319" |grep -v "grep" |wc -l`;
if [ $third_p_num -lt $third_p_max_num ]
then
    third_add_p_num=`expr $third_p_max_num - $third_p_num`;
    for((i=0;i<$third_add_p_num;i++))
    do
        ${curPath}/startCron.sh message thirdPartyTemplateMsg 1319 > /dev/null 2>&1 &
        sleep 1s;
    done
fi