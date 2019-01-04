#!/bin/sh
while true
do 
	`bash ~/cron_v2/protected/commands/shell/startCron.sh NewComment  DeleteComment 4001 > /dev/null 2>&1`
	curSecond=`date  +"%S"`
	if [ $curSecond -eq 00 ] ; then
		exit
	fi
done
