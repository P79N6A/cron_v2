<?php
/**
 * Created by PhpStorm.
 * User: zwg
 * Date: 2016/2/29
 * Time: 17:30
 */

class PlanPlannerCommentMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 计划-理财师说说提醒
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try {
            $this->commonHandler->checkRequireParam($msg, array('cmn_id','relation_id','content'));
            $cmn_id = $msg['cmn_id'];
            //说说信息
            //$cmn_info = Comment::model()->getCommentInfoByID($cmn_id);
            //$cmn_info = isset($cmn_info[$cmn_id]) ? $cmn_info[$cmn_id] : array();

            $cmn_info['relation_id']=$msg['relation_id'];
            $cmn_info['content']=CommonUtils::removeEmoji($msg['content']);

            if(empty($cmn_info)){
                throw new Exception('cmn_info 为空');
            }

            //计划信息
            $pln_id = $cmn_info['relation_id'];
            $plan_info = Plan::model()->getPlanInfoById($pln_id);
            if(empty($plan_info)){
                throw new Exception('plan_info 为空');
            }
            //理财师信息
            $planner_info = Planner::model()->getPlannerById(array($plan_info['p_uid']));
            $planner_info = isset($planner_info[$plan_info['p_uid']]) ? $planner_info[$plan_info['p_uid']] : array();
            if(!empty($planner_info)&&isset($planner_info['company_id'])){
                $companys = Common::model()->getCompany($planner_info['company_id']);
                if(!empty($companys)&&isset($companys[$planner_info['company_id']])){
                    $planner_info['company']=$companys[$planner_info['company_id']]['name'];
                }
            }
            //获取订阅计划的用户id
            $uids = Plan::model()->getSubPlanUids($pln_id);
            if(empty($uids)){
                throw new Exception('uids 为空');
            }

            foreach($uids as $uid){
                $msg_data = array(
                    'uid'=>$uid,
                    'u_type'=>1,
                    'type'=>7,
                    'relation_id'=>$pln_id,
                    'child_relation_id'=>$cmn_id,
                    'content'=>json_encode(array(
                        array('value'=>$planner_info['name'],'class'=>'','link'=>'/planner/'.$plan_info['p_uid'].'/1'),
                        array('value'=>"在",'class'=>'','link'=>''),
                        array('value'=>"《".CHtml::encode($plan_info['name'])."》",'class'=>'','link'=>'/plan/'.$pln_id),
                        array('value'=>"中说",'class'=>'','link'=>''),
                        array('value'=>"：".CHtml::encode($cmn_info['content']),'class'=>'','link'=>'/plan/'.$pln_id.'?type=comment#plannerComment')
                    ),JSON_UNESCAPED_UNICODE),
                    'content_client'=>json_encode(array(
                        'p_uid' => $plan_info['p_uid'],
                        'planner_name' => CHtml::encode($planner_info['name']),
                        'plan_name' => $plan_info['name'],
                        'content' => CHtml::encode($cmn_info['content']),
                        'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                        'company' => isset($planner_info['company']) ? $planner_info['company'] : ''
                    ),JSON_UNESCAPED_UNICODE),
                    'link_url'=>'/plan/'.$pln_id.'?type=comment#plannerComment',
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );

                //保存通知消息
                Message::model()->saveMessage($msg_data);

                $log_data['ext_data'][]=array('uid'=>$msg_data['uid'],'relation_id'=>$msg_data['relation_id']);

                $msg_data['title'] = $plan_info['name'];
            }//foreach

            //加入提醒队列
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
}