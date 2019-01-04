<?php

/**
 * Desc  : 云片网络第三方手机短息发送工具 
 * Author: meixin
 * Date  : 2016-1-5 10:47:57
 */
class YunpianSms {
       
    private $apikey = array( 
//                    '1' => '5546a33d59d1d35138541e5ba589d34c' , //新浪理财师
                    '2' => '299748e57ab558773ceffbd1a8700b45' ,  //新浪理财师-营销
                    '3' => '4e1de3ad95f109ec179700b1e4215f5b' , //仓石理财(线上)
                    '4' => '4e1de3ad95f109ec179700b1e4215f5b' , //仓石理财（定时任务）
                    '1'=>'e3378d44a83f42c1d411be6f2d2ddeb6',  //新浪时金
                    '5'=>'e3378d44a83f42c1d411be6f2d2ddeb6',  //银如意
                    
        ); 
    
    private $url = array(
            '1' => 'https://sms.yunpian.com/v1/sms/send.json' ,  //单条
            '2' => 'https://sms.yunpian.com/v1/sms/multi_send.json',//批量
            );
    
    public function sendSms($params){
        
        $data=array('apikey'=>$this->apikey[$params['source']], 'mobile'=>$params['phone'] , 'text' => $params['content']);
        $url = ($params['source'] == 4 ) ? $this->url['2']: $this->url['1'];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8', 'Content-Type:application/x-www-form-urlencoded','charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $json_data = curl_exec($ch);
        $result = json_decode($json_data,true);
        return $result;
    }
}
