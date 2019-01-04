<?php
/**
 * 课程过期取消三个付费观点包权限
 */
class PlusKtSend
{

    //任务代码
    const CRON_NO=15001 ;
    /**
     * 入口
     */
    public function handle(){
        try{
            //退出时间 每次随机向后推30-60秒
            $stop_time = time()+rand(2,4)*15;
            while (true) {
                if(time()>$stop_time){
                    break;
                }
                $phone = Yii::app()->redis_w->lPop('lcs_user_plus_activation');
                if (!empty($phone)) {
                    Common::model()->saveLog("PlusKtSend data:" . $phone, "info", "PlusKtSend");
                    $content = "尊贵的会员，恭喜您成功激活会员PLUS身份，快去最新版的“新浪理财师尊享版app”查看权益吧~";
                    $content = iconv("UTF-8", "GB2312//IGNORE", $content);
                    $send_rs = $this->sendYunPianSms($phone, $content);
                    if (empty($send_rs)) {
                        throw new Exception('激活短信发送失败');
                    }
                }else{
                    break;
                }
            }
        }catch (Exception $e){
            Common::model()->saveLog("plus激活短信发送失败:" . $e->getMessage() , "error", "plusKtSend");
        }

    }
    // 新短信发送逻辑 云片
    public static function sendYunPianSms($phone, $content, $type = '', $source=1){
        $params = array(
            'phone' => $phone,
            'content' => iconv("GB2312//IGNORE", "UTF-8", $content),
            'source' => $source, //新浪理财师
            'type' => $type
        );
        $redis_key = MEM_PRE_KEY . 'sendPhoneMsg';
        $result = Yii::app()->redis_w->rPush($redis_key, json_encode($params));
        return $result;
    }
}