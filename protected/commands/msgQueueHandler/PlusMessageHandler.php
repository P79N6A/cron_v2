<?php

class PlusMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * plus 会员推送
     * 
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            $uid = $msg['uid'];
            //测试用户
            if(!empty($uid)) {
                $msg_data = array(
                    'uid' => $uid,
                    'u_type' => 1,
                    'type' => 73,
                    'relation_id' => 0,
                    'content' => json_encode(array(
                        array('value' => $msg['message'], 'class' => '', 'link' => ""),
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => '',
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
                // 保存通知消息
                Message::model()->saveMessage($msg_data);
                //加入通知队列
                $this->commonHandler->addToPushQueue($msg_data, $uid, array(11,12));
                $log_data['uid'] = $msg_data['uid'];
                $log_data['relation_id'] = 0;
            }
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
