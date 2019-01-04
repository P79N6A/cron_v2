<?php
class UpdatePFToken {
	const CRON_NO = 1901; //任务代码
	
	public function __construct(){
	}
	
	public function process() {
        try{
            $wxapi = new WeixinApi();
            //更新微信公众号accessToken
            $result = $wxapi->updateAccessTokenGodStock();
            echo "god_stock_token";
            print_r($result);
            if(isset($result['errcode']) && $result['errcode']==0){
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '更新微信公众平台token：'.json_encode($result));
            }else{
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '更新微信公众平台token：'.json_encode($result));
            }
            //更新小程序accessToken
//            $result = $wxapi->updateAccessTokenXcx();
//            echo "xcx_access_token";
//            print_r($result);
//            if(isset($result['errcode']) && $result['errcode']==0){
//                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '更新小程序token：'.json_encode($result));
//            }else{
//                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '更新小程序token：'.json_encode($result));
//            }
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }
}
