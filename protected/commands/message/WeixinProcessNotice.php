<?php 
/**
 * 定时任务:
 * User: lixiang29
 * Date: 2017-10-09
 */

class WeixinProcessNotice{
    const CRON_NO = 1321; //任务代码

    public function __construct(){
        Yii::import('application.commands.msgQueueHandler.*');
    }


    /**
     * @throws LcsException
     */
    public function Process(){
        try {
        	$redis_w = Yii::app()->redis_w;
        	$success_ids = array();
            $data = WeixinPush::model()->getPushByStatus(OperateNotice::PUSH_TYPE_INIT);

            foreach($data as $info){
                $success_ids[] = $info['id'];
            }
        	if(!empty($success_ids)) {
        		//推送成功的，改为‘已推送’
        		WeixinPush::model()->setPushState($success_ids);
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, json_encode($success_ids));
        	}
            $template = WeixinPush::model()->getTemplate();
            $CommonHand = new CommonHandler();

            foreach ($data as $info) {
                if(!isset($template[$info['template_id']])){
                    continue;
                }
                $msg_data = array(
                    'id'            => '0',
                    'uid'           => '0',
                    'u_type'        => 1,
                    'type'          => $template[$info['template_id']],
                    'relation_id'   => $info['id'],
                    'content'       => $info['content'],
                    'content_client'=> '',
                    'link_url'      => '',
                    'wechat'        => $info['wechat'],
                    'c_time'        => date("Y-m-d H:i:s"),
                    'u_time'        => date("Y-m-d H:i:s")
                );
                ///测试账号
                if($info['account_type']==2){
                    $uids_arr = explode(',',$info['account_uid']);
                    if(count($uids_arr)==0){
                        continue;
                    }
                    $channel_user = Message::model()->getChannelUserInfoByUid($uids_arr,2);
                    echo "推送:\r\n";
					var_dump($channel_user);
					if(!empty($channel_user)){
                        foreach($channel_user as $val){
                            if($msg_data['wechat'] == 2 && $val['channel_type'] == 15){
                                continue;
                            }
                            if($msg_data['wechat'] == 3 && $val['channel_type'] == 1){
                                continue;
                            }
                            //只推送微信渠道
                            if($val['channel_type'] == 1 || $val['channel_type'] == 15){
                                $push_users[$val['uid']] = $val['uid'];
                                $push_users[$val['uid']] = $val['channel_id'];
                                $val['message'] = $msg_data;
                                //1 模板推送 2 图文推送
                                if($info['push_type'] == 1){
                                    echo "模板推送\r\n";
                                    Yii::app()->redis_w->rPush(WeiXinMessagePushQueue::TEMPLET_QUEUE_KEY,json_encode($val,JSON_UNESCAPED_UNICODE));
                                }else{
                                    echo "图文推送\r\n";
                                    $pushData['uid'] = $val['uid'];
                                    $pushData['channel_id'] = $val['channel_id'];
                                    $pushData['openId'] = $val['channel_id'];
                                    $pushData['channel_type'] = $val['channel_type'];
                                    $pushData['type'] = 'news';
                                    $content = json_decode($info['content'],true);
                                    $pushData['content'] = array(
                                        array(
                                            "title"=>$content['title'],
                                            "description"=>$content['description'],
                                            "url"=>$content['url'],
                                            "picurl"=>$content['picurl'],
                                        )
                                    );
                                    var_dump($pushData);
                                    echo "\r\n";
                                    Yii::app()->redis_w->rPush(WeiXinMessagePushQueue::IMAGE_QUEUE_KEY,json_encode($pushData,JSON_UNESCAPED_UNICODE));
                                }
                            }
                        }
                        echo "测试账号推送记录:\r\n";
                        //var_dump($push_users);
                    }else{
                        echo date("Y-m-d H:i:s"),"\r\n没有可推送用户";
                    }
                }else{
                    ///全部账号
                    $offset=0;
                    $limit = 10000;
                    $step=1;
                    $uids_arr = Message::model()->getAllChannelUid($offset,$limit,array(1,15));
                    while(!empty($uids_arr)){
                        $channel_user = Message::model()->getChannelUserInfoByUid($uids_arr,2);
                        if(!empty($channel_user)){
                            foreach($channel_user as $val){
                                if($msg_data['wechat'] == 2 && $val['channel_type'] == 15){
                                    continue;
                                }
                                if($msg_data['wechat'] == 3 && $val['channel_type'] == 1){
                                        continue;
                                }

                                //只推送微信渠道
                                if($val['channel_type'] == 1 || $val['channel_type'] == 15){
                                    $push_users[$val['uid']] = $val['uid'];
                                    $push_users[$val['uid']] = $val['channel_id'];
                                    $val['message'] = $msg_data;
                                    //1 模板推送 2 图文推送
                                    if($info['push_type'] == 1){
                                        echo "模板推送\r\n";
                                        Yii::app()->redis_w->rPush(WeiXinMessagePushQueue::TEMPLET_QUEUE_KEY,json_encode($val,JSON_UNESCAPED_UNICODE));
                                    }else{
                                        echo "图文推送\r\n";
                                        $pushData['uid'] = $val['uid'];
                                        $pushData['channel_id'] = $val['channel_id'];
                                        $pushData['openId'] = $val['channel_id'];
                                        $pushData['channel_type'] = $val['channel_type'];
                                        $pushData['type'] = 'news';
                                        $content = json_decode($info['content'],true);
                                        $pushData['content'] = array(
                                            array(
                                                "title"=>$content['title'],
                                                "description"=>$content['description'],
                                                "url"=>$content['url'],
                                                "picurl"=>$content['picurl'],
                                            )
                                        );
                                        var_dump($pushData);
                                        echo "\r\n";
                                        Yii::app()->redis_w->rPush(WeiXinMessagePushQueue::IMAGE_QUEUE_KEY,json_encode($pushData,JSON_UNESCAPED_UNICODE));
                                    }
                                }
                            }
//                            foreach($channel_user as $val){
//                                //只推送微信渠道
//                                if($val['channel_type'] == 1){
//                                    $push_users[$val['uid']] = $val['uid'];
//                                    $push_users[$val['uid']] = $val['channel_id'];
//                                    $val['message'] = $msg_data;
//                                    Yii::app()->redis_w->rPush(WeiXinMessagePushQueue::TEMPLET_QUEUE_KEY,json_encode($val,JSON_UNESCAPED_UNICODE));
//                                }
//                            }
                            echo "推送记录:\r\n";
                            //var_dump($push_users);
                        }else{
                            echo date("Y-m-d H:i:s"),"\r\n没有可推送用户";
                        }

                        $step++;
                        if($step>=100){
                            //TODO 防止死循环，发送100万退出
                            break;
                        }
                        $offset = ($step-1)*$limit;
                        $uids_arr = Message::model()->getAllChannelUid($offset,$limit,array(1,15));
                    }
                }
        	}//end foreach.
        	
        }catch (Exception $e) {
        	throw LcsException::errorHandlerOfException($e);
        }
    }
    public function getUserChannel($uids_arr){
            //添加到中央处理(模板消息处理:创建一个新的处理队列)
            // $CommonHand->addToPushQueue($msg_data, $uids_arr, array(1));
            $channel_user1 = Message::model()->getChannelUserInfoByUid($uids_arr,1); 
            $channel_user2 = Message::model()->getChannelUserInfoByUid($uids_arr,2);
            $channel_user = array_merge($channel_user1,$channel_user2);
            //建立一个目标数组 
            $res = array();
            $key = "channel_type";
            $akey = "uid";
            foreach ($channel_user as $value) {             
                    //查看有没有重复项    
                    if(isset($res[$value[$key]]) && isset($res[$value[$akey]])){    
                            unset($value[$key]);  //有：销毁    
                    }else{      
                            $res[$value[$key]] = $value;    
                    }      
            }
            return $res;
    }
}
