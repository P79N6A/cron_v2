依赖包：
MySQLdb模块：https://sourceforge.net/projects/mysql-python/ 下载源码安装
celery模块：    pip install -U Celery 
celery监控模块： pip install flower
mysql连接池模块：pip install dbutils
redis client： pip install redis 
进程监控supervisor: pip install superviosr


运行：
	启动worker：      celery -A lcs_task worker --loglevel=info
	启动beat(派任务)： celery -A lcs_task beat
	启动monitor：     celery -b redis://127.0.0.1:6379/1 flower
	启动某一个queue的worker：celery -A lcs_task worker -c 2 -Q celery_plan --loglevel=info

监控：
	http://ip:5555

supervisor 配置：
	[program:celery]
	directory = /root/lcs_task
	command = celery -A lcs_task worker --loglevel=info
	autostart = false
	autorestart = true
	stdout_logfile=/root/micro/bin/celery.log
	
	[program:celery_beat]
	directory = /root/lcs_task
	command = celery -A lcs_task beat
	autostart = false
	autorestart = true
	stdout_logfile=/root/micro/bin/celery_beat.log
	
	[program:celery_flowers]
	directory = /root/lcs_task
	command = celery -b redis://127.0.0.1:6379/1 flower
	autostart = false
	autorestart = true
	stdout_logfile=/root/micro/bin/celery_flower.log 
 
 未知高级应用：
 	分配多队列执行任务，配置各任务队列优先级
 	任务执行数据如何存储