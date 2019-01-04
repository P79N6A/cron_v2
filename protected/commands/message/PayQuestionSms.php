<?php 
/**
 * 定时任务: 付费问题的短信通知
 * User: weiguang3
 * Date: 2015-11-19
 */

class PayQuestionSms {


    const CRON_NO = 1304; //任务代码

    public function __construct(){

    }


    /**
     * 发送短信
     * @param $type 0 每天提醒一次   1 每5钟提醒一次
     * @throws LcsException
     */
    public function Process($type=1){
        try {
            $p_ids = array();
            $_cur_time = date("Y-m-d H:i:s");
            $b_time = '';
            if(1==$type){
                $redis_key = "lcs_pay_question_sms_message_last_time";
                $b_time = Yii::app()->redis_r->get($redis_key);

                if($b_time === false){
                    $b_time = date("Y-m-d H:i:s",(strtotime($_cur_time)-300));
                }
                Yii::app()->redis_w->set($redis_key,$_cur_time);

            }else{
                $b_time = date("Y-m-d H:i:s",time()-86400);
            }

            //查询需要通知的理财师ID
            $sql = "select distinct(p_uid) from lcs_ask_question where status=1 and is_price=1 and c_time>'$b_time' and c_time<='$_cur_time'";
            $planner_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();

            if(empty($planner_ids)) {
                return 0;
            }

            if(1==$type){
                $sql_cdn='remind_minute=1';
            }else{
                $sql_cdn='remind_everyday=1';
            }
            $sql = "select distinct(s_uid) from lcs_ask_planner where ".$sql_cdn." and s_uid in (".implode(',',$planner_ids).");";
            $planner_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
            if(empty($planner_ids)) {
                return 0;
            }
            //理财师的手机号
            $sql = "select s_uid,phone from lcs_planner where s_uid in (".implode(',',$planner_ids).");";
            $planers = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            //理财师未回答的问题数量
            $sql = "SELECT p_uid, COUNT(uid) as num FROM lcs_ask_question WHERE STATUS=1 AND is_price=1 and p_uid in (".implode(',',$planner_ids).") GROUP BY p_uid;;";
            $planers_ask_num = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            $ask_num = array();
            foreach($planers_ask_num as $p){
                $ask_num[$p['p_uid']]=$p['num'];
            }

            $result=0;
            $err_info = array();
            foreach($planers as $p){
                if(!empty($p['phone']) && isset($ask_num[$p['s_uid']])){
                    try{
                        $url = rawurlencode("http://licaishi.sina.com.cn/wap/appExt?p_uid=".$p['s_uid']."&time=".$_cur_time."&token=".md5($p['s_uid'].$_cur_time."licaishi@!1@2#3"));
                        $link = json_decode(Yii::app()->curl->get("http://api.t.sina.com.cn/short_url/shorten.json?source=3743280666&url_long=".$url),true);

                        $content = "截止至".date("m-d H:i")."之前，还有".$ask_num[$p['s_uid']]."个付费问题未回答，请登录理财师官网或手机APP回答用户问题。点击查看详情：".(isset($link[0]['url_short']) ? $link[0]['url_short'] : '')."。详询021-36129996【新浪理财师】。";
                        CommonUtils::sendSms($p['phone'],rawurlencode(mb_convert_encoding($content,'GBK','UTF-8')));
                        echo $p['s_uid'], ' ' ,$p['phone'],' ',$content,"\r\n";
                        $result++;
                    }catch (Exception $e){
                        $err_info[$p['s_uid']]=$e->getMessage();
                    }
                }else{
                    $err_info[$p['s_uid']]='phone or num is null';
                }
            }
            if(!empty($err_info)){
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, json_encode($err_info,JSON_UNESCAPED_UNICODE));
            }
            return $result;
        }catch (Exception $e) {
        	throw LcsException::errorHandlerOfException($e);
        }
    }


}
