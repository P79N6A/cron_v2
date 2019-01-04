<?php

/**
 * 直播
 * Created by PhpStorm.
 * User: ff
 * Date: 16-8-16
 * Time: 下午1:41
 */
class Live
{
    const CRON_NO = 2003; // 任务代码
    const SINA_VIDEO_TYPE = 2; // 火沙直播

    const LCS_PUSH_MESSAGE_QUEUE = 'lcs_common_message_queue'; // 消息队列 key
    const LCS_LIVE_STARTED_CACHE = 'lcs_live_started_cache_'; // 已开启直播，避免重复开启
    const LCS_LIVE_PROLONG_CACHE = 'lcs_live_prolong_cache_'; // 已延长直播，避免重复延长
    const LCS_LIVE_PLANNER_CACHE = 'lcs_live_planner_cache_'; // 理财师提醒
    const LCS_LIVE_STARTED_TIMEOUT = 1800; // 开启直播缓存时间
    const LCS_LIVE_PROLONG_TIMEOUT = 3600; // 延长直播缓存时间
    /**
    * 徐小明、冯矿伟消息提醒
    */
    public function startKtLive(){
        //徐小明 冯矿伟每天提醒
        $curr_hour = date("Gi");        
        $list = array(
            '550'=>array('st'=>'920','et'=>'930'),
            '580'=>array('st'=>'920','et'=>'930'),
            '4'=>array('st'=>'1450','et'=>'1500'),
            '579'=>array('st'=>'1450','et'=>'1500'),            
        );
        foreach($list as $id=>$t){
            if (Yii::app()->redis_r->exists(self::LCS_LIVE_STARTED_CACHE.'1_'.$id)) {
                continue;
            }                        
            if($curr_hour >= $t['st'] && $curr_hour <= $t['et']){                
                self::sendMpMessage($id);
                Yii::app()->redis_w->setex(self::LCS_LIVE_STARTED_CACHE.'_'.$id, self::LCS_LIVE_PROLONG_TIMEOUT, 1);
            }
        }
    }
    /**
     * 开启直播
     * 公众号提醒
     * @return bool
     */
    public function startLive()
    {
        $list = PlannerLive::model()->getCommingLive();
        if (empty($list)) {
            return false;
        }

        foreach ($list as $live) {
            if (Yii::app()->redis_r->exists(self::LCS_LIVE_STARTED_CACHE.$live['id'])) {
                continue;
            }

            if ($live['type'] == 1 && (self::SINA_VIDEO_TYPE == $live['video_type']) && !empty($live['program_id'])) {
                $stat = NewLiveUtils::startProgram($live);
                if (empty($stat)) {
                    self::logLiveError('start live error:'.json_encode($live));
                    continue;
                }
            }

            Yii::app()->redis_w->setex(self::LCS_LIVE_STARTED_CACHE.$live['id'], self::LCS_LIVE_STARTED_TIMEOUT, 1);
            self::sendMpMessage($live['id']);
        }
        
    }

    /**
     * 延长直播
     * @return bool
     */
    public function prolongLive()
    {
        $list = PlannerLive::model()->getLiveByStatus(2, 1);
        if (empty($list)) {
            return false;
        }

        foreach ($list as $live) {
            if ((self::SINA_VIDEO_TYPE != $live['video_type']) || empty($live['program_id'])) {
                continue;
            }

            if (Yii::app()->redis_r->exists(self::LCS_LIVE_PROLONG_CACHE.$live['id'])) {
                continue;
            }

            $live['time'] = strtotime($live['end_time']) - $_SERVER['REQUEST_TIME'];
            $stat         = NewLiveUtils::prolongLive($live);
            if (empty($stat)) {
                self::logLiveError('prolong live error:'.json_encode($live));
                continue;
            }

            Yii::app()->redis_w->setex(self::LCS_LIVE_PROLONG_CACHE.$live['id'], self::LCS_LIVE_PROLONG_TIMEOUT, 1);
        }
    }

    /**
     * 理财师提醒
     * @return bool
     */
    public function remindPlanner()
    {
        $this->remindPlannerByTime(10);
        $this->remindPlannerByTime(30);
    }

    /**
     * 根据时间提前提醒理财师
     * @param $time
     * @return bool
     */
    private function remindPlannerByTime($time)
    {
        $cond = array('start_time' => array('elt', date('Y-m-d H:i:s', time() + $time * 60)));
        $list = PlannerLive::model()->getLiveByTime($cond, null, array(1, 2, 3));
        if (empty($list)) {
            return false;
        }

        $curr_time = date("Y-m-d H:i:s");
        $time_gap = $time*60;
        foreach ($list as $live) {
            if (($curr_time >= $live['start_time']) || ($curr_time<($live['start_time']-$time_gap))) {
                continue;
            }
            if (Yii::app()->redis_r->exists(self::LCS_LIVE_PLANNER_CACHE.$time.'_'.$live['id'])) {
                continue;
            }

            self::sendMpMessage($live['id'], 2);
            Yii::app()->redis_w->setex(self::LCS_LIVE_PLANNER_CACHE.$time.'_'.$live['id'], self::LCS_LIVE_PROLONG_TIMEOUT, 1);
        }        
    }

    /**
     * 推送公众号
     * @param $live_id
     * @param int $type 1用户 2理财师
     */
    private function sendMpMessage($live_id, $type = 1)
    {
        $YiiRedis = Yii::app()->redis_w;
        $pushData = json_encode(array('type' => 'createLiveNotice', 'live_id' => $live_id, 'to_u_type' => $type));
        $YiiRedis->rPush(self::LCS_PUSH_MESSAGE_QUEUE, $pushData);
    }

    /**
     * 直播错误日志
     * @param $msg
     */
    private function logLiveError($msg)
    {
        if (!empty($msg)) {
            echo $msg."\r\n";
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_WARNING, $msg);
        }
    }

}
