<?php
/**
 * 大赛推送
 */
class MatchPush
{

    //任务代码
    const CRON_NO_INTERESTED='20180608';
    const CRON_NO_SIGNUP='20180609';
    const CRON_NO_TRADE='20180610';
    const CRON_NO_MONEY='20180611';
    /**
     * 感兴趣推送
     */
    public function interested(){
        $c_time = $this->getPrevTime();
        //统计感兴趣的人数
        $pushInfo = Match::model()->getInterested($c_time);
        $val = array(
            "type"=>"matchPush",
            "match_type"=>1,
            "data"=>$pushInfo,
        );
        echo "加入推送队列的数据\r\n";
        var_dump($val);
        yii::app()->redis_w->push("lcs_common_message_queue",json_encode($val));
    }
    //报名推送
    public function signUp(){
        $c_time = $this->getPrevTime();
        $signUpInfo = Match::model()->getSignUpPeople($c_time);
        $val = array(
            "type"=>"matchPush",
            "match_type"=>2,
            "data"=>$signUpInfo,
        );
        echo "加入推送队列的数据\r\n";
        var_dump($val);
        yii::app()->redis_w->push("lcs_common_message_queue",json_encode($val));
    }
    //参赛推送
    public function matchTradePush(){
        $matchTradeInfo = Match::model()->matchTradePush();
        $val = array(
            "type"=>"matchPush",
            "match_type"=>3,
            "data"=>$matchTradeInfo,
        );
        echo "加入推送队列的数据\r\n";
        var_dump($val);
        yii::app()->redis_w->push("lcs_common_message_queue",json_encode($val));
    }
    //获奖推送
    public function moneyPush(){
        $moneyPushInfo = Match::model()->moneyPush();
        $val = array(
            "type"=>"matchPush",
            "match_type"=>4,
            "data"=>$moneyPushInfo,
        );
        echo "加入推送队列的数据\r\n";
        var_dump($val);
        yii::app()->redis_w->push("lcs_common_message_queue",json_encode($val));
    }
    //获取四小时之前的时间
    public function getPrevTime(){
        //计算当前时间减4小时的时间
        $prev = time()-14400;
        return date("Y-m-d H:i:s",$prev);
    }
}