<?php

class AudioMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 语音推送
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            $this->commonHandler->checkRequireParam($msg,array('relation_id'));
            //查询有权限的用户
            $push_user = UserAuth::model()->getPlannerUis($msg['relation_id']);
            //拼接数据
            $data['relation_id'] = $msg['relation_id'];
            // $data['child_relation_id'] = '123';
            $data['pushString'] = $msg["plannerName"].'向你发送了一条语音消息，前往查看';
            //title定制
            if($msg['relation_id'] == "6567967440"){
                $msg["plannerName"] = "伯乐";
            }
            if($msg['relation_id'] == "6150188584"){
                $msg["plannerName"] = "王牌";
            }
            //测试用户
            $push_user[] = "171429858";
            $push_user[] = "171429906";
            $push_user[] = "171432010";
            if(!empty($push_user)) {
                foreach ($push_user as $uids) {
                    $msg_data = array(
                        'uid' => $uids,
                        'u_type' => 1,
                        'type' => 76,
                        'relation_id' => $msg['id'],
                        'content' => json_encode(array(
                            array('value' => $data['pushString'], 'class' => '', 'link' => ""),
                        ), JSON_UNESCAPED_UNICODE),
                        'content_client' => json_encode(array(
                            'title'=>$msg["plannerName"].'直达',
                            // 'p_uid' => $data['p_uid'],
                        ), JSON_UNESCAPED_UNICODE),
                        'link_url' => "",
                        'c_time' => date("Y-m-d H:i:s"),
                        'u_time' => date("Y-m-d H:i:s")
                    );
                }
                // 保存通知消息
                Message::model()->saveMessage($msg_data);

                foreach ($push_user as $uid) {
                    //加入通知队列
                    var_dump($msg_data);
                    $this->commonHandler->addToPushQueue($msg_data, $uid, array(13));
                }
                $log_data['uid'] = $msg_data['uid'];
                $log_data['relation_id'] = $msg_data['relation_id'];
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
