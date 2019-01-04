<?php 
/**
 * 定时任务:
 * Date: 2015-12-28
 */

class ViewNumOf2Month {


    const CRON_NO = 1206; //任务代码

    public function __construct(){

    }

    /**
     * 统计两个月（60）天内观点包的观点数
     * @author liyong3
     * @throws LcsException
     */
    public function process() {
        try {
        	$redis_key = 'lcs_package_2month_num';
            $redis_w = Yii::app()->redis_w;
            //$redis_w->delete($redis_key);
            $pkgs = Package::model()->getAllPackages();
            foreach ($pkgs as $pkg) {
                //统计数量
                $tomorrow = new DateTime('tomorrow');
                $stat_num = View::model()->getViewCountInPkg($pkg['pkg_id'], $tomorrow->modify('-60 days')->format('Y-m-d'));
                $redis_w->hset($redis_key, $pkg['pkg_id'], $stat_num);
            }
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '更新数目：'.count($pkgs));
        }catch (Exception $e) {
        	throw LcsException::errorHandlerOfException($e);
        }
    }


}
