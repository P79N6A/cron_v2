<?php 
/**
 * 定时任务:
 * Date: 2015-11-03
 */

class UpdatePkgComment {


    const CRON_NO = 1201; //任务代码

    public function __construct(){

    }

    /**
     * 运营后台删除大家说的广告内容时引起lcs_package.comment_num误差
     * 定时修复数据  半小时执行一次
     * @author liyong3
     * @throws LcsException
     */
    public function update() {
        try {
            $db_w = Yii::app()->lcs_w;
            $db_r = Yii::app()->lcs_standby_r;
            $start_time = date('Y-m-d H:i:s', time()-60*35);
            $sql = "select distinct parent_relation_id from lcs_comment where cmn_type=2 and parent_relation_id>0 and u_time>='". $start_time ."'";
            $pkg_ids = $db_r->createCommand($sql)->queryColumn();
            $i = 0;
            foreach($pkg_ids as $pkg_id) {
                $num = $this->getPkgCommentNum($pkg_id);
                $rs = $db_w->createCommand()->update('lcs_package', array('comment_num'=> $num), 'id='.$pkg_id);
                if($rs > 0) {
                    $i++;
                }
            }

        	if($i > 0) {
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '更新数目：'.$i);
        	}
        }catch (Exception $e) {
        	throw LcsException::errorHandlerOfException($e);
        }
    }

    /**
     * 统计观点包的大家说数量
     */
    private function getPkgCommentNum($pkg_id) {
        $db_r = Yii::app()->lcs_standby_r;
        $sql_count = 'select count(1) as num from lcs_comment where status=0 and is_display!=0 and replay_id=0 and parent_relation_id='.$pkg_id;
        $num = $db_r->createCommand($sql_count)->queryScalar();
        return $num;
    }


}
