<?php
/**
 * 运营push消息的通知处理
 * User: weiguang3
 * Date: 2016/2/29
 * Time: 14:24
 */

class OperateNoticeMessageHandler {


    private $commonHandler = null;

    public function __construct(){
        $this->commonHandler=new CommonHandler();
    }


    /**
     * 运营push消息的通知处理
     * @param $msg
     *  消息体结构：
     *  type=operateNotice  notice_type content  relation_id url uids[为空为所有用户] channel[2:android 3:ios]
     *  title image  $u_type 1用户  2理财师   notice_id 通知消息ID
     */
    public function run($msg){
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try{
            $this->commonHandler->checkRequireParam($msg,array('notice_type','content','relation_id','url'));
            $u_type=1;
            if(isset($msg['u_type'])&&$msg['u_type']==2){
                $u_type=2;
            }
            //处理渠道
            $channel=intval(isset($msg['channel'])?$msg['channel']:0);
            if(!in_array($channel,array(1,2,3,4,5,6,7,8,9,10,11,12,13,14))){
                $channel=0; //所有渠道
            }

            $msg_data = array(
                'uid'=>'',
                'u_type'=>$u_type,  //1普通用户   2理财师
                'type'=>12,
                'relation_id'=>$msg['relation_id'],
                'child_relation_id'=>isset($msg['notice_id'])?intval($msg['notice_id']):0, //记录通知消息ID 方便统计消息的数量和阅读数量
                'content'=>json_encode(array(
                    array('value'=>CommonUtils::getSubStrNew($msg['content'],40,'...'),'class'=>'','link'=>$msg['url'])
                ),JSON_UNESCAPED_UNICODE),
                'link_url'=>$msg['url'],
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );

            $content_client['t']=$msg['notice_type'];
            switch($msg['notice_type']){
                case 3:
                case 4:
                case 5:
                case 6:
                    $content_client['pln_id']=''; //TODO $msg 缺少此字段
                case 8:
                case 9:
                case 10:
                    //获取理财师的tab信息
                    $redis_key = MEM_PRE_KEY."planner_tab_".$msg['relation_id'];
                    $data = json_decode(Yii::app()->redis_r->get($redis_key),true);
                    if(!empty($data)){
                        $content_client['planner_tab'] = $data;
                    }
                    $content_client['id']=$msg['relation_id'];
                    break;
                case 11:
                    $content_client['k']=$msg['relation_id'];
                    break;
                case 12:
                    $content_client['url']=$msg['url'];
                    break;
                case 14:
                    $content_client['id']=$msg['relation_id'];
                    $content_client['url']='http://licaishi.sina.com.cn/web/noticeInfo?n_id='.$msg['relation_id'];
                case 19:
                    $content_client['id']=$msg['relation_id'];
                    break;
                case 20:
                    $content_client['id']=$msg['relation_id'];
                    break;
                case 21:
                    $circles_info = Circle::model()->getCircleInfoMapByCircleids(array($msg['relation_id']));
                    $redis_key = MEM_PRE_KEY."planner_tab_".$circles_info[$msg['relation_id']]['p_uid'];
                    $data = json_decode(Yii::app()->redis_r->get($redis_key),true);
                    if(!empty($data)){
                        $content_client['planner_tab'] = $data;
                    }
                    $content_client['id']=$msg['relation_id'];
                    break;
                default:
                    break;
            }

            $content_client['title']=isset($msg['title'])?CommonUtils::getSubStrNew($msg['title'],50,'...'):'小妹通知';
            $content_client['image']=isset($msg['image'])?$msg['image']:'';
            $content_client['content']=CommonUtils::getSubStrNew($msg['content'],80,'');

            $msg_data['content_client']=json_encode($content_client,JSON_UNESCAPED_UNICODE);

            $uids = isset($msg['uids'])?$msg['uids']:'';
            if(!empty($uids)){
                if(!$this->is_not_json($uids)){
                    $data = json_decode($uids,true);
                    if($data['account_type'] == "uid"){
                        $uids_arr = explode(",",$data['account_id']);
                        $_uids=array_unique($uids_arr);
                    }else{
                        $_uids = explode(",",$data['account_id']);
                        $_uids=array_unique($_uids);
                        $uids_arr = Message::model()->getChannelUidBySuids($_uids);
                    }
                }
                echo "小妹推送通知 uids_arr \r\n";
                var_dump($uids_arr);
                $this->saveMsgAndPush($u_type,$uids_arr,$_uids,$msg_data,$channel);
            }else{
                if($u_type==2){
                    //获取全部理财师s_uids
                    $s_uids = Message::model()->getAllChannelSUid();
                    $uids_arr = Message::model()->getChannelUidBySuids($s_uids);
                    $this->saveMsgAndPush($u_type,$uids_arr,$s_uids,$msg_data,$channel);
                }else{
                    //获取全体用户
                    $offset=0;
                    $limit = 10000;
                    $step=1;
                    ///lixiang29 add 财道中理财师小妹只推送给新版用户
                    $uids_arr = Message::model()->getAllChannelUid($offset,$limit,array(9,10,11,12));
                    while(!empty($uids_arr)){
                        $this->saveMsgAndPush($u_type,$uids_arr,array(),$msg_data,$channel);
                        $step++;
                        if($step>=100){
                            //TODO 防止死循环，发送100万退出
                            break;
                        }
                        $offset = ($step-1)*$limit;
                        ///lixiang29 add 财道中理财师小妹只推送给新版用户
                        $uids_arr = Message::model()->getAllChannelUid($offset,$limit,array(9,10,11,12));
                    }
                }
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
        unset($_uids);
        if(!empty($uids)){
            echo "小妹推送通知 uids \r\n";
            var_dump($uids);
            //将用户500个一组，分批放入队列。否则会导致数据过大。
            $uids_arr = array_chunk($uids,500);
            $msg['content'] = json_decode($msg['content'],true);
            foreach($uids_arr as $_uids){
                $this->commonHandler->addToPushQueue($msg,$_uids,$channel===0?array(1,2,3):array($channel));
            }
        }
    }
    /**
     * 判断是否为json
     */
    public function is_not_json($str){ 
        return is_null(json_decode($str));
    }
}
