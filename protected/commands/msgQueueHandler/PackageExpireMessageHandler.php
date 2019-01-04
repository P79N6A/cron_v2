<?php
/**
 * Created by PhpStorm.
 * User: zwg
 * Date: 2016/3/15
 * Time: 10:00
 */

class PackageExpireMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 观点包到期提醒
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            $this->commonHandler->checkRequireParam($msg,array('uid','pkg_id','pkg_title','day'));

            $uid = $msg['uid'];
            $pkg_id = $msg['pkg_id'];
            $pkg_title = $msg['pkg_title'];
            $day = $msg['day'];

            $msg_data = array(
                'uid' => $uid,
                'u_type' => 1,
                'type' => 3,
                'relation_id'=>$pkg_id,
                'content'=>json_encode(array(
                    array('value'=>'亲！您购买的观点包','class'=>'','link'=>''),
                    array('value'=>"《".$pkg_title."》",'class'=>'','link'=>"/web/packageInfo?pkg_id=".$pkg_id),
                    array('value'=> ($day>0 ? $day."天后将到期" : "已到期")."，为保证服务，",'class'=>'','link'=>''),
                    array('value'=> "请尽快续费。",'class'=>'','link'=>"/web/packageInfo?pkg_id=".$pkg_id)
                ),JSON_UNESCAPED_UNICODE),
                'content_client'=>json_encode(array(
                    'type' => $day>0 ? 5 : 6,
                    'package_title' => CHtml::encode($pkg_title),
                    'day' => $day
                ),JSON_UNESCAPED_UNICODE),
                'link_url'=>'/web/packageInfo?pkg_id='.$pkg_id,
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );

            //增加关注
            if($day <= 0) {
                Package::model()->saveUserCollect($uid,$pkg_id);
            }
            //保存通知消息
            Message::model()->saveMessage($msg_data);

            //加入通知队列
            $this->commonHandler->addToPushQueue($msg_data, $msg_data['uid'], array(2, 3));

            $log_data['uid'] = $msg_data['uid'];
            $log_data['relation_id'] = $msg_data['relation_id'];
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