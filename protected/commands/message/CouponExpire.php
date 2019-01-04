<?php
/**
 * 优惠劵即将过期提醒  优惠劵到期前一天的上午10点
 * User: zwg
 * Date: 2015/11/6
 * Time: 10:38
 */

class CouponExpire {

    const CRON_NO = 1303; //任务代码

    public function __construct(){

    }


    /**
     * 处理即将过期的优惠劵
     */
    public function Process(){
        //查找即将过期的优惠劵用户
        $s_time = date('Y-m-d 00:00:00',strtotime('+1day'));
        $e_time = date('Y-m-d 00:00:00',strtotime('+2day'));
        $couponUsers = Coupon::model()->getCouponUserOfExpire($s_time, $e_time);

        if(empty($couponUsers)){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '无过期的优惠劵通知');
            return;
        }
        $coupon_ids = array();
        foreach($couponUsers as $item){
            $coupon_ids[] = $item['coupon_id'];
        }

        //获取优惠劵信息
        $coupons = Coupon::model()->getCouponInfoById($coupon_ids);
        if(empty($coupons)){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '未找到优惠劵信息 '.json_encode($coupon_ids));
            return;
        }
        $source_ids = array();
        foreach($coupons as $source_id){
            if($source_id['source']==1 && $source_id['coupon_type']!=6){
                $source_ids[] = $source_id['source_id'];
            }
        }
        //获取理财师信息
        $planners = Planner::model()->getPlannerById($source_ids);

        $company_ids = array();
        $companys = array();
        if($planners){
            foreach($planners as $company){
                $company_ids[] = $company['company_id'];
            }
        }
        $companys = Planner::model()->getCompanyById($company_ids);
        $records = 0;
        //生成通知
        foreach($couponUsers as $couponUser){
            if(!isset($coupons[$couponUser['coupon_id']]) || empty($coupons[$couponUser['coupon_id']])){
                break;
            }
            $coupon = $coupons[$couponUser['coupon_id']];
            if($coupon['coupon_type']==6){
                continue;
            }

            //type 0通用10问答21观点包30计划
            $msg_data=null;

            $msg_data=$this->processMessage($couponUser,$coupon,$planners,$companys);

            //保存通知，并且放到推送队列
            if(!empty($msg_data) && Yii::app()->lcs_w->createCommand()->insert("lcs_message",$msg_data)){
                //加入推送队列
                $msg_data['content'] = json_decode($msg_data['content'],true);
                $redis_key = "lcs_push_message_queue";
                //Yii::app()->redis_w->rPush($redis_key,json_encode($msg_data,JSON_UNESCAPED_UNICODE));
                $records++;
            }
        }

        Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, 'total:'.count($couponUsers).'  push:'.$records);

    }


    /**
     * 处理问答类型的优惠劵通知内容
     * @param $couponUser
     * @param $coupon
     * @return array
     */
    private function processMessage($couponUser, $coupon,$planners=array(),$companys=array())
    { //您有一张价值200元问答优惠券10月19日将到期，快去用掉吧
        $p_uid='';
        $planner_image='';
        $planner_name='';
        $company_name='';
        if($coupon['source']==1){
            $p_uid = $coupon['source_id'];
            $planner_name = isset($planners[$coupon['source_id']]['name'])?$planners[$coupon['source_id']]['name']:'';
            $planner_image = isset($planners[$coupon['source_id']]['image'])?$planners[$coupon['source_id']]['image']:'';

            $company_name = !empty($companys[$planners[$coupon['source_id']]['company_id']])?$companys[$planners[$coupon['source_id']]['company_id']]['name']:'';
        }
        $msg_data = array(
            'uid'=>$couponUser['uid'],
            'u_type'=>1,  //1普通用户   2理财师
            'type'=>3,
            'relation_id'=>$couponUser['coupon_id'],
            'child_relation_id'=>0,
            'content'=>json_encode(array(
                array('value'=>"您有一张",'class'=>'','link'=>""),
                array('value'=>intval($coupon['price']).'元'.($coupon['source']==1?$planners[$coupon['source_id']]['name'].'的':'').$this->getTypeTitle($coupon['type'],$coupon['coupon_type']),'class'=>'','link'=>""),
                array('value'=>date('m月d日',strtotime($couponUser['validity_date']))."将到期，快去用掉吧!",'class'=>'','link'=>""),
            ),JSON_UNESCAPED_UNICODE),
            'content_client'=>json_encode(array(
                'type'=>9,
                'p_uid'=>$p_uid,//理财师id
                'planner_name'=>$planner_name,//理财师名称
                'planner_image'=>$planner_image,//理财师头像
                'company'=>$company_name,//理财师所在公司
                'coupon_type'=>$coupon['coupon_type'],//优惠券类型 1:服务券 2:现金券
                'type2'=>$coupon['type'],//优惠券类型 0:现金券 10:问答券 21:观点券 30:计划券
                'coupon_id' => $couponUser['coupon_id'],
                'coupon_price' => intval($coupon['price']),
                'e_time'=> $couponUser['validity_date'],
            ),JSON_UNESCAPED_UNICODE),
            'link_url'=>LCS_WEB_URL.'/web/myCoupon', //TODO
            'c_time' => date("Y-m-d H:i:s"),
            'u_time' => date("Y-m-d H:i:s")
        );

        return $msg_data;

    }
    /**
     * 获取优惠券类型名称
     * @params int type 类型 0：通用券 10：问答券 21：观点券 30：计划券
     */
    public function getTypeTitle($type='',$coupon_type=1){
        $types = array(
            '0'=>'通用券',
            '10'=>'问答券',
            '21'=>'观点券',
            '30'=>'计划券'
        );
        if($coupon_type==2){
            return '现金券';
        }else{
            if(isset($types[$type])){
                return $types[$type];
            }
        }
    }



}
