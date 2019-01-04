<?php
/**
 * 用户定时任务入口  任务编号  1501 - 1599
 * User: zwg
 * Date: 2015/5/18
 * Time: 17:34
 */

class UserCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.user.*');
        Yii::import('application.commands.moments.*');
    }

    /**
     * 用户等级评估
     * @author zhihao6
     * 1501
     */
    public function actionRankingEvaluate($level=0, $is_init=0) {
        try{
            $re = new RankingEvaluate();
            if ($is_init) {
                $re->evaluateInit($level);
            } else {
                $re->evaluateDaily($level);
            }
            $this->monitorLog(RankingEvaluate::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(RankingEvaluate::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 同步用户微信open_id
     */
    public function actionTongbuWx(){
        try{
            $moments_producer = new TongbuWx();
            $moments_producer->process();
            $this->monitorLog(TongbuWx::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(RankingEvaluate::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * Momtents
     * @author zhihao6
     * 1502
     */
    public function actionMomtentsProducer() {
        try{
            $moments_producer = new MomentsProducer();
            $moments_producer->process();
            $this->monitorLog(MomentsProducer::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(MomentsProducer::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1504
     * 同步新动态数据（公有，接口处取用户的购买的计划的交易动态、观点列表）
     * @param string $start_time
     * @param string $end_time
     */
    public function actionMomentsProducerV1($start_time = "", $end_time = "") {
        try{
            $moments_producer = new MomentsProducerV1();
            $moments_producer->process($start_time, $end_time);
            $this->monitorLog(MomentsProducerV1::CRON_NO);
        }catch(Exception $e) {
            echo $e->getMessage();
            Cron::model()->saveCronLog(MomentsProducerV1::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }


    public function actionChangeNotice(){
        try{
            $n = new UserChangeNotice();
            $n->main();
            $this->monitorLog(UserChangeNotice::CRON_NO);
        }catch(Exception $e){
            Cron::model()->saveCronLog(UserChangeNotice::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }        
    }

    /**
     * 保存用户信息到es
     */
    public function actionIndexData(){
        try{
            $obj = new IndexData();
            $obj->SaveUsers();
            $this->monitorLog(IndexData::CRON_NO);
            
        }catch(Exception $e) {
            Cron::model()->saveCronLog(IndexData::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 导入历史数据
     */
    public function actionPushData(){
        try{
            $obj = new PushData();
            $obj->SaveUsers();
            $this->monitorLog(PushData::CRON_NO);
            
        }catch(Exception $e) {
            Cron::model()->saveCronLog(PushData::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    public function actionCreateIndex(){
        $obj= new Common();
        $url = $obj->url;
        $url.=Common::INDEX_USER_NAME;
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
        $url.=Common::INDEX_USER_NAME;
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
        $url .= Common::INDEX_USER_NAME."/" .Common::TYPE_USER_NAME."/_mapping?pretty";    
        $data='{
            "'.Common::INDEX_USER_NAME.'": {
                "properties": {
                    "uid": {"type": "integer"},
                    "w_uid": {"type": "long"},
                    "s_uid": {"type": "long"},
                    "phone": {"type": "long"},
                    "name": {"type": "text"},
                    "name_u_time": {"type": "date"},
                    "gender": {"type": "text"},
                    "image": {"type": "text"},
                    "wb_name": {"type": "text"},
                    "wb_image": {"type": "text"},
                    "wx_unionid": {"type": "text"},
                    "wx_open_uid": {"type": "text"},
                    "wx_public_uid": {"type": "text"},
                    "wx_name": {"type": "text"},
                    "wx_image": {"type": "text"},
                    "source": {"type": "text"},
                    "ind_id": {"type": "integer"},
                    "client_token": {"type": "text"},
                    "is_first_login": {"type": "integer"},
                    "pact": {"type": "integer"},
                    "status": {"type": "integer"},
                    "ranking_lv": {"type": "integer"},
                    "c_time": {"type": "date"},
                    "client_time": {"type": "date"},
                    "u_time": {"type": "date"},
                    "r_time": {"type": "date"},
                    "cert_id": {"type": "integer"}
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
    
    public function actionUpdateIndex(){
        try{
            $obj = new UpdateIndex();
            $obj->UpdateUsers();
            $this->monitorLog(UpdateIndex::CRON_NO);
            
        }catch(Exception $e) {
            Cron::model()->saveCronLog(UpdateIndex::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 课程过期取消三个付费观点包权限
     */
    public function actionCancelPkg(){
        try{
            $obj = new CancelPkgPower();
            $obj->handle();
            $this->monitorLog(CancelPkgPower::CRON_NO);

        }catch(Exception $e) {
            Cron::model()->saveCronLog(CancelPkgPower::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * plus 开通激活短信通知
     */
    public function actionPlusKtSend(){
        try{
            $obj = new PlusKtSend();
            $obj->handle();
            $this->monitorLog(PlusKtSend::CRON_NO);

        }catch(Exception $e) {
            Cron::model()->saveCronLog(PlusKtSend::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 苹果内购充值活动
     */
    public function actionCouponNiubi(){
        try{
            $obj = new CouponNiubi();
            $obj->handle();
            $this->monitorLog(CouponNiubi::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(CouponNiubi::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 冲牛币双12活动
     */
    public function actionCouponNiubi12(){
        try{
            $obj = new CouponNiubi12();
            $obj->handle();
            $this->monitorLog(CouponNiubi12::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(CouponNiubi12::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 合并用户信息
     */
    public function actionMergeUser(){
        try{
            $obj = new MergeUser();
            $obj->process();
            $this->monitorLog(MergeUser::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(MergeUser::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
      * 用户新增尊享号
      **/
    public function actionAddSpecial(){
        try{
            $obj = new AddSpecial();
            $obj->AddUsersSpecialInfo();
            $this->monitorLog(AddSpecial::CRON_NO);
            
        }catch(Exception $e) {
            Cron::model()->saveCronLog(AddSpecial::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
}
