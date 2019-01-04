<?php
/**
 * 理财师计划通知用户评价 计划结束后通知所有订阅用户
 * User: zwg
 * Date: 2016/5/4
 * Time: 17:30
 */

class PlanGCNoticeMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 理财师计划通知用户评价
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try {
            $this->commonHandler->checkRequireParam($msg, array('pln_id'));

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

            // 4获取计划的订阅用户 并且有评价权限的用户
            //$uids = Plan::model()->getSubPlanUids($pln_id);
            $uids = $this->getCanGradeUser($pln_id);
            if(empty($uids)){
                throw new Exception('uids 为空');
            }


            // 5组装消息结构 content_client: type=1  pln_id pln_name  p_uid p_name
            foreach($uids as $uid){
                $msg_data = array(
                    'uid'=>$uid,
                    'u_type'=>1,
                    'type'=>17,
                    'relation_id'=>$pln_id,
                    'content'=>json_encode(array(
                        array('value'=>'您觉得理财师','class'=>'','link'=>''),
                        array('value'=>CHtml::encode($planner_info['name']),'class'=>'','link'=>'/planner/'.$plan_info['p_uid'].'/1'),
                        array('value'=>'的计划','class'=>'', 'link'=>''),
                        array('value'=>"《".CHtml::encode($plan_info['name'])."》",'class'=>'','link'=>'/plan/'.$pln_id),
                        array('value'=>"如何，快来评价一下吧~",'class'=>'','link'=>'')
                    ),JSON_UNESCAPED_UNICODE),
                    'content_client'=>json_encode(array(
                        'p_uid' => $plan_info['p_uid'],
                        'p_name' => CHtml::encode($planner_info['name']),
                        'plan_name' => CHtml::encode($plan_info['name']),
                        'plan_id'=>$pln_id,
                        'type'=>1
                    ),JSON_UNESCAPED_UNICODE),
                    'link_url'=>'/plan/'.$pln_id.'?type=gradeCmn',
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
                // 6保存通知消息
                Message::model()->saveMessage($msg_data);

                $log_data['ext_data'][]=array('uid'=>$msg_data['uid'],'relation_id'=>$msg_data['relation_id']);
            }
            

            // 7加入提醒队列
            if(!empty($uids)){
                //将用户500个一组，分批放入队列。否则会导致数据过大。
                $uids_arr = array_chunk($uids,500);
                foreach($uids_arr as $_uids){
                    $this->commonHandler->addToPushQueue($msg_data,(array)$_uids,array(2,3));
                }
            }

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


    private function getCanGradeUser($pln_id){
        //获取订阅用户
        $sub_uids = Plan::model()->getSubPlanUids($pln_id);
        if(empty($sub_uids)){
            return array();
        }

        //获取已经评价的用户
        $grade_users = GradeComment::model()->getGradeCommentListByCdn(1,$pln_id,'','','','','uid');
        if(empty($grade_users)){
            return $sub_uids;
        }else{
            $grade_uids = array();
            foreach($grade_users as $user){
                $grade_uids[]=$user['uid'];
            }

            return array_diff($sub_uids,$grade_uids);
        }

    }
}