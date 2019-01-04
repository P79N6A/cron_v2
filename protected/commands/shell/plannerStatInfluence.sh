#!/bin/sh
curDate=`date  +"%Y%m%d"`;
curPath=`dirname $0`;
yiiPath=${curPath}'/../../yiic';
logPath=${curPath}'/../../../log';

stat_date=`date -d "-1 day" +%Y-%m-%d`;
#计划
${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=1 --is_stat_score=0 >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1
result=$?;
if [ $result -ne 0 ]
then
    cur_time=`date "+%Y-%m-%d %H:%M:%S"`;
    echo "${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=1 --is_stat_score=0" >> ${logPath}/$(basename $0 .sh)_${curDate}.log
    echo "==== ${cur_time} ====" >> ${logPath}/$(basename $0 .sh)_${curDate}.log
    ${yiiPath} common saveCronLog --cron_no=1001 --level='error' --msg="file:$(basename $0 .sh)_${curDate}.log time:${cur_time}" >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1 &
fi

#问答
${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=2 --is_stat_score=0 >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1
result=$?;
if [ $result -ne 0 ]
then
    cur_time=`date "+%Y-%m-%d %H:%M:%S"`;
    echo "${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=2 --is_stat_score=0" >> ${logPath}/$(basename $0 .sh)_${curDate}.log
    echo "==== ${cur_time} ====" >> ${logPath}/$(basename $0 .sh)_${curDate}.log
    ${yiiPath} common saveCronLog --cron_no=1001 --level='error' --msg="file:$(basename $0 .sh)_${curDate}.log time:${cur_time}" >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1 &
fi

#观点
${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=4 --is_stat_score=0 >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1
result=$?;
if [ $result -ne 0 ]
then
    cur_time=`date "+%Y-%m-%d %H:%M:%S"`;
    echo "${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=4 --is_stat_score=0" >> ${logPath}/$(basename $0 .sh)_${curDate}.log
    echo "==== ${cur_time} ====" >> ${logPath}/$(basename $0 .sh)_${curDate}.log
    ${yiiPath} common saveCronLog --cron_no=1001 --level='error' --msg="file:$(basename $0 .sh)_${curDate}.log time:${cur_time}" >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1 &
fi


#观点包
${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=8 --is_stat_score=0 >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1
result=$?;
if [ $result -ne 0 ]
then
    cur_time=`date "+%Y-%m-%d %H:%M:%S"`;
    echo "${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=8 --is_stat_score=0" >> ${logPath}/$(basename $0 .sh)_${curDate}.log
    echo "==== ${cur_time} ====" >> ${logPath}/$(basename $0 .sh)_${curDate}.log
    ${yiiPath} common saveCronLog --cron_no=1001 --level='error' --msg="file:$(basename $0 .sh)_${curDate}.log time:${cur_time}" >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1 &
fi


#理财师 以及得分
${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=16 --is_stat_score=1 >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1
result=$?;
if [ $result -ne 0 ]
then
    cur_time=`date "+%Y-%m-%d %H:%M:%S"`;
    echo "${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=16 --is_stat_score=1" >> ${logPath}/$(basename $0 .sh)_${curDate}.log
    echo "==== ${cur_time} ====" >> ${logPath}/$(basename $0 .sh)_${curDate}.log
    ${yiiPath} common saveCronLog --cron_no=1001 --level='error' --msg="file:$(basename $0 .sh)_${curDate}.log time:${cur_time}" >> ${logPath}/$(basename $0 .sh)_${curDate}.log 2>&1 &
fi
