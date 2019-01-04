<?php
/**
 * 计划和观点包可以评价的通知给用户
 * 1. 每天下午16点给具有权限的用户发送通知
 * 2. 每天结束的计划通知所有订阅用户
 * 3. 5日和20日通知观点包未评价的用户
 * User: zwg
 * Date: 2016/5/10
 * Time: 11:08
 */

class GradeCommentToUser {
    const CRON_NO=1015;

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
        $result['pkg_num']=0;
        $day = date('j');
        if($day==5 || $day==20){
            $result['pkg_num'] = $this->statPackageData();
        }

        Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, json_encode($result));

    }
    
    /**
     * 每天结束的计划通知所有订阅用户
     */
    public function statPlanData(){
        $plan_num=0;
        try {
            //获取当天结束的计划
            $end_time = time()-60 ;
            $etime = date("Y-m-d H:i:s", $end_time);
            $plan_list = Plan::model()->getPLanEndList($etime);
            if(!empty($plan_list)){
                $plan_num = count($plan_list);
                foreach($plan_list as $plan_info){
                    $push_data=array("type" => "planGCNotice", "pln_id" => $plan_info['pln_id']);
                    Yii::app()->redis_w->rPush("lcs_common_message_queue",json_encode($push_data));
                }
            }
        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
        return $plan_num;
    }



    /**
     * 5日和20日通知观点包未评价的用户
     */
    public function statPackageData(){
        $pkg_num=0;
        try {
            //获取6天前付费的观点包  每月1,16日重新统计评价权限  通知是5,20日发送，所有要5天前开启收费的观点包
            $package_list = Package::model()->getChargePkgIds(date("Y-m-d", strtotime('-5 day')));
            if(!empty($package_list)){
                $pkg_num = count($package_list);
                foreach($package_list as $pkg_id){
                    $push_data=array("type" => "packageGCNotice", "pkg_id" => $pkg_id);
                    Yii::app()->redis_w->rPush("lcs_common_message_queue",json_encode($push_data));
                }
            }

        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
        return $pkg_num;
    }
    

    
    
    
   
}