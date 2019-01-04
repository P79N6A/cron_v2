<?php
/**
 * 缓存定时任务
 */

class CacheCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.deletecache.*');
    }

    /**
     * 删除缓存
     */
    public function actionDeleteCache(){
        try{
            $cache = new DeleteCache();
            $cache->removeCache();
            //记录任务结束时间
            $this->monitorLog(DeleteCache::CRON_NO);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(DeleteCache::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }


    }
}
