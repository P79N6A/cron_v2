<?php

class CircleChoiceMessageHandler {
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
     * 圈子精选内容推送
     * array(
     *      'circle_id'=>123,
     * )
     */
    public function pushToUser(){
        try{
            $data = $this->data;
            if(empty($data)){
                return ;
            }

            $push_uids = Circle::model()->getCircleUser($data['circle_id'], 1);

            $circles_info = Circle::model()->getCircleInfoMapByCircleids(array($data['circle_id']));

            $planner_info = Planner::model()->getPlannerById($circles_info[$data['circle_id']]['p_uid']);

            $push_channel = $this->getJxOption($data['circle_id']);
            var_dump($push_channel);
            /**
             * 验证推送时间(同一个圈子半个小时只推送一次)
             */
            $redis_key_expire = MEM_PRE_KEY."cron2_circle_choice_is_push_expire_".$data['circle_id'];
            echo $redis_key_expire."\n";
            $isPush = Yii::app()->redis_w->get($redis_key_expire);
            if(!empty($isPush)){
                echo "理财师已经推送过了,不进行推送\n";
                return;
            }else{
                echo "记录推送记录:".Yii::app()->redis_w->setex($redis_key_expire,1800,"ispush");
            }

            $notice_content = $planner_info[$circles_info[$data['circle_id']]['p_uid']]['name']."向您发送了一条精选内容,立刻前往查看";

            foreach($push_uids as $uid){
                // 组装消息
                $msg_data = array(
                    'uid' => $uid,
                    'u_type' => 1,
                    'type' => 75,
                    'relation_id' => $data['circle_id'],
                    'child_relation_id' => 0,
                    'content' => json_encode(array(
                        array('value' => $notice_content, 'class' => "", 'link' => ""),
                    ),JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'title'=>'精选内容',
                        'circle_id'=>$data['circle_id'],
                    ),JSON_UNESCAPED_UNICODE),
                    'link_url' => "",
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
                // 保存通知消息
                $msg_data['id'] = Message::model()->saveMessage($msg_data);
            }
            $uids_arr = array_chunk($push_uids, 500);
            if(!empty($push_channel)){
                foreach($uids_arr as $_uids){
                    $this->commonHandler->addToPushQueue($msg_data,(array)$_uids,$push_channel);
                }
            }else{
                echo "没有推送渠道";
            }
        }catch(Exception $e){
            $log_data['status'] = -1;
            $log_data['result'] = $e->getMessage();
            var_dump($log_data);
        }
    }
    public function getJxOption($circle_id){
        $redis_key = MEM_PRE_KEY."CircleJxPush0ption".$circle_id;
        $circleJxOption = Yii::app()->redis_r->get($redis_key);
        $push_channel = [];
        if(!empty($circleJxOption)){
            var_dump($circleJxOption);
            $circleJxOption = json_decode($circleJxOption,true);
            foreach ($circleJxOption as $key => $value) {
                if($value == 0){
                    unset($circleJxOption[$key]);
                }else{
                    $push_channel[] = $value;
                }
            }
        }
        return $push_channel;
    }

}