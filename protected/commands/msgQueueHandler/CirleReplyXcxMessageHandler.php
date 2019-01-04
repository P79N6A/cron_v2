<?php
/**
 * Created by PhpStorm.
 * User: pcy
 * Date: 18-11-2
 * Time: 下午2:54
 */
class CirleReplyXcxMessageHandler{
    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            $p_uid = $msg['p_uid'];
            //查询需要通知的用户
            $push_user = Xcx::model()->getFormOrPayIdByUid($msg['uid']);

            echo "被推送的用户:\r\n";
            print_r($push_user);
            if(!empty($push_user)) {
                foreach ($push_user as $v) {
                    $msg_data = array(
                        'uid' => $v['uid'],
                        'u_type' => 1,
                        'type' => 73,
                        'relation_id' => 0,
                        'content' => json_encode(array(
                            array('value' => $msg['message'], 'class' => '', 'link' => ""),), JSON_UNESCAPED_UNICODE),
                        'content_client' => json_encode(array('p_uid' => $msg['p_uid'],), JSON_UNESCAPED_UNICODE),
                        'c_time' => date("Y-m-d H:i:s"),
                        'u_time' => date("Y-m-d H:i:s")
                    );
                    Message::model()->saveMessage($msg_data);
                    $msg_data['s_uid'] = '';
                    $msg_data['pagepath'] = 'pages/liveList/main?p_uid='.$p_uid.'&service=4&isScan=1';
                    $msg_data['form_id'] = $v['form_id'];
                    $msg_data['touser'] = $v['open_id'];
                    $msg_data['keyword1'] = $msg['message']['name'].'直播间的回复提醒';
                    $msg_data['keyword2'] = '理财师-'.$msg['message']['name'];
                    $msg_data['keyword3'] = $msg['message']['content'];
                    $msg_data['keyword4'] = $msg['message']['time'];
                    $msg_data['channel_type'] = 15;
                    $channel_user = $msg_data;
                    $channel_user['message'] = $msg_data;
                    if($v['uid']){
                        Xcx::model()->delRecordById($v['uid'],$v['form_id'],1);
                    }else{
                        Xcx::model()->delRecordById($v['open_id'],$v['form_id'],2);
                    }
                    Yii::app()->redis_w->rPush(WeiXinMessagePushQueue::QUEUE_KEY,json_encode($channel_user,JSON_UNESCAPED_UNICODE));
                }
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