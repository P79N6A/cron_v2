<?php
/**
 * Created by PhpStorm.
 * User: zwg
 * Date: 2016/3/15
 * Time: 9:53
 */

class PackageChargeMessageHandler {

    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 观点包开始收费提醒
     */
    public function run($msg){

        $log_data = array('status'=>1,'result'=>'ok','uid'=>'','relation_id'=>'','ext_data'=>'');
        try {
            $this->commonHandler->checkRequireParam($msg, array('pkg_id'));
            $pkg_id = $msg['pkg_id'];
            $package = Package::model()->getPackagesById($pkg_id, false);
            $package = isset($package[$pkg_id]) ? $package[$pkg_id] : array();

            if(empty($package)){
                throw new Exception('package 为空');
            }
            $uids = Package::model()->getCollectUid($pkg_id);
            foreach ($uids as $uid) {
                $msg_data = array(
                    'uid' => $uid,
                    'u_type' => 1,
                    'type' => 3,
                    'relation_id' => $pkg_id,
                    'content' => json_encode(array(
                        array('value' => "您关注的观点包", 'class' => '', 'link' => ''),
                        array('value' => "《" . CHtml::encode($package['title']) . "》", 'class' => '', 'link' => '/web/packageInfo?pkg_id=' . $pkg_id),
                        array('value' => "将在" . date("m月d日", strtotime($package['charge_time'])) . "起增加收费私密内容，敬请关注！", 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'type' => 4,
                        'package_title' => CHtml::encode($package['title']),
                        'charge_time' => date("m月d日", strtotime($package['charge_time']))
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => '/web/packageInfo?pkg_id=' . $pkg_id,
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );

                //保存通知消息
                Message::model()->saveMessage($msg_data);

                //加入通知队列
                $this->commonHandler->addToPushQueue($msg_data, $msg_data['uid'], array(2, 3));
                $log_data['ext_data'][]=array('uid'=>$msg_data['uid'],'relation_id'=>$msg_data['relation_id']);
            }

        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            $log_data['ext_data']=json_encode($log_data['ext_data']);
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }
}