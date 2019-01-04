<?php
/**
 * 观点包状态变化通知
 * User: zwg
 * Date: 2016/5/19
 * Time: 16:56
 */

class PackageChangeMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     *  计划提醒
     * @param #msg type=packageChange 'pkg_id',"pkg_name","p_uid","status",'reason'
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok','uid'=>'','relation_id'=>'','ext_data'=>'');
        try {
            $this->commonHandler->checkRequireParam($msg, array('pkg_id',"pkg_name","p_uid","status",'reason'));


            $msg_data = array();
            if($msg['status'] == -3) {//审核不通过
                $msg_data = array(
                    'uid' => $msg['p_uid'],
                    'u_type' => 2,
                    'type' => 18,
                    'relation_id' => $msg['pkg_id'],
                    'content' => json_encode(array(
                        array('value' => '您的观点包《'.$msg['pkg_name'].'》由于"'.$msg['reason'].'"，没有通过审核，请到电脑网页端重新申请！', 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'status' => -3,//t
                        'pkg_id' => $msg['pkg_id'],
                        'pkg_name' => CHtml::encode($msg['pkg_name']),
                        'reason' =>CHtml::encode($msg['reason'])
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "",
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            } else if($msg['status'] == 0) {//审核通过，待运行中

                $msg_data = array(
                    'uid' => $msg['p_uid'],
                    'u_type' => 2,
                    'type' => 18,
                    'relation_id' => $msg['pkg_id'],
                    'content' => json_encode(array(
                        array('value' => '您的观点包《'.$msg['pkg_name'].'》审核通过，快去发表一篇观点吧！', 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'status' => 0,
                        'pkg_id' => $msg['pkg_id'],
                        'pkg_name' => CHtml::encode($msg['pkg_name'])
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "web/packageInfo?pkg_id=" . $msg['pkg_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            }
            if(empty($msg_data)){
                throw new Exception($msg['pkg_id'].': msg_data 推送消息为空');
            }
            //保存通知消息
            Message::model()->saveMessage($msg_data);

            //加入通知队列
            $push_uid=0;
            if($msg_data['u_type']==2){
                $push_uid = User::model()->getUidBySuid($msg_data['uid']);
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