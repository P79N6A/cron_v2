<?php
/**
 * 计划计划订阅到期
 * 判断逻辑  lcs_plan_subscription 中 expire_time>time()
 * User: zwg
 * Date: 2016/1/5
 * Time: 15:06
 */

class PlanSubscriptionExpire {
    const CRON_NO= 5015;
    public function planSubExpire(){
        try{
            //获取过期的订阅用户
            $list = Plan::model()->getPlanSubExpireList(date('Y-m-d H:i:s'),'id,uid,pln_id,expire_time');
            if(!empty($list)){
                foreach($list as & $item){
                    //更新订阅用户信息
                    $item['res'] = Plan::model()->updatePlanSubscription(array('status' => -2),"id=:id",array(":id"=>$item['id']));

                    //add by danxian 2016/12/28 增加订阅到期提醒
                    $this->addNoticePush($item['uid'], $item['pln_id'], $item['expire_time']);
                }
            }

            return $list;
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }

    /**
     * @param $uid
     * @param $pln_id
     * @param $expire_time
     */
    private function addNoticePush($uid, $pln_id, $expire_time) {
        $msg_data['type']        = 'planSubExpireNotice';
        $msg_data['uid']         = $uid;
        $msg_data['pln_id']      = $pln_id;
        $msg_data['expire_time'] = $expire_time;
        $redis_key = MEM_PRE_KEY . 'common_message_queue';
        Yii::app()->redis_w->rPush($redis_key, json_encode($msg_data, JSON_UNESCAPED_UNICODE));
    }

}