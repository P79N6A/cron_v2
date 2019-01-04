#!/bin/sh
curDate=`date  +"%Y%m%d"`;
curPath=`dirname $0`;
yiiPath=${curPath}'/../../yiic';
logPath=${curPath}'/../../../log';
#echo $curPath;
stat_date='2015-07-10';
for i in $(seq 46)
do
    echo `date "+%Y-%m-%d %H:%M:%S"`" planner stat stat_time:$stat_date";
#    ${yiiPath} planner statActivity --stat_date=$stat_date --is_stat_data=0 --is_stat_score=1
#
#    ${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=1 --is_stat_score=0
#    ${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=2 --is_stat_score=0
#    ${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=4 --is_stat_score=0
#    ${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=8 --is_stat_score=0
#    ${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=16 --is_stat_score=0
#     ${yiiPath} planner statInfluence --stat_date=$stat_date --stat_data=0 --is_stat_score=1
    stat_date=`date -d "$stat_date +1 day " +%Y-%m-%d`;
done

#${yiiPath} test SortUserByDay --limit=5 >> ${logPath}/SortUserByDay_${curDate}.log 2>&1&