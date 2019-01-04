<?php

class PushLog {
    const CRON_NO = 15005; //任务代码
    public function option(){
        $end = time() + 60;
        while(time()<$end){
            $key = "lcs_push_log_es";
            $data = Yii::app()->redis_w->lpop($key);
            $logs=array();
            if(!$data){
                sleep(2);
                continue;
            }
            $data=json_decode($data,true);
            $data['push_body'] = json_encode($data['push_body']);
            $data['push_return'] = json_encode($data['push_return']);

            $data['logtime']=date(DATE_RFC3339,$data['logtime']);
            $logs= json_encode($data);
            echo "json \r\n";
            echo $logs;
            if($logs){
                $obj = new Common();
                $url = $obj->url;
                $url.='pushlog/pushlog';
                $header['content-type']="application/json; charset=UTF-8";
                Yii::app()->curl->setHeaders($header);
                if(defined('ENV') && ENV == 'dev'){
                   $res=Yii::app()->curl->post($url,$logs."\n");
                }else{
                   $res=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->post($url,$logs."\n");
                }
                echo "es返回值 \r\n";
                print_r($res);
            }
        }
    }
}
