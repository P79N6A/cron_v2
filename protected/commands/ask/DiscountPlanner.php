<?php
/**
 * 定时任务:计算折扣中的理财师，和即将开始折扣中的理财师

 * User: zwg
 * Date: 2015/5/18
 * Time: 17:33
 */

class DiscountPlanner {


    const CRON_NO = 1105; //任务代码


    public function __construct(){

    }


    /**
     * 计算折扣中的理财师，和即将开始折扣中的理财师
     * @throws LcsException
     */
    public function discounting(){
        try{
            $records = Ask::model()->getDiscountPlanner(array('s_uid','discounting_status','discount_s_time','discount_e_time'));
            $update_data=array();
            if(!empty($records)){
                foreach($records as $item){
                    $discounting_status=1440;
                    if($this->isCanDiscount($item['discount_s_time'],$item['discount_e_time'])){
                        $discounting_status=0;
                    }else if((strtotime(date('Y-m-d ').$item['discount_s_time'])-time())>0){ // && (strtotime(date('Y-m-d ').$item['discount_s_time'])-time())<=1800
                        $discounting_status=ceil((strtotime(date('Y-m-d ').$item['discount_s_time'])-time())/300);
                    }

                    if($item['discounting_status']!=$discounting_status){
                        $update_data[$item['s_uid']]=$discounting_status;
                    }
                }
            }

            //echo json_encode($discounting);
            $count=0;
            if(!empty($update_data)){
                $count = Ask::model()->updateAskPlannerDiscountInfo($update_data);
            }

            return $count;
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }


    /**
     * 判断当前是否可以折扣
     * @param string $s_time 折扣开始时间  'H:i'
     * @param string $e_time 折扣结束时间  'H:i'
     * @param string $compare_time 比较的时间
     */
    public static function isCanDiscount($s_time,$e_time, $compare_time=''){
        $cur_time = empty($compare_time)? date('Y-m-d H:i:s') : date('Y-m-d H:i:s',strtotime($compare_time));
        $cur_his =  date('H:i:s',strtotime($cur_time));
        if($s_time<$e_time){
            $start_time = date('Y-m-d ').$s_time;
            $end_time = date('Y-m-d ').$e_time;
        }else{
            if($cur_his<$e_time){
                $start_time = date('Y-m-d ',strtotime('-1 day')).$s_time;
                $end_time = date('Y-m-d ').$e_time;
            }else{
                $start_time = date('Y-m-d ').$s_time;
                $end_time = date('Y-m-d ',strtotime('+1 day')).$e_time;
            }
        }
        //echo $cur_time, ' ', $start_time, ' ', $end_time;
        if($start_time<$cur_time && $cur_time<$end_time){
            return true;
        }else{
            return false;
        }
    }





}