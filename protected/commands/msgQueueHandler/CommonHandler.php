<?php

/**
 * 消息处理公告方法
 * @datetime 2016-02-29
 * @author weiguang3
 */
class CommonHandler {


    static public $handler_fun=array(
        'OperateNoticeMessageHandler',
        'PkgPlannerCommentMessageHandler',
        'PlanPlannerCommentMessageHandler',
        'QuestionAnswerMessageHandler',
        'QuestionScoreMessageHandler',
        'ReplayCommentMessageHandler',
        'ReplayCommentNewMessageHandler',
        'PlanTransactionMessageHandler',
        'BuyPackageMessageHandler',
        'NewViewMessageHandler',
        'PackageChargeMessageHandler',
        'PackageExpireMessageHandler',
        'BuyPlanMessageHandler',
        'PlanChangeMessageHandler',
        'PackageGCNewMessageHandler',
        'PackageGCNoticeMessageHandler',
        'PackageGCReplyMessageHandler',
        'PlanGCNewMessageHandler',
        'PlanGCNoticeMessageHandler',
        'PlanGCReplyMessageHandler',
        'PackageChangeMessageHandler',
        'PlannerToUserNoticeMessageHandler',
        'CreateLiveNoticeMessageHandler',
        'PlannerCircleMessageHandler',
        'PlannerCircleNoticeMessageHandler',
        'PlannerCircleLiveNoticeStartMessageHandler',
        'PlannerCircleJoinMessageHandler',
        'PlanSubExpireNoticeMessageHandler',
        'MomentsPlannerAttentionMessageHandler',
        'MomentsProducerMessageHandler',
	    'WeixinPushMessageHandler',
	    'CourseNoticeMessageHandler',
        'SilkPushMessageHandler',
        'MatchPushMessageHandler',
        'MatchTradePushMessageHandler',
        'CirclePushToUserMessageHandler',
        'TaoGuStrategyMessageHandler',
        'DynamicNewsMessageHandler',
        'DepthViewMessageHandler',
        'TaoGuWeiXinMessageHandler',
        'PlusMessageHandler',
        'FreeViewXcxMessageHandler',
        'BsBuySellPointMessageHandler',
        'CirleReplyXcxMessageHandler',
        'CircleChoiceMessageHandler',
        'AudioMessageHandler'
    );

    public function __construct(){

    }

    /**
     * 将消息放入push队列 统一处理就是为了规范队列key 和消息的通知渠道  以及用户是否关闭了通知等
     * @param array $msg_data 通知的消息体
     * @param array $uids 需要通知的用户
     * @param array $push_channel 通知的渠道
     * @return null
     */
    public function addToPushQueue($msg_data,$uids,$push_channel=array(1,2,3)){
        $uids = (array) $uids;
        //不推送的类型
        $not_push_type = array(
            1 => array(2,3,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20), //微信
            2 => array(12),  //android
            3 => array(12), //ios
            4 => array(12), //ios
            5 => array(12), //ios
            6 => array(1,2,3,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21), //作为用户端给第三方服务端推送消息
            7 => array(12), //信达android
            8 => array(12), //信达ios
            9 => array(), //财道android
            10 => array(), //财道ios
            11 => array(), //财道投教android
            12 => array(), //财道投教ios
            13 => array(), //尊享版
            14 => array(), //平台版
            15 => array(), //百万股神公众号
        );


        if(empty($uids) || empty($msg_data) || empty($push_channel)){
            return;
        }
        
        $push_channel = (array)$push_channel;
        $_push_channel = array();
        if(in_array('1',$push_channel)){
            array_push($_push_channel,'1');
            array_push($_push_channel,'15');
        }
        if(in_array('2',$push_channel)){
            array_push($_push_channel,'2');
            array_push($_push_channel,'4');
            array_push($_push_channel,'7');
            array_push($_push_channel,'9');
            array_push($_push_channel,'11');
        }
        if(in_array('3',$push_channel)){
            array_push($_push_channel,'3');
            array_push($_push_channel,'5');
            array_push($_push_channel,'8');
            array_push($_push_channel,'10');
            array_push($_push_channel,'12');
        }
        if(in_array('13',$push_channel)){
            array_push($_push_channel,'11');
            array_push($_push_channel,'12');
        }
        if(in_array('14',$push_channel)){
            array_push($_push_channel,'9');
            array_push($_push_channel,'10');
        }
        //第三方接口用户消息推送 add by danxian 2016/12/27
        if(in_array('6', $push_channel)){
            array_push($_push_channel,6);
        }

        if(in_array('7', $push_channel)){
            array_push($_push_channel,7);
        }

        if(in_array('8', $push_channel)){
            array_push($_push_channel,8);
        }

        if(in_array('9', $push_channel)){
            array_push($_push_channel,9);
        }

        if(in_array('10', $push_channel)){
            array_push($_push_channel,10);
        }

        if(in_array('11', $push_channel)){
            array_push($_push_channel,11);
        }

        if(in_array('12', $push_channel)){
            array_push($_push_channel,12);
        }
        if(in_array('15', $push_channel)){
            array_push($_push_channel,15);
        }
        echo "=============被推送的渠道\r\n";
        var_dump($push_channel);

        $_uids = array();
        foreach($uids as $uid){
            $uid = intval($uid);
            $uid>0 && $_uids[]=$uid;
        }

        if(!empty($_uids)){
            $uids=$_uids;
        }else{
            return;
        }
        unset($_uids);
        $uids = Message::model()->filterCloseUids($uids,$msg_data['u_type'],$msg_data['type'],2);
        if(empty($uids)){
            return;
        }
        
        $user_channel = Message::model()->getChannelUserInfoByUid($uids,$msg_data['u_type']);
        //echo 'weixin_user:',json_encode($uids),"\n";
        //echo 'weixin_user:',json_encode($user_channel),"\n";
        $weixin_user = array();

        $android_user = array();
        $ios_user = array();

        $getui_android_user = array();
        $getui_ios_user = array();

        $getui_android_xinda_user = array();
        $getui_ios_xinda_user = array();

        $getui_android_caidao_user= array();
        $getui_ios_caidao_user = array();

        $getui_ios_caidao_tj_user = array();
        $getui_android_caidao_tj_user = array();
        
        $weixin_user_bwgs = array();
        
        $third_user = array(); //第三方用户

        if(!empty($user_channel)){
            foreach($user_channel as $val){
                //验证消息指定渠道的用户
                if(!in_array($val['channel_type'],$_push_channel)){
                    continue;
                }
                if($val['channel_type']==1 && !in_array($msg_data['type'],$not_push_type[1])){
                    array_push($weixin_user,$val);
                }elseif($val['channel_type']==2 && !in_array($msg_data['type'],$not_push_type[2])){
                    array_push($android_user,$val);
                }elseif($val['channel_type']==3 && !in_array($msg_data['type'],$not_push_type[3])){
                    array_push($ios_user,$val);
                }elseif($val['channel_type']==4 && !in_array($msg_data['type'],$not_push_type[4])){
                    array_push($getui_android_user,$val);
                }elseif($val['channel_type']==5 && !in_array($msg_data['type'],$not_push_type[5])){
                    array_push($getui_ios_user,$val);
                }elseif($val['channel_type']==6 && !in_array($msg_data['type'],$not_push_type[6])){
                    //第三方接口用户消息推送 add by danxian 2016/12/27
                    array_push($third_user,$val);
                }elseif($val['channel_type']==7 && !in_array($msg_data['type'],$not_push_type[7])){
                    ///信达android
                    array_push($getui_android_xinda_user,$val);
                }elseif($val['channel_type']==8 && !in_array($msg_data['type'],$not_push_type[8])){
                    ///信达ios
                    array_push($getui_ios_xinda_user,$val);
                }elseif($val['channel_type']==9 && !in_array($msg_data['type'],$not_push_type[9])){
                    ///财道android
                    array_push($getui_android_caidao_user,$val);
                }elseif($val['channel_type']==10 && !in_array($msg_data['type'],$not_push_type[10])){
                    ///财道ios
                    array_push($getui_ios_caidao_user,$val);
                }elseif($val['channel_type']==11 && !in_array($msg_data['type'],$not_push_type[11])){
                    ///财道投教andiroid
                    array_push($getui_android_caidao_tj_user,$val);
                }elseif($val['channel_type']==12 && !in_array($msg_data['type'],$not_push_type[12])){
                    ///财道投教ios
                    array_push($getui_ios_caidao_tj_user,$val);
                }elseif($val['channel_type']==15 && !in_array($msg_data['type'],$not_push_type[12])){
                    ///百万股神公共号
                    array_push($weixin_user_bwgs,$val);
                }
            }
        }
        echo "苹果投教版推送\r\n";
        var_dump($getui_ios_caidao_tj_user);

        echo "安卓投教版推送\r\n";        
        var_dump($getui_android_caidao_tj_user);
        
        echo "苹果理财师推送\r\n";        
        var_dump($getui_ios_caidao_user);
        echo "安卓理财师推送\r\n";
        var_dump($getui_android_caidao_user);
        echo "微信百万股神推送\r\n";
        var_dump($weixin_user_bwgs);

        //echo 'android_user:',json_encode($android_user),"\n";
        //echo 'ios_user:',json_encode($ios_user),"\n";
        //echo 'weixin_user:',json_encode($weixin_user),"\n";

        if(!empty($android_user)){
            //channel_type push_message   push_user
            $push_data['channel_type']=2;
            $push_data['push_message']=$msg_data;
            $push_data['push_user']=$android_user;
            Yii::app()->redis_w->rPush(SinaSpnsPushQueue::QUEUE_KEY,json_encode($push_data,JSON_UNESCAPED_UNICODE));
        }

        if(!empty($ios_user)){
            $push_data['channel_type']=3;
            $push_data['push_message']=$msg_data;
            $push_data['push_user']=$ios_user;
            Yii::app()->redis_w->rPush(SinaSpnsPushQueue::QUEUE_KEY,json_encode($push_data,JSON_UNESCAPED_UNICODE));
        }

        if(!empty($weixin_user)){
            foreach($weixin_user as $channel_user){
                $channel_user['message'] = $msg_data;
                Yii::app()->redis_w->rPush(WeiXinMessagePushQueue::QUEUE_KEY,json_encode($channel_user,JSON_UNESCAPED_UNICODE));
            }
        }

        if(!empty($weixin_user_bwgs)){
            foreach($weixin_user_bwgs as $channel_user){
                $channel_user['message'] = $msg_data;
                Yii::app()->redis_w->rPush(WeiXinMessagePushQueue::QUEUE_KEY,json_encode($channel_user,JSON_UNESCAPED_UNICODE));
            }
        }

        //第三方接口用户消息推送 add by danxian 2016/12/27
       if(!empty($third_user)){
            foreach ($third_user as $channel_user) {
                $channel_user['message'] = $msg_data;
                Yii::app()->redis_w->rPush(ThirdPartyPushQueue::QUEUE_KEY,json_encode($channel_user,JSON_UNESCAPED_UNICODE));
            }
       }


        if(!empty($getui_android_user)){
            //channel_type push_message   push_user
            $push_data['channel_type']=4;
            $push_data['push_message']=$msg_data;
            $push_data['push_user']=$getui_android_user;
            Yii::app()->redis_w->rPush(GetuiPushQueue::QUEUE_KEY,json_encode($push_data,JSON_UNESCAPED_UNICODE));
        }

        if(!empty($getui_ios_user)){
            $push_data['channel_type']=5;
            $push_data['push_message']=$msg_data;
            $push_data['push_user']=$getui_ios_user;
            Yii::app()->redis_w->rPush(GetuiPushQueue::QUEUE_KEY,json_encode($push_data,JSON_UNESCAPED_UNICODE));
        }

        if(!empty($getui_android_xinda_user)){
            $push_data['channel_type']=7;
            $push_data['push_message']=$msg_data;
            $push_data['push_user']=$getui_android_xinda_user;
            Yii::app()->redis_w->rPush(GetuiPushQueue::QUEUE_KEY,json_encode($push_data,JSON_UNESCAPED_UNICODE));
        }

        if(!empty($getui_ios_xinda_user)){
            $push_data['channel_type']=8;
            $push_data['push_message']=$msg_data;
            $push_data['push_user']=$getui_ios_xinda_user;
            Yii::app()->redis_w->rPush(GetuiPushQueue::QUEUE_KEY,json_encode($push_data,JSON_UNESCAPED_UNICODE));
        }
        
        if(!empty($getui_android_caidao_user)){
            $push_data['channel_type']=9;
            $push_data['push_message']=$msg_data;
            $push_data['push_user']=$getui_android_caidao_user;
            Yii::app()->redis_w->rPush(GetuiPushQueue::QUEUE_KEY,json_encode($push_data,JSON_UNESCAPED_UNICODE));
        }

        if(!empty($getui_ios_caidao_user)){
            $push_data['channel_type']=10;
            $push_data['push_message']=$msg_data;
            $push_data['push_user']=$getui_ios_caidao_user;
            Yii::app()->redis_w->rPush(GetuiPushQueue::QUEUE_KEY,json_encode($push_data,JSON_UNESCAPED_UNICODE));
        }

        if(!empty($getui_android_caidao_tj_user)){
            $push_data['channel_type']=11;
            $push_data['push_message']=$msg_data;
            $push_data['push_user']=$getui_android_caidao_tj_user;
            Yii::app()->redis_w->rPush(GetuiPushQueue::QUEUE_KEY,json_encode($push_data,JSON_UNESCAPED_UNICODE));
        }

        if(!empty($getui_ios_caidao_tj_user)){
            $push_data['channel_type']=12;
            $push_data['push_message']=$msg_data;
            $push_data['push_user']=$getui_ios_caidao_tj_user;
            Yii::app()->redis_w->rPush(GetuiPushQueue::QUEUE_KEY,json_encode($push_data,JSON_UNESCAPED_UNICODE));
        }

    }

    //检查必须参数  否则返回exception
    public function checkRequireParam($params,$fields){
        if(empty($params)){
            throw new Exception('参数为空');
        }
        if(!empty($fields)){
            foreach($fields as $field){
                if(!isset($params[$field])){
                    throw new Exception('缺少参数：'.$field);
                }
            }
        }
    }


    /**
     * 添加用户的其他通知渠道队列
     *
     * 1. 第三方微信公众号
     *
     * @param $msg_data
     * @param $uids
     */
    public function addToPushOtherQueue($msg_data,$uids){

        if(empty($uids)){
            return;
        }

        //首先获取用户的来源 根据来源判断通知渠道
        $sources_map = User::model()->getUserSource($uids);
        if(empty($sources_map)){
            return;
        }
        $channel_ids = array();
        foreach ($sources_map as $source){
            if($source['channel']=="partner"){
                $channel_ids[] = $source['channel_id'];
            }
        }
        $channel_ids = array_unique($channel_ids);
        if(empty($channel_ids)){
            return;
        }

        $partner_map = Partner::model()->getPartnerByAppKey($channel_ids);
        if(empty($partner_map)){
            return;
        }

        foreach ($uids as $uid){
            // 根据  appkey 获取通知渠道信息
            $notice_app_type = $partner_map[$sources_map[$uid]['channel_id']]['notice_app_type'];
            if($notice_app_type=='wx_public_template'){
                //微信模板消息数据放入队列 数据结构  uid  channel_id  wx_app_id message
                $push_data['message']=$msg_data;
                $push_data['uid']=$uid;
                $push_data['wx_app_id']=$partner_map[$sources_map[$uid]['channel_id']]['notice_app_key'];
                $push_data['channel_id'] = $sources_map[$uid]['channel_uid'];
                Yii::app()->redis_w->rPush(WeiXinThirdMsgPushQueue::QUEUE_KEY,json_encode($push_data,JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
