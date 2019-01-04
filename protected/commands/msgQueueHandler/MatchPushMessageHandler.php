<?php

/**
 * Class MatchPushMessageHandler
 * 大赛推送
 */
class MatchPushMessageHandler {

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
        switch($msg['match_type']){
            case 1:
                $this->interesed();
                break;
            case 2:
                $this->signUp();
                break;
            case 3:
                $this->matchTrade();
                break;
            case 4:
                $this->money();
                break;
        }
    }
    /**
     * 感兴趣推送处理
     */
    public function interesed(){
        try{
            $data = $this->data;
            if(empty($data)){
                return ;
            }
            foreach($data as $key=>$value){
                $msg_data = array(
                        'uid' => $value['parent_id'],
                        'u_type' => 1,
                        'type' => 67,
                        'relation_id' => 0,
                        'child_relation_id' => 0,
                        'content' => json_encode(array(
                            array('value' => $value['count']."位好友对您的大赛邀请感兴趣啦，奖金快到碗里来，点击查看~", 'class' => '', 'link' => "/wap/silkArticle?id=1"),
                        ), JSON_UNESCAPED_UNICODE),
                        'content_client' => json_encode(array(
                            'p_uid' => 0,
                        ), JSON_UNESCAPED_UNICODE),
                        'link_url' => 0,
                        'c_time' => date("Y-m-d H:i:s"),
                        'u_time' => date("Y-m-d H:i:s")
                    );
                // 保存通知消息
                Message::model()->saveMessage($msg_data);
                $this->commonHandler->addToPushQueue($msg_data, $value['parent_id'], array(2, 3));
            }
        }catch(Exception $e){
            $log_data['status'] = -1;
            $log_data['result'] = $e->getMessage();
            var_dump($log_data);
        }
    }
    /**
     * 报名推送
     *
     */
    public function signUp(){
        try{
            $data = $this->data;
            if(empty($data)){
                return ;
            }
            foreach($data as $key=>$value){
                $content = $value['count'] < 10 ? array('value' => "恭喜获得".$value['money']."元奖金！".$value['count']."位好友通过您的邀请报名百万股神，点击查看~", 'class' => '', 'link' => "/wap/silkArticle?id=1")
                     : array('value' => $value['count']."位好友通过您的邀请报名百万股神，恭喜您离小米电视又近一步啦，点击查看~", 'class' => '', 'link' => "/wap/silkArticle?id=1");
                $msg_data = array(
                    'uid' => $value['parent_id'],
                    'u_type' => 1,
                    'type' => 67,
                    'relation_id' => 0,
                    'child_relation_id' => 0,
                    'content' => json_encode(array(
                        $content,
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'p_uid' => 0,
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => 0,
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
                // 保存通知消息
                Message::model()->saveMessage($msg_data);
                $this->commonHandler->addToPushQueue($msg_data, $value['parent_id'], array(2, 3));
            }
        }catch(Exception $e){
            $log_data['status'] = -1;
            $log_data['result'] = $e->getMessage();
            var_dump($log_data);
        }
    }
    /**
     * 参赛推送
     *
     */
    public function matchTrade(){
        try{
            $data = $this->data;
            if(empty($data)){
                return ;
            }
            foreach($data as $key=>$value){
                $content = $content = array('value' => "您有".$value['count']."位好友刚刚加入比赛，表现如何？快来围观~", 'class' => '', 'link' => "/wap/silkArticle?id=1");
                $msg_data = array(
                    'uid' => $value['parent_id'],
                    'u_type' => 1,
                    'type' => 67,
                    'relation_id' => 0,
                    'child_relation_id' => 0,
                    'content' => json_encode(array(
                        $content,
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'p_uid' => 0,
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => 0,
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
                // 保存通知消息
                //Message::model()->saveMessage($msg_data);
                $this->commonHandler->addToPushQueue($msg_data, $value['parent_id'], array(2, 3));
            }
        }catch(Exception $e){
            $log_data['status'] = -1;
            $log_data['result'] = $e->getMessage();
            var_dump($log_data);
        }
    }
    /**
     * 奖金推送
     *
     */
    public function money(){
        try{
            $data = $this->data;
            if(empty($data)){
                return ;
            }
            foreach($data as $key=>$value){
                $content = array('value' => "恭喜获得".$value['money']."元奖金！好友成功登榜百万股神，快来看看都是谁~", 'class' => '', 'link' => "/wap/silkArticle?id=1");
                $msg_data = array(
                    'uid' => $value['match_uid'],
                    'u_type' => 1,
                    'type' => 67,
                    'relation_id' => 0,
                    'child_relation_id' => 0,
                    'content' => json_encode(array(
                        $content,
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'p_uid' => 0,
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => 0,
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
                // 保存通知消息
                Message::model()->saveMessage($msg_data);
                $this->commonHandler->addToPushQueue($msg_data, $value['match_uid'], array(2, 3));
            }
        }catch(Exception $e){
            $log_data['status'] = -1;
            $log_data['result'] = $e->getMessage();
            var_dump($log_data);
        }
    }
}
