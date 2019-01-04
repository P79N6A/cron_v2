<?php

/**
 * 新财讯新闻相关
 *
 */
class NewService{

    /**
     * 从财讯处同步观点
     * @param   int $time  最新更新的一次时间
     */
    public static function getNewFromCX($time){
        if(defined("ENV") && ENV == "dev"){
	        $url = "http://116.236.205.27:1380/information/api/info/1/articles/getSinaNews?lastArticleTime=$time";
        }else{
            $url = "http://article.caixun99.com/api/info/1/articles/getSinaNews?lastArticleTime=$time";
        }
        $headers = array(
            #"content-type = application/x-www-form-urlencoded",
            "Accept:application/json"
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        try{
            $result = curl_exec($ch);
            $result = json_decode($result,true);
            if(isset($result['code']) && $result['code']==1){
                return $result;
            }
            Common::model()->saveLog("从新财讯同步观点失败:".json_encode($result),"info","get_caixun_view");
            return false;
        }catch(Exception $e){
            Common::model()->saveLog("从新财讯同步观点异常:".$e->getMessage(),"error","get_caixun_view");
            return false;
        }
    }

}
