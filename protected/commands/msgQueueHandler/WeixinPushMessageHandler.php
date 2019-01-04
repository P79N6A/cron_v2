<?php

/**
 * 微信推送消息+消息渠道分发
 */
class WeixinPushMessageHandler
{
    private $commonHandler = null;

    public function __construct()
    {
        $this->commonHandler = new CommonHandler();
    }

    public function run($msg)
    {
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try{
            $msg_data = array(
                'id'            => '0',
                'uid'           => '0',
                'u_type'        => 1,
                'type'          => $msg['msg_type'],
                'relation_id'   => 0,
                'content'       => '',
                'content_client'=> '',
                'link_url'      => '',
                'c_time'        => date("Y-m-d H:i:s"),
                'u_time'        => date("Y-m-d H:i:s")
            );

            //获取全体用户
            $offset=0;
            $limit = 10000;
            $step=1;
            $uids_arr = Message::model()->getAllChannelUid($offset,$limit,array(1));
            $uids_arr = array('13115927');
            #$uids_arr = array('171429421','13115927');
            $this->commonHandler->addToPushQueue($msg_data, $uids_arr, array(1));
            exit;
            while(!empty($uids_arr)){
                $this->commonHandler->addToPushQueue($msg_data, $uids_arr, array(1));
                $step++;
                if($step>=100){
                    //TODO 防止死循环，发送100万退出
                    break;
                }
                $offset = ($step-1)*$limit;
                $uids_arr = Message::model()->getAllChannelUid($offset,$limit,array(1));
            }
            $log_data['ext_data']=isset($msg['uids'])?$msg['uids']:'';
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
