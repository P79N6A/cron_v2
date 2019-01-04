<?php
/**
 * 理财师计划有新的评价通知给理财师
 * User: zwg
 * Date: 2016/5/4
 * Time: 17:30
 */

class PlanGCNewMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 理财师计划有新的评价通知给理财师
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try {
            // 评价用户uids
            $this->commonHandler->checkRequireParam($msg, array('pln_id','uids'));

            //TODO 未正式上线，屏蔽功能
            /*if(!(defined('ENV')&&ENV=='dev')&&!in_array($msg['pln_id'],array('33670','33789'))){
                throw new Exception('未正式上线，屏蔽功能');
            }*/

            // 根据计划ID获取计划详情
            $pln_id = $msg['pln_id'];
            $plan_info = Plan::model()->getPlanInfoById($pln_id);
            if(empty($plan_info)){
                throw new Exception('plan_info 为空');
            }

            // 计划结束后才能发送评价通知  4 5 6 7 (和用户是否可以评价的判断逻辑相同)
            if(!in_array($plan_info['status'], array(4,5,6,7))){
                throw new Exception('该计划尚未结束');
            }

            $user_name = array();
            $count=0;
            foreach ($msg['uids'] as $uid) {
                if(intval($uid)<=0){
                    continue;
                }
                $user_info  = User::model()->getUserInfoByUid($uid);
                $user_name[] = $user_info['name'];
                if(++$count==2){
                    break;
                }
            }
            if (empty($user_name)) {
                $user_name[]="匿名用户";
            }
            //content_client: type=3  pln_id pln_name  u_num u_names[股市小能手、财神]
            $msg_data = array(
                'uid' => $plan_info['p_uid'],
                'u_type' => 2,
                'type' => 17,
                'relation_id' => $pln_id,
                'content' => json_encode(array(
                    array('value' => implode('，',array_slice($user_name,0,2)), 'class' => '', 'link' => ''),
                    array('value' => (count($msg['uids'])>2?"等".count($msg['uids']).'人':'').'评价了您的计划', 'class' => '', 'link' => ''),
                    array('value'=>"《".CHtml::encode($plan_info['name'])."》",'class'=>'','link'=>'/plan/'.$pln_id)
                ), JSON_UNESCAPED_UNICODE),
                'content_client' => json_encode(array(
                    'type' => 3,
                    'plan_id' => $pln_id,
                    'plan_name' => CHtml::encode($plan_info['name']),
                    'u_num' => count($msg['uids']),
                    'u_names' => $user_name,
                ), JSON_UNESCAPED_UNICODE),
                'link_url'=>'/plan/'.$pln_id.'?type=gradeCmn',
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );
            // 保存通知消息
            Message::model()->saveMessage($msg_data);
            $log_data['ext_data'][]=array('uid'=>$msg_data['uid'],'relation_id'=>$msg_data['relation_id']);
            
            //加入提醒队列
            $push_uid = array();
            $push_uid[]=User::model()->getUidBySuid($msg_data['uid']);
            $this->commonHandler->addToPushQueue($msg_data,$push_uid,array(2,3));

        }catch(Exception $e){
            //echo $e->getMessage(),"\n";
            //echo $e->getTraceAsString();
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