<?php

/**
 * Class MatchTradePushMessageHandler
 * 大赛交易推送
 */
class MatchTradePushMessageHandler {

    private $commonHandler = null;
    private $msg = array();
    private $data = array();

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 入口
     */
    public function run($msg){
        $this->data = $msg['data'];
        switch($msg['trade_type']){
            case 1:
                //观察
                $this->watch();
                break;
            case 2:
                //订阅
                $this->subscribe();
                break;
        }
    }
    /**
     * 观察推送处理
     */
    public function watch(){
        try{
            $data = $this->data;
            if(empty($data)){
                return ;
            }
            foreach($data as $key=>$value){
                $msg_data = array(
                    'uid' => $value['uid'],
                    'u_type' => 1,
                    'type' => 28,
                    'relation_id' => 0,
                    'child_relation_id' => 0,
                    'content' => json_encode(array(
                        array('value' => "您观察的".$value['name']."选手动态".date("m月d日",time())."最新收益出炉，请立即查看", 'class' => '', 'link' => ""),
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'p_uid' => 0,
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => 0,
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
                //保存通知消息
                Message::model()->saveMessage($msg_data);
                $this->commonHandler->addToPushQueue($msg_data, $value['uid'], array(2, 3));
            }
        }catch(Exception $e){
            $log_data['status'] = -1;
            $log_data['result'] = $e->getMessage();
            var_dump($log_data);
        }
    }
    /**
     * 订阅推送处理
     */
    public function subscribe(){
        try{
            $data = $this->data;
            if(empty($data)){
                return ;
            }
            foreach($data as $key=>$value){
                $msg_data = array(
                    'uid' => $value['uid'],
                    'u_type' => 1,
                    'type' => 29,
                    'relation_id' => 0,
                    'child_relation_id' => 0,
                    'content' => json_encode(array(
                        array('value' => "您订阅的".$value['name']."选手动态".date("m月d日",time())."最新操作开始了，请立即查看", 'class' => '', 'link' => ""),
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'p_uid' => 0,
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => 0,
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
                //保存通知消息
                Message::model()->saveMessage($msg_data);
                $this->commonHandler->addToPushQueue($msg_data, $value['uid'], array(2, 3));
            }
        }catch(Exception $e){
            $log_data['status'] = -1;
            $log_data['result'] = $e->getMessage();
            var_dump($log_data);
        }
    }
}
