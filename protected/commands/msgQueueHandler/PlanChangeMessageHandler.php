<?php
/**
 * Created by PhpStorm.
 * User: zwg
 * Date: 2016/3/23
 * Time: 16:56
 */

class PlanChangeMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     *  计划提醒
     * @param #msg type=planChange pln_id
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok','uid'=>'','relation_id'=>'','ext_data'=>'');
        try {
            $this->commonHandler->checkRequireParam($msg, array('pln_id'));
            $pln_id = $msg['pln_id'];
            $plan_infos = Plan::model()->getPlanInfoByIds($pln_id);
            $plan_info = !empty($plan_infos) && count($plan_infos) > 0 ? current($plan_infos) : array();
            if (empty($plan_info)) {
                throw new Exception('plan_info 为空');
            }
            $plan_name = isset($plan_info['name']) ? $plan_info['name'] : '';
            $plan_name .= (isset($plan_info['number']) && $plan_info['number'] > 9 ? $plan_info['number'] : "0" . $plan_info['number']) . "期";


            $msg_data = array();
            if($plan_info['status'] == -4) {//审核不通过
                $msg_data = array(
                    'uid' => $plan_info['p_uid'],
                    'u_type' => 2,
                    'type' => 15,
                    'relation_id' => $plan_info['pln_id'],
                    'content' => json_encode(array(
                        array('value' => "您的计划《" . $plan_name . '》由于“'.(isset($plan_info['audit_reason'])?$plan_info['audit_reason']:'').'”的原因没有通过审核，请到电脑网页端修改重新提交审核。', 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'status' => -4,//todo
                        'pln_id' => $plan_info['pln_id'],
                        'pln_name' => CHtml::encode($plan_name),
                        'audit_reason' =>isset($plan_info['audit_reason'])?$plan_info['audit_reason']:''
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "/plan/" . $plan_info['pln_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            } else if($plan_info['status'] == 2) {//审核通过，待运行中
                $start_date = $plan_info['start_date'];
                $days = ceil((strtotime($plan_info['start_date'])-time())/86400);
                $msg_data = array(
                    'uid' => $plan_info['p_uid'],
                    'u_type' => 2,
                    'type' => 15,
                    'relation_id' => $plan_info['pln_id'],
                    'content' => json_encode(array(
                        array('value' => "您的计划《" . $plan_name . "》审核通过，将于".$start_date."开始运行，距离运行还有".$days."天！", 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'status' => 2,//todo
                        'pln_id' => $plan_info['pln_id'],
                        'pln_name' => CHtml::encode($plan_name),
                        'start_date' =>$plan_info['start_date'],
                        'days'=>$days
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "/plan/" . $plan_info['pln_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            } else if($plan_info['status'] == 3 && isset($msg['expire']) && $msg['expire']==1) {//运行中 即将到期通知
                $msg_data = array(
                    'uid' => $plan_info['p_uid'],
                    'u_type' => 2,
                    'type' => 15,
                    'relation_id' => $plan_info['pln_id'],
                    'content' => json_encode(array(
                        array('value' => "您的计划《" . $plan_name . "》将于".$plan_info['end_date']."到期，请安排好您的操作哦~", 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'status' => -99,//运行中 即将到期通知
                        'pln_id' => $plan_info['pln_id'],
                        'pln_name' => CHtml::encode($plan_name),
                        'end_date' => $plan_info['end_date']
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "/plan/" . $plan_info['pln_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            } else if($plan_info['status'] == 3) {//运行中
                $msg_data = array(
                    'uid' => $plan_info['p_uid'],
                    'u_type' => 2,
                    'type' => 15,
                    'relation_id' => $plan_info['pln_id'],
                    'content' => json_encode(array(
                        array('value' => "您的计划《" . $plan_name . "》今天开始运行，期待您的操作哦！", 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'status' => 3,//todo
                        'pln_id' => $plan_info['pln_id'],
                        'pln_name' => CHtml::encode($plan_name),
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "/plan/" . $plan_info['pln_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            } else if($plan_info['status'] == 4){//运行成功
                $msg_data = array(
                    'uid' => $plan_info['p_uid'],
                    'u_type' => 2,
                    'type' => 15,
                    'relation_id' => $plan_info['pln_id'],
                    'content' => json_encode(array(
                        array('value' => "恭喜！您的计划《" . $plan_name . "》已达到目标收益，您可以继续操作创造更高收益，也可以终止计划哦~", 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'status' => 4,
                        'pln_id' => $plan_info['pln_id'],
                        'pln_name' => CHtml::encode($plan_name),
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "/plan/" . $plan_info['pln_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            } else if($plan_info['status'] == 5){//运行失败
                $msg_data = array(
                    'uid' => $plan_info['p_uid'],
                    'u_type' => 2,
                    'type' => 15,
                    'relation_id' => $plan_info['pln_id'],
                    'content' => json_encode(array(
                        array('value' => "很遗憾《" . $plan_name . "》计划未完成，可重新发起计划", 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'status' => 5,
                        'pln_id' => $plan_info['pln_id'],
                        'pln_name' => CHtml::encode($plan_name),
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "/plan/" . $plan_info['pln_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            }else if($plan_info['status'] == 6){//止损失败
                $msg_data = array(
                    'uid' => $plan_info['p_uid'],
                    'u_type' => 2,
                    'type' => 15,
                    'relation_id' => $plan_info['pln_id'],
                    'content' => json_encode(array(
                        array('value' => "抱歉，您的计划《" . $plan_name . "》由于触及止损线已被终止，期待您下一期计划的表现哦！", 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'status' => 6,
                        'pln_id' => $plan_info['pln_id'],
                        'pln_name' => CHtml::encode($plan_name),
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "/plan/" . $plan_info['pln_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            }else if($plan_info['status'] == 7){//到期冻结
                $msg_data = array(
                    'uid' => $plan_info['p_uid'],
                    'u_type' => 2,
                    'type' => 15,
                    'relation_id' => $plan_info['pln_id'],
                    'content' => json_encode(array(
                        array('value' => "您的计划《" . $plan_name . "》运行时间已到期，".($plan_info['target_ror']>$plan_info['curr_ror']?"目标未达成，期待您下一期计划的表现哦！":"恭喜您达成目标，期待您下一期计划的表现哦！"), 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'status' => 7,
                        'pln_id' => $plan_info['pln_id'],
                        'pln_name' => CHtml::encode($plan_name),
                        'target_ror'=>$plan_info['target_ror'],
                        'curr_ror'=>$plan_info['curr_ror']
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "/plan/" . $plan_info['pln_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            }
            if(empty($msg_data)){
                throw new Exception($pln_id.': msg_data 推送消息为空');
            }
            //保存通知消息
            Message::model()->saveMessage($msg_data);

            //加入通知队列
            $push_uid=0;
            if($msg_data['u_type']==2){
                $push_uid = User::model()->getUidBySuid($plan_info['p_uid']);
            }else{
                $push_uid = $msg_data['uid'];
            }
            $this->commonHandler->addToPushQueue($msg_data, $push_uid, array(2, 3));
            $log_data['uid']=$msg_data['uid'];
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