<?php
/**
 * 定时任务:按理财师观点包的说说数量排序，取前300位理财师
 * User: liyong3
 * Date: 2015-09-09
 */

class CommentNum {


    const CRON_NO = 1004; //任务代码

    public function __construct(){
        
    }


    /**
     * 计算理财师观点包的说说数量
     * @throws LcsException
     */
    public function Process(){
        try{
            
        	$comment_nums = array();
        	$p_uids = View::model()->getAllPlannerByView();
        	foreach($p_uids as $p_uid) {
        		$comment_num = Package::model()->getAllCommentNumByPuid($p_uid);
        		if($comment_num > 0) {
        			Planner::model()->updateCommentNum($p_uid, array('pkg_comment_num'=>$comment_num));
        		}
        	}

            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "统计日期:".date('Y-m-d H:i:s'));
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
        return ;
    }

}
