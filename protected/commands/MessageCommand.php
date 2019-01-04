<?php
/**
 * 主要包括：消息的定时任务   1301 - 1399
 * Date: 2015/10/29
 */

class MessageCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.message.*');
        Yii::import('application.commands.msgQueueHandler.*');
        Yii::import('application.commands.spMsgQueueHandler.*');
    }

    
    /**
     * 1321
     * 运营发布的定时微信push通知消息
     */
    public function actionPushWeixinNotice() {
        try{
            $operate_notice = new WeixinProcessNotice();
            $operate_notice->process();
            $this->monitorLog(WeixinProcessNotice::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(WeixinProcessNotice::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1301(模板消息 图文推送)
     * 运营发布的定时push通知消息
     */
    public function actionOperateNotice() {
        try{
            $operate_notice = new ProcessNotice();
            $operate_notice->process();
            $this->monitorLog(ProcessNotice::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(ProcessNotice::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
    /**
     * 1302
     * 5天未发布收费观点的警告短信
     * 每天执行。
     */
    public function actionNoViewsWarning() {
    	try{
    		$o_warning = new NoViewWarning();
    		$o_warning->process();
    		$this->monitorLog(NoViewWarning::CRON_NO);
    	}catch(Exception $e) {
            Cron::model()->saveCronLog(ProcessNotice::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
    


    /**
     * 1303
     * 优惠劵即将过期提醒  提前一天10点通知
     */
    public function actionCouponExpire(){
        try{
            $coupon = new CouponExpire();
            $coupon->process();
            $this->monitorLog(CouponExpire::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(CouponExpire::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }


    /**
     * 1304
     * 付费问题的短信通知 五分钟通知一次
     */
    public function actionPayQuestionSmsType1(){
        try{
            $payQuestionSms = new PayQuestionSms();
            $result = $payQuestionSms->process(1);
            $this->monitorLog(PayQuestionSms::CRON_NO);
            if(!empty($result)){
                Cron::model()->saveCronLog(PayQuestionSms::CRON_NO, CLogger::LEVEL_INFO, "付费问题的短信通知 五分钟通知一次：".$result);
            }
        }catch(Exception $e) {
            Cron::model()->saveCronLog(PayQuestionSms::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1304
     * 付费问题的短信通知 一天通知一次
     */
    public function actionPayQuestionSmsType0(){
        try{
            $payQuestionSms = new PayQuestionSms();
            $result = $payQuestionSms->process(0);
            $this->monitorLog(PayQuestionSms::CRON_NO);
            if(!empty($result)){
                Cron::model()->saveCronLog(PayQuestionSms::CRON_NO, CLogger::LEVEL_INFO, "付费问题的短信通知 一天通知一次：".$result);
            }
        }catch(Exception $e) {
            Cron::model()->saveCronLog(PayQuestionSms::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1305
     * 微信消息推送
     */
    public function actionWeiXinMessagePushQueueHandler(){
        try{
            $weiXinMessage = new WeiXinMessagePushQueue();
            $weiXinMessage->process();
            $this->monitorLog(WeiXinMessagePushQueue::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(WeiXinMessagePushQueue::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 1306
     * 微信消息推送失败处理
     */
    public function actionWeiXinMessagePushFail(){
        try{
            $weiXin = new WeiXinMessagePushFail();
            $weiXin->check();
            $this->monitorLog(WeiXinMessagePushFail::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(WeiXinMessagePushFail::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1307
     * 新浪博客消息推送
     */
    public function actionSinaSpnsMessagePushQueueHandler(){
        try{
            $sinaSpnsPushQueue = new SinaSpnsPushQueue();
            $sinaSpnsPushQueue->processMessage();
            $this->monitorLog(SinaSpnsPushQueue::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(SinaSpnsPushQueue::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1308
     * 启动快速消息队列处理
     */
    public function actionFastMsgQueueHandler(){
        try{

            $messageQueue = new MessageQueue();
            $messageQueue->processFastMessageQueue();
            $this->monitorLog(MessageQueue::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(MessageQueue::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1308
     * 启动普通消息队列处理
     */
    public function actionCommonMsgQueueHandler(){
        try{

            //$msg='{"type":"commentNew","cmn_type":2,"cmn_id":"11964","relation_id":1145,"u_type":1,"uid":"10000344","name":"\u8d22\u53cb30726589","image":"http:\/\/tp4.sinaimg.cn\/2456983843\/30\/0\/1","content":"\u6536\u62fe\u6536\u62fe\u662f\u6536\u62fe\u6536\u62fe\u6536\u62fe\u6536\u62fe"}';
            $messageQueue = new MessageQueue();
            $messageQueue->processCommonMessageQueue();
            //$messageQueue->processMessage($msg,"lcs_common_message_queue");
            $this->monitorLog(MessageQueue::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(MessageQueue::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1309 向财经客户端推送消息给理财师
     * 每天推一次
     */
    public function actionClientMsgForPlannerDay() {
    	try{
    		$cm = new ClientMsg();
    		$cm->sendForPlannerDay();
    		$this->monitorLog(ClientMsg::CRON_NO);
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(ClientMsg::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }
    
    /**
     * 1309 向财经客户端推送消息给理财师
     */
    public function actionClientMsgForPlanner() {
    	try{
    		$cm = new ClientMsg();
    		$cm->sendForPlanner();
    		$this->monitorLog(ClientMsg::CRON_NO);
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(ClientMsg::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }
    
    /**
     * 1309 向财经客户端推送消息给用户
     */
    public function actionClientMsgForUser() {
    	try{
    		$cm = new ClientMsg();
    		$cm->sendForUser();
    		$this->monitorLog(ClientMsg::CRON_NO);
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(ClientMsg::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }

    /**
     * 1310 观点包到期提醒
     */
    public function actionPkgExpire() {
    	try{
    		$p = new PkgExpire();
    		$p->sendMsg();
    		$this->monitorLog(PkgExpire::CRON_NO);
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(PkgExpire::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }
    
    /**
     * 1311 观点包收费提醒
     */
    public function actionPkgCharge() {
    	try{
    		$p = new PkgCharge();
    		$p->sendMsg();
    		$this->monitorLog(PkgCharge::CRON_NO);
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(PkgCharge::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }

    /**
     * 1312
     * 个推消息推送
     */
    public function actionGetuiMessagePushQueueHandler(){
        try{
            $getuiPushQueue = new GetuiPushQueue();
            $getuiPushQueue->processMessage();
            $this->monitorLog(GetuiPushQueue::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(GetuiPushQueue::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1313
     * 微信第三方平台消息推送
     */
    public function actionWeiXinThirdMessagePushQueueHandler(){
        try{
            $weixinThridMsg = new WeiXinThirdMsgPushQueue();
            $weixinThridMsg->process();
            $this->monitorLog(WeiXinThirdMsgPushQueue::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(WeiXinThirdMsgPushQueue::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

     /**
     * 1314
     * 机构第三方推送观点、交易动态、每日收益播报
     */
    public function actionSdkMsgQueueHandler(){
        try{
            $partner_sdk_msg_queue = new SdkMessagePushQueue();
            $partner_sdk_msg_queue->process();
            $this->monitorLog($partner_sdk_msg_queue::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(SdkMessagePushQueue::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
    /**
     * 1315
     * 机构第三方交易日推送每日收益播报
     */
    public function actionPartnerDailyReport(){
        try{
            $partner_sdk_daily_report=new SdkMessagePushDailyReport();
            $partner_sdk_daily_report->process();
            $this->monitorLog($partner_sdk_daily_report::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(SdkMessagePushDailyReport::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }


    /**
     * 1319
     * 第三方模板消息推送
     */
    public function actionThirdPartyTemplateMsg() {
        try {
            $p = new ThirdPartyPushQueue();
            $p->process();
            $this->monitorLog(ThirdPartyPushQueue::CRON_NO);
        } catch(Exception $e) {
            Cron::model()->saveCronLog(ThirdPartyPushQueue::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1320
     * 向上海推荐圈子事件
     */
    public function actionCircleEventQueue(){
        try{
            $p = new CircleEventQueue();
            $p->process();
            $this->monitorLog(CircleEventQueue::CRON_NO);
        }catch (Exception $e){
            Cron::model()->saveCronLog(CircleEventQueue::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 1321
     * 微信模板推送
    */
    public function actionWeiXinTempPush(){
        try{
            $weiXinMessage = new WeiXinMessagePushQueue();
            $weiXinMessage->newProcess();
            $this->monitorLog(WeiXinMessagePushQueue::CRON_NO_TEMPLET);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(WeiXinMessagePushQueue::CRON_NO_TEMPLET, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 1322
     * 微信图文推送
    */
    public function actionWeiXinImagePush(){
        try{
            $weiXinMessage = new WeiXinMessagePushQueue();
            $weiXinMessage->imagePush();
            $this->monitorLog(WeiXinMessagePushQueue::CRON_NO_IMAGE);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(WeiXinMessagePushQueue::CRON_NO_IMAGE, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
}
