<?php

/**
 * 消息处理队列
 * 1. 处理消息逻辑
 * 2. 放入push队列
 * @datetime 2015-12-6  16:00:36
 * @author hailin3
 */
class MessageQueue {
    const CRON_NO = 1308; //任务代码

    public function __construct(){

    }


    /**
     * 处理快速通知的队列
     */
    public function processFastMessageQueue(){
        //退出时间 每次随机向后推30-150秒
        $stop_time = time()+rand(3,5)*15;

        //Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "start processFastMessageQueue end time:".date('Y-m-d H:i:s',$stop_time));

        $redis_key="lcs_fast_message_queue";
        $tick = 0;
        while (true){
            if(time()>$stop_time){
                //Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "processFastMessageQueue 到达时间退出");
                break;
            }

            if($tick%10==0){
                Yii::app()->lcs_r->setActive(false);
                Yii::app()->lcs_r->setActive(true);

                Yii::app()->lcs_w->setActive(false);
                Yii::app()->lcs_w->setActive(true);

                Yii::app()->lcs_comment_r->setActive(false);
                Yii::app()->lcs_comment_r->setActive(true);

                Yii::app()->lcs_comment_w->setActive(false);
                Yii::app()->lcs_comment_w->setActive(true);

                Yii::app()->lcs_standby_r->setActive(false);
                Yii::app()->lcs_standby_r->setActive(true);
            }
            $tick = $tick + 1;

            $msg = Yii::app()->redis_w->lPop($redis_key);
            if(!empty($msg)){
                $this->processMessage($msg,$redis_key);
            }else{
                //队列无数据，睡眠2秒钟
                sleep(1);
            }
        }
    }


    /**
     * 处理普通通知队列
     */
    public function processCommonMessageQueue(){
        //退出时间 每次随机向后推30-150秒
        $stop_time = time()+rand(3,5)*15;

        //Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "start processCommonMessageQueue end time:".date('Y-m-d H:i:s',$stop_time));

        $redis_key="lcs_common_message_queue";
        $tick = 0;
        while (true){
            if(time()>$stop_time){
                //Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "processCommonMessageQueue 到达时间退出");
                break;
            }

            if($tick%10==0){
                Yii::app()->lcs_r->setActive(false);
                Yii::app()->lcs_r->setActive(true);

                Yii::app()->lcs_w->setActive(false);
                Yii::app()->lcs_w->setActive(true);

                Yii::app()->lcs_comment_r->setActive(false);
                Yii::app()->lcs_comment_r->setActive(true);

                Yii::app()->lcs_comment_w->setActive(false);
                Yii::app()->lcs_comment_w->setActive(true);

                Yii::app()->lcs_standby_r->setActive(false);
                Yii::app()->lcs_standby_r->setActive(true);
            }
            $tick = $tick + 1;

            $msg = Yii::app()->redis_w->lPop($redis_key);
            if(!empty($msg)){
                $this->processMessage($msg,$redis_key);
            }else{
                //队列无数据，睡眠5秒钟
                sleep(3);
            }
        }
    }

    /**
     * 处理消息
     * @param $msg
     * @param $queue_key
     */
    public function processMessage($msg,$queue_key){
        if(empty($msg)){
            return;
        }
        echo date("Y-m-d H:i:s")," ",$msg,"\r\n";
        $queue_log_id = Message::model()->saveMessageQueueLog(array('queue_key'=>$queue_key,'queue_data'=>$msg,'status'=>0));
        try{
            $msg_json = json_decode($msg,true);
            //消息体中没有类型直接排除掉
            if(!isset($msg_json['type'])){
                throw new Exception('not find type field');
            }
            $msg_json['queue_log_id']=$queue_log_id;

            $class_name = ucfirst($msg_json['type'].'MessageHandler');
            $func_name=$msg_json['type'].'MessageHandler';
            //要先验证文件存在不存在，否则验证class是否存在的时候会报错误
            /*$class_name_arr = array(
                'OperateNoticeMessageHandler',
                'PkgPlannerCommentMessageHandler',
                'PlanPlannerCommentMessageHandler',
                'QuestionAnswerMessageHandler',
                'QuestionScoreMessageHandler',
                'ReplayCommentMessageHandler',
                'ReplayCommentNewMessageHandler',
                'PlanTransactionMessageHandler');*/
            $class_name_arr = CommonHandler::$handler_fun;
            if(in_array($class_name,$class_name_arr)&&class_exists($class_name)){
                $_class = new $class_name();
                $_class->run($msg_json);
            }else if(method_exists($this,$func_name)){
                $this->$func_name($msg_json);
            }else{
                throw new Exception('not find func:'.$func_name);
            }
        }catch (Exception $e){
            if(!empty($queue_log_id)){
                Message::model()->updateMessageQueueLog(array('status'=>-1,'result'=>$e->getMessage()), $queue_log_id);
            }
        }
    }


    /**
     * 将消息放入push队列 统一处理就是为了规范队列key 和消息的通知渠道  以及用户是否关闭了通知等
     * @param array $msg_data 通知的消息体
     * @param array $uids 需要通知的用户
     * @param array $push_channel 通知的渠道
     * @return null
     */
    private function addToPushQueue($msg_data,$uids,$push_channel=array(1,2,3)){
        $uids = (array) $uids;
        //不推送的类型
        $not_push_type = array(
            1 => array(2,3,6,7,8,9,10,11,12,13,14,15,16), //微信
            2 => array(),  //android
            3 => array() //ios
        );


        if(empty($uids) || empty($msg_data) || empty($push_channel)){
            return;
        }

        $push_channel = (array)$push_channel;
        /*$user_channel = Message::model()->getChannelUserInfoByUid($uids,$msg_data['u_type']);
        if(!empty($user_channel)){
            $_user_channel=array();
            $_uids= array();
            if($msg_data['u_type']==1){
                foreach($user_channel as $val){
                    $_user_channel[$val['uid']]=$val;
                    $_uids[]=$val['uid'];
                }
            }else{
                foreach($user_channel as $val){
                    $_user_channel[$val['s_uid']]=$val;
                }
                $_uids[]=$val['s_uid'];
            }
            //个性化 去掉关闭提醒的uid
            $uids_close = Message::model()->getCloseMessageUsers($_uids,$msg_data['u_type'],$msg_data['type'],2);
            if(!empty($uids_close)){
                foreach($uids_close as $_uid){
                    unset($_user_channel[$_uid]);
                }
            }
            $user_channel = array_values($_user_channel);
        }*/
        $uids = Message::model()->filterCloseUids($uids,$msg_data['u_type'],$msg_data['type'],2);
        $user_channel = Message::model()->getChannelUserInfoByUid($uids,$msg_data['u_type']);
        //echo 'weixin_user:',json_encode($uids),"\n";
        //echo 'weixin_user:',json_encode($user_channel),"\n";
        $weixin_user = array();
        $android_user = array();
        $ios_user = array();
        if(!empty($user_channel)){
            foreach($user_channel as $val){
                //验证消息指定渠道的用户
                if(!in_array($val['channel_type'],$push_channel)){
                    continue;
                }
                if($val['channel_type']==1 && !in_array($msg_data['type'],$not_push_type[1])){
                    array_push($weixin_user,$val);
                }elseif($val['channel_type']==2 && !in_array($msg_data['type'],$not_push_type[2])){
                    array_push($android_user,$val);
                }elseif($val['channel_type']==3 && !in_array($msg_data['type'],$not_push_type[3])){
                    array_push($ios_user,$val);
                }
            }
        }

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
    }


    //////////////////////////////////////////////////////////////
    //
    // 具体的消息处理
    //
    //////////////////////////////////////////////////////////////


    /**
     * 问题评价提醒
     * @param $msg    type=questionScore  q_id  p_uid  score  uid u_name u_image score_reason
     */
    /*
    private function questionScoreMessageHandler($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try{
            $this->checkRequireParam($msg,array('q_id','p_uid','score','uid','u_name','u_image','score_reason'));

            $msg_data = array(
                'uid'=>$msg['p_uid'],
                'u_type'=>2,  //1普通用户   2理财师
                'type'=>1,
                'relation_id'=>$msg['q_id'],
                'child_relation_id'=>0,
                'content'=>json_encode(array(
                    array('value'=>$msg['u_name'].'给您的回答做了评价','class'=>'','link'=>'')
                ),JSON_UNESCAPED_UNICODE),
                'link_url'=>'/ask/'.$msg['q_id'],
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );

            $content_client['status']=6;
            $content_client['q_id']=$msg['q_id'];
            $content_client['uid']=$msg['uid'];
            $content_client['u_name']=$msg['u_name'];
            $content_client['u_image']=$msg['u_image'];
            $content_client['score']=$msg['score'];
            $content_client['score_reason']=$msg['score_reason'];
            $content_client['time']= date("Y-m-d H:i:s");

            $msg_data['content_client']=json_encode($content_client,JSON_UNESCAPED_UNICODE);

            //保存通知消息
            Message::model()->saveMessage($msg_data);

            //加入提醒队列
            $msg_data['content'] = json_decode($msg_data['content'],true);
            $channel_user = Message::model()->getChannelUidBySuids($msg['p_uid']);
            $this->addToPushQueue($msg_data,$channel_user,array (2,3));

            $log_data['uid']=$msg['p_uid'];
            $log_data['relation_id']=$msg['q_id'];
        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }*/

    //检查必须参数  否则返回exception
    private function checkRequireParam($params,$fields){
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
     *
     * 评论赞提醒
     * @param $msg  type=commentPraise  cmn_id  cmn_type  u_type  uid cu_name cu_image
     */
    /** TODO 未使用

    private function commentPraiseMessageHandler($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try{
            //
            $this->checkRequireParam($msg,array('cmn_id','cmn_type','u_type','uid','cu_name','cu_image'));

            $msg_data = array(
                'uid'=>$msg['uid'],
                'u_type'=>$msg['u_type'],  //1普通用户   2理财师
                'type'=>13,
                'relation_id'=>$msg['cmn_id'],
                'child_relation_id'=>0,
                'content'=>json_encode(array(
                    array('value'=>$msg['cu_name'].'赞了您的说说','class'=>'','link'=>'')
                ),JSON_UNESCAPED_UNICODE),
                'link_url'=>'',
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );

            $content_client['cmn_type']=$msg['cmn_type'];
            $content_client['cmn_id']=$msg['cmn_id'];
            $content_client['name']=$msg['cu_name'];
            $content_client['image']=$msg['cu_image'];

            $msg_data['content_client']=json_encode($content_client,JSON_UNESCAPED_UNICODE);

            //保存通知消息
            Message::model()->saveMessage($msg_data);

            //加入提醒队列
            $msg_data['content'] = json_decode($msg_data['content'],true);
            $push_uid=array();
            if($msg_data['u_type']==2){
                $push_uid[]=User::model()->getUidBySuid($msg['uid']);
            }else if($msg_data['u_type']==1){
                $push_uid[]=$msg_data['uid'];
            }

            $this->addToPushQueue($msg_data,$push_uid ,array (2,3));

            $log_data['uid']=$msg['uid'];
            $log_data['relation_id']=$msg['cmn_id'];
        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }
     *
     */
    /**
     * 新说说提醒  不记录数据库
     * @param $msg   type=commentNew cmn_type  cmn_id relation_id  u_type uid name image content
     */
    /** TODO 未使用
    private function commentNewMessageHandler($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try{
            //
            $this->checkRequireParam($msg,array('cmn_id','cmn_type','u_type','uid','name','image','content','relation_id'));

            $msg_data = array(
                'uid'=>0,
                'u_type'=>1,  //1普通用户   2理财师
                'type'=>14, //未读新说说
                'relation_id'=>$msg['cmn_id'],
                'child_relation_id'=>$msg['relation_id'],
                'content'=>json_encode(array(
                    array('value'=>$msg['name'].'：'.$msg['content'],'class'=>'','link'=>'')
                ),JSON_UNESCAPED_UNICODE),
                'link_url'=>'',
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );

            $content_client['cmn_type']=$msg['cmn_type'];
            $content_client['cmn_id']=$msg['cmn_id'];
            $content_client['name']=$msg['name'];
            $content_client['image']=$msg['image'];
            $content_client['content']=$msg['content'];

            $msg_data['content_client']=json_encode($content_client,JSON_UNESCAPED_UNICODE);

            //保存通知消息
            //Message::model()->saveMessage($msg_data);

            $msg_user_1 = array(); //用户
            $msg_user_2 = array(); //理财师
            if($msg['cmn_type']==1){ //计划
                //获取计划的购买用户
                $plan_users = Plan::model()->getPlanSubInfoByPlanIds(array($msg['relation_id']), array('id','uid'));
                if($msg['u_type']==1 || $msg['u_type']==3){ //如果发说说的不是计划的创建者 还要获取计划的创建者
                    $plan_infos = Plan::model()->getPlanInfoByIds(array($msg['relation_id']), array('pln_id','p_uid'));
                    if(!empty($plan_infos)){
                        $plan_info = current($plan_infos);
                        $p_uid = !empty($plan_info)?$plan_info['p_uid']:'';
                        if(!empty($p_uid)){
                            $msg_user_2[] = $p_uid;
                        }
                    }
                }
                if(!empty($plan_users)){
                    foreach($plan_users as $user){
                        if($msg['u_type']==1 && $msg['uid']==$user['uid']){
                            continue;
                        }else{
                            if(!empty($user['uid'])){
                                $msg_user_1[] = $user['uid'];
                            }

                        }
                    }
                }
            }else if($msg['cmn_type']==2){ //观点  relation_id
                $pkg_sub_uids = Package::model()->getSubscriptionUid($msg['relation_id']);
                if($msg['u_type']==1 || $msg['u_type']==3){ //如果发说说的不是计划的创建者 还要获取计划的创建者
                    $pkg_infos = Package::model()->getPackageInfoByIds(array($msg['relation_id']), array('id','p_uid'));
                    if(!empty($pkg_infos)){
                        $pkg_info = current($pkg_infos);
                        $p_uid = !empty($pkg_info)?$pkg_info['p_uid']:'';
                        if(!empty($p_uid)){
                            $msg_user_2[] = $p_uid;
                        }
                    }
                }

                if(!empty($pkg_sub_uids)){
                    foreach($pkg_sub_uids as $uid){
                        if($msg['u_type']==1 && $msg['uid']==$uid){
                            continue;
                        }else{
                            if(!empty($uid)) {
                                $msg_user_1[] = $uid;
                            }
                        }
                    }
                }
            }

            //加入提醒队列
            $msg_data['content'] = json_decode($msg_data['content'],true);
            if(!empty($msg_user_1)){
                $msg_data['u_type']=1;
                $this->addToPushQueue($msg_data,$msg_user_1,array (2,3));

            }
            if(!empty($msg_user_2)){
                $msg_data['u_type']=2;
                $p_uid = current($msg_user_2);
                $uid=User::model()->getUidBySuid($p_uid);
                if(!empty($uid)){
                    $this->addToPushQueue($msg_data,array($uid),array (2,3));
                }

            }


            $log_data['ext_data']=json_encode(array('uids'=>$msg_user_1,'p_uids'=>$msg_user_2));
            $log_data['relation_id']=$msg['cmn_id'];
        }catch(Exception $e){
            $log_data['ext_data']=json_encode(array('uids'=>$msg_user_1,'p_uids'=>$msg_user_2));
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }

     */
    /**
     * 订单退款提醒
     */
    private function orderRefundMessageHandler($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            $this->checkRequireParam($msg,array('order_no'));
            $order_no = $msg['order_no'];
            $order_info = Yii::app()->lcs_r->createCommand("select id,uid,status from lcs_orders where order_no='$order_no'")->queryRow();
            if (!isset($order_info['status']) || !in_array($order_info['status'], array(-2, 4))) {
                throw new Exception('status!=-2 || status!=4：');
            }
            $msg_data = array(
                'uid' => $order_info['uid'],
                'u_type' => 1,
                'type' => 3,
                'relation_id' => $order_info['id'],
                'content' => json_encode(array(
                    array('value' => '编号', 'class' => '', 'link' => ''),
                    array('value' => $order_no, 'class' => '', 'link' => '/web/ordersinfo?order_no=' . $order_no),
                    array('value' => $order_info['status'] == 4 ? '的订单已退款成功，请查看您的账户' : '的订单退款失败，请联系客服处理失败订单', 'class' => '', 'link' => ''),
                ), JSON_UNESCAPED_UNICODE),
                'content_client' => json_encode(array(
                    'type' => $order_info['status'] == 4 ? 1 : 2,
                    'order_no' => $order_no
                ), JSON_UNESCAPED_UNICODE),
                'link_url' => '/web/ordersinfo?order_no=' . $order_no,
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );

            //保存通知消息
            Message::model()->saveMessage($msg_data);

            //加入通知队列
            $this->addToPushQueue($msg_data, $msg_data['uid'], array(2, 3));

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

    /**
     * 观点包到期提醒
     */
    /*private function packageExpireMessageHandler($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            $this->checkRequireParam($msg,array('uid','pkg_id','pkg_title','day'));

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
            $this->addToPushQueue($msg_data, $msg_data['uid'], array(2, 3));

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
    }*/
    
    /**
     * 计划特权购买提醒
     */
    private function planPrivilegeMessageHandler($msg){
        $log_data = array('status'=>1,'result'=>'ok','uid'=>'','relation_id'=>'','ext_data'=>'');
        try {
            $this->checkRequireParam($msg, array('pln_id'));
            $pln_id = $msg['pln_id'];
            $privilege_id = Yii::app()->lcs_r->createCommand("select privilege_id from lcs_panic_buy where relation_id=$pln_id")->queryScalar();

            if ($privilege_id <= 0) {
                throw new Exception('privilege_id <= 0');
            }
            $sub_uid = Yii::app()->lcs_r->createCommand("select uid from lcs_plan_subscription where pln_id=$privilege_id and status>0")->queryColumn();
            if(empty($sub_uid)){
                throw new Exception('sub_uid 为空');
            }

            $plan_info = Plan::model()->getPlanInfoById($pln_id);
            $planner_info = Planner::model()->getPlannerById(array($plan_info['p_uid']));
            $planner_info = isset($planner_info[$plan_info['p_uid']]) ? $planner_info[$plan_info['p_uid']] : array();


            foreach ($sub_uid as $uid) {
                $msg_data = array(
                    'uid' => $uid,
                    'u_type' => 1,
                    'type' => 3,
                    'relation_id' => $pln_id,
                    'content' => json_encode(array(
                        array('value' => $planner_info['name'], 'class' => '', 'link' => '/planner/' . $plan_info['p_uid'] . '/1'),
                        array('value' => '发布了新一期计划', 'class' => '', 'link' => ''),
                        array('value' => "《" . CHtml::encode($plan_info['name']) . "》", 'class' => '', 'link' => "/plan/" . $pln_id),
                        array('value' => "您拥有老用户特权可优先购买。", 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'type' => 3,
                        'planner_name' => $planner_info['name'],
                        'plan_name' => $plan_info['name'],
                        'panic_buy_time' => $plan_info['panic_buy_time']
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "/plan/" . $pln_id,
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );

                //保存通知消息
                Message::model()->saveMessage($msg_data);

                //加入通知队列
                $this->addToPushQueue($msg_data, $msg_data['uid'], array(2, 3));
                $log_data['ext_data'][]=array('uid'=>$msg_data['uid'],'relation_id'=>$msg_data['relation_id']);

            }//foreach
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


    /**
     *  计划提醒
     * @param #msg type=planChange pln_id
     */
    /*
    private function planChangeMessageHandler($msg){
        $log_data = array('status'=>1,'result'=>'ok','uid'=>'','relation_id'=>'','ext_data'=>'');
        try {
            $this->checkRequireParam($msg, array('pln_id'));
            $pln_id = $msg['pln_id'];
            $plan_infos = Plan::model()->getPlanInfoByIds($pln_id);
            $plan_info = !empty($plan_infos) && count($plan_infos) > 0 ? current($plan_infos) : array();
            if (empty($plan_info)) {
                throw new Exception('plan_info 为空');
            }
            $plan_name = isset($plan_info['name']) ? $plan_info['name'] : '';
            $plan_name .= (isset($plan_info['number']) && $plan_info['number'] > 9 ? $plan_info['number'] : "0" . $plan_info['number']) . "期";


            $msg_data = array();
            if($plan_info['status'] == 3) {//运行中
                $msg_data = array(
                    'uid' => $plan_info['p_uid'],
                    'u_type' => 2,
                    'type' => 15,
                    'relation_id' => $plan_info['pln_id'],
                    'content' => json_encode(array(
                        array('value' => "《" . $plan_name . "》已开始运行", 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'status' => 3,//todo
                        'pln_id' => $plan_info['pln_id'],
                        'pln_name' => CHtml::encode($plan_name),
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "/plan/" . $plan_info['pln_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            } else if($plan_info['status'] == 4){//运行成功
                $msg_data = array(
                    'uid' => $plan_info['p_uid'],
                    'u_type' => 2,
                    'type' => 15,
                    'relation_id' => $plan_info['pln_id'],
                    'content' => json_encode(array(
                        array('value' => "祝贺《" . $plan_name . "》计划目标达成，再接再厉发起新计划", 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'status' => 4,
                        'pln_id' => $plan_info['pln_id'],
                        'pln_name' => CHtml::encode($plan_name),
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "/plan/" . $plan_info['pln_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            } else if($plan_info['status'] == 5){//运行失败
                $msg_data = array(
                    'uid' => $plan_info['p_uid'],
                    'u_type' => 2,
                    'type' => 15,
                    'relation_id' => $plan_info['pln_id'],
                    'content' => json_encode(array(
                        array('value' => "很遗憾《" . $plan_name . "》计划未完成，可重新发起计划", 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'status' => 5,
                        'pln_id' => $plan_info['pln_id'],
                        'pln_name' => CHtml::encode($plan_name),
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "/plan/" . $plan_info['pln_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            }else if($plan_info['status'] == 6){//止损失败
                $msg_data = array(
                    'uid' => $plan_info['p_uid'],
                    'u_type' => 2,
                    'type' => 15,
                    'relation_id' => $plan_info['pln_id'],
                    'content' => json_encode(array(
                        array('value' => "很遗憾《" . $plan_name . "》计划触及止损，可重新发起计划", 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'status' => 6,
                        'pln_id' => $plan_info['pln_id'],
                        'pln_name' => CHtml::encode($plan_name),
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "/plan/" . $plan_info['pln_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            }else if($plan_info['status'] == 7){//到期冻结
                $msg_data = array(
                    'uid' => $plan_info['p_uid'],
                    'u_type' => 2,
                    'type' => 15,
                    'relation_id' => $plan_info['pln_id'],
                    'content' => json_encode(array(
                        array('value' => "很遗憾《" . $plan_name . "》计划未完成，可重新发起计划", 'class' => '', 'link' => '')
                    ), JSON_UNESCAPED_UNICODE),
                    'content_client' => json_encode(array(
                        'status' => 7,
                        'pln_id' => $plan_info['pln_id'],
                        'pln_name' => CHtml::encode($plan_name),
                    ), JSON_UNESCAPED_UNICODE),
                    'link_url' => "/plan/" . $plan_info['pln_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
            }
            if(empty($msg_data)){
                throw new Exception($pln_id.': msg_data 推送消息为空');
            }
            //保存通知消息
            Message::model()->saveMessage($msg_data);

            //加入通知队列
            $push_uid=0;
            if($msg_data['u_type']==2){
                $push_uid = User::model()->getUidBySuid($plan_info['p_uid']);
            }else{
                $push_uid = $msg_data['uid'];
            }
            $this->addToPushQueue($msg_data, $push_uid, array(2, 3));
            $log_data['uid']=$msg_data['uid'];
            $log_data['relation_id'] = $msg_data['relation_id'];


        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }*/

    /**
     * 计划交易提醒
     * @param $msg type=planTransaction tran_id
     */
    /*private function planTransactionMessageHandler($msg){
        $log_data = array('status'=>1,'result'=>'ok','uid'=>'','relation_id'=>'','ext_data'=>'');
        try {
            $this->checkRequireParam($msg, array('tran_id'));
            $trans_map = Plan::model()->getPlanTransactionByIds($msg['tran_id']);
            if(empty($trans_map)){
                sleep(1);//未获取数据休眠1秒
                $trans_map = Plan::model()->getPlanTransactionByIds($msg['tran_id']);
            }
            if(empty($trans_map)){
                sleep(1);//未获取数据休眠1秒
                $trans_map = Plan::model()->getPlanTransactionByIds($msg['tran_id']);
            }
            $tran_info = isset($trans_map[$msg['tran_id']])?$trans_map[$msg['tran_id']]:null;
            if(empty($tran_info)){
                throw new Exception('计划的交易信息不存在'.$msg['tran_id']);
            }

            //订阅用户
            //$sub_uids = $this->getSubPlanUids($tran_info['pln_id']);
            $sub_uids = Yii::app()->lcs_r->createCommand("select uid from lcs_plan_subscription where pln_id=".intval($tran_info['pln_id'])." and status>0")->queryColumn();
            //TODO 测试
            $test_uids = array("3","46","17248545","105","1488","10923084");
            $sub_uids=array_intersect($sub_uids, $test_uids);
            //$sub_uids=in_array("3",$sub_uids)?array('3'):array();
            //个性化 去掉关闭提醒的uid
            $sub_uids = Message::model()->filterCloseUids($sub_uids,1,4,1);
            //计划信息
            $plan_info = Yii::app()->lcs_r->createCommand("select name,number,init_value,p_uid from lcs_plan_info where pln_id=".$tran_info['pln_id']." limit 1")->queryRow();
            if(isset($plan_info['name'])){
                $plan_info['name'] .= ($plan_info['number']>9 ? $plan_info['number'] : "0".$plan_info['number'])."期";
            }
            //股票名称
            $stock_name= Yii::app()->lcs_r->createCommand("select name from lcs_ask_tags where type='stock_cn' and symbol='".$tran_info['symbol']."' limit 1")->queryScalar();
            //理财师
            $planner_info = Planner::model()->getPlannerById(array(intval($plan_info['p_uid'])));
            $planner_info = isset($planner_info[intval($plan_info['p_uid'])]) ? $planner_info[intval($plan_info['p_uid'])] : array();
            if(!empty($planner_info)&&isset($planner_info['company_id'])){
                $companys = Common::model()->getCompany($planner_info['company_id']);
                if(!empty($companys)&&isset($companys[$planner_info['company_id']])){
                    $planner_info['company']=$companys[$planner_info['company_id']]['name'];
                }
            }


            if(!empty($sub_uids)){
                $msg_id=0;
                $msg_data = array(
                    'uid'=>'',
                    'u_type'=>1,
                    'type'=>4,
                    'relation_id'=>$tran_info['pln_id'],
                    'child_relation_id'=>$tran_info['id'],
                    'content'=>json_encode(array(
                        array('value'=>'您购买的计划','class'=>'','link'=>''),
                        array('value'=>"《".$plan_info['name']."》",'class'=>'','link'=>"/plan/".$tran_info['pln_id']."?type=dynamic"),
                        array('value'=>sprintf("%.2f",$tran_info['deal_price'])."元".($tran_info['type']==1 ? '买入' : '卖出'),'class'=>'','link'=>''),
                        array('value'=>$stock_name."（".$tran_info['symbol']."）",'class'=>'','link'=>'/s/'.$stock_name),
                        array('value'=>$tran_info['deal_amount']."股",'class'=>'','link'=>'')
                    ),JSON_UNESCAPED_UNICODE),
                    'content_client'=>json_encode(array(
                        'pln_id'=>$tran_info['pln_id'],
                        'plan_name'=>$plan_info['name'],
                        'planner_name'=>$planner_info['name'],
                        'deal_price'=>sprintf("%.2f",$tran_info['deal_price']),
                        'trans_type'=> $tran_info['type'],
                        'stock_name'=>$stock_name,
                        'symbol'=>$tran_info['symbol'],
                        'deal_amount'=>$tran_info['deal_amount'],
                        'total_price'=>sprintf("%.2f",$tran_info['deal_price']*$tran_info['deal_amount']),
                        'wgt_before'=>sprintf("%.2f",$tran_info['wgt_before']*100),
                        'wgt_after'=>sprintf("%.2f",$tran_info['wgt_after']*100),
                        'profit'=>sprintf("%.2f",$tran_info['profit']),
                        'single_ratio'=>($tran_info['hold_avg_cost']>0 && $tran_info['deal_amount']>0) ? sprintf("%.2f",(($tran_info['deal_price']*$tran_info['deal_amount']-$tran_info['transaction_cost'])/($tran_info['hold_avg_cost']*$tran_info['deal_amount'])-1)*100) : 0,
                        'profit_ratio'=>(isset($plan_info['init_value']) && $plan_info['init_value']>0) ? sprintf("%.2f",$tran_info['profit']/$plan_info['init_value']*100) : 0,
                        'reason'=>$tran_info['reason'],
                        'p_uid' => $plan_info['p_uid'],
                        'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                        'company' => isset($planner_info['company']) ? $planner_info['company'] : ''
                    ),JSON_UNESCAPED_UNICODE),
                    'link_url'=>"/plan/".$tran_info['pln_id']."?type=dynamic",
                    'c_time' => $tran_info['c_time'],
                    'u_time' => date("Y-m-d H:i:s")
                );
                foreach($sub_uids as $uid){
                    $msg_data['uid']=$uid;
                    $msg_id = Message::model()->saveMessage($msg_data);
                }

                //添加其他信息
                $msg_data['id']=$msg_id;
                $msg_data['content'] = json_decode($msg_data['content'],true);
                $msg_data['trans_type'] = $tran_info['type'];
                $msg_data['wgt_before'] = sprintf("%.2f",$tran_info['wgt_before']*100);
                $msg_data['wgt_after'] = sprintf("%.2f",$tran_info['wgt_after']*100);
                $msg_data['total_price'] = sprintf("%.2f",$tran_info['deal_price']*$tran_info['deal_amount']);
                $msg_data['profit'] = sprintf("%.2f",$tran_info['profit']);
                $msg_data['single_ratio'] = ($tran_info['hold_avg_cost']>0 && $tran_info['deal_amount']>0) ? sprintf("%.2f",(($tran_info['deal_price']*$tran_info['deal_amount']-$tran_info['transaction_cost'])/($tran_info['hold_avg_cost']*$tran_info['deal_amount'])-1)*100) : 0;
                $msg_data['profit_ratio'] = (isset($plan_info['init_value']) && $plan_info['init_value']>0) ? sprintf("%.2f",$tran_info['profit']/$plan_info['init_value']*100) : 0;
                $msg_data['reason'] = $tran_info['reason'];
                $msg_data['plan_name'] = $plan_info['name'];
                $msg_data['title'] = $plan_info['name'];
                $msg_data['symbol'] = $tran_info['symbol'];
                $msg_data['planner_name'] = $planner_info['name'];

                //用户超过500分组发送
                $uids_arr = array_chunk($sub_uids,500);
                foreach($uids_arr as $_uids){
                    $this->addToPushQueue($msg_data, $_uids, array(1,2,3));
                }
            }
            $log_data['uid']='';
            $log_data['relation_id'] = $msg['tran_id'];
            $log_data['ext_data'] = json_encode($sub_uids);

            //发布说说
            try{
                $curl =Yii::app()->curl;
                $curl->setHeaders(array('Referer'=>'http://licaishi.sina.com.cn'));
                $url=LCS_WEB_INNER_URL.'/api/planComment';
                $params = array('pln_id'=>$tran_info['pln_id'],'content'=>'我有一笔新的'.($tran_info['type']==1 ? '买入' : '卖出').'交易操作，大家可以看下。','is_planner'=>1,'discussion_type'=>7,'discussion_id'=>$msg['tran_id'],'p_uid'=>$plan_info['p_uid'],'is_anonymous'=>($tran_info['type']==1?1:0));
                $res = $curl->post($url,$params);
                if(!empty($res)){
                    $res_json = json_decode($res,true);
                    if(!isset($res_json['code']) || $res_json['code']!=0){
                        $log_data['ext_data'].=" 发送理财师交易说说 返回错误：".$res;
                    }
                }else{
                    $log_data['ext_data'].=" 发送理财师交易说说 返回数据为空";
                }
            }catch (Exception $e){
                $log_data['ext_data'].=" 发送理财师交易说说 error:".$e->getMessage();
            }


        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }


    }*/


    /**
     * 观点包开始收费提醒
     */
    /*private function packageChargeMessageHandler($msg){

        $log_data = array('status'=>1,'result'=>'ok','uid'=>'','relation_id'=>'','ext_data'=>'');
        try {
            $this->checkRequireParam($msg, array('pkg_id'));
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
                $this->addToPushQueue($msg_data, $msg_data['uid'], array(2, 3));
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
    }*/


    /**
     * 新发观点提醒
     */
    /*private function newViewMessageHandler($msg){
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try {
            $this->checkRequireParam($msg, array('v_id'));
            $v_id = $msg['v_id'];
            $v_info = View::model()->getViewById($v_id);
            if(empty($v_info)){
                sleep(1); //未获取数据，休息一秒钟
                $v_info = View::model()->getViewById($v_id);
            }
            $v_info = isset($v_info[$v_id]) ? $v_info[$v_id] : array();

            if(empty($v_info)){
                throw new Exception('v_info 为空');
            }

            $pkg_id = $v_info['pkg_id'];
            $package = Package::model()->getPackagesById($pkg_id,false);
            $package = isset($package[$pkg_id]) ? $package[$pkg_id] : array();

            $msg_data = array();
            $uids = Package::model()->getSubscriptionUid($pkg_id);

            //理财师信息
            $planner_info = Planner::model()->getPlannerById(array(intval($v_info['p_uid'])));
            $planner_info = isset($planner_info[intval($v_info['p_uid'])]) ? $planner_info[intval($v_info['p_uid'])] : array();
            if(!empty($planner_info)&&isset($planner_info['company_id'])){
                $companys = Common::model()->getCompany($planner_info['company_id']);
                if(!empty($companys)&&isset($companys[$planner_info['company_id']])){
                    $planner_info['company']=$companys[$planner_info['company_id']]['name'];
                }
            }
            foreach($uids as $uid){
                $msg_data = array(
                    'uid'=>$uid,
                    'u_type'=>1,
                    'type'=>2,
                    'relation_id'=>$pkg_id,
                    'child_relation_id'=>$v_id,
                    'content'=>json_encode( array(
                        array('value'=>"《".CHtml::encode($package['title'])."》",'class'=>'','link'=>"/web/packageInfo?pkg_id=".$pkg_id),
                        array('value'=>'内更新了一条观点','class'=>'','link'=>''),
                        array('value'=>"：".CHtml::encode($v_info['title']),'class'=>'','link'=>"/view/".$v_id."?ind_id=".$v_info['ind_id'])
                    ),JSON_UNESCAPED_UNICODE),
                    'content_client'=>json_encode(array(
                        'package_title' => CHtml::encode($package['title']),
                        'view_title' => CHtml::encode($v_info['title']),
                        'summary' => CHtml::encode($v_info['summary']),
                        'ind_id' => $v_info['ind_id'],
                        'p_uid' => $v_info['p_uid'],
                        'planner_name' => isset($planner_info['name']) ? $planner_info['name'] : '',
                        'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                        'company' => isset($planner_info['company']) ? $planner_info['company'] : ''
                    ),JSON_UNESCAPED_UNICODE),
                    'link_url'=>"/view/".$v_id."?ind_id=".$v_info['ind_id'],
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );
                //保存通知消息
                Message::model()->saveMessage($msg_data);
                $log_data['ext_data'][]=array('uid'=>$msg_data['uid'],'relation_id'=>$msg_data['relation_id']);
            }

            //加入提醒队列
            if(!empty($uids)){
                //将用户500个一组，分批放入队列。否则会导致数据过大。
                $uids_arr = array_chunk($uids,500);
                foreach($uids_arr as $_uids){
                    $this->addToPushQueue($msg_data,(array)$_uids, array(2, 3));
                }
            }




        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            $log_data['ext_data'] = json_encode($log_data['ext_data']);
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }

    }*/

    /**
     * 计划-理财师说说提醒
     */
    /*
    private function planPlannerCommentMessageHandler($msg){
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try {
            $this->checkRequireParam($msg, array('cmn_id','relation_id','content'));
            $cmn_id = $msg['cmn_id'];
            //说说信息
            //$cmn_info = Comment::model()->getCommentInfoByID($cmn_id);
            //$cmn_info = isset($cmn_info[$cmn_id]) ? $cmn_info[$cmn_id] : array();

            $cmn_info['relation_id']=$msg['relation_id'];
            $cmn_info['content']=$msg['content'];

            if(empty($cmn_info)){
                throw new Exception('cmn_info 为空');
            }

            //计划信息
            $pln_id = $cmn_info['relation_id'];
            $plan_info = Plan::model()->getPlanInfoById($pln_id);
            if(empty($plan_info)){
                throw new Exception('plan_info 为空');
            }
            //理财师信息
            $planner_info = Planner::model()->getPlannerById(array($plan_info['p_uid']));
            $planner_info = isset($planner_info[$plan_info['p_uid']]) ? $planner_info[$plan_info['p_uid']] : array();
            if(!empty($planner_info)&&isset($planner_info['company_id'])){
                $companys = Common::model()->getCompany($planner_info['company_id']);
                if(!empty($companys)&&isset($companys[$planner_info['company_id']])){
                    $planner_info['company']=$companys[$planner_info['company_id']]['name'];
                }
            }
            //获取订阅计划的用户id
            $uids = Plan::model()->getSubPlanUids($pln_id);
            if(empty($uids)){
                throw new Exception('uids 为空');
            }

            foreach($uids as $uid){
                $msg_data = array(
                    'uid'=>$uid,
                    'u_type'=>1,
                    'type'=>7,
                    'relation_id'=>$pln_id,
                    'child_relation_id'=>$cmn_id,
                    'content'=>json_encode(array(
                        array('value'=>$planner_info['name'],'class'=>'','link'=>'/planner/'.$plan_info['p_uid'].'/1'),
                        array('value'=>"在",'class'=>'','link'=>''),
                        array('value'=>"《".CHtml::encode($plan_info['name'])."》",'class'=>'','link'=>'/plan/'.$pln_id),
                        array('value'=>"中说",'class'=>'','link'=>''),
                        array('value'=>"：".CHtml::encode($cmn_info['content']),'class'=>'','link'=>'/plan/'.$pln_id.'?type=comment#plannerComment')
                    ),JSON_UNESCAPED_UNICODE),
                    'content_client'=>json_encode(array(
                        'p_uid' => $plan_info['p_uid'],
                        'planner_name' => CHtml::encode($planner_info['name']),
                        'plan_name' => $plan_info['name'],
                        'content' => CHtml::encode($cmn_info['content']),
                        'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                        'company' => isset($planner_info['company']) ? $planner_info['company'] : ''
                    ),JSON_UNESCAPED_UNICODE),
                    'link_url'=>'/plan/'.$pln_id.'?type=comment#plannerComment',
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                );

                //保存通知消息
                Message::model()->saveMessage($msg_data);

                $log_data['ext_data'][]=array('uid'=>$msg_data['uid'],'relation_id'=>$msg_data['relation_id']);

                $msg_data['title'] = $plan_info['name'];
            }//foreach

            //加入提醒队列
            $this->addToPushQueue($msg_data, $uids, array(2, 3));

        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            $log_data['ext_data'] = json_encode($log_data['ext_data']);
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }*/


    /**
     * 理财师观点包说说提醒
     * @param $msg_json   type=pkgPlannerComment  cmn_id
     */
    /*
    private function pkgPlannerCommentMessageHandler($msg_json){
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');

        try {
            $this->checkRequireParam($msg_json, array('cmn_id','relation_id','parent_relation_id','content'));
            $cmn_id = $msg_json['cmn_id'];

            //说说信息
            //$cmn_info = Comment::model()->getCommentInfoByID($cmn_id);
            //$cmn_info = isset($cmn_info[$cmn_id]) ? $cmn_info[$cmn_id] : array();

            $cmn_info['relation_id']=$msg_json['relation_id'];
            $cmn_info['parent_relation_id']=$msg_json['parent_relation_id'];
            $cmn_info['content']=$msg_json['content'];

            if(empty($cmn_info)){
                throw new Exception('cmn_info 为空');
            }

            $v_id = $cmn_info['relation_id'];
            $pkg_id = $cmn_info['parent_relation_id'];
            if($v_id == 0) {  //取观点包的信息
                $pkg_info = Package::model()->getPackagesById($pkg_id);
                $pkg_info = $pkg_info[$pkg_id];
                $p_uid = $pkg_info['p_uid'];

                //获取订阅观点包的用户id
                $uids = Package::model()->getSubscriptionUid($pkg_id);
            }else{  //取观点信息
                $v_info = View::model()->getViewById($v_id);
                $v_info = $v_info[$v_id];
                $p_uid = $v_info['p_uid'];

                //获取订阅观点包的用户id
                $uids = Package::model()->getSubscriptionUid($v_info['pkg_id']);
            }

            if(empty($p_uid)) {
                throw new Exception('p_uid 为空');
            }

            //理财师信息
            $planner_info = Planner::model()->getPlannerById($p_uid);
            $planner_info = isset($planner_info[$p_uid]) ? $planner_info[$p_uid] : array();
            if(!empty($planner_info)&&isset($planner_info['company_id'])){
                $companys = Common::model()->getCompany($planner_info['company_id']);
                if(!empty($companys)&&isset($companys[$planner_info['company_id']])){
                    $planner_info['company']=$companys[$planner_info['company_id']]['name'];
                }
            }
            if(empty($uids)) {
                throw new Exception('uids 为空');
            }
            $msg_data = array();
            foreach($uids as $uid) {
                if($v_id == 0) {
                    $content = array(
                        array('value'=>$planner_info['name'],'class'=>'','link'=>'/planner/'.$p_uid.'/1'),
                        array('value'=>"在",'class'=>'','link'=>''),
                        array('value'=>"《".CHtml::encode($pkg_info['title'])."》",'class'=>'','link'=>'/web/packageInfo?pkg_id='.$pkg_id),
                        array('value'=>"中说",'class'=>'','link'=>''),
                        array('value'=>"：".CHtml::encode($cmn_info['content']),'class'=>'','link'=>'/web/packageInfo?pkg_id='.$pkg_id.'#wetalk')
                    );
                }else{
                    $content = array(
                        array('value'=>$planner_info['name'],'class'=>'','link'=>'/planner/'.$p_uid.'/1'),
                        array('value'=>"在",'class'=>'','link'=>''),
                        array('value'=>"《".CHtml::encode($v_info['title'])."》",'class'=>'','link'=>'/view/'.$v_id),
                        array('value'=>"中说",'class'=>'','link'=>''),
                        array('value'=>"：".CHtml::encode($cmn_info['content']),'class'=>'','link'=>'/view/'.$v_id)
                    );
                }

                $now_time = date("Y-m-d H:i:s");
                $msg_data = array(
                    'uid' => $uid,
                    'u_type' => 1,
                    'type' => 11,
                    'relation_id' => $pkg_id,
                    'child_relation_id'=>$cmn_id,
                    'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                    'content_client'=>json_encode(array(
                        'p_uid' => $p_uid,
                        'planner_name' => CHtml::encode($planner_info['name']),
                        'title' => isset($v_info['title'])?$v_info['title']:$pkg_info['title'], //
                        'sub_type' => isset($v_info['title'])?'view':'package', //
                        'content' => CHtml::encode($cmn_info['content']),
                        'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                        'company' => isset($planner_info['company']) ? $planner_info['company'] : ''
                    ),JSON_UNESCAPED_UNICODE),
                    'link_url'=> '',
                    'c_time' => $now_time,
                    'u_time' => $now_time
                );
                if($v_id == 0) {
                    $msg_data['link_url'] = '/web/packageInfo?pkg_id='.$pkg_id.'#wetalk';
                }else{
                    $msg_data['link_url'] = '/view/'.$v_id;
                }

                Message::model()->saveMessage($msg_data);
                $msg_data['title'] = isset($v_info['title'])?$v_info['title']:$pkg_info['title'];
            }//end foreach.

            //加入提醒队列
            if(!empty($uids)){
                //将用户500个一组，分批放入队列。否则会导致数据过大。
                $uids_arr = array_chunk($uids,500);
                foreach($uids_arr as $_uids){
                    $this->addToPushQueue($msg_data,(array)$_uids,array(2,3));
                }
            }
           //$log_data['uid'] = $msg_data['uid'];
            $log_data['relation_id'] = $msg_data['relation_id'];
            $log_data['ext_data']=json_encode(array('uids'=>$uids));
        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }


        //记录队列处理结果
        //$log_data['ext_data'] =$cmn_id;
        if(isset($msg_json['queue_log_id']) && !empty($msg_json['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg_json['queue_log_id']);
        }

    }*/

    /**
     * 运行消息的通知处理
     * @param $msg
     *  消息体结构：
     *  type=operateNotice  notice_type content  relation_id url uids[为空为所有用户] channel[2:android 3:ios]
     */
    /*
    private function operateNoticeMessageHandler($msg){
        if(empty($msg) || !isset($msg['notice_type'])|| !isset($msg['content'])|| !isset($msg['url'])){
            return;
        }

        $msg_data = array(
            'uid'=>'',
            'u_type'=>1,  //1普通用户   2理财师
            'type'=>12,
            'relation_id'=>0,
            'child_relation_id'=>0,
            'content'=>json_encode(array(
                array('value'=>$msg['content'],'class'=>'','link'=>$msg['url'])
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
                $content_client['id']=$msg['relation_id'];
                break;
            case 11:
                $content_client['k']=$msg['relation_id'];
                break;
            case 12:
                $content_client['url']=$msg['url'];
                break;
            default:
                break;
        }

        $msg_data['content_client']=json_encode($content_client,JSON_UNESCAPED_UNICODE);

        //获取用户
        $uids = isset($msg['uids'])?$msg['uids']:'';
        $uids_arr = array();
        if(empty($uids)){
            //全部通知用户
            $uids_arr = Message::model()->getChannelUidBySuids();
        }else{
            //处理一下用
            $_uids = explode(',',$uids);
            if(!empty($_uids)){
                $temp=array();
                foreach($_uids as $uid){
                    $temp[] = intval($uid);
                }
                $_uids=array_unique($_uids);
            }
            $uids = implode(',',$_uids);
            $uids_arr = Message::model()->getChannelUidBySuids($_uids);
        }

        //加入提醒队列
        $msg_data['content'] = json_decode($msg_data['content'],true);
        //处理渠道
        $channel=intval(isset($msg['channel'])?$msg['channel']:0);
        if(!in_array($channel,array(1,2,3))){
            $channel=0; //所有渠道
        }

        if(!empty($uids_arr)){
            //将用户500个一组，分批放入队列。否则会导致数据过大。
            $uids_arr = array_chunk($uids_arr,500);
            foreach($uids_arr as $_uids){
                $this->addToPushQueue($msg_data,$_uids,$channel===0?array(1,2,3):array($channel));
            }
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            Message::model()->updateMessageQueueLog(array('status'=>1,'result'=>'ok','ext_data'=>json_encode($uids_arr)), $msg['queue_log_id']);
        }
    }*/


    /**
     * 问题回答通知处理 ok
     * 消息体包含如下信息：type=questionAnswer, q_id, answer_id(非必须)
     * @param $msg
     */
    /*
    private function questionAnswerMessageHandler($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            //验证必填项目
            $this->checkRequireParam($msg, array('q_id'));
            $q_id = $msg['q_id'];
            $q_info = Question::model()->getQuestionById($q_id);
            $q_info = isset($q_info[$q_id]) ? $q_info[$q_id] : array();
            if(empty($q_info)){
                throw new Exception('问题不存在，q_id'.$q_id);
            }

            $answer_id = $q_info['answer_id'];
            if (isset($msg ['answer_id']) && !empty($msg['answer_id'])) {
                $answer_id = $msg['answer_id'];
            }

            $answer_info = Question::model()->getAnswerById($answer_id);
            $answer_info = isset($answer_info[$answer_id]) ? $answer_info[$answer_id] : array();

            //理财师信息
            if(array_key_exists("p_uid",$answer_info)) {
                //抢答问题从抢答表中获取p_uid
                $p_uid=intval($answer_info['p_uid']);
            }
            else{
                //普通问答从问题表中获取p_uid
                $p_uid=intval($q_info['p_uid']);
            }

            $planner_info = Planner::model()->getPlannerById(array($p_uid));
            $planner_info = isset($planner_info[$p_uid]) ? $planner_info[$p_uid] : array();
            if(!empty($planner_info) && isset($planner_info['company_id'])){
                $companys = Common::model()->getCompany($planner_info['company_id']);
                if(!empty($companys)&&isset($companys[$planner_info['company_id']])){
                    $planner_info['company']=$companys[$planner_info['company_id']]['name'];
                }

            }

            //获取用户信息
            if(isset($q_info['is_anonymous'])&&$q_info['is_anonymous']==1){
                $user_info = array('name'=>null,'image'=>null);
            }
            else {
                $user_info = User::model()->getUserInfoByUid($q_info['uid']);
            }*/


            //同问的用户
            //$uids = array();//Yii::app()->lcs_r->createCommand("select uid from lcs_collect where type=1 and relation_id=$q_id")->queryColumn();
            //array_push($uids, $q_info['uid']);


            //个性化 去掉关闭提醒的uid
            /*$close_uids = Message::model()->getCloseMessageUsers($uids, 1, 1, 1);
            if (!empty($uids)) {
                throw new Exception('无通知用户 q_id:'.$q_id);
            }
            foreach ($uids as $uid) {
                if (!empty($close_uids) && in_array($uid, $close_uids)) {
                    continue;
                }*/
/*
                $msg_data = array();

                if ($q_info['status'] == 1) { //提问
                    $msg_data = array(
                        'uid' => $q_info['p_uid'],
                        'u_type' => 2,
                        'type' => 1,
                        'relation_id' => $q_id,
                        'content' => json_encode(array(
                            array('value' => "有新的用户提问：", 'class' => '', 'link' => ''),
                            array('value' => CHtml::encode($q_info['content']), 'class' => '', 'link' => '/ask/' . $q_id)
                        ), JSON_UNESCAPED_UNICODE),
                        'content_client' => json_encode(array(
                            'status' => $q_info['status'],
                            'content' => CHtml::encode($q_info['content']),
                            'q_id' => $msg['q_id'],
                            'industry' => CommonUtils::parseIndustry($q_info['ind_id']),
                            'price' => $q_info['price'],
                            'u_name' => $user_info['name'],
                            'u_image' => $user_info['image'],
                            'time' => $q_info['c_time'],
                        ), JSON_UNESCAPED_UNICODE),
                        'link_url' => '/ask/' . $q_id,
                        'c_time' => date("Y-m-d H:i:s"),
                        'u_time' => date("Y-m-d H:i:s")
                    );
                } elseif ($q_info['status'] == 2) { //拒绝回答
                    $msg_data = array(
                        'uid' => $q_info['uid'],
                        'u_type' => 1,
                        'type' => 1,
                        'relation_id' => $q_id,
                        'content' => json_encode(array(
                            array('value' => isset($planner_info['name']) ? $planner_info['name'] : '', 'class' => '', 'link' => '/planner/' . $q_info['p_uid'] . '/1'),
                            array('value' => '无法回答您的问题', 'class' => '', 'link' => ''),
                            array('value' => "：" . CHtml::encode($q_info['content']), 'class' => '', 'link' => '/ask/' . $q_id),
                        ), JSON_UNESCAPED_UNICODE),
                        'content_client' => json_encode(array(
                            'status' => $q_info['status'],
                            'planner_name' => isset($planner_info['name']) ? $planner_info['name'] : '',
                            'content' => CHtml::encode($q_info['content']),
                            'p_uid' => isset($planner_info['p_uid']) ? $planner_info['p_uid'] : 0,
                            'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                            'company' => isset($planner_info['company']) ? $planner_info['company'] : ''
                        ), JSON_UNESCAPED_UNICODE),
                        'link_url' => '/ask/' . $q_id,
                        'c_time' => date("Y-m-d H:i:s"),
                        'u_time' => date("Y-m-d H:i:s")
                    );
                } elseif ($q_info['status'] == 3) { //回答
                    $msg_data = array(
                        'uid' => $q_info['uid'],
                        'u_type' => 1,
                        'type' => 1,
                        'relation_id' => $q_id,
                        'content' => json_encode(array(
                            array('value' => isset($planner_info['name']) ? $planner_info['name'] : '', 'class' => '', 'link' => '/planner/' . $q_info['p_uid'] . '/1'),
                            array('value' => '回答了您的问题', 'class' => '', 'link' => ''),
                            array('value' => "：" . CHtml::encode($q_info['content']), 'class' => '', 'link' => '/ask/' . $q_id),
                        ), JSON_UNESCAPED_UNICODE),
                        'content_client' => json_encode(array(
                            'status' => $q_info['status'],
                            'planner_name' => isset($planner_info['name']) ? $planner_info['name'] : '',
                            'content' => CHtml::encode($q_info['content']),
                            'p_uid' => isset($planner_info['p_uid']) ? $planner_info['p_uid'] : 0,
                            'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                            'company' => isset($planner_info['company']) ? $planner_info['company'] : '',
                            'answer_content' => isset($answer_info['summary']) ? CHtml::encode($answer_info['summary']) : '',
                            'response_time' => CommonUtils::formatRespTime(ceil((strtotime($answer_info['c_time']) - strtotime($q_info['c_time'])) / 60))
                        ), JSON_UNESCAPED_UNICODE),
                        'link_url' => '/ask/' . $q_id,
                        'c_time' => date("Y-m-d H:i:s"),
                        'u_time' => date("Y-m-d H:i:s")
                    );
                } elseif ($q_info['status'] == 4) { //追问中
                    //追问回答信息
                    $question_add_info = Question::model()->getQuestionAddInfo($q_info['q_add_id']);
                    $question_add_info = isset($question_add_info[$q_info['q_add_id']]) ? $question_add_info[$q_info['q_add_id']] : array();
                    $msg_data = array(
                        'uid' => $q_info['p_uid'],
                        'u_type' => 2,
                        'type' => 1,
                        'relation_id' => $q_id,
                        'content' => json_encode(array(
                            array('value' => "有新的用户追问：", 'class' => '', 'link' => ''),
                            array('value' => CHtml::encode($q_info['content']), 'class' => '', 'link' => '/ask/' . $q_id)
                        ), JSON_UNESCAPED_UNICODE),
                        'content_client' => json_encode(array(
                            'status' => $q_info['status'],
                            'content' => CHtml::encode($q_info['content']),
                            'add_content'=>isset($question_add_info['content']) ? CHtml::encode(strip_tags($question_add_info['content'])) : '',
                            'q_id' => $msg['q_id'],
                            'u_name' => $user_info['name'],
                            'u_image' => $user_info['image'],
                            'time' => $q_info['c_time'],
                        ), JSON_UNESCAPED_UNICODE),
                        'link_url' => '/ask/' . $q_id,
                        'c_time' => date("Y-m-d H:i:s"),
                        'u_time' => date("Y-m-d H:i:s")
                    );
                } elseif ($q_info['status'] == 5) { //追问回答
                    //追问回答信息
                    $question_add_info = Question::model()->getQuestionAddInfo($q_info['q_add_id']);
                    $question_add_info = isset($question_add_info[$q_info['q_add_id']]) ? $question_add_info[$q_info['q_add_id']] : array();

                    $msg_data = array(
                        'uid' => $q_info['uid'],
                        'u_type' => 1,
                        'type' => 1,
                        'relation_id' => $q_id,
                        'content' => json_encode(array(
                            array('value' => isset($planner_info['name']) ? $planner_info['name'] : '', 'class' => '', 'link' => '/planner/' . $q_info['p_uid'] . '/1'),
                            array('value' => '补充回答了您的问题', 'class' => '', 'link' => ''),
                            array('value' => "：" . CHtml::encode($q_info['content']), 'class' => '', 'link' => '/ask/' . $q_id),
                        ), JSON_UNESCAPED_UNICODE),
                        'content_client' => json_encode(array(
                            'status' => $q_info['status'],
                            'planner_name' => isset($planner_info['name']) ? $planner_info['name'] : '',
                            'content' => CHtml::encode($q_info['content']),
                            'p_uid' => isset($planner_info['p_uid']) ? $planner_info['p_uid'] : 0,
                            'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                            'company' => isset($planner_info['company']) ? $planner_info['company'] : '',
                            'answer_content' => isset($question_add_info['answer']) ? CHtml::encode(strip_tags($question_add_info['answer'])) : ''
                        ), JSON_UNESCAPED_UNICODE),
                        'link_url' => '/ask/' . $q_id,
                        'c_time' => date("Y-m-d H:i:s"),
                        'u_time' => date("Y-m-d H:i:s")
                    );
                }else{
                    throw new Exception("问答状态错误：".$q_info['status']);
                }

                //保存通知消息
                $_log_id = Message::model()->saveMessage($msg_data);
                if($_log_id){
                    $msg_data['id']=$_log_id;
                }
                //加入提醒队列
                $push_uid=array();
                if($msg_data['u_type']==2){
                    $push_uid[]=User::model()->getUidBySuid($msg_data['uid']);
                }else if($msg_data['u_type']==1){
                    $push_uid[]=$msg_data['uid'];
                }

                $this->addToPushQueue($msg_data,$push_uid,array (1,2,3));

                $log_data['uid']=$msg_data['uid'];
                $log_data['relation_id']=$msg_data['relation_id'];
            //}
        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }*/


    /**
     * 购买观点包通知处理
     *
     * 消息体包含如下信息：type, pkg_id, uid, start_time,end_time
     *
     * @param $msg
     */
    /*private function buyPackageMessageHandler($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            //验证必填项目
            $this->checkRequireParam($msg, array('u_id','pkg_id'));
            $pkg_id = $msg['pkg_id'];

            //添加通知
            $pkgs = Package::model()->getPackagesById($pkg_id);
            $pkg = !empty($pkgs) && isset($pkgs[$pkg_id]) ? $pkgs[$pkg_id] : null;
            $pkg_name = !empty($pkg) && isset($pkg['title']) ? $pkg['title'] : '';
            $p_uid = !empty($pkg) && isset($pkg['p_uid']) ? $pkg['p_uid'] : '';
            $planner = array();

            if (!empty($p_uid)) {
                $planners = Planner::model()->getPlannerById($p_uid);
                $planner = !empty($planners) && isset($planners[$p_uid]) ? $planners[$p_uid] : null;
            }
            $msg_data = array(
                'uid' => $msg['u_id'],
                'u_type' => 1,  //1普通用户   2理财师
                'type' => 3,
                'relation_id' => $pkg_id,
                'child_relation_id' => 0,
                'content' => json_encode(array(
                    array('value' => "您已成功购买", 'class' => '', 'link' => ""),
                    array('value' => '《' . $pkg_name . '》', 'class' => '', 'link' => "/web/packageInfo?pkg_id=" . $pkg_id),
                    array('value' => "观点包，我们将实时提醒你观点包的更新动态，请及时查看新消息", 'class' => '', 'link' => ""),
                ), JSON_UNESCAPED_UNICODE),
                'content_client' => json_encode(array(
                    'type' => 7,
                    'pkg_id' => $pkg_id,
                    'pkg_name' => $pkg_name,
                    "start_time" => isset($msg['start_time']) ? $msg['start_time'] : '',
                    "end_time" => isset($msg['end_time']) ? $msg['end_time'] : '',
                    "p_uid" => $p_uid,
                    "planner_name" => !empty($planner) && isset($planner['name']) ? $planner['name'] : '',
                    "planner_image" => !empty($planner) && isset($planner['image']) ? $planner['image'] : '',
                    "company" => !empty($planner) && isset($planner['company']) ? $planner['company'] : '',
                ), JSON_UNESCAPED_UNICODE),
                'link_url' => "/web/packageInfo?pkg_id=" . $pkg_id,
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );
            //保存通知消息
            Message::model()->saveMessage($msg_data);
            //加入提醒队列
            $push_uid = array();
            $push_uid[] = $msg_data['uid'];
            $this->addToPushQueue($msg_data, $push_uid, array(2, 3));

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
    }*/

    /**
     * 购买计划通知处理
     * 消息体：type,pln_id,uid
     *
     * @param $msg
     */
    /**
    private function buyPlanMessageHandler($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            //验证必填项目
            $this->checkRequireParam($msg, array('u_id','pln_id'));
            //添加通知
            $pln_id = $msg['pln_id'];
            $plan_infos = Plan::model()->getPlanInfoByIds($pln_id, array('pln_id', 'p_uid', 'name', 'number'));
            $plan_info = !empty($plan_infos) && count($plan_infos) > 0 ? current($plan_infos) : array();
            $p_uid = '';
            $plan_name = '';
            if (!empty($plan_info)) {
                $plan_name = isset($plan_info['name']) ? $plan_info['name'] : '';
                $plan_name .= (isset($plan_info['number']) && $plan_info['number'] > 9 ? $plan_info['number'] : "0" . $plan_info['number']) . "期";
                $p_uid = isset($plan_info['p_uid']) ? $plan_info['p_uid'] : '';
            }

            $planner = array();
            if (!empty($p_uid)) {
                $planners = Planner::model()->getPlannerById($p_uid);
                $planner = !empty($planners) && isset($planners[$p_uid]) ? $planners[$p_uid] : null;
            }

            $msg_data = array(
                'uid' => $msg['u_id'],
                'u_type' => 1,  //1普通用户   2理财师
                'type' => 3,
                'relation_id' => $pln_id,
                'child_relation_id' => 0,
                'content' => json_encode(array(
                    array('value' => "您已成功购买", 'class' => '', 'link' => ""),
                    array('value' => '《' . $plan_name . '》', 'class' => '', 'link' => "/plan/" . $pln_id),
                    array('value' => "计划，我们将实时提醒你理财师的操作动态，请及时查看新消息", 'class' => '', 'link' => ""),
                ), JSON_UNESCAPED_UNICODE),
                'content_client' => json_encode(array(
                    'type' => 8,
                    'pln_id' => $pln_id,
                    'pln_name' => $plan_name,
                    "p_uid" => $p_uid,
                    "planner_name" => !empty($planner) && isset($planner['name']) ? $planner['name'] : '',
                    "planner_image" => !empty($planner) && isset($planner['image']) ? $planner['image'] : '',
                    "company" => !empty($planner) && isset($planner['company']) ? $planner['company'] : '',
                ), JSON_UNESCAPED_UNICODE),
                'link_url' => "/plan/" . $pln_id,
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );

            //保存通知消息
            Message::model()->saveMessage($msg_data);
            //加入提醒队列
            $push_uid = array();
            $push_uid[] = $msg_data['uid'];
            $this->addToPushQueue($msg_data, $push_uid, array(2, 3));

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
    }*/



    /**
 * 回复评论提醒  ok
 * @param $msg  type=replayComment   cmn_id
 *//*
    private function replayCommentMessageHandler($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            //验证必填项目
            $this->checkRequireParam($msg, array('cmn_id','replay_id','u_type','uid','cmn_type','relation_id','parent_relation_id','content','r_cmn_type','r_uid','r_u_type','r_content'));
            $cmn_id=$msg['cmn_id'];
            //$cmn_info = Comment::model()->getCommentInfoByID($cmn_id);
            //$cmn_info = isset($cmn_info[$cmn_id]) ? $cmn_info[$cmn_id] : array();

            $cmn_info['replay_id']=$msg['replay_id'];
            $cmn_info['u_type']=$msg['u_type'];
            $cmn_info['uid']=$msg['uid'];
            $cmn_info['cmn_type']=$msg['cmn_type'];
            $cmn_info['relation_id']=$msg['relation_id'];
            $cmn_info['parent_relation_id']=$msg['parent_relation_id'];
            $cmn_info['content']=$msg['content'];

            if(empty($cmn_info)){
                throw new Exception('评论的说说不存在');
            }

            //被评论的评论内容
            $replay_cmn_info = array();
            $replay_cmn_info['cmn_type']=$msg['r_cmn_type'];
            $replay_cmn_info['uid']=$msg['r_uid'];
            $replay_cmn_info['u_type']=$msg['r_u_type'];
            $replay_cmn_info['content']=$msg['r_content'];
            *//*
            if(isset($cmn_info['replay_id']) && $cmn_info['replay_id']>0){
                $replay_cmn_info = Comment::model()->getCommentInfoByID($cmn_info['replay_id']);
                $replay_cmn_info = isset($replay_cmn_info[$cmn_info['replay_id']]) ? $replay_cmn_info[$cmn_info['replay_id']] : array();
            }*/
  /*
            if(empty($replay_cmn_info)) {
                throw new Exception('被评论的说说不存在');
            }

            $name = '';
            $u_image='';
            $planner_info = array();//理财师信息
            if(1 == $cmn_info['u_type']){//普通用户
                $user = User::model()->getUserInfoByUid($cmn_info['uid']);
                //$user = !empty($users)&&isset($users[$cmn_info['uid']])?$users[$cmn_info['uid']]:array();
                $name = "财友".CommonUtils::encodeId($cmn_info['uid']);
                $u_image = isset($user['image']) ? $user['image'] : "";;
            }elseif(2 == $cmn_info['u_type']){//理财师
                $planner_info = Planner::model()->getPlannerById(array($cmn_info['uid']));
                $planner_info = isset($planner_info[$cmn_info['uid']]) ? $planner_info[$cmn_info['uid']] : array();
                $name = isset($planner_info['name']) ? $planner_info['name'] : "";
                $u_image=isset($planner_info['image']) ? $planner_info['image'] : "";
            }elseif(3 == $cmn_info['u_type']){//理财小妹
                $name = "理财小妹";
                $u_image='http://licaishi.sina.com.cn/web_img/lcs_comment_systemuser.jpg';
            }


            $link_url = '';
            $type = 0;//提醒类型
            $from = '';//来源
            $_p_uid = '';//记录当前说说对象类型的理财师ID
            if($replay_cmn_info['cmn_type'] == 1){//计划
                $link_url = "/plan/".$cmn_info['relation_id']."?type=comment#myComment"; //计划
                $type = 6;//计划说说回复

                //计划信息
                $plan_info = Plan::model()->getPlanInfoById($cmn_info['relation_id']);
                $from = isset($plan_info['name']) ? $plan_info['name'] : '';
                $_p_uid = isset($plan_info['p_uid']) ? $plan_info['p_uid'] : '';

            }elseif($replay_cmn_info['cmn_type'] == 2){ //观点
                $type = 9;// 关注 观点包/观点 说说回复

                if($cmn_info['relation_id'] > 0){ //观点评论
                    $link_url = "/view/".$cmn_info['relation_id']."#myComment";

                    //观点信息
                    $view_info = View::model()->getViewById($cmn_info['relation_id']);
                    $view_info = isset($view_info[$cmn_info['relation_id']]) ? $view_info[$cmn_info['relation_id']] : array();
                    $from = isset($view_info['title']) ? $view_info['title'] : '';
                    $_p_uid = isset($view_info['p_uid']) ? $view_info['p_uid'] : '';
                    if(isset($view_info['subscription_price']) && $view_info['subscription_price']>0){
                        $type = 8;// 已购 观点包/观点 说说回复
                    }

                }else{//观点包评论
                    $link_url = "/web/packageInfo?pkg_id=".$cmn_info['parent_relation_id']."#myComment";
                    $cmn_info['relation_id'] = $cmn_info['parent_relation_id'];
                    $replay_cmn_info['cmn_type'] = -2;

                    //观点包信息
                    $package_info = Package::model()->getPackagesById($cmn_info['parent_relation_id']);
                    $package_info = isset($package_info[$cmn_info['parent_relation_id']]) ? $package_info[$cmn_info['parent_relation_id']] : array();
                    $from = isset($package_info['title']) ? $package_info['title'] : '';
                    $_p_uid = isset($package_info['p_uid']) ? $package_info['p_uid'] : '';
                    if(isset($package_info['subscription_price']) && $package_info['subscription_price']>0){
                        $type = 8;// 已购 观点包/观点 说说回复
                    }
                }

            }else {
                throw new Exception('无法处理的回复说说类型 cmn_type:'.$replay_cmn_info['cmn_type']);
            }

            //获取理财师
            if(empty($plan_info) || $plan_info['p_uid']!==$_p_uid) {
                $planner_info = Planner::model()->getPlannerById(array($_p_uid));
                $planner_info = isset($planner_info[$cmn_info['uid']]) ? $planner_info[$cmn_info['uid']] : array();
                if(isset($planner_info['company_id'])&& !empty($planner_info['company_id'])){
                    $company_infos = Planner::model()->getCompanyById($planner_info['company_id']);
                    $company_info = isset($company_infos[$planner_info['company_id']])?$company_infos[$planner_info['company_id']]:array();
                    $planner_info['company']=isset($company_info['name']) ? $company_info['name'] : '';
                }
            }

            $msg_data = array(
                'uid'=>$replay_cmn_info['uid'],
                'u_type'=>$replay_cmn_info['u_type'],  //普通用户   2理财师
                'type'=>$type,
                'relation_id'=>$cmn_info['cmn_type']==1?$cmn_info['relation_id']:$cmn_info['parent_relation_id'],
                'child_relation_id'=>$cmn_info['replay_id'],
                'content'=>json_encode(array(
                    array('value'=>$name,'class'=>'','link'=>$cmn_info['u_type']==2?"/planner/".$cmn_info['uid']."/1":""),  //当前用户为理财师的时候 显示连接
                    array('value'=>"回复了您的评论",'class'=>'','link'=>""),
                    array('value'=>CHtml::encode($replay_cmn_info['content']),'class'=>'','link'=>$link_url),
                ),JSON_UNESCAPED_UNICODE),
                'content_client'=>json_encode(array(
                    'name' => $name,
                    'image' => $u_image,
                    'u_type'=> $cmn_info['u_type'],
                    'comment_type'=>$replay_cmn_info['cmn_type'],
                    'content' => CHtml::encode($replay_cmn_info['content']),
                    'cmn_content' => CHtml::encode($cmn_info['content']),
                    'p_uid' => isset($planner_info['p_uid']) ? $planner_info['p_uid'] : 0,
                    'planner_name' => isset($planner_info['name']) ? $planner_info['name'] : '',
                    'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                    'company' => isset($planner_info['company']) ? $planner_info['company'] : '',
                    'from' => $from
                ),JSON_UNESCAPED_UNICODE),
                'link_url'=>$link_url,
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );

            //保存通知消息
            Message::model()->saveMessage($msg_data);

            //加入提醒队列
            $msg_data['content'] = json_decode($msg_data['content'],true);
            $push_uid=array();
            if($msg_data['u_type']==2){
                $push_uid[]=User::model()->getUidBySuid($msg_data['uid']);
            }else if($msg_data['u_type']==1){
                $push_uid[]=$msg_data['uid'];
            }

            $this->addToPushQueue($msg_data,$push_uid ,array (2,3));

            $log_data['uid']=$msg_data['uid'];
            $log_data['relation_id']=$msg_data['relation_id'];

        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }*/
    
    /**
     * 计划止损、到期、止损冻结、 到期冻结
     * @param unknown $msg
     */
    private function planStatusMessageHandler($msg) {
    	$log_data = array('status'=>1, 'result'=>'ok');
    	try {
    		$params = array('type','uid','plan_name','pln_id','plan_result','curr_ror','target_ror','status',
    				        'min_profit','p_uid','planner_name','planner_image','company');
    		$this->checkRequireParam($msg, $params);
    		
    		$status = $msg['status'];
    		
    		if($status == Plan::PLAN_STATUS_SUCCESS){ //成功提醒
    				
    			$msg_data = array(
    					'uid' => $msg['uid'],
    					'u_type' => 1,
    					'type' => 5,
    					'relation_id' => $msg['pln_id'],
    					'content'=>json_encode(array(
    						array('value'=>'您购买的计划','class'=>'','link'=>''),
    						array('value'=>"《".$msg['plan_name']."》",'class'=>'','link'=>"/plan/".$msg['pln_id']),
    						array('value'=>$msg['plan_result']."目标,实际收益".sprintf("%.2f",$msg['curr_ror']*100)."%，目标收益".sprintf("%.2f",$msg['target_ror']*100)."%。",'class'=>'','link'=>'')
    					),JSON_UNESCAPED_UNICODE),
    					'content_client'=>json_encode(array(
    							'type'=> 1,
    							'pln_id'=> $msg['pln_id'],
    							'plan_name'=> $msg['plan_name'],
    							'status'=> $msg['status'],
    							'target_ror'=> sprintf("%.2f",$msg['target_ror']*100),
    							'curr_ror'=> sprintf("%.2f",$msg['curr_ror']*100),
    							'min_profit'=> $msg['min_profit'],
    							'stop_loss'=> isset($msg['stop_loss']) ? $msg['stop_loss'] : 0,
    							'p_uid' => $msg['p_uid'],
    							'planner_name' => isset($msg['planner_name']) ? $msg['planner_name'] : '',
    							'planner_image' => isset($msg['planner_image']) ? $msg['planner_image'] : '',
    							'company' => isset($msg['company']) ? $msg['company'] : ''
    					),JSON_UNESCAPED_UNICODE),
    					'link_url'=>"/plan/".$msg['pln_id'],
    					'c_time' => date("Y-m-d H:i:s"),
    					'u_time' => date("Y-m-d H:i:s")
    			);
    		}else{ //失败提醒
    			$msg_data = array(
    					'uid' => $msg['uid'],
    					'u_type' => 1,
    					'type' => 5,
    					'relation_id' => $msg['pln_id'],
    					'content'=>json_encode(array(
    							array('value'=>'您购买的计划','class'=>'','link'=>''),
    							array('value'=>"《".$msg['plan_name']."》",'class'=>'','link'=>"/plan/".$msg['pln_id']),
    							array('value'=>$msg['plan_result']."目标。",'class'=>'','link'=>'')
    							#array('value'=>"点击可申请退款。",'class'=>'','link'=>"/plan/".$msg['pln_id'])
    					),JSON_UNESCAPED_UNICODE),
    					'content_client'=>json_encode(array(
    							'type'=>1,
    							'pln_id' => $msg['pln_id'],
    							'plan_name' => $msg['plan_name'],
    							'status'=> $msg['status'],
    							'target_ror'=> sprintf("%.2f", $msg['target_ror']*100),
    							'curr_ror'=> sprintf("%.2f", $msg['curr_ror']*100),
    							'min_profit'=> $msg['min_profit'],
    							'stop_loss'=>isset($msg['stop_loss']) ? $msg['stop_loss'] : 0,
    							'p_uid' => $msg['p_uid'],
    							'planner_name' => isset($msg['planner_name']) ? $msg['planner_name'] : '',
    							'planner_image' => isset($msg['planner_image']) ? $msg['planner_image'] : '',
    							'company' => isset($msg['company']) ? $msg['company'] : ''
    					),JSON_UNESCAPED_UNICODE),
    					'link_url'=>"/plan/".$msg['pln_id'],
    					'c_time' => date("Y-m-d H:i:s"),
    					'u_time' => date("Y-m-d H:i:s")
    			);
    		}
    		
    		//提醒添加成功，加入redis队列
    		if(Yii::app()->lcs_w->createCommand()->insert("lcs_message", $msg_data)) {
    			$msg_id = Yii::app()->lcs_w->getLastInsertID("lcs_message");
    			$msg_data['id'] = $msg_id;
    			$msg_data['content'] = json_decode($msg_data['content'], true);
    			$msg_data['plan_name'] = $msg['plan_name'];
    			$msg_data['target_ror'] = sprintf("%.2f", $msg['target_ror']*100);
    			$msg_data['curr_ror'] = sprintf("%.2f", $msg['curr_ror']*100);
    			$msg_data['plan_result'] = "目标".$msg['plan_result'];
    		
    			$msg_data['subscription_price'] = isset($msg['subscription_price']) ? sprintf("%.2f", $msg['subscription_price']) : 0;
    		
    			if($msg['min_profit'] <= $msg['stop_loss']){
    				$msg_data['stop_reason'] = "计划止损终止";
    			}elseif($msg['curr_ror'] >= $msg['target_ror']){
    				$msg_data['stop_reason'] = "达成目标终止";
    			}else{
    				$msg_data['stop_reason'] = "计划到期终止";
    			}
    		
    			Message::model()->addMessageQueue($msg_data);
    			
    			$log_data['uid'] = $msg_data['uid'];
    			$log_data['relation_id'] = $msg_data['relation_id'];
    		}
    		
    	}catch (Exception $e) {
    		$log_data['status']=-1;
    		$log_data['result']=$e->getMessage();
    	}
    	
        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }

    /**
     * 回复评论提醒  ok
     * @param $msg  type=replayComment   cmn_id
     */
    /*
    private function replayCommentNewMessageHandler($msg){
        $log_data = array('status'=>1,'result'=>'ok');
        try {
            //验证必填项目
            $this->checkRequireParam($msg, array('cmn_id','replay_id','u_type','uid','cmn_type','relation_id','parent_relation_id','content','r_cmn_type','r_uid','r_u_type','r_content'));
            $cmn_id=$msg['cmn_id'];
            //$cmn_info = Comment::model()->getCommentInfoByID($cmn_id);
            //$cmn_info = isset($cmn_info[$cmn_id]) ? $cmn_info[$cmn_id] : array();
            $cmn_info['cmn_id']=$msg['cmn_id'];
            $cmn_info['replay_id']=$msg['replay_id'];
            $cmn_info['u_type']=$msg['u_type'];
            $cmn_info['uid']=$msg['uid'];
            $cmn_info['cmn_type']=$msg['cmn_type'];
            $cmn_info['relation_id']=$msg['relation_id'];
            $cmn_info['parent_relation_id']=$msg['parent_relation_id'];
            $cmn_info['content']=$msg['content'];
            if(empty($cmn_info)){
                throw new Exception('评论的说说不存在');
            }

            //被评论的评论内容
            $replay_cmn_info = array();
            $replay_cmn_info['cmn_type']=$msg['r_cmn_type'];
            $replay_cmn_info['uid']=$msg['r_uid'];
            $replay_cmn_info['u_type']=$msg['r_u_type'];
            $replay_cmn_info['content']=$msg['r_content'];
            *//*
            if(isset($cmn_info['replay_id']) && $cmn_info['replay_id']>0){
                $replay_cmn_info = Comment::model()->getCommentInfoByID($cmn_info['replay_id']);
                $replay_cmn_info = isset($replay_cmn_info[$cmn_info['replay_id']]) ? $replay_cmn_info[$cmn_info['replay_id']] : array();
            }*/
/*
            if(empty($replay_cmn_info)) {
                throw new Exception('被评论的说说不存在');
            }

            //评论的用户信息   二级说说
            $name = '';
            $u_image='';
            $company='';
            if(1 == $cmn_info['u_type']){//普通用户
                $user = User::model()->getUserInfoByUid($cmn_info['uid']);
                //$user = !empty($users)&&isset($users[$cmn_info['uid']])?$users[$cmn_info['uid']]:array();
                $name = "财友".CommonUtils::encodeId($cmn_info['uid']);
                $u_image = isset($user['image']) ? $user['image'] : "";;
            }elseif(2 == $cmn_info['u_type']){//理财师
                $planner_info = Planner::model()->getPlannerById(array($cmn_info['uid']));
                $planner_info = isset($planner_info[$cmn_info['uid']]) ? $planner_info[$cmn_info['uid']] : array();
                $name = isset($planner_info['name']) ? $planner_info['name'] : "";
                $u_image=isset($planner_info['image']) ? $planner_info['image'] : "";
                if(isset($planner_info['company_id']) && !empty($planner_info['company_id'])){
                    $company_infos = Planner::model()->getCompanyById(array($planner_info['company_id']));
                    $company_info = isset($company_infos[$planner_info['company_id']])?$company_infos[$planner_info['company_id']]:array();
                    $company=isset($company_info['name']) ? $company_info['name'] : '';
                }


            }elseif(3 == $cmn_info['u_type']){//理财小妹
                $name = "理财小妹";
                $u_image='http://licaishi.sina.com.cn/web_img/lcs_comment_systemuser.jpg';
            }

            //被回复的评论的用户信息  一级说说
            $r_name = '';
            $r_u_image='';
            $r_company='';
            if(1 == $replay_cmn_info['u_type']){//普通用户
                $user = User::model()->getUserInfoByUid($replay_cmn_info['uid']);
                $r_name = "财友".CommonUtils::encodeId($replay_cmn_info['uid']);
                $r_u_image = isset($user['image']) ? $user['image'] : "";;
            }elseif(2 == $replay_cmn_info['u_type']){//理财师
                $r_planner_info = Planner::model()->getPlannerById(array($replay_cmn_info['uid']));
                $r_planner_info = isset($r_planner_info[$replay_cmn_info['uid']]) ? $r_planner_info[$replay_cmn_info['uid']] : array();
                $r_name = isset($r_planner_info['name']) ? $r_planner_info['name'] : "";
                $r_u_image=isset($r_planner_info['image']) ? $r_planner_info['image'] : "";
                if(isset($r_planner_info['company_id']) && !empty($r_planner_info['company_id'])){

                    $company_infos = Planner::model()->getCompanyById(array($r_planner_info['company_id']));

                    $company_info = isset($company_infos[$r_planner_info['company_id']])?$company_infos[$r_planner_info['company_id']]:array();
                    $r_company=isset($company_info['name']) ? $company_info['name'] : '';
                }
            }elseif(3 == $replay_cmn_info['u_type']){//理财小妹
                throw new Exception('理财小妹的说说不用回复');
            }

            if($replay_cmn_info['uid']==$cmn_info['uid']){
                throw new Exception('自己回复自己的说说不用通知');
            }



            $type="";
            $link_url = '';
            $from = '';//来源
            if($replay_cmn_info['cmn_type'] == 1){//计划
                $type="plan";
                $link_url = "/plan/".$cmn_info['relation_id']."?type=comment#myComment"; //计划
                //计划信息
                $plan_info = Plan::model()->getPlanInfoById($cmn_info['relation_id']);
                if(!empty($plan_info)){
                    $plan_name = isset($plan_info['name']) ? $plan_info['name'] : '';
                    $plan_name .= (isset($plan_info['number']) && $plan_info['number'] > 9 ? $plan_info['number'] : "0" . $plan_info['number']) . "期";
                    $from = $plan_name;
                }

            }else if($replay_cmn_info['cmn_type'] == 2){ //观点
                if($cmn_info['relation_id'] > 0){ //观点评论
                    $type="view";
                    $link_url = "/view/".$cmn_info['relation_id']."#myComment";

                    //观点信息
                    $view_info = View::model()->getViewById($cmn_info['relation_id']);
                    $view_info = isset($view_info[$cmn_info['relation_id']]) ? $view_info[$cmn_info['relation_id']] : array();
                    $from = isset($view_info['title']) ? $view_info['title'] : '';
                }else{//观点包评论
                    $type="package";
                    $link_url = "/web/packageInfo?pkg_id=".$cmn_info['parent_relation_id']."#myComment";
                    $cmn_info['relation_id'] = $cmn_info['parent_relation_id'];

                    //观点包信息
                    $package_info = Package::model()->getPackagesById($cmn_info['parent_relation_id']);
                    $package_info = isset($package_info[$cmn_info['parent_relation_id']]) ? $package_info[$cmn_info['parent_relation_id']] : array();
                    $from = isset($package_info['title']) ? $package_info['title'] : '';
                }

            }else if($replay_cmn_info['cmn_type'] == 3){
                $type="topic";

                $topic_infos = Topic::model()->getTopicInfoIds($cmn_info['relation_id']);
                $topic_info = isset($topic_infos[$cmn_info['relation_id']])?$topic_infos[$cmn_info['relation_id']]:array();
                $from = isset($topic_info['title']) ? $topic_info['title'] : '';

            }else if($replay_cmn_info['cmn_type'] == 4){
                $type="system";

                $from="说说广场";
            }else {
                throw new Exception('无法处理的回复说说类型 cmn_type:'.$replay_cmn_info['cmn_type']);
            }

            $msg_data = array(
                'uid'=>$replay_cmn_info['uid'],
                'u_type'=>$replay_cmn_info['u_type'],  //普通用户   2理财师
                'type'=>16,
                'relation_id'=>$cmn_info['cmn_id'],
                'child_relation_id'=>$cmn_info['replay_id'],
                'content'=>json_encode(array(
                    array('value'=>$name."回复了您的评论".CHtml::encode($replay_cmn_info['content']),'class'=>'','link'=>""),  //当前用户为理财师的时候 显示连接
                ),JSON_UNESCAPED_UNICODE),
                'content_client'=>json_encode(array(
                    'cmn_type'=>$type,
                    "cmn_id"=>$cmn_info['cmn_id'],
                    'name' => $name,
                    'image' => $u_image,
                    'company' =>$company,
                    'content' => CHtml::encode($cmn_info['content']),
                    "r_cmn_id"=>$replay_cmn_info['cmn_id'],
                    'r_name' => $r_name,
                    'r_image' => $r_u_image,
                    'r_company' =>$r_company,
                    'r_content' => CHtml::encode($replay_cmn_info['content']),
                    'from' => $from,
                    'from_id' => $cmn_info['relation_id']
                ),JSON_UNESCAPED_UNICODE),
                'link_url'=>$link_url,
                'c_time' => date("Y-m-d H:i:s"),
                'u_time' => date("Y-m-d H:i:s")
            );

            //保存通知消息
            Message::model()->saveMessage($msg_data);

            //加入提醒队列
            $msg_data['content'] = json_decode($msg_data['content'],true);
            $push_uid=array();
            if($msg_data['u_type']==2){
                $push_uid[]=User::model()->getUidBySuid($msg_data['uid']);
            }else if($msg_data['u_type']==1){
                $push_uid[]=$msg_data['uid'];
            }

            $this->addToPushQueue($msg_data,$push_uid ,array (2,3));

            $log_data['uid']=$msg_data['uid'];
            $log_data['relation_id']=$msg_data['relation_id'];

        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }*/
}
