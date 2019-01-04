#!/bin/sh
curDate=`date  +"%Y%m%d"`;
curPath=`dirname $0`;
logPath=${curPath}'/../../../log';


# 将每天的日志收集到当天的目录中
function collect_log()
{
    if [ "X$1" == "X" ]; then
        the_day=$curDate
    else
        the_day=$1
    fi

    old_path=$curPath
    collect_path=$logPath
    collect_dir=$(date -d "-1 day $the_day" +%d)
    collect_day=$(date -d "-1 day $the_day" +%Y%m%d)

    cd $collect_path

    if [ -d $collect_dir ]; then
        rm -rf $collect_dir
    fi

    ls *_${collect_day}.log &>/dev/null
    [ $? -eq 0 ] || {
        echo 'nothing need to do'
        return 0
    }

    mkdir $collect_dir

    for file in $(ls *_${collect_day}.log); do
        mv $file ${collect_dir}/${file}
    done

    cd $old_path
}

collect_log $1




