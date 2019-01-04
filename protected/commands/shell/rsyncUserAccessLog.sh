#!/bin/sh

curPath=`dirname $0`;
cd $curPath/../../../log/userAccess;

curtime=$(date -d "-1 day" +%Y%m%d)

if [ -f "access_${curtime}.txt" ]
then
    mv "access_${curtime}.txt" "/data0/CS/lcs_log/access_${curtime}.txt"
    threetime=$(date -d "-3 day" +%Y%m%d)

    rm "/data0/CS/lcs_log/access_${threetime}.txt"
    #rsync -avz "access_${curtime}.txt" finance@172.16.11.114::finance_log/licaishi/
fi

