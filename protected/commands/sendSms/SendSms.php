<?php

/**
 * Desc  : 发送短信手机短信队列处理逻辑 
 * Author: meixin
 * Date  : 2016-1-6 16:25:40
 */
class SendSms {
    const CRON_NO = 1318; //给用户发送手机短信
    const CHANNEL = 2;  //1 内部短信渠道  2 第三方短信平台
	//redis 里，存储 type 发送信息的模板(哪类短信)  -- 方便以后做统计工作,
	//目前可以由三方管理后台做不同签名下的短信量统计	 

    public function __construct() {
    }
    
    public function sendPhoneMsg(){  
        $fail_num = 0;
        $redis_key = MEM_PRE_KEY . 'sendPhoneMsg';
        while($params_json = Yii::app()->redis_w->lPop($redis_key)) {

            $params = json_decode($params_json, true);
            Common::model()->saveLog($params_json,"info","send_sms");
            $res = 0;
            if (1 == self::CHANNEL) {

                $content = iconv("UTF-8", "GB2312//IGNORE", $params['content']);
                $res = CommonUtils::sendSms($params['phone'], $content);

            } else {

                $content = '';
                if ($params['source'] == 4) {
                    foreach ($params['content'] as $v) {
                        $content .= urlencode($v) . ',';
                    }
                    $params['content'] = substr($content, 0, -1);
                } else {
                    $params['content'] = $params['content'];
                }
                $phone = '';
                foreach((array)$params['phone'] as $v){
                    $phone .= $v .',';                   
                }
                $params['phone'] = substr($phone, 0, -1);
                $send_obj = new YunpianSms;
                $result = $send_obj->sendSms($params);
                echo '========================';
                var_dump($params);
                print_r($result);
                echo '========================';
                $params['result'] = $result;
                Common::model()->saveLog(json_encode($params),"info","send_sms_log");
                if(0 == $result['code'] && $result['result']['count'] == 1) {
                    $res = 1;
                }

            }
            $fail_num = $res ? $fail_num ++ : $fail_num;
            
        }
        return $fail_num;
    }
    
    
}
