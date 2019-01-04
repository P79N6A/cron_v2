<?php

/**
 * 第三方消息接口推送
 *
 */
class ThirdPartyPushQueue
{
    const CRON_NO = 1319; //任务代码
    const QUEUE_KEY = "lcs_push_client_third_party_queue";
    private $_msg_api;

    public function __construct(){
        $this->_msg_api = new ThirdPartyMsg();
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

            $sleep = true;
            $msg = Yii::app()->redis_w->lPop(self::QUEUE_KEY);
            if (!empty($msg)) {
                $this->sendMessage($msg);
                $sleep = false;
            }

            if ($sleep) {
                sleep(2);
            }
        }
    }


    /**
     * 发送第三方消息
     * @param $item
     */
    public function sendMessage($item) {
        if (empty($item)) {
            return;
        }
        echo date("Y-m-d H:i:s")," ",$item,"\r\n";

        if (is_string($item)) {
            $item = json_decode($item, true);
        }

        $res_item = array();
        $res_item['wx_uid'] = $item['channel_id'];
        $res_item['s_uid'] = $item['s_uid'];
        $res_item['uid'] = $item['uid'];
        $res_item['message_relation_id'] = $item['message']['relation_id'];
        $res_item['message_type'] = $item['message']['type'];
        $res_item['message_id'] = $item['message']['id'];
        $res_item['message_content'] = json_encode($item);
        $res_item['send_status'] = -1;
        $time =  date('Y-m-d H:i:s');
        $res_item['c_time'] = $time;
        $res_item['u_time'] = $time;

        $item['message_id'] = $item['message']['id'];
        $wx_res = null;
        if($item['channel_type']==6){
            switch ($item['message']['type']){
                case '4':
                    $wx_res = $this->sendTMessageOfPlanOperation($item);
                    break;
                case '24':
                    $wx_res = $this->sendTMessageOfPlanSubExpire($item);
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
            Weixin::model()->saveWxMsgResult($res_item);
        }catch (Exception $e){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '记录第三方消息发送结果错误 error:'.$e->getMessage());
        }
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

        $dift = '';

        $template_id = '';
        $title = '《'.$message['plan_name'].'》在'.$time;
        if($is_buy){
            $template_id = '71';
            $title .= '有新的买入建仓成交';
            $dift = '由'.$wgt_before.'%增加至'.$wgt_after.'%';
        }else{
            $template_id = '72';
            $title .= '有新的卖出平仓成交';
            $dift = '由'.$wgt_before.'%减少至'.$wgt_after.'%';
        }
        if (!empty($reason)){
            $reason = '操作理由：'.CommonUtils::getSubStrNew($reason, 140, '...');
        }else {
            $reason = '投顾计划选股、选时、仓位使用等信息仅供参考，您须独立作出投资决策。';
        }

        $msg = array();
        $msg['message_id'] = $item['message_id'];
        $msg['touser']= $to_user;
        $msg['template_id'] = $template_id;
        $msg['url']= $this->getUrlBySymbol($message['symbol']);
        //$msg['url']= $this->weixin->getOauth2Url('weixinplandynamic,'.$message['pln_id'].','.$message['symbol'].','.$time);
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

        return $this->_msg_api->sendGuoHaiTemplateMessage($msg);
    }

    /**
     * 体验卡到期通知
     * @param $item
     * @return array|mixed
     */
    private function sendTMessageOfPlanSubExpire($item){
        $to_user = $item['channel_id'];
        $message = json_decode($item['message']['content_client'],true);

        $pln_name = "计划《{$message['pln_name']}》";
        //$pln_id   = $message['pln_id'];
        $expire_time = date("Y-m-d H:i", strtotime($message['expire_time']));
        $title = '体验卡到期通知';
        $template_id = '73';
        $remark = "您可以在该公众号内发送“投顾姓名”，系统将自动给您推送该投顾的计划； 您也可以去大赛首页发觉其他心仪的计划哦。点击去大赛首页>>";

        $msg = array();
        $msg['message_id'] = $item['message_id'];
        $msg['touser']= $to_user;
        $msg['template_id'] = $template_id;
        $msg['url']= "http://finance.sina.com.cn/tgds/ghzq.shtml?act=1";
        $msg['topcolor']= '#FF0000';
        $msg['data'] = array(
            'first'   => array('value'=>$title, 'color'=>'#000000'),
            'name'    => array('value'=>$pln_name, 'color'=>'#000000'),
            'expDate' => array('value'=>$expire_time, 'color'=>'#000000'), //品种名称
            'remark'  => array('value'=>"\n".$remark, 'color'=>'#000000')
        );
        //echo json_encode($msg,JSON_UNESCAPED_UNICODE);

        return $this->_msg_api->sendGuoHaiTemplateMessage($msg);
    }

    /**
     *
     * @param string $symbol
     * @return string
     */
    private function getUrlBySymbol($symbol) {
        //股票类型
        $type = substr($symbol, 0, 2);
        $area = array(
            'sh' => '2',
            'sz' => '1',
        );
        //股票代码
        $code = substr($symbol, 2);
        $url = "http://m.ghzq.cn/#trend/{$area[$type]}/{$code}/3/ZDF/1/home/";

        return $url;
    }


}