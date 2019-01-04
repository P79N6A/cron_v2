<?php
/**
 * 向微信第三方公众账号发送模板消息的处理
 * User: zwg
 * Date: 2016/09/09
 * Time: 14:32
 */

class WeiXinThirdMsgPushQueue {

    const CRON_NO = 1313; //任务代码

    const QUEUE_KEY = "lcs_push_weixin_third_queue";
    private $weixin = null;

    public function __construct(){
        if(empty($this->weixin)){
            $this->weixin = new WeixinComponentApi();
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
            $msg = Yii::app()->redis_w->lPop(self::QUEUE_KEY);
            if(!empty($msg)){
                $this->sendMessage($msg);
            }else{
                sleep(2);
            }
        }
    }


    /**
     * 发送微信消息
     *
     * 消息结构中必须包含的消息内容   uid  channel_id  wx_app_id message
     *
     * @param $item
     */
    public function sendMessage($item){
        if(empty($item)){
            return;
        }
        echo date("Y-m-d H:i:s")," ",$item,"\r\n";
        if(is_string($item)){
            $item = json_decode($item, true);
        }
        $res_item = array();
        $res_item['wx_uid'] = $item['channel_id'];
        $res_item['wx_app_id'] = $item['wx_app_id'];
        $res_item['uid'] = $item['uid'];
        $res_item['message_relation_id'] = $item['message']['relation_id'];
        $res_item['message_type'] = $item['message']['type'];
        $res_item['message_content'] = json_encode($item);
        $res_item['send_status'] = -1;
        $time =  date('Y-m-d H:i:s');
        $res_item['c_time'] = $time;
        $res_item['u_time'] = $time;

        $wx_res = null;

        if(empty($wx_res)){
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
                default:
                    $wx_res = array('errcode'=>-2,'errmsg'=>'无法处理的消息类型');
                    break;
            }

        }

        if (!empty($wx_res) && isset($wx_res['errcode']) && intval($wx_res['errcode'])==0) {
            $res_item['wx_message_id'] = $wx_res['msgid'];
            $res_item['send_status'] = 0;
        }else{
            $res_item['send_status'] = (isset($wx_res['errcode']) && intval($wx_res['errcode'])==-2) ? -2 : -1;
            $res_item['wx_message_id']=0;
            $res_item['wx_message_error'] = json_encode($wx_res,JSON_UNESCAPED_UNICODE);
        }

        try {
            WeixinTS::model()->saveWxtsMsgLog($res_item);
        }catch (Exception $e){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '记录微信消息发送结果错误 error:'.$e->getMessage());
        }
    }


    /**
     * 发送用户问题被处理的模板消息
     * @param json $item 消息内容
     * @return void|boolean
     */
    private function sendTMessageOfAskReply($item){
        $touser = $item['channel_id'];
        $message = json_decode($item['message']['content_client'],true);
        $time = $item['message']['c_time'];
        //问答提醒模板
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

        //获取通知的模板ID
        $template_id=WeixinTS::model()->getMsgTemplateId($item['wx_app_id'],"1");
        if(empty($template_id)){
            return  array('errcode'=>-2,'errmsg'=>'template_id is null');
        }

        $msg = array();
        $msg['touser']= $touser;
        $msg['template_id'] = $template_id;
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
        return $this->weixin->sendTemplateMessage($msg,$item['wx_app_id']);
    }


    /**
     * 计划建仓和平仓操作通知的消息模板
     * @param unknown $item
     * @return array
     */
    private function sendTMessageOfPlanOperation($item){
        $to_user = $item['channel_id'];
        $time = $item['message']['c_time'];
        $message = json_decode($item['message']['content_client'],true);
        $wgt_before = $message['wgt_before'];
        $wgt_after = $message['wgt_after'];
        $reason = $message['reason'];
        $is_buy = $message['trans_type']==1? true : false;

        //获取通知的模板ID
        $template_id =WeixinTS::model()->getMsgTemplateId($item['wx_app_id'],$is_buy?"4_1":"4_2");
        if(empty($template_id)){
            return  array('errcode'=>-2,'errmsg'=>'template_id is null');
        }

        $dift = '';
        $title = '《'.$message['plan_name'].'》在'.$time;
        if($is_buy){
            $title .= '有新的买入建仓成交';
            $dift = '由'.$wgt_before.'%增加至'.$wgt_after.'%';
        }else{
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
        $msg['url']= "";//$this->weixin->getOauth2Url('weixinplandynamic,'.$message['pln_id'].','.$message['symbol'].','.$time);
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
        return $this->weixin->sendTemplateMessage($msg,$item['wx_app_id']);
    }


    /**
     * 计划收益通知的模板
     * TODO 终止原因   和订阅价格没有在 content_client里面
     * @param unknown $item
     * @return array
     */
    private function sendTMessageOfPlanProfit($item){
        $touser = $item['channel_id'];
        $message = json_decode($item['message']['content_client'],true);
        $time = $item['message']['c_time'];
        $first = '《'.$message['plan_name'].'》已于'.$time.'终止。';
        $subscription_price = intval($item['message']['subscription_price']);
        $subscription_price = $subscription_price==0 ? '免费' : $subscription_price.'元';
        $subscription_price .= $message['status']==5?' (您可以申请退款)':' (将支付给理财师)';

        //获取通知的模板ID
        $template_id=WeixinTS::model()->getMsgTemplateId($item['wx_app_id'],"5");
        if(empty($template_id)){
            return  array('errcode'=>-2,'errmsg'=>'template_id is null');
        }


        $curr_ror_color = '#000000';
        if(intval($message['curr_ror'])>0){
            $curr_ror_color = '#E85B43';
        }else if(intval($message['curr_ror'])<0){
            $curr_ror_color = '#009933';
        }
        $msg = array();
        $msg['touser']= $touser;
        $msg['template_id'] = $template_id;
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
        return $this->weixin->sendTemplateMessage($msg,$item['wx_app_id']);
    }

    /**
     * 理财师创建直播和直播即将开始通知
     * @param  [type] $item [description]
     * @return [type]       [description]
     */
    private function sendTMessageOfCreateLive($item){
        $touser = $item['channel_id'];
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

        //获取通知的模板ID
        $template_id=WeixinTS::model()->getMsgTemplateId($item['wx_app_id'],"21");
        if(empty($template_id)){
            return  array('errcode'=>-2,'errmsg'=>'template_id is null');
        }

        $msg = array();
        $msg['touser']= $touser;
        $msg['template_id'] = $template_id;
        $msg['url']= "http://licaishi.sina.com.cn/wap/videoLive?liveid={$message['live_id']}";
        $msg['topcolor']= '#FF0000';
        $msg['data'] = array(
            'first'=>array('value'=>"$first", 'color'=>'#000000'),
            'keyword1'=>array('value'=>"$keyword1", 'color'=>'#000000'),
            'keyword2'=>array('value'=>"$keyword2", 'color'=>'#E85B43'),
            'remark'=>array('value'=>"\n{$remark}", 'color'=>'#000000')
        );

        //echo json_encode($msg,JSON_UNESCAPED_UNICODE);
        return $this->weixin->sendTemplateMessage($msg,$item['wx_app_id']);
    }
}