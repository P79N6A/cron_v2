<?php
/**
 * 定时任务: 理财师圈子直播公告开始前提醒
 * add by zhihao6 2017/01/12
 */

class CircleLiveNotice
{
    const CRON_NO = 1050; //任务代码

    public function __construct()
    {
    }

    public function process()
    {
        try {
            // |curr提醒|----15分钟--->|start|
            $time_10 = time()+1000; // 开始前15分钟，额外多加一点
            $push_notice_ids = [];

            $notice_list = Circle::model()->getCircleUnpushLiveNotice();
            if (!empty($notice_list)) {
                foreach ($notice_list as $notice) {
                    $notice_content = json_decode($notice['notice'], true);
                    // if (1) {
                    if (strtotime($notice_content['start_time']) <= $time_10) {
                        $push_notice_ids[] = $notice['id'];

                        $push_data = [];
                        $push_data['type'] = 'plannerCircleLiveNoticeStart';
                        $push_data['n_id'] = $notice['id'];
                        Yii::app()->redis_w->rPush("lcs_common_message_queue", json_encode($push_data));

                        $columns['status'] = 2;
                        $columns['u_time'] = date("Y-m-d H:i:s");
                        $conditions = 'id=:id';
                        $params['id'] = $notice['id'];
                        Circle::model()->updateCircleNotice($columns, $conditions, $params);
                    }
                }
            }

            if (empty($push_notice_ids)) {
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "push to planner circle live notice: nothing need to do.");
            } else {
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "push to planner circle live notice: " . implode(",", $push_notice_ids) . ".");
            }
        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
    }
}
