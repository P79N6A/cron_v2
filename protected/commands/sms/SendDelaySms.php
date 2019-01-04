<?php

/**
 * 检索应该在未来某个时刻发送的短信并发送
 * @author lixiang23 <lixiang23@staff.sina.com.cn>
 * @copyright (c) 20161107,
 */
class SendDelaySms {

    ///定时任务号
    const CRON_NO = 7702;

    ///全部短信处理队列
    public $all_sms_queue_key = "lcs_org_all_sms_queue";
    ///快速短信处理队列
    public $fast_sms_queue_key = "lcs_org_fast_sms_queue";

    public function process() {
        $delay_sms = Sms::model()->getDelaySms();
        if (count($delay_sms) > 0) {
            echo json_encode($delay_sms)."\n\r";
            foreach ($delay_sms as $item) {
                Yii::app()->redis_w->rpush($this->all_sms_queue_key, json_encode($item));
            }
        }
    }

}
