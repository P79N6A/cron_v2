<?php
/**
 * Desc  : 删除comment_master表中的超过三个月的记录，只保留最近三个月的数据
 * Author: lixiang
 * Date  : 2018-02-25 15:14:00
 */
class DeleteCommentMaster {
    const CRON_NO = 4002; //任务代码

    public function __construct() {
    }

    public function deleteMaster(){
        $end = time() + 60;
        while(time()<$end){
            NewComment::model()->deleteMasterRecord(); 
            sleep(2);
        }
    }
}
