<?php
/**
 * Created by PhpStorm.
 * User: pcy
 * Date: 18-11-1
 * Time: 上午11:35
 */
class FreeViewXcxMessageHandler{
    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            $p_uid = $msg['p_uid'];
            //查询需要通知的用户
            if(isset($msg['message']['pkg_id'])){
                if(defined('ENV') && ENV == 'dev'){
                    if($msg['message']['pkg_id'] == '10070'){
                        $push_user = Xcx::model()->notActive48ByPuid($p_uid,2);
                    }else{
                        $push_user = Xcx::model()->notActive48ByPuid($p_uid,1);
                    }
                }else{
                    if($msg['message']['pkg_id'] == '10071'){
                        $push_user = Xcx::model()->notActive48ByPuid($p_uid,2);
                    }else{
                        $push_user = Xcx::model()->notActive48ByPuid($p_uid,1);
                    }
                }
            }else{
                $push_user = Xcx::model()->notActive48ByPuid($p_uid,1);
            }
            echo "被推送的用户:\r\n";
            $log_str = $msg['message']['name']."的免费观点:".$msg['message']['title'].",观点id为:".$msg['message']['view_id'].",推送人数:" . count($push_user).",推送人详情：".json_encode($push_user);
            echo $log_str."\r\n";
            Common::model()->saveLog($log_str, "info", "xcx_free_view_push");
            print_r($push_user);
            if(!empty($push_user)) {
                foreach ($push_user as $v) {
                    $msg_data = array(
                        'uid' => $v['uid'],
                        'u_type' => 1,
                        'type' => 72,
                        'relation_id' => 0,
                        'content' => json_encode(array(
                            array('value' => $msg['message'], 'class' => '', 'link' => ""),), JSON_UNESCAPED_UNICODE),
                        'content_client' => json_encode(array('p_uid' => $msg['p_uid'],), JSON_UNESCAPED_UNICODE),
                        'c_time' => date("Y-m-d H:i:s"),
                        'u_time' => date("Y-m-d H:i:s")
                    );
                    Message::model()->saveMessage($msg_data);
                    $msg_data['s_uid'] = '';
                    $msg_data['pagepath'] = 'pages/viewDetail/main?p_uid='.$p_uid.'&v_id='.$msg['message']['view_id'].'&isScan=1';
                    $msg_data['form_id'] = $v['form_id'];
                    $msg_data['touser'] = $v['open_id'];
                    $msg_data['keyword1'] = $msg['message']['title'];
                    $msg_data['keyword2'] = $msg['message']['name'].'的免费观点';
                    $msg_data['keyword3'] = '观点内容更新';
                    $msg_data['keyword4'] = $msg['message']['time'];
                    $msg_data['channel_type'] = 15;
                    $channel_user = $msg_data;
                    $channel_user['message'] = $msg_data;
                    if($v['uid']){
                        Xcx::model()->delRecordById($v['uid'],$v['form_id'],1);
                    }else{
                        Xcx::model()->delRecordById($v['open_id'],$v['form_id'],2);
                    }
                    $log_str = $msg['message']['name']."的免费观点:".$msg['message']['title'].",观点id为:".$msg['message']['view_id'].",推送对象信息:" . json_encode($v);
                    Common::model()->saveLog($log_str, "info", "xcx_free_view_push_detail");
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
