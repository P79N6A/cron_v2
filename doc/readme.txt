1. 整理配置文件乱问题
 采用两个配置文件，main.php 线上的正式配置 dev.php 为差异配置， 在开发环境index.php 直接配置dev.php


2. 日志和数据文件的存放规则以及定时清理规则
  系统配置统一的日志和数据文件路径，任务负责创建和删除文件


3. 代码文件混乱，需要按模块分类问题
  由于定时任务执行入口文件只能在commands目录下，可以按模块建立文件，action方法调用具体的任务文件

4. 文件命名规则
   a.类文件的首字母大写
   b.command文件里面的action名称和对应的任务类类名称相同   比如： plannerCommand.php下的actionStatActivity方法是 调用  commands/planner/StatActivity.php
   b.启动shell的名称 为  commend + action   比如:  plannerStatActivity.sh   是启动  plannerCommand.php下的actionStatActivity方法。

5. shell规范
   参见 shell/demo.sh

6. cron配置规范
   参见 doc/cron.txt

==============================问题
V1. 怎么保证CRON_NO不重复？
   每个任务都要在  lcs_cron 表中注册登记，注册的时候要保证 CRON_NO唯一，
   CRON_NO规则如下:
       1. 四位数字
       2. 前两位为大类 比如: 理财师10  问答11 统计80  系统99
       3. 后两位为顺序码

2. 解决大家迁移过程中的测试问题
V3. rsync到cron运行目录的.sh脚本没有执行权限，很可能漏 chmod +x 就会导致问题，如何避免?
   同步的时候对 shell 目录的 *.sh 文件统一加 执行权限

4. 有需要时，shell中增加 basePath=$(cd `dirname $0`; pwd) 以便得到绝对路径

V5. 日志中应该记录一下运行脚本所在的服务器IP， 主机还是备机
    在CommonUtils::getServerIp()获取服务器IP地址的方法，在记录日志的时候会默认方法到message中如：[IP:10.38.32.56,10.39.32.56]清除数据库 lcs_cron_log2015-06-16 00:00:00日志,:0