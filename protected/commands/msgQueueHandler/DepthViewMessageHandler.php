<?php
/**
 * Created by PhpStorm.
 * User: pcy
 * Date: 18-9-7
 * Time: 上午11:27
 */
class DepthViewMessageHandler{
    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            $p_uid = $msg['p_uid'];
            //查询需要通知的用户
            $push_user = Vip::model()->getUidsByPlannerService($p_uid);
            echo "被推送的用户:\r\n";
            print_r($push_user);
            if(!empty($push_user)) {
                foreach ($push_user as $v) {
                    $msg_data = array(
                        'uid' => $v['uid'],
                        'u_type' => 1,
                        'type' => 71,
                        'relation_id' => 0,
                        'content' => json_encode(array(
                            array('value' => $msg['message'], 'class' => '', 'link' => ""),), JSON_UNESCAPED_UNICODE),
                        'content_client' => json_encode(array('p_uid' => $msg['p_uid'],), JSON_UNESCAPED_UNICODE),
                        'c_time' => date("Y-m-d H:i:s"),
                        'u_time' => date("Y-m-d H:i:s")
                    );
                    Message::model()->saveMessage($msg_data);
                    $msg_data['url'] = $msg['message']['url'];
                    $msg_data['pagepath'] = 'pages/viewDetail/main?p_uid='.$p_uid.'&v_id='.$msg['message']['view_id'];
                    $msg_data['first'] = $msg['message']['title'];
                    $msg_data['keyword1'] = $msg['message']['service_name'];
                    $msg_data['keyword3'] = $msg['message']['detail'];
                    $this->commonHandler->addToPushQueue($msg_data, $v['uid'], array(15));
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