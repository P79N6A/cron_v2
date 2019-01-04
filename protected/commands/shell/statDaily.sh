#!/bin/sh
curDate=`date  +"%Y%m%d"`
curPath=`dirname $0`
basePath=$(cd `dirname $0`; pwd)
yiiPath=${curPath}'/../../yiic'
logPath=${curPath}'/../../../data'
rlogPath=${curPath}'/../../../log'
logDir=`basename $0 .sh`
mkdir ${logPath}/${logDir}_${curDate}

${yiiPath} Stat StatBase  > ${logPath}/${logDir}_${curDate}/base_${curDate}.xls
${yiiPath} Stat StatSearchHot  > ${logPath}/${logDir}_${curDate}/searchhot_${curDate}.xls
${yiiPath} Stat StatPlannerViews --dt=today --inc=0 > ${logPath}/${logDir}_${curDate}/planner_${curDate}.xls
${yiiPath} Stat StatPlannerViews --dt=today --inc=1 > ${logPath}/${logDir}_${curDate}/planner_inc_${curDate}.xls
#${yiiPath} Stat UpdateViewNum >>  ${rlogPath}/$(basename $0 .sh)_${curDate}.log
${yiiPath} Stat StatPackageSub > ${logPath}/${logDir}_${curDate}/package_${curDate}.xls
${yiiPath} Stat StatPackageSubToday --dt=today > ${logPath}/${logDir}_${curDate}/package_today_${curDate}.xls
${yiiPath} Stat Staff --st=total > ${logPath}/${logDir}_${curDate}/lake_${curDate}.xls
${yiiPath} Stat Staff --st=month > ${logPath}/${logDir}_${curDate}/lake_month_${curDate}.xls
${yiiPath} Stat StatPlannerLost --d=5 > ${logPath}/${logDir}_${curDate}/lost_${curDate}.xls
${yiiPath} Stat StatIndData --dt=today > ${logPath}/${logDir}_${curDate}/industry_${curDate}.xls

cd $basePath
cd $logPath
filename=${logDir}_${curDate}".tar"
tar -cf $filename  ${logDir}_${curDate}
reciver=${logPath}/${filename}
cd $basePath
${yiiPath} Stat SendMail2 --at=${reciver} >>  ${rlogPath}/$(basename $0 .sh)_${curDate}.log

result=$?;
if [ $result -ne 0 ]
then
    cur_time=`date "+%Y-%m-%d %H:%M:%S"`;
    echo "==== ${cur_time} ====" >> ${rlogPath}/$(basename $0 .sh)_${curDate}.log
    ${yiiPath} common saveCronLog --cron_no=8001 --level='error' --msg="file:$(basename $0 .sh)_${curDate}.log time:${cur_time}" >> ${rlogPath}/$(basename $0 .sh)_${curDate}.log 2>&1 &
fi
