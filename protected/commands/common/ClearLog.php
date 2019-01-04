<?php
/**
 * 清理日志的任务
 * 处理规则，如果有时间按指定时间清理  否则采用当前时间减去指定参数为清理时间
 * User: zwg
 * Date: 2015/5/28
 * Time: 16:57
 */

class ClearLog {
    const CRON_NO = 9901; //任务代码
    const CRON_LOG_DAYS = 15;//定时任务的日志保留时间
    const WX_MESSAGE_DAYS = 30; //微信消息通知的日志保留时间
    const MESSAGE_QUEUE_LOG_DAYS = 30; // 消息队列日志保留时间
    const LCS_COMMON_LOG = 30; //  普通日志

    public function __construct(){

    }
    
    //lcs_log日志类型
    public static $categorys = array(
        'client_token_error',
        'client_token_expire',
        'exception.CDbException',
        'exception.CException',
        'exception.CHttpException.404',
        'exception.Exception',
        'exception.ImagickException',
        'exception.RedisException',
        'exception.WeiBoApiException',
        'weixin',
        'weixin.result',
        'weixin.SendTemplateMessage',
        'weixin_finance',
        'web_repeat_opt',
        'web_wbTokenInfo',
        'php',
        'Planner-Answer-Admin-Client',
        'Planner-Answer-Update-Price',
        'plan_fishing',
        'question_pay_sms',
        'sina_pay_refund_result',
        'staff.controller.api.saveImageToS3',
        'system.db.CDbCommand',
        'tmp',
        'u_time_no_update',
        'wbUserInfoError',
        'web.curlUseTime'
    );
    
    /**
     * 清理数据库日志
     * @param $type  1 定时任务日志   2微信日志  4 消息队列日志   8 lcs_log日志
     */
    public function clearDB($type=15, $clear_date=''){
        try{
            
            if(($type&1)==1){
                $this->clearCronLog($clear_date);
            }
            
            if(($type&2)==2){
                $this->clearWxMessage($clear_date);
            }

            if(($type&4)==4){
                $this->clearMessageQueueLog($clear_date);
            }
            
            if(($type&8)==8){
                $this->clearLcsLog($clear_date);
            }
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }

    }


    private function clearCronLog($clear_date=''){
        if(empty($clear_date)){
            $clear_date = date("Y-m-d 00:00:00",time()-24*3600*self::CRON_LOG_DAYS);
        }else{
            $clear_date = date("Y-m-d 00:00:00",strtotime($clear_date));
        }

        try{
            $records = Cron::model()->removeCronLog($clear_date);
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "清除数据库 lcs_cron_log".$clear_date."日志,:".$records);
        }catch (Exception $e){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    private function clearWxMessage($clear_date=''){
        if(empty($clear_date)){
            $clear_date = date("Y-m-d 00:00:00",time()-24*3600*self::WX_MESSAGE_DAYS);
        }else{
            $clear_date = date("Y-m-d 00:00:00",strtotime($clear_date));
        }
        try{
            $r_msg_result = Weixin::model()->removeWxMsgResult($clear_date);
            $r_msg = Weixin::model()->removeWxMessage($clear_date);
            $r_reply = Weixin::model()->removeWxReply($clear_date);
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "清除数据库 微信消息".$clear_date."日志, msg_result:".$r_msg_result.' msg:'.$r_msg.' reply:'.$r_reply);
        }catch (Exception $e){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    private function clearMessageQueueLog($clear_date=''){
        if(empty($clear_date)) {
            $clear_date = date("Y-m-d 00:00:00", time()-24*3600*self::MESSAGE_QUEUE_LOG_DAYS);
        } else {
            $clear_date = date("Y-m-d 00:00:00", strtotime($clear_date));
        }

        try{
            $records = Message::model()->removeMessageQueueLog($clear_date);
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "清除数据库 lcs_message_queue_log".$clear_date."日志,:".$records);
        }catch (Exception $e){
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
    
    private function clearLcsLog($clear_date=''){
        if(empty($clear_date)) {
            $clear_date = strtotime(date("Y-m-d 00:00:00", time()-24*3600*self::LCS_COMMON_LOG));
        } else {
            $clear_date = strtotime($clear_date);
        }
        try {
            foreach (self::$categorys as $category){
                $records = Common::model()->clearLog($category,$clear_date);
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "清除数据库 lcs_log".$clear_date."日志, category->".$category.":".$records);
            }

        } catch (Exception $e) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
}
