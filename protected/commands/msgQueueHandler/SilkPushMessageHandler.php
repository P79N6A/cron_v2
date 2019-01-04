<?php

class SilkPushMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 锦囊发布推送
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            $this->commonHandler->checkRequireParam($msg,array('article_id'));
            //获取锦囊文章的id
            $article_id = $msg['article_id'];
            //获取锦囊id
            $acticle_data = Silk::model()->getArticleById(array($article_id));
            $silk_id = isset($acticle_data[0]['silk_id'])?array($acticle_data[0]['silk_id']):array(0);
            //获取锦囊信息
            $silkInfo = Silk::model()->getSilkByIds($silk_id);

            $silkInfo = $silkInfo[$silk_id[0]];
            //获取订阅锦囊的用户
            $silk_sub_user = Silk::model()->getSilkSubListBySilkids($silk_id);
            $push_user = array();
            if(!empty($silk_sub_user)){
                foreach ($silk_sub_user[$silk_id[0]] as $key=>$value) {
                    $push_user[] = $value['uid'];
                }
            }
            
            //测试用户
            $push_user[] = "171429858";
            $push_user[] = "171429906";
            $acticle_data = $acticle_data[0];
            if(!empty($push_user)) {
                foreach ($push_user as $uids) {
                    $msg_data = array(
                        'uid' => $uids,
                        'u_type' => 1,
                        'type' => 66,
                        'relation_id' => $silk_id['0'],
                        'child_relation_id' => $article_id,
                        'content' => json_encode(array(
                            array('value' => "《" . CHtml::encode($silkInfo['title']) . "》", 'class' => '', 'link' => "/wap/silkArticle?id=" . $silkInfo['id']),
                            array('value' => '内更新了文章', 'class' => '', 'link' => ''),
                            array('value' => "：" . $acticle_data['title'], 'class' => '', 'link' => "/wap/silkArticle?id=" . $acticle_data['id'])
                        ), JSON_UNESCAPED_UNICODE),
                        'content_client' => json_encode(array(
                            'p_uid' => $silkInfo['p_uid'],
                        ), JSON_UNESCAPED_UNICODE),
                        'link_url' => "/view/" . $acticle_data['id'] . "?ind_id=" . $acticle_data['id'],
                        'c_time' => date("Y-m-d H:i:s"),
                        'u_time' => date("Y-m-d H:i:s")
                    );
                }
                // 保存通知消息
                Message::model()->saveMessage($msg_data);

                foreach ($push_user as $uid) {
                    //加入通知队列
                    var_dump($msg_data);
                    $this->commonHandler->addToPushQueue($msg_data, $uid, array(2, 3));
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
