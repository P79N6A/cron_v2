<?php
/**
 * Created by PhpStorm.
 * User: pcy
 * Date: 18-9-18
 * Time: 下午1:29
 */

class DynamicNewsMessageHandler{
    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }

    /**
     * 淘股策略
     *
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            if($msg['message']['type'] == 1){
                $channel_type = array(9,10);
                $channel = 14;
            }elseif ($msg['message']['type'] == 2){
                $channel_type = array(11,12);
                $channel = 13;
            }else{
                $channel_type = array(9,10,11,12);
                $channel = 'all';
            }
            $msg['message']['content'] = mb_substr($msg['message']['content'],0,60,'utf-8');
            $msg_data = array(
                'uid'=>'',
                'u_type'=>1,  //1普通用户   2理财师
                'type'=>72,
                'relation_id'=>$msg['message']['relation_id'],
                'child_relation_id'=>isset($msg['notice_id'])?intval($msg['notice_id']):0, //记录通知消息ID 方便统计消息的数量和阅读数量
                'content'=>json_encode(array(
                    array('value'=>$msg['message']['content'],'class'=>'','link'=>'')
                ),JSON_UNESCAPED_UNICODE),
                'content_client' => json_encode(array(
                    'content' => $msg['message']['content'],
                    'title' => '快讯'
                ), JSON_UNESCAPED_UNICODE),
                'link_url'=>'',
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );
            //获取全体用户
            $offset=0;
            $limit = 10000;
            $step=1;

            $uids_arr = Message::model()->getAllChannelUid($offset,$limit,$channel_type);
            while(!empty($uids_arr)){
                $this->saveMsgAndPush(1,$uids_arr,array(),$msg_data,$channel);
                $step++;
                if($step>=100){
                    //TODO 防止死循环，发送100万退出
                    break;
                }
                $offset = ($step-1)*$limit;
                $uids_arr = Message::model()->getAllChannelUid($offset,$limit,$channel_type);
            }
                $log_data['ext_data']=isset($msg['uids'])?$msg['uids']:'';
                $log_data['relation_id'] = $msg['message']['relation_id'];
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
    /**
     * 保存消息并且发送通知
     * @param $u_type
     * @param $uids
     * @param $s_uids
     * @param $msg
     * @param $channel
     */
    private function saveMsgAndPush($u_type,$uids,$s_uids,$msg,$channel){
        $_uids = $u_type==1?$uids:$s_uids;
        if(!empty($_uids)){
            foreach($_uids as $uid){
                $msg['uid']=$uid;
                $msg['channel']=$channel;
                //TODO 可以优化批量插入
                Message::model()->saveMessage($msg);
            }
        }
        $channel = $channel == 'all' ? array(13,14) : array($channel);
        unset($_uids);
        if(!empty($uids)){
            echo "7*24小时通知 uids \r\n";
            var_dump($uids);
            //将用户500个一组，分批放入队列。否则会导致数据过大。
            $uids_arr = array_chunk($uids,500);
            $msg['content'] = json_decode($msg['content'],true);
            foreach($uids_arr as $_uids){
                $this->commonHandler->addToPushQueue($msg,$_uids,$channel===0?array(1,2,3):$channel);
            }
        }
    }
}