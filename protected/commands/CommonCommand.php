<?php
/**
 * 理财师项目公共的任务命令
 * 主要包括：检测任务状态  清理日志 等和业务无关的系统任务
 * User: zwg
 * Date: 2015/5/21
 * Time: 10:42
 */

class CommonCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.common.*');
    }

    /**
     * 清理日志文件 包括数据库和服务器日志文件
     */
    public function actionClearLog($type=15,$clear_date=''){
        try{

            //$clear_date='2015-05-25';
            $clearLog = new ClearLog();
            $clearLog->clearDB($type,$clear_date);
            //记录任务结束时间
            $this->monitorLog(ClearLog::CRON_NO);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(ClearLog::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }


    /**
     * 发送任务日志  主要用户shell中发现错误记录日志
     * @param $cron_no
     * @param $level
     * @param $msg
     */
    public function actionSaveCronLog($cron_no, $level, $msg){

        try{
            Cron::model()->saveCronLog($cron_no, $level, $msg);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(ClearLog::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }

    }


    /**
     * 检查定时任务状态，发送通知
     */
    public function actionCheckCron(){
        try{
            $checkCron = new CheckCron();
            $checkCron->check();
            //记录任务结束时间
            $this->monitorLog(CheckCron::CRON_NO);
        }catch (Exception $e) {
            Cron::model()->saveCronLog($checkCron::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }


    /**
     * 检查定时任务的错误日志，发送通知
     */
    public function actionCheckErrLog(){
        try{

            $checkErrLog = new CheckErrLog();
            $checkErrLog->check();
            //记录任务结束时间
            $this->monitorLog(CheckErrLog::CRON_NO);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(CheckErrLog::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }


    public function actionTest($flag=1){
        if($flag){
            $extension='memcached';
            if(!extension_loaded($extension)){
                echo 'not find memcached extension';
            }else{
                echo 'ok';
                $servers = array(array('10.73.48.64', 7817));
                $memcacheD = new Memcached;
                $memcacheD->addServers($servers);
                echo $memcacheD->get('lcs_weixin_access_token');
            }
            //echo CommonUtils::getServerIp();
        }else{
            echo Yii::app()->cache->get('lcs_weixin_access_token');
            //phpinfo();
        }
    }


    /**
     * 热葫芦回调
     */
    public function actionRehuluCallBack(){
        try{
            $rehulu = new Rehulu();
            $rehulu->callBack();
            //记录任务结束时间
            $this->monitorLog(Rehulu::CRON_NO);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(Rehulu::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    public function actionCreateIndex(){
        $obj= new Common();
        $url = $obj->url;
        $url.=Common::INDEX_NAME;
        $header['content-type']="application/json; charset=UTF-8";
        Yii::app()->curl->setHeaders($header);
        if(defined('ENV') && ENV == 'dev'){
           $res=Yii::app()->curl->put($url,'');
        }else{
           $res=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->put($url,'');
        }
        var_dump($res);
    }
    public function actionDelete(){
        $obj= new Common();
        $url = $obj->url;
        $url.=Common::INDEX_NAME;
        $header['content-type']="application/json; charset=UTF-8";
        Yii::app()->curl->setHeaders($header);
        if(defined('ENV') && ENV == 'dev'){
           $res=Yii::app()->curl->delete($url);
        }else{
           $res=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->delete($url);
        }
        var_dump($res);
    }
    public function actionCreateMapping(){
        $obj= new Common();
        $url = $obj->url;
        $url .= Common::INDEX_NAME."/" .Common::INDEX_NAME."/_mapping?pretty";    
        $data='{
            "'.Common::INDEX_NAME.'": {
                "properties": {
                    "level": {"type": "text","analyzer": "ik_max_word","search_analyzer": "ik_max_word"},
                    "category": {"type": "text","analyzer": "ik_max_word","search_analyzer": "ik_max_word"},
                    "message": {"type": "text","analyzer": "ik_max_word","search_analyzer": "ik_max_word"},
                    "logtime": {"type": "date"}
                }
            }
        }';
        $header['content-type']="application/json; charset=UTF-8";
        Yii::app()->curl->setHeaders($header);
        if(defined('ENV') && ENV == 'dev'){
           $res=Yii::app()->curl->put($url, $data);
        }else{
           $res=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->put($url, $data);
        }
        var_dump($res); 
    }
    /**
     * 保存日志到es
     */
    public function actionSaveLog(){
        try{
            $saveLog = new SaveLog();
            $saveLog->option();
            $this->monitorLog(SaveLog::CRON_NO);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(SaveLog::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 保存推送日志到es
     */
    public function actionPushLogEs(){
        try{
            $pushLog = new PushLog();
            $pushLog->option();
            $this->monitorLog(PushLog::CRON_NO);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(PushLog::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 每天向圈子添加初始化值
     */
    public function actionCircleHotInit(){
        try{
            $circlehot = new CircleHotInits();
            $circlehot->check();
            $this->monitorLog(CircleHotInits::CRON_NO);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(CircleHotInits::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

}
