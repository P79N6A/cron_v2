<?php

class CirclePushToUserMessageHandler {
	private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 入口
     */
    public function run($msg){
        $this->data = $msg['data'];
        $this->pushToUser();
    }
	/**
     * 感兴趣推送处理
     */
    public function pushToUser(){
        try{
            $data = $this->data;
            if(empty($data)){
                return ;
            }

            $circles_info = Circle::model()->getCircleInfoMapByCircleids(array($data['circle_id']));
            $redis_key = MEM_PRE_KEY."planner_tab_".$circles_info[$data['circle_id']]['p_uid'];
            $planner_tab = json_decode(Yii::app()->redis_r->get($redis_key),true);

            $msg_data = array(
                    'uid' => $data['userId'],
                    'u_type' => 1,
                    'type' => 68,
                    'relation_id' => $data['circle_id'],
                    'child_relation_id' => 0,
                    'content' => json_encode(array(
                        array('value' => $data['plannerName']."回复了您的发言,点击查看", 'class' => '', 'link' => "/wap/silkArticle?id=1"),
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'p_uid' => 0,
                        'circle_id'=>$data['circle_id'],
                        'planner_tab'=>$planner_tab,
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => 0,
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            // 保存通知消息
            Message::model()->saveMessage($msg_data);
            $this->commonHandler->addToPushQueue($msg_data, $data['userId'], array(2, 3));
        }catch(Exception $e){
            $log_data['status'] = -1;
            $log_data['result'] = $e->getMessage();
            var_dump($log_data);
        }
    }

}