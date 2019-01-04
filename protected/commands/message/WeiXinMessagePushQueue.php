<?php
/**
 * 向微信发送模板消息的处理
 * User: zwg
 * Date: 2015/11/30
 * Time: 14:32
 */

class WeiXinMessagePushQueue {

    const CRON_NO = 1305; //任务代码
    const CRON_NO_TEMPLET = 1306; //模板消息任务代码
    const CRON_NO_IMAGE = 1307; //微信消息任务代码

    const QUEUE_KEY = "lcs_push_weixin_queue2";
    const TEMPLET_QUEUE_KEY = "lcs_push_weixin_queue2_templet";
    const IMAGE_QUEUE_KEY = "lcs_push_weixin_queue2_image";

    const QUEUE_CUST_MSG_KEY = "lcs_push_wx_cust_msg_queue";
    private $weixin = null;

    public function __construct(){
        if(empty($this->weixin)){
            $this->weixin = new WeixinApi();
        }
    }


    /**
     * 处理推送的消息
     */
    public function process(){
        //退出时间 每次随机向后推30-150秒
        $stop_time = time()+rand(2,10)*15;

        while (true){
            if(time()>$stop_time){
                break;
            }

            $sleep=true;
            $msg = Yii::app()->redis_w->lPop(self::QUEUE_KEY);
            if(!empty($msg)){
                $this->sendMessage($msg);
                $sleep=false;
            }
            $custMsg = Yii::app()->redis_w->lPop(self::QUEUE_CUST_MSG_KEY);
            if(!empty($custMsg)){
                $this->sendCustomMessage($custMsg);
                $sleep=false;
            }

            if($sleep){
                sleep(2);
            }
        }
    }

    /**
     * 单独处理微信模板消息 
     */
    public function newProcess(){
        //退出时间 每次随机向后推30-150秒
        $stop_time = time()+rand(2,10)*15;

        while (true){
            if(time()>$stop_time){
                break;
            }

            $sleep=true;
            $msg = Yii::app()->redis_w->lPop(self::TEMPLET_QUEUE_KEY);
            if(!empty($msg)){
				$this->sendMessage($msg);
                $sleep=false;
            }

            if($sleep){
                sleep(2);
            }
        }
    }
    /**
     * 图文推送
     */
    public function imagePush(){
        //退出时间 每次随机向后推30-150秒
        $stop_time = time()+rand(2,10)*15;

        while (true){
            if(time()>$stop_time){
                break;
            }

            $sleep=true;
            $msg = Yii::app()->redis_w->lPop(self::IMAGE_QUEUE_KEY);
            if(!empty($msg)){
                $this->sendCustomMessage($msg);
                $sleep=false;
            }

            if($sleep){
                sleep(2);
            }
        }
    }


    /**
     * 发送微信消息
     * @param $item
     */
    public function sendMessage($item){
        if(empty($item)){
            return;
        }
        echo date("Y-m-d H:i:s")," ",$item,"\r\n";
        // return;
        if(is_string($item)){
            $item = json_decode($item, true);
        }
        $res_item = array();
        $res_item['wx_uid'] = !empty($item['channel_id'])?$item['channel_id']:"channel_id is null";
        $res_item['s_uid'] = $item['s_uid'];
        $res_item['uid'] = $item['uid'];
        $res_item['message_relation_id'] = $item['message']['relation_id'];
        $res_item['message_type'] = $item['message']['type'];
        $res_item['message_id'] = !empty($item['message']['id'])?$item['message']['id']:"0";
        $res_item['message_content'] = json_encode($item);
        $res_item['send_status'] = -1;
        $time =  date('Y-m-d H:i:s');
        $res_item['c_time'] = $time;
        $res_item['u_time'] = $time;
        $wx_res = null;
        if($item['channel_type']==1 || $item['channel_type']==15){
            echo "推送类型:".$item['message']['type']."\r\n";
            switch ($item['message']['type']){
                case '1':
                    $wx_res = $this->sendTMessageOfAskReply($item);
                    break;
                case '4':
                    $wx_res = $this->sendTMessageOfPlanOperation($item);
                    break;
                case '5':
                    $wx_res = $this->sendTMessageOfPlanProfit($item);
                    break;
                case '21':
                    $wx_res = $this->sendTMessageOfCreateLive($item);
                    break;
                case '22':
                    $wx_res = $this->sendTMessageOfPushMsg($item);
                    break;
                case '23':
                    $wx_res = $this->sendTMessageOfActiveNotice($item);
                    break;
                case '24':
                    $wx_res = $this->sendTMessageOfStrategy($item);
                    break;
                case '70':
                    $wx_res = $this->sendTMessageOfTaoGuStrategy($item);
                    break;
                case '71':
                    $wx_res = $this->sendTMessageOfDepthView($item);
                    break;
                case '72':
                    $wx_res = $this->sendXcxServiceNotice($item);
                    break;
                case '73':
                    $wx_res = $this->sendXcxMessageReply($item);
                    break;
                default:
                    $wx_res = array('errcode'=>-2,'errmsg'=>'无法处理的消息类型');
                    break;
            }
        }
        
        $es_data = array(
            'logtime'=>time(),
            'uid'=>$res_item['uid'],
            'relatine_id'=>isset($item['message']['touser']) ? $item['message']['touser'] : 0,
            'message_type'=>$res_item['message_type'],
            'push_client'=>'wechat',
            'push_user'=>$res_item['uid'],
            'push_status'=>$wx_res['errcode'],
            'push_body'=>$res_item,
            'push_return'=>$wx_res,
        );
        var_dump($es_data);
        echo "es 日志-\r\n";
        echo yii::app()->redis_w->rpush('lcs_push_log_es',json_encode($es_data));

        if (!empty($wx_res) && isset($wx_res['errcode']) && intval($wx_res['errcode'])==0) {
            $res_item['wx_message_id'] = isset($wx_res['msgid']) ? $wx_res['msgid'] : 0;
            $res_item['send_status'] = 0;
        }else{
            $res_item['send_status'] = (isset($wx_res['errcode']) && intval($wx_res['errcode'])==-2) ? -2 : -1;
            $res_item['wx_message_id']=0;
            $res_item['wx_message_error'] = json_encode($wx_res,JSON_UNESCAPED_UNICODE);
        }

        try {
            Weixin::model()->saveWxMsgResult($res_item);
        }catch (Exception $e){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '记录微信消息发送结果错误 error:'.$e->getMessage());
        }
    }


    /**
     * 发送用户问题被处理的模板消息
     * @param unknown $item
     * @return void|boolean
     */
    private function sendTMessageOfAskReply($item){

        $touser = $item['channel_id'];
        $pushWechat = $item['channel_type'];
        $message = json_decode($item['message']['content_client'],true);
        $time = $item['message']['c_time'];
        //问答提醒模板
        //$message_json = '[{"value":"杨茂","class":"msg_planner","link":"/planner/1661790084/1"},{"value":"回答了您的问题","class":"msg_msg","link":""},{"value":"$华海药业(sh600521)$ 123","class":"msg_q_title","link":"/ask/62573"}]';
        //$message = json_decode($message_json,true);
        $serviceInfo = '';
        $serviceStatus = '';
        // status 1:提问   2:拒绝回答  3:回答   4:追问    5:追问回答  -1:长时间未回答
        if($message['status']==1){
            $serviceInfo = '有新的用户提问：《'.$message['content']."》\n";
            $serviceStatus = '新问题';
        }else if($message['status']==4){
            $serviceInfo = '有新的用户追问：《'.$message['content']."》\n";
            $serviceStatus = '新问题';
        }else if($message['status']==2){
            $serviceInfo = '理财师'.$message['planner_name'].'无法回答您的问题《'.$message['content']."》\n";
            $serviceStatus = '未解决';
        }else if($message['status']==-1){
            $serviceInfo = '理财师'.$message['planner_name'].'长时间没有回答您的问题《'.$message['content']."》\n";
            $serviceStatus = '未解决';
        }else if($message['status']==3){
            $serviceInfo = '理财师'.$message['planner_name'].'回答了您的问题《'.$message['content']."》\n";
            $serviceStatus = '已解决';
        }else if($message['status']==5){
            $serviceInfo = '理财师'.$message['planner_name'].'补充回答了您的问题《'.$message['content']."》\n";
            $serviceStatus = '已解决';
        }

        $msg = array();
        $msg['touser']= $touser;
        $msg['template_id'] = '2JiZAF7KOjF8syuIydjwBC1TRL-Jpj-exYFYwB3bRlI';
        //推送类型
        $msg['pushWechat'] = $pushWechat;
        $msg['url']= '';
        $msg['topcolor']= '#FF0000';
        $msg['data'] = array(
            'serviceInfo'=>array('value'=>$serviceInfo, 'color'=>'#000000'),
            'serviceType'=>array('value'=>'问题咨询', 'color'=>'#000000'),
            'serviceStatus'=>array('value'=>$serviceStatus, 'color'=>'#000000'),
            'time'=>array('value'=>$time, 'color'=>'#000000'),
            'remark'=>array('value'=>"\n".'最权威，最专业的理财师一直在新浪理财师平台恭候您！', 'color'=>'#000000')
        );
        //echo json_encode($msg,JSON_UNESCAPED_UNICODE);
        return $this->weixin->sendTemplateMessage($msg);
    }


    /**
     * 计划建仓和平仓操作通知的消息模板
     * @param unknown $item
     * @return array
     */
    private function sendTMessageOfPlanOperation($item){
        $to_user = $item['channel_id'];
        $pushWechat = $item['channel_type'];
        $time = $item['message']['c_time'];
        $message = json_decode($item['message']['content_client'],true);
        $wgt_before = $message['wgt_before'];
        $wgt_after = $message['wgt_after'];
        $reason = $message['reason'];
        $is_buy = $message['trans_type']==1? true : false;

        $dift = '';

        $template_id = '';
        $title = '《'.$message['plan_name'].'》在'.$time;
        if($is_buy){
            $template_id = (defined('ENV') && ENV == 'dev') ? 'Cbxx6aDheVw146j7kQcThaAQnBOrD_cUAuXrmedN4OY' : 'kXYA7hjbwK3FwiY1wnn1YcQ93rlh1M2oV0FbCXiiDc8';//'YnkVj07aXMqhXIu5LjeByehHJp2CS0Zk0V6aqkblkL0';
            $title .= '有新的买入建仓成交';
            $dift = '由'.$wgt_before.'%增加至'.$wgt_after.'%';
        }else{
            $template_id = (defined('ENV') && ENV == 'dev') ? 'DWwvuCOTHF9QgUQOT7RY9AhqXoizP7csRw6PhmtXdGs' : 'ggOUQrIw39vbn7PfVJhzCYKWzgAf_fUYxrbl_XXuEKU';//'dt3nHpJZS8cIxkTu3dEskjm8CC4d116SmjauE2Zv2_0';
            $title .= '有新的卖出平仓成交';
            $dift = '由'.$wgt_before.'%减少至'.$wgt_after.'%';
        }
        if (!empty($reason)){
            $reason = '操作理由：'.CommonUtils::getSubStrNew($reason, 140, '...');
        }else {
            $reason = '新浪理财师计划选股、选时、仓位使用等信息仅供参考，您须独立作出投资决策。';
        }

        $msg = array();
        $msg['touser']= $to_user;
        $msg['template_id'] = $template_id;
        //推送类型
        $msg['pushWechat'] = $pushWechat;

        $msg['url']= 'http://licaishi.sina.com.cn/wap/planInfo?pln_id='.$message['pln_id']; //$this->weixin->getOauth2Url('weixinplandynamic,'.$message['pln_id'].','.$message['symbol'].','.$time,$pushWechat);
        $msg['topcolor']= '#FF0000';
        $msg['data'] = array(
            'first'=>array('value'=>$title, 'color'=>'#000000'),
            'keyword1'=>array('value'=>$message['stock_name'].'（'.$message['symbol'].'）', 'color'=>'#000000'), //品种名称
            'keyword2'=>array('value'=>$message['deal_amount'], 'color'=>'#000000'), //成交量
            'keyword3'=>array('value'=>$message['deal_price'].'元', 'color'=>'#000000'), //成交价
            'keyword4'=>array('value'=>$dift, 'color'=>'#000000'), //仓位变化
            'remark'=>array('value'=>"\n".$reason, 'color'=>'#000000')
        );
        if(!$is_buy){
            //update by zwg 20150126 修改显示收益信息 增加对个股的收益
            $single_ratio = $message['single_ratio']!='0.00' ? $message['single_ratio'] : '小于'.(intval($message['profit']) > 0  ? '0.01':'-0.01');
            $profit_ratio = $message['profit_ratio']!='0.00' ? $message['profit_ratio'] : '小于'.(intval($message['profit']) > 0  ? '0.01':'-0.01');
            $profit = isset($message['profit']) ? $single_ratio.'% (对计划收益贡献'.$profit_ratio.'%)' : '';
            $profit_color = '#000000';
            if(intval($message['profit'])>0){
                $profit_color = '#E85B43';
            }else if(intval($message['profit'])<0){
                $profit_color = '#009933';
            }
            $msg['data']['keyword5'] = array('value'=>$profit, 'color'=>$profit_color); //成交收益
        }

        //echo json_encode($msg,JSON_UNESCAPED_UNICODE);
        return $this->weixin->sendTemplateMessage($msg);
    }


    /**
     * 计划收益通知的模板
     * TODO 终止原因   和订阅价格没有在 content_client里面
     * @param unknown $item
     * @return array
     */
    private function sendTMessageOfPlanProfit($item){
        $touser = $item['channel_id'];
        $pushWechat = $item['channel_type'];
        $message = json_decode($item['message']['content_client'],true);
        $time = $item['message']['c_time'];
        $first = '《'.$message['plan_name'].'》已于'.$time.'终止。';
        $subscription_price = intval($item['message']['subscription_price']);
        $subscription_price = $subscription_price==0 ? '免费' : $subscription_price.'元';
        $subscription_price .= $message['status']==5?' (您可以申请退款)':' (将支付给理财师)';

        $curr_ror_color = '#000000';
        if(intval($message['curr_ror'])>0){
            $curr_ror_color = '#E85B43';
        }else if(intval($message['curr_ror'])<0){
            $curr_ror_color = '#009933';
        }
        $msg = array();
        $msg['touser']= $touser;
        $msg['template_id'] = (defined('ENV') && ENV == 'dev') ? 'EY8wl6u1w6d25SgNI6SxUkAdMGkXlncvRBdlEeGLKCo' : '-poG9iNK4K_FTyVheP_eRDzhV3WzrjJMQzAdE63GeFg';//'SCD7sGdGOuLANAzU8M3MfNF86v_KtdqXcLj81FS_80Y';
        //微信通知渠道
        $msg['pushWechat'] = $pushWechat;
        $msg['url']= '';
        $msg['topcolor']= '#FF0000';
        $msg['data'] = array(
            'first'=>array('value'=>$first, 'color'=>'#000000'),
            'keyword1'=>array('value'=>$item['message']['stop_reason'], 'color'=>'#000000'),  //终止原因
            'keyword2'=>array('value'=>$message['curr_ror'].'%', 'color'=>$curr_ror_color), //实际收益
            'keyword3'=>array('value'=>$message['target_ror'].'%', 'color'=>'#000000'), //目标收益
            'keyword4'=>array('value'=>$message['status']==5?'目标未达成':'目标已达成', 'color'=>'#000000'), //计划结果
            'keyword5'=>array('value'=>$subscription_price, 'color'=>'#000000'), //购买费用
            'remark'=>array('value'=>"\n".'新浪理财师计划选股、选时、仓位使用等信息仅供参考，您须独立作出投资决策。', 'color'=>'#000000')
        );

        //echo json_encode($msg,JSON_UNESCAPED_UNICODE);
        Common::model()->saveLog(json_encode($msg),"info","plan_profit");
        return $this->weixin->sendTemplateMessage($msg);
    }

    /**
     * 理财师创建直播和直播即将开始通知
     * @param  [type] $item [description]
     * @return [type]       [description]
     */
    private function sendTMessageOfCreateLive($item){
        $touser = $item['channel_id'];
        $pushWechat = $item['channel_type'];
        $message = json_decode($item['message']['content_client'],true);
        if ($message['status'] == 0) {
            $first = "{$message['p_name']}的直播预告";
            $keyword2 = date("m月d日 H:i", strtotime($message['start_time']));
        } elseif ($message['status'] == 1) {
            $first = "{$message['p_name']}的直播即将开始";
            $keyword2 = floor((strtotime($message['start_time'])-time())/60) . "分钟后";
        } else {
            $first = "{$message['p_name']}的直播";
            $keyword2 = $message['start_time'];
        }
        if ($message['type'] == 1) {
            $keyword1 = "视频直播";
        } elseif ($message['type'] == 2) {
            $keyword1 = "图文直播";
        } else {
            $keyword1 = "直播";
        }
        $remark = $message['content'];

        $msg = array();
        $msg['touser']= $touser;
        //微信通知渠道
        $msg['pushWechat'] = $pushWechat;
        $msg['template_id'] = '6gj7kBaoy63iaqUeZwxbJlmoJdZQff1tOIA7JX3vq-I';
        $msg['url']= "http://licaishi.sina.com.cn/wap/videoLive?liveid={$message['live_id']}";
        $msg['topcolor']= '#FF0000';
        $msg['data'] = array(
            'first'=>array('value'=>"$first", 'color'=>'#000000'),
            'keyword1'=>array('value'=>"$keyword1", 'color'=>'#000000'),
            'keyword2'=>array('value'=>"$keyword2", 'color'=>'#E85B43'),
            'remark'=>array('value'=>"\n{$remark}", 'color'=>'#000000')
        );

        //echo json_encode($msg,JSON_UNESCAPED_UNICODE);
        return $this->weixin->sendTemplateMessage($msg);
    }

    /**
     * 投资策略提醒
     * @param unknown $item
     * @return array
     */
    public function sendTMessageOfStrategy($item){
        $touser = $item['channel_id'];
        $pushWechat = $item['channel_type'];
	if(isset($item['message']['content'])){
	    $content = $item['message']['content'];
	    try{
	    	$content = json_decode($content,true);
	    }catch(Exception $e){
		Common::model()->saveLog("发送微信消息错误:".json_encode($item),'error','weixin_template');
	    	return false;
	    }
	}else{
	    Common::model()->saveLog("发送微信消息错误:".json_encode($item),'error','weixin_template');
	    return false;
	}
        $first = $content['msg1'];
        $title = $content['msg2'];
        $service_time = $content['msg3'];
        $remark = $content['msg4'];
        $msg = array();
        $msg['touser']= $touser;
        //微信通知渠道
        $msg['pushWechat'] = $pushWechat;
        $msg['template_id'] = '85ovFXY9OE0yXO5PQQd-CJ2FJ3SchIrCQZi6Y_FuaxM';
        $msg['url']= $content['url'];
        $msg['topcolor']= $content['msg1_color'];
        $msg['data'] = array(
            'first'=>array('value'=>$first, 'color'=>$content['msg1_color']),
            'keyword1'=>array('value'=>$title, 'color'=>$content['msg2_color']),
            'keyword2'=>array('value'=>$service_time, 'color'=>$content['msg3_color']),
            'remark'=>array('value'=>$remark, 'color'=>$content['msg4_color'])
        );
        //echo json_encode($msg,JSON_UNESCAPED_UNICODE);
        return $this->weixin->sendTemplateMessage($msg);
    }

    /**
     * 活动提醒
     * @param unknown $item
     * @return array
     */
    public function sendTMessageOfActiveNotice($item){
        $touser = $item['channel_id'];
        $pushWechat = $item['channel_type'];
	if(isset($item['message']['content'])){
	    $content = $item['message']['content'];
	    try{
	    	$content = json_decode($content,true);
	    }catch(Exception $e){
		Common::model()->saveLog("发送微信消息错误:".json_encode($item),'error','weixin_template');
	    	return false;
	    }
	}else{
	    Common::model()->saveLog("发送微信消息错误:".json_encode($item),'error','weixin_template');
	    return false;
	}
        $first = $content['msg1'];
        $title = $content['msg2'];
        $service_time = $content['msg3'];
        $remark = $content['msg4'];
        $msg = array();
        $msg['touser']= $touser;
        $msg['template_id'] = '6XlSC7VTZExyQxt9Tc_GOcT-721nP090AaEgPFbAsDI';
        //微信推送渠道
        $msg['pushWechat'] = $pushWechat;
        $msg['url']= $content['url'];
        $msg['topcolor']= $content['msg1_color'];
        $msg['data'] = array(
            'first'=>array('value'=>$first, 'color'=>$content['msg1_color']),
            'keyword1'=>array('value'=>$title, 'color'=>$content['msg2_color']),
            'keyword2'=>array('value'=>$service_time, 'color'=>$content['msg3_color']),
            'remark'=>array('value'=>$remark, 'color'=>$content['msg4_color'])
        );
        //echo json_encode($msg,JSON_UNESCAPED_UNICODE);
        return $this->weixin->sendTemplateMessage($msg);
    }

    /**
     * 投顾服务通知
     * @param unknown $item
     * @return array
     */
    public function sendTMessageOfPushMsg($item){
        $touser = $item['channel_id'];
        $pushWechat = $item['channel_type'];
	if(isset($item['message']['content'])){
	    $content = $item['message']['content'];
	    try{
	    	$content = json_decode($content,true);
	    }catch(Exception $e){
		Common::model()->saveLog("发送微信消息错误:".json_encode($item),'error','weixin_template');
	    	return false;
	    }
	}else{
	    Common::model()->saveLog("发送微信消息错误:".json_encode($item),'error','weixin_template');
	    return false;
	}
        $first = $content['msg1'];
        $title = $content['msg2'];
        $service_time = $content['msg3'];
        $remark = $content['msg4'];
        $msg = array();
        $msg['touser']= $touser;
        //微信推送渠道
        $msg['pushWechat'] = $pushWechat;
        $msg['template_id'] = 'r2oZrfIHYHYSihCXWap_E7Tnm50ByysxzuVWggABQ6k';
        $msg['url']= $content['url'];
        $msg['topcolor']= $content['msg1_color'];
        $msg['data'] = array(
            'first'=>array('value'=>$first, 'color'=>$content['msg1_color']),
            'keyword1'=>array('value'=>$title, 'color'=>$content['msg2_color']),
            'keyword2'=>array('value'=>$service_time, 'color'=>$content['msg3_color']),
            'remark'=>array('value'=>$remark, 'color'=>$content['msg4_color'])
        );
        //echo json_encode($msg,JSON_UNESCAPED_UNICODE);
        return $this->weixin->sendTemplateMessage($msg);
    }

    /**
     * 投顾服务通知
     * @param unknown $item
     * @return array
     */
    public function sendTMessageOfPlannerService($item){
        $touser = $item['channel_id'];
        $pushWechat = $item['channel_type'];
        $first = '尊敬的新浪理财师客户:';
        $title = '石寿玉狙击热点计划01期';
        $service_time = '2016-7-8 - 2016-8-6';
        $remark = '10万入市，18个月零5天赚280倍，实盘高手石寿玉来开炒股计划啦！本周一出手即中富临精工两天赚15%。280倍收益如何打造？';
        $msg = array();
        $msg['touser']= $touser;
        //微信推送渠道
        $msg['pushWechat'] = $pushWechat;
        $msg['template_id'] = 'r2oZrfIHYHYSihCXWap_E7Tnm50ByysxzuVWggABQ6k';
        $msg['url']= 'http://finance.sina.com.cn/stock/2016-07-05/doc-ifxtsatn8132162.shtml';
        $msg['topcolor']= '#FF0000';
        $msg['data'] = array(
            'first'=>array('value'=>$first, 'color'=>'#000000'),
            'keyword1'=>array('value'=>$title, 'color'=>'#000000'),
            'keyword2'=>array('value'=>$service_time, 'color'=>'#000000'),
            'remark'=>array('value'=>$remark, 'color'=>'#000000')
        );

        //echo json_encode($msg,JSON_UNESCAPED_UNICODE);
        return $this->weixin->sendTemplateMessage($msg);
    }

    public function sendTMessageOfPlannerLiveTmp($item){
        $url      = $item['url'];
        $touser   = $item['channel_id'];
        $pushWechat = $item['channel_type'];
        $first    = $item['first'];
        $keyword1 = $item['keyword1'];
        $keyword2 = $item['keyword2'];
        $remark   = $item['remark'];

        $msg = array();
        $msg['template_id'] = '6gj7kBaoy63iaqUeZwxbJlmoJdZQff1tOIA7JX3vq-I';
        $msg['topcolor']    = '#FF0000';
        //微信推送渠道
        $msg['pushWechat'] = $pushWechat;
        $msg['url']         = $url;
        $msg['touser']      = $touser;
        $msg['data']        = array(
            'first'    => array('value'=>$first, 'color'=>'#000000'),
            'keyword1' => array('value'=>$keyword1, 'color'=>'#000000'),
            'keyword2' => array('value'=>$keyword2, 'color'=>'#E85B43'),
            'remark'   => array('value'=>$remark, 'color'=>'#000000')
        );

        //echo json_encode($msg,JSON_UNESCAPED_UNICODE);
        return $this->weixin->sendTemplateMessage($msg);
    }

    /**
     * 深度观点推送
     * @param $item
     * @return array|mixed
     */
    public function sendTMessageOfDepthView($item){
        $url      = $item['message']['url'];
        $pagepath = $item['message']['pagepath'];
        $touser   = $item['channel_id'];
        $pushWechat = $item['channel_type'];
        $first    = $item['message']['first'];
        $keyword1 = $item['message']['keyword1'];
        $keyword2 = '深度观点内容更新';
        $keyword3 = $item['message']['keyword3'];

        $msg = array();
        $msg['template_id'] = (defined('ENV') && ENV == 'dev') ? 'ZAo0MbIpwtQdHkjsAkFctQXZDlchGbwpkeWYs7JGmeM' : 'A0hWhwRmVYm55ult2M1zDus-T7H02pUq5H9au1k2fxY';
        $msg['topcolor']    = '#FF0000';
        //微信推送渠道
        $msg['pushWechat'] = $pushWechat;
        $msg['url']         = $url;
        $msg['miniprogram'] = array('appid'=>'wx166d1d432d032c87','pagepath'=>$pagepath);
        $msg['touser']      = $touser;
        $msg['data']        = array(
            'first'    => array('value'=>$first, 'color'=>'#000000'),
            'keyword1' => array('value'=>$keyword1, 'color'=>'#000000'),
            'keyword2' => array('value'=>$keyword2, 'color'=>'#000000'),
            'keyword3' => array('value'=>$keyword3, 'color'=>'#000000')
        );

        return $this->weixin->sendTemplateMessage($msg);
    }

    /**
     * 淘股策略推送
     * @param $item
     * @return array|mixed
     */
    public function sendTMessageOfTaoGuStrategy($item){
        $url      = $item['message']['url'];
        $pagepath = $item['message']['pagepath'];
        $touser   = $item['channel_id'];
        $pushWechat = $item['channel_type'];
        $first    = $item['message']['first'];
        $keyword1 = $item['message']['keyword1'];
        $keyword2 = $item['message']['keyword2'];
        $keyword3 = $item['message']['keyword3'];
        $keyword4 = $item['message']['keyword4'];
        $keyword5 = $item['message']['keyword5'];
        $remark   = $item['message']['remark'];

        $msg = array();
        $msg['template_id'] = (defined('ENV') && ENV == 'dev') ? 'hRHXBfwpAGK9SQ1IKOs_y4Z3W4Fa3abHvic91HCs71k' : 'izKO_K6QN_3HNTH9MDNe6YCy_68sVvg00aa2aUkUjQw';
        $msg['topcolor']    = '#FF0000';
        //微信推送渠道
        $msg['pushWechat'] = $pushWechat;
        $msg['url']         = $url;
        $msg['touser']      = $touser;
        $msg['miniprogram'] = array('appid'=>'wx166d1d432d032c87','pagepath'=>$pagepath);
        $msg['data']        = array(
            'first'    => array('value'=>$first, 'color'=>'#000000'),
            'keyword1' => array('value'=>$keyword1, 'color'=>'#000000'),
            'keyword2' => array('value'=>$keyword2, 'color'=>'#000000'),
            'keyword3' => array('value'=>$keyword3, 'color'=>'#000000'),
            'keyword4' => array('value'=>$keyword4, 'color'=>'#000000'),
            'keyword5' => array('value'=>$keyword5, 'color'=>'#000000'),
            'remark'   => array('value'=>$remark, 'color'=>'#000000')
        );

        return $this->weixin->sendTemplateMessage($msg);
    }

    //小程序服务状态通知
    public function sendXcxServiceNotice($item){
        $pagepath = $item['message']['pagepath'];
        $touser = $item['message']['touser'];
        $formId   = $item['message']['form_id'];
        $keyword1 = $item['message']['keyword1'];
        $keyword2 = $item['message']['keyword2'];
        $keyword3 = $item['message']['keyword3'];
        $keyword4 = $item['message']['keyword4'];

        $msg = array();
        $msg['template_id'] = 'vfDIMSikL20eWkbALsDLIXdzj2_rqP8TWSpTCpcH0ao';
        $msg['page'] = $pagepath;
        $msg['form_id'] = $formId;
        $msg['touser'] = $touser;
        $msg['data'] = array(
            'keyword1' => array('value'=>$keyword1),
            'keyword2' => array('value'=>$keyword2),
            'keyword3' => array('value'=>$keyword3),
            'keyword4' => array('value'=>$keyword4),
        );

        return $this->weixin->sendXcxTemplateMessage($msg);
    }

    //小程序消息回复
    public function sendXcxMessageReply($item){
        $pagepath = $item['message']['pagepath'];
        $touser = $item['message']['touser'];
        $formId   = $item['message']['form_id'];
        $keyword1 = $item['message']['keyword1'];
        $keyword2 = $item['message']['keyword2'];
        $keyword3 = $item['message']['keyword3'];
        $keyword4 = $item['message']['keyword4'];

        $msg = array();
        $msg['template_id'] = 'Dgwu3wkKojDwJL-v5EXZ-HFPJ1MVayBUBJzz_SDG96E';
        $msg['page'] = $pagepath;
        $msg['form_id'] = $formId;
        $msg['touser'] = $touser;
        $msg['data'] = array(
            'keyword1' => array('value'=>$keyword1),
            'keyword2' => array('value'=>$keyword2),
            'keyword3' => array('value'=>$keyword3),
            'keyword4' => array('value'=>$keyword4),
        );

        return $this->weixin->sendXcxTemplateMessage($msg);
    }

    /**
     * 发送客服消息
     * @param $msg  type [text|news] openId   content 字符串或是[{title,description,url,picurl}]
     */
    public function sendCustomMessage($msg){
        if(empty($msg)){
            return;
        }
        echo date("Y-m-d H:i:s")," ",$msg,"\r\n";
        if(is_string($msg)){
            $msg = json_decode($msg, true);
        }
        if(empty($msg) || !isset($msg['type']) || empty($msg['openId']) || empty($msg['content'])){
            return;
        }
        $custMsg=array();
        $custMsg['touser']=$msg['openId'];
        $custMsg['pushWechat']=!empty($msg['channel_type'])?$msg['channel_type']:"default";
        switch ($msg['type']){
            case 'text':
                $custMsg['msgtype']='text';
                $custMsg['text'] = array('content'=>$msg['content']);
                break;
            case 'news':
                $custMsg['msgtype']='news';
                $custMsg['news'] = array('articles'=>$msg['content']);
                break;
            case 'image':
                $custMsg['msgtype']='image';
                $custMsg['image'] = array('media_id'=>$msg['content']);
            default:
                $custMsg=null;
                break;
        }
        //发送
        if(!empty($custMsg)){
            echo date("Y-m-d H:i:s"),"  send custMsg:",json_encode($custMsg,JSON_UNESCAPED_UNICODE),"\r\n";
            $wx_res = $this->weixin->sendCustomMessage($custMsg);
            $es_data = array(
                'logtime'=>time(),
                'uid'=>$msg['uid'],
                'relatine_id'=>0,
                'message_type'=>$msg['channel_type'],
                'push_client'=>'wechat_image',
                'push_user'=>$msg['uid'],
                'push_status'=>$wx_res['errcode'],
                'push_body'=>$msg,
                'push_return'=>$wx_res,
            );
            echo "es 日志-\r\n";
            echo yii::app()->redis_w->rpush('lcs_push_log_es',json_encode($es_data));
            if (!empty($wx_res) && isset($wx_res['errcode']) && intval($wx_res['errcode'])==0) {
                if (!empty($wx_res) && isset($wx_res['errcode']) && intval($wx_res['errcode'])==0) {
                    $res_item['wx_message_id'] = "1";
                    $res_item['send_status'] = 0;
                }else{
                    $res_item['send_status'] = (isset($wx_res['errcode']) && intval($wx_res['errcode'])==-2) ? -2 : -1;
                    $res_item['wx_message_id']=0;
                    $res_item['wx_message_error'] = json_encode($wx_res,JSON_UNESCAPED_UNICODE);
                }

                try {
                    Weixin::model()->saveWxMsgResult($res_item);
                }catch (Exception $e){
                    Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '记录微信消息发送结果错误 error:'.$e->getMessage());
                }
            }else{
                echo date("Y-m-d H:i:s")," send custMsgError:",json_encode($wx_res),"\r\n";
            }
        }else{
            return;
        }
    }


}
