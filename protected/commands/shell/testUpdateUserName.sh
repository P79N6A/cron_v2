#!/bin/sh
curDate=`date  +"%Y%m%d"`;
curPath=`dirname $0`;
yiiPath=${curPath}'/../../yiic';
logPath=${curPath}'/../../../log';


if [ $# -lt 1 ]; then
    echo -ne "Usage：testUpdateUserName.sh :tableIndex\n\n"
    exit
fi

tableIndex=$1
userNum=200
repeatTimes=25000
# userNum=2
# repeatTimes=1

## 每种性别进程最大数量
currProcNumMax=1



## 生成昵称库测试效率
# currProcNum=$(ps aux | fgrep 'yiic test batchCreateUserNameDB --gender=m' | fgrep -v 'grep' | wc -l)
# if [ $currProcNum -lt $currProcNumMax ]; then
#     ${yiiPath} test batchCreateUserNameDB --gender=m --num=${userNum} --times=${repeatTimes} | tee -a ${logPath}/test_batchCreateUserNameDB_${curDate}.log &
# fi

# currProcNum=$(ps aux | fgrep 'yiic test batchCreateUserNameDB --gender=f' | grep -v 'grep' | wc -l)
# if [ $currProcNum -lt $currProcNumMax ]; then
#     ${yiiPath} test batchCreateUserNameDB --gender=f --num=${userNum} --times=${repeatTimes} | tee -a ${logPath}/test_batchCreateUserNameDB_${curDate}.log &
# fi



## 修复重复昵称
currProcNum=$(ps aux | fgrep 'yiic test bachUpdateRepeatUsername' | grep -v 'grep' | wc -l)
if [ $currProcNum -lt $currProcNumMax ]; then
    ${yiiPath} test bachUpdateRepeatUsername --table_index=${tableIndex} | tee -a ${logPath}/test_bachUpdateRepeatUsername_${tableIndex}_${curDate}.log &
fi


## 清除用户昵称词典索引
# ${yiiPath} test delUsernameDicIndex


## 更新16年前的用户昵称
# currProcNum=$(ps aux | fgrep 'yiic test batchUpdateOldUsername --gender=m' | grep -v 'grep' | wc -l)
# if [ $currProcNum -lt $currProcNumMax ]; then
#     ${yiiPath} test batchUpdateOldUsername --table_index=${tableIndex} --gender=m --num=200 --times=25000 | tee -a ${logPath}/test_batchUpdateOldUsername_${tableIndex}_${curDate}.log &
# fi

# currProcNum=$(ps aux | fgrep 'yiic test batchUpdateOldUsername --gender=f' | grep -v 'grep' | wc -l)
# if [ $currProcNum -lt $currProcNumMax ]; then
#     ${yiiPath} test batchUpdateOldUsername --table_index=${tableIndex} --gender=f --num=200 --times=25000 | tee -a ${logPath}/test_batchUpdateOldUsername_${tableIndex}_${curDate}.log &
# fi


## 更新用户昵称
# currProcNum=$(ps aux | fgrep 'yiic test batchUpdateUserName --gender=m' | grep -v 'grep' | wc -l)
# if [ $currProcNum -lt $currProcNumMax ]; then
#     ${yiiPath} test batchUpdateUserName --table_index=${tableIndex} --gender=m --num=200 --times=25000 | tee -a ${logPath}/test_batchUpdateUserName_${tableIndex}_${curDate}.log &
# fi

# currProcNum=$(ps aux | fgrep 'yiic test batchUpdateUserName --gender=f' | grep -v 'grep' | wc -l)
# if [ $currProcNum -lt $currProcNumMax ]; then
#     ${yiiPath} test batchUpdateUserName --table_index=${tableIndex} --gender=f --num=200 --times=25000 | tee -a ${logPath}/test_batchUpdateUserName_${tableIndex}_${curDate}.log &
# fi
