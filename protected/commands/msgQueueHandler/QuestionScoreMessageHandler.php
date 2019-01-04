<?php
/**
 * 问题评价提醒
 * User: zwg
 * Date: 2016/2/29
 * Time: 16:13
 */

class QuestionScoreMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }


    /**
     * 问题评价提醒
     * @param $msg    type=questionScore  q_id  p_uid  score  uid u_name u_image score_reason
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try{
            $this->commonHandler->checkRequireParam($msg,array('q_id','p_uid','score','uid','u_name','u_image','score_reason'));

            $msg_data = array(
                'uid'=>$msg['p_uid'],
                'u_type'=>2,  //1普通用户   2理财师
                'type'=>1,
                'relation_id'=>$msg['q_id'],
                'child_relation_id'=>0,
                'content'=>json_encode(array(
                    array('value'=>$msg['u_name'].'给您的回答做了评价','class'=>'','link'=>'')
                ),JSON_UNESCAPED_UNICODE),
                'link_url'=>'/ask/'.$msg['q_id'],
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );

            $content_client['status']=6;
            $content_client['q_id']=$msg['q_id'];
            $content_client['uid']=$msg['uid'];
            $content_client['u_name']=$msg['u_name'];
            $content_client['u_image']=$msg['u_image'];
            $content_client['score']=$msg['score'];
            $content_client['score_reason']=$msg['score_reason'];
            $content_client['time']= date("Y-m-d H:i:s");

            $msg_data['content_client']=json_encode($content_client,JSON_UNESCAPED_UNICODE);

            //保存通知消息
            Message::model()->saveMessage($msg_data);

            //加入提醒队列
            $msg_data['content'] = json_decode($msg_data['content'],true);
            $channel_user = Message::model()->getChannelUidBySuids($msg['p_uid']);
            $this->commonHandler->addToPushQueue($msg_data,$channel_user,array (2,3));

            $log_data['uid']=$msg['p_uid'];
            $log_data['relation_id']=$msg['q_id'];
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