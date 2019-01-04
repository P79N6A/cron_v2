<?php

class BsBuySellPointMessageHandler {
	private $commonHandler = null;
	private $data;
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
            //验证是否有权限
            $phones = UserAuth::model()->getAllUserAuthPhone();

            $uids = User::model()->getPhoneUidIm($phones);

            echo "Bspoint 要推送的用户:\n";

            print_r($uids);

            $push_user = $uids;

            if(!empty($push_user)){
            	foreach ($push_user as $uid) {
            		$msg_data = array(
                        'uid' => $uid,
                        'u_type' => 1,
                        'type' => 74,
                        'relation_id' => 0,
                        'child_relation_id' => 0,
                        'content' => json_encode(array(
                            array('value' => $data['pushString'], 'class' => '', 'link' => ""),
                        ), JSON_UNESCAPED_UNICODE),
                        'content_client' => json_encode(array(
                            'symbol' => $data['code'],
                        ), JSON_UNESCAPED_UNICODE),
                        'link_url' => "",
                        'symbol' => $data['code'],
                        'c_time' => date("Y-m-d H:i:s"),
                        'u_time' => date("Y-m-d H:i:s")
                    );
            	}
            	echo "方程式3.0推送的内容:\n";
            	print_r($msg_data);
            	echo "方程式3.0推送的用户:\n";
            	print_r($push_user);
            	foreach ($push_user as $uid) {
                    //20181115 只推送尊享版
            		$this->commonHandler->addToPushQueue($msg_data,$uid, array(13));
            	}
            }
            // 保存通知消息
            //Message::model()->saveMessage($msg_data);
        }catch(Exception $e){
            $log_data['status'] = -1;
            $log_data['result'] = $e->getMessage();
            var_dump($log_data);
        }
    }

}
