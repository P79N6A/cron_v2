#! /bin/sh

if [ $# -lt 1 ] ; then 
    echo "USAGE: $0 [send file or path]" 
    exit 1; 
fi

cur_time=`date "+%Y-%m-%d %H:%M:%S"`;
cur_date=`date "+%Y-%m-%d"`;

#设置shell脚本执行权限
basePath=$(cd `dirname $0`; pwd)"/cron_v2";
chmod +x $basePath/protected/commands/shell/*.sh

#record log
echo "${cur_time} rsync files finance@10.39.32.56::cron_lcs finance@10.55.30.35::cron_lcs" >> log/sendCronLog_${cur_date}.log

#rsync file
for file in "$@"   # "$@" is array   "$*" is string
do
    echo "==${file}"  >> log/sendCronLog_${cur_date}.log
    rsync -avzR --exclude=.svn ${file} finance@10.39.32.56::cron_lcs  #本地 
    rsync -avzR --exclude=.svn ${file} finance@10.55.30.35::cron_lcs  #备份 
done
