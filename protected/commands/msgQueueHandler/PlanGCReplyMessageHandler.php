<?php
/**
 * 理财师计划理财师回复用户的评价后通知评价用户
 * User: zwg
 * Date: 2016/5/4
 * Time: 17:30
 */

class PlanGCReplyMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 理财师计划理财师回复用户的评价后通知评价用户
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try {
            $this->commonHandler->checkRequireParam($msg, array('pln_id','cmn_id'));

            //TODO 未正式上线，屏蔽功能
            /*if(!(defined('ENV')&&ENV=='dev')&&!in_array($msg['pln_id'],array('33670','33789'))){
                throw new Exception('未正式上线，屏蔽功能');
            }*/

            // 1根据计划ID获取计划详情
            $pln_id = $msg['pln_id'];
            $plan_info = Plan::model()->getPlanInfoById($pln_id);
            if(empty($plan_info)){
                throw new Exception('plan_info 为空');
            }

            // 2计划结束后才能发送评价通知  4 5 6 7 (和用户是否可以评价的判断逻辑相同)
            if(!in_array($plan_info['status'], array(4,5,6,7))){
                throw new Exception('该计划尚未结束');
            }

            // 3理财师信息
            $planner_info = Planner::model()->getPlannerById(array($plan_info['p_uid']));
            $planner_info = isset($planner_info[$plan_info['p_uid']]) ? $planner_info[$plan_info['p_uid']] : array();
            if(empty($planner_info)){
                throw new Exception('planner_info 为空');
            }
            //获取评价的回复内容
            $cmn_info = GradeComment::model()->getGradeCmnById($msg['cmn_id']);
            if (empty($cmn_info)) {
                throw new Exception('cmn_info 为空');
            }
            if (empty($cmn_info['reply'])) {
                throw new Exception('回复内容为空');
            }
            if ($cmn_info['relation_id']!=$pln_id) {
                throw new Exception('不是此计划的评价');
            }
            if ($cmn_info['type']!=1) {
                throw new Exception('不是计划的评价');
            }

            $msg_data = array(
                'uid'=>$cmn_info['uid'],
                'u_type'=>1,
                'type'=>17,
                'relation_id'=>$pln_id,
                'content'=>json_encode(array(
                    array('value'=>CHtml::encode($planner_info['name']),'class'=>'','link'=>'/planner/'.$plan_info['p_uid'].'/1'),
                    array('value'=>'回复评价：','class'=>'', 'link'=>''),
                    array('value'=>CHtml::encode(CommonUtils::getSubStrNew($cmn_info['reply'],30,'...')),'class'=>'','link'=>''),
                    array('value'=>'~','class'=>'','link'=>'')
                ),JSON_UNESCAPED_UNICODE),
                'content_client'=>json_encode(array(
                    'cmn_id'=>$msg['cmn_id'],
                    'p_uid' => $plan_info['p_uid'],
                    'p_name' => CHtml::encode($planner_info['name']),
                    'content' => CHtml::encode(CommonUtils::getSubStrNew($cmn_info['content'],30,'...')),
                    'reply'=> CHtml::encode(CommonUtils::getSubStrNew($cmn_info['reply'],30,'...')),
                    'plan_name' => CHtml::encode($plan_info['name']),
                    'plan_id'=>$pln_id,
                    'type'=>2
                ),JSON_UNESCAPED_UNICODE),
                'link_url'=>'/plan/'.$pln_id.'?type=gradeCmn',
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );
            // 6保存通知消息
            Message::model()->saveMessage($msg_data);

            $log_data['ext_data'][]=array('uid'=>$msg_data['uid'],'relation_id'=>$msg_data['relation_id']);

            // 7加入提醒队列
            $this->commonHandler->addToPushQueue($msg_data,array($cmn_info['uid']),array(2,3));

        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            $log_data['ext_data'] = json_encode($log_data['ext_data']);
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }
}