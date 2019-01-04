<?php

/**
 * 观点包到期提醒
 */
class PkgExpire {

    const CRON_NO = 1310; //任务代码

    public function __construct() {
        
    }

    /**
     * 每5分钟执行一次
     */
    public function sendMsg() {
        try {
            $this->pkgExpireMsg();    //已过期提醒
            $this->pkgExpireMsg(3);   //3天后将过期
        } catch (Exception $e) {
            throw LcsException::errorHandlerOfException($e);
        }
    }

    private function pkgExpireMsg($day = 0) {
        $end_time = time() + 86400 * intval($day);
        $begin_time = $end_time - 300;

        $btime = date("Y-m-d H:i:s", $begin_time);
        $etime = date("Y-m-d H:i:s", $end_time);
        $log_info = '';
        $sub_list = Package::model()->getPackageSubUserList($btime, $etime);

        if (empty($sub_list)) {
            return;
        }

        $pkg_ids = array();
        foreach ($sub_list as $sub_info) {
            array_push($pkg_ids, $sub_info['pkg_id']);
        }
        $pkg_ids = array_unique($pkg_ids);
        $packages = Package::model()->getPackagesById($pkg_ids);

        foreach ($sub_list as $sub_info) {

            $push_data = array("type" => "packageExpire", 'day' => $day, 'uid' => $sub_info['uid'], 'pkg_title' => $packages[$sub_info['pkg_id']]['title'], "pkg_id" => $sub_info['pkg_id']);
            Yii::app()->redis_w->rPush("lcs_common_message_queue", json_encode($push_data));
            $log_info .= $sub_info['uid'] . ":" . $sub_info['pkg_id'] . ";";
        } // end foreach.
        if ($log_info != '') {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, $log_info);
        }
    }

}
