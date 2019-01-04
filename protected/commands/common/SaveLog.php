<?php
/**
 * Created by PhpStorm.
 * User: zwg
 * Date: 2015/6/2
 * Time: 13:39
 */

class SaveLog {
    const CRON_NO = 14005; //任务代码
    public function option(){
        $end = time() + 60;
        while(time()<$end){
            $key = "lcs_all_online_log";
            $data = Yii::app()->redis_w->lpop($key);
            $logs=array();
            if(!$data){
                sleep(2);
                continue;
            }
            $data=json_decode($data,true);
            $data['logtime']=date(DATE_RFC3339,$data['logtime']);
            $logs= json_encode($data) ;
            if($logs){
                $obj= new Common();
                $url = $obj->url;
                $url.=Common::INDEX_NAME.'/' . Common::INDEX_NAME;
                $header['content-type']="application/json; charset=UTF-8";
                Yii::app()->curl->setHeaders($header);
                if(defined('ENV') && ENV == 'dev'){
                   $res=Yii::app()->curl->post($url,$logs."\n");
                }else{
                   $res=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->post($url,$logs."\n");
                }
                print_r($res);
            }
        }
    }
}
