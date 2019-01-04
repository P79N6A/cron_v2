<?php
/**
 * 计划和观点包的新评价通知给理财师
 * 1. 每天下午16点给理财师发送新评价通知
 * User: zwg
 * Date: 2016/5/5
 * Time: 11:08
 */

class GradeCommentToPlanner {
    const CRON_NO=1014;

    private $s_time='';
    private $e_time='';

    public function __construct(){
        $this->s_time = date('Y-m-d 16:00:00',strtotime("-1 day"));
        $this->e_time = date('Y-m-d 16:00:00');
    }


    /**
     * 执行
     * @throws LcsException
     */
    public function process(){
        $result = array();
        $result['plan_num']=$this->statPlanData();
        $result['pkg_num']=$this->statPackageData();

        Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, json_encode($result));

    }
    
    /**
     * 统计计划评价数据通过给理财师
     */
    public function statPlanData(){
        $plan_num=0;
        try {
            //获取最新评价的数据
            $list = GradeComment::model()->getGradeCommentListByCdn(1,0,0,0,$this->s_time,$this->e_time,'relation_id,uid,is_anonymous');
            //计划ID进行统计uid
            if(!empty($list)){
                $sort_map=array();
                foreach($list as $item){
                    $uid = $item['uid'];
                    if($item['is_anonymous']=='1'){
                        $uid=0;
                    }

                    if(isset($sort_map[$item['relation_id']])){
                        $sort_map[$item['relation_id']][]=$uid;
                    }else{
                        $sort_map[$item['relation_id']]=array($uid);
                    }
                }
                $plan_num=count($sort_map);
                foreach($sort_map as $pln_id=>$uid_arr){
                    $uid_arr = array_values(array_unique($uid_arr));
                    if(empty($uid_arr)){
                        continue;
                    }
                    $push_data=array("type" => "planGCNew", "pln_id" =>$pln_id,'uids'=>$uid_arr);
                    Yii::app()->redis_w->rPush("lcs_common_message_queue",json_encode($push_data));
                }
            }

        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
        return $plan_num;
    }



    /**
     * 统计观点包评价数据通过给理财师
     */
    public function statPackageData(){
        $pkg_num=0;
        try {
            //获取最新评价的数据
            $list = GradeComment::model()->getGradeCommentListByCdn(2,0,0,0,$this->s_time,$this->e_time,'relation_id,uid,is_anonymous');
            //计划ID进行统计uid
            if(!empty($list)){
                $sort_map=array();
                foreach($list as $item){
                    $uid = $item['uid'];
                    if($item['is_anonymous']=='1'){
                        $uid=0;
                    }
                    if(isset($sort_map[$item['relation_id']])){
                        $sort_map[$item['relation_id']][]=$uid;
                    }else{
                        $sort_map[$item['relation_id']]=array($uid);
                    }
                }
                $pkg_num=count($sort_map);
                foreach($sort_map as $pkg_id=>$uid_arr){
                    $uid_arr = array_values(array_unique($uid_arr));
                    if(empty($uid_arr)){
                        continue;
                    }
                    $push_data=array("type" => "packageGCNew", "pkg_id" =>$pkg_id,'uids'=>$uid_arr);
                    Yii::app()->redis_w->rPush("lcs_common_message_queue",json_encode($push_data));
                }
            }

        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
        return $pkg_num;
    }
    

    
    
    
   
}