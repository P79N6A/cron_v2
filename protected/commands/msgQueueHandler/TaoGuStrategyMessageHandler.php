<?php

class TaoGuStrategyMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 淘股策略
     * 
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            $p_uid = $msg['p_uid'];
            //查询需要通知的用户
            $pkg_id = Vip::model()->getPlannerService($p_uid);
            // if(empty($pkg_id)){
            //     throw new Exception("观点包id为空", 1);
            // }
            //获取订阅观点包的用户id
            $push_user = Package::model()->getSubscriptionUid($pkg_id);
            //测试用户
            $push_user[] = "171429906";
            echo "被推送的用户:\r\n";
            print_r($push_user);
            if(!empty($push_user)) {
                foreach ($push_user as $uids) {
                    $msg_data = array(
                        'uid' => $uids,
                        'u_type' => 1,
                        'type' => 69,
                        'relation_id' => 0,
                        'content' => json_encode(array(
                            array('value' => $msg['message'], 'class' => '', 'link' => ""),
                        ), JSON_UNESCAPED_UNICODE),
                        'content_client' => json_encode(array(
                            'p_uid' => $msg['p_uid'],
                        ), JSON_UNESCAPED_UNICODE),
                        'c_time' => date("Y-m-d H:i:s"),
                        'u_time' => date("Y-m-d H:i:s")
                    );
                }
                // 保存通知消息
                Message::model()->saveMessage($msg_data);
                foreach ($push_user as $uid) {
                    //加入通知队列
                    $this->commonHandler->addToPushQueue($msg_data, $uid, array(14));
                }
                $log_data['uid'] = $msg_data['uid'];
                $log_data['relation_id'] = 0;
            }
        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
            var_dump($log_data);
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }
}
