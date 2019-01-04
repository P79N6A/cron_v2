<?php

/**
 * 短信批量发送定时任务，进程可以多开.
 * @author lixiang23 <lixiang23@staff.sina.com.cn>
 * @copyright (c) 20161107
 * 
 * 队列数据格式如下：
 * $data=array(
 *      "channel"=>0,                                ///必填，发送接口类型，默认使用理财师短信通道，可对接其他短信通道
 *      "mobiles"=>"13501136911,13648358020",     ///必填，发送的手机号码，字符串类型，可以发送多个用户，通过逗号','隔开.
 *      "content"=>"恭喜您中奖了，我开玩笑的",        ///必填，发送的短信内容
 *      "sms_log_id"=>12342,                    ///可选参数,队列日志id,如果有队列日志id则表示重发，没有则会重新分配一个
 *      "send_time"=>'2016-11-20 00:00:00',       ///可选参数,待发送时间
 * );
 */
class SmsHandler {

    ///定时任务号
    const CRON_NO = 7701;

    ///全部短信处理队列
    public $all_sms_queue_key = "lcs_all_sms_queue";
    ///快速短信处理队列
    public $fast_sms_queue_key = "lcs_fast_sms_queue";

    /**
     * 短信发送队列
     */
    public function processAll() {
        $count = 1;
        while (True) {
            ///发送普通短信
            $this->PopMsg($this->all_sms_queue_key);
            sleep(3);
            $count = $count + 1;
            if ($count == 60) {
                break;
            }
        }
    }

    /**
     * 快速短信发送队列
     */
    public function processFast() {
        $count = 1;
        while (True) {
            ///发送快速短信
            $this->PopMsg($this->fast_sms_queue_key);
            sleep(1);
            $count = $count + 1;
            if ($count == 120) {
                break;
            }
        }
    }

    /**
     * 取出消息并处理
     * @param string $queue_key 队列键值
     */
    public function PopMsg($queue_key) {
        $count = 1;
        while (True) {
            try {
                $data = Yii::app()->redis_w->lpop($queue_key);
                if (empty($data)) {
                    break;
                } else {
                    ///打印日志
                    echo $data . "\n\r";
                    $data=json_decode($data,true);
                    
                    $sms_log_id = 0;
                    $times = 0;
                    if (isset($data['sms_log_id'])) {
                        $sms_log_id = $data['sms_log_id'];
                        $times = $data['times'] + 1;
                    } else {
                        $data['status'] = 0;
                        if(!isset($data['c_time'])){
                            $data['c_time']=date("Y-m-d H:i:s",time());
                        }
                        $sms_log_id = Sms::model()->saveSmsLog($data);
                    }
                    ///处理短信数据
                    $res = $this->ProcessMsg($data);
                    ///回写数据库成功或者失败原因
                    if ($res['code'] == 0) {
                        Sms::model()->updateSmsLog($sms_log_id, array('status' => 1, 'result' => $res['msg'], 'times' => $times));
                    } else {
                        Sms::model()->updateSmsLog($sms_log_id, array('status' => $res['code'], 'result' => $res['msg'], 'times' => $times));
                    }
                }

                $count++;
                if ($count % 200 == 0) {
                    sleep(0.5);
                }
            } catch (Exception $ex) {
            }
        }
    }

    public function ProcessMsg($data) {

        try {

            if (!isset($data['channel'])) {
                $data['channel'] = 0;
            }

            if (isset($data['channel']) && isset($data['mobiles']) && isset($data['content'])) {
                ///如果存在发送时间，并且发送时间大于未来1分钟，则发送失败.
                if (isset($data['send_time']) && $data['send_time'] > date("Y-m-d H:i:s", strtotime("+1 minute"))) {
                    return array("code" => 2, "msg" => "发送时间未到，稍等下");
                }

                ///是否已经超时
                if (isset($data['c_time']) && $data['c_time'] < date("Y-m-d H:i:s", strtotime("-4 minute"))){
                    return array("code" => -3, "msg" => "超时4分钟未发送");
                }
                
                $mobiles = explode(',', $data['mobiles']);
                if (count($mobiles) == 0) {
                    return array("code" => -1, "msg" => "电话号码为空或者格式错误");
                }

                $res = 0;
                $data['content']=  urldecode($data['content']);
                switch ($data['channel']) {
                    case 0:
                        ///默认理财师短信通道
                        $res = $this->SendDefault($mobiles, $data['content']);
                        break;
                    case 1:
                        ///信达天下
                        $res = $this->SendXinDa($mobiles, $data['content']);
                        break;
                    case 2:break;
                    case 3:break;
                    default:
                        ///默认理财师短信通道
                        $res = $this->SendDefault($mobiles, $data['content']);
                        break;
                }

                return $res;
            } else {
                return array("code" => -1, "msg" => "发送短信参数错误");
            }
        } catch (Exception $ex) {
            return array("code" => -1, "msg" => "发送短信系统错误：" .json_encode($data). $ex->getMessage());
        }
    }

    /**
     * 默认发送方式，理财师默认发送方式
     * @param type $mobiles 电话号码
     * @param type $content 内容
     * @return type
     */
    public function SendDefault($mobiles, $content) {
        $failed_mobile = array();
        foreach ($mobiles as $item) {
            $str_code_type=  mb_detect_encoding($content,array("UTF-8","GB2312"));
            if($str_code_type=="UTF-8"){
                $content = iconv("UTF-8", "GB2312//IGNORE", $content);   
            }
            
            $res = CommonUtils::sendSms($item, urlencode($content));
            if (empty($res)) {
                echo "短信接口调用发送失败，接口返回结果为：空\n\r";
                $failed_mobile[]=$item;
            }
        }

        if (count($failed_mobile) == 0) {
            return array("code" => 0, "msg" => "默认理财师渠道:" . count($mobiles) . "条全部发送成功");
        } else {
            return array("code" => -1, "msg" => "默认理财师渠道:" . implode(',', $failed_mobile) . "等发送失败,详细原因见服务器日志");
        }
    }

    /**
     * 信达天下短信发送
     * @param type $mobiles
     * @param type $content
     */
    public function SendXinDa($mobiles, $content) {
        $failed_mobile = array();
        foreach ($mobiles as $item) {
            $res = $this->sendSmsXinda($item, $content);
            
            try {
                $res = json_decode($res, true);
            } catch (Exception $ex) {
                ///解析失败
                echo "短信接口调用发送失败，接口返回结果为：".$res."\n\r"; 
                $failed_mobile[]=$item;
                continue;
            }
            
            if (!isset($res['error_no']) || $res['error_no']!=0) {
                echo "短信接口调用发送失败，接口返回结果为：".json_encode($res)."\n\r";
                $failed_mobile[]=$item;
            }
        }

        if (count($failed_mobile) == 0) {
            return array("code" => 0, "msg" => "信达天下:" . count($mobiles) . "条全部发送成功,result=" . json_encode($res));
        } else {
            return array("code" => -1, "msg" => "信达天下:" . implode(',', $failed_mobile) . "等发送失败,result=" . json_encode($res));
        }
    }
    
        /**
     * 信达天下短信发送
     * @param type $phone
     * @param type $content
     * @return type
     */
    private function sendSmsXinda($phone, $content) {
        $md5_key="www.cindasc.com";
        $md5= base64_encode(md5($phone.$content.$md5_key,true));
        #$url = 'http://114.251.97.186:9603/servlet/json?funcNo=3004004&phone=' . $phone . '&content=' . urlencode($content) . '&placeName=sina&messageNum=1&md5='.$md5;
        $url = 'https://tianxia.cindasc.com:9603/servlet/json?funcNo=3004004&phone=' . $phone . '&content=' . urlencode($content) . '&placeName=sina&messageNum=1&md5='.$md5;
        var_dump($url);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}
