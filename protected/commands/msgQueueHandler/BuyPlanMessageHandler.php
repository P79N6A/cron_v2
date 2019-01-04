<?php
/**
 * Created by PhpStorm.
 * User: zwg
 * Date: 2016/3/21
 * Time: 20:32
 */

class BuyPlanMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 购买计划通知处理
     * 消息体：type,pln_id,uid
     *
     * @param $msg
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            //验证必填项目
            $this->commonHandler->checkRequireParam($msg, array('uid','pln_id'));
            //添加通知
            $pln_id = $msg['pln_id'];
            $plan_infos = Plan::model()->getPlanInfoByIds($pln_id, array('pln_id', 'p_uid', 'name', 'number'));
            $plan_info = !empty($plan_infos) && count($plan_infos) > 0 ? current($plan_infos) : array();
            $p_uid = '';
            $plan_name = '';
            if (!empty($plan_info)) {
                $plan_name = isset($plan_info['name']) ? $plan_info['name'] : '';
                $plan_name .= (isset($plan_info['number']) && $plan_info['number'] > 9 ? $plan_info['number'] : "0" . $plan_info['number']) . "期";
                $p_uid = isset($plan_info['p_uid']) ? $plan_info['p_uid'] : '';
            }

            $planner = array();
            if (!empty($p_uid)) {
                $planners = Planner::model()->getPlannerById($p_uid);
                $planner = !empty($planners) && isset($planners[$p_uid]) ? $planners[$p_uid] : null;
            }

            $msg_data = array(
                'uid' => $msg['uid'],
                'u_type' => 1,  //1普通用户   2理财师
                'type' => 3,
                'relation_id' => $pln_id,
                'child_relation_id' => 0,
                'content' => json_encode(array(
                    array('value' => "您已成功购买", 'class' => '', 'link' => ""),
                    array('value' => '《' . $plan_name . '》', 'class' => '', 'link' => "/plan/" . $pln_id),
                    array('value' => "计划，我们将实时提醒你理财师的操作动态，请及时查看新消息", 'class' => '', 'link' => ""),
                ), JSON_UNESCAPED_UNICODE),
                'content_client' => json_encode(array(
                    'type' => 8,
                    'pln_id' => $pln_id,
                    'pln_name' => $plan_name,
                    "p_uid" => $p_uid,
                    "planner_name" => !empty($planner) && isset($planner['name']) ? $planner['name'] : '',
                    "planner_image" => !empty($planner) && isset($planner['image']) ? $planner['image'] : '',
                    "company" => !empty($planner) && isset($planner['company']) ? $planner['company'] : '',
                ), JSON_UNESCAPED_UNICODE),
                'link_url' => "/plan/" . $pln_id,
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );

            //保存通知消息
            Message::model()->saveMessage($msg_data);
            //加入提醒队列
            $push_uid = array();
            $push_uid[] = $msg_data['uid'];
            $this->commonHandler->addToPushQueue($msg_data, $push_uid, array(2, 3));

            $log_data['uid'] = $msg_data['uid'];
            $log_data['relation_id'] = $msg_data['relation_id'];
        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }
}