<?php

/**
 * 同步IM投顾到CRM系统
 *
 * @author Administrator
 */
class SyncImCrmCommand extends LcsConsoleCommand
{

	public function init()
	{
		Yii::import('application.commands.syncImCrm.*');
	}

	public function actionSyncIm()
	{
		try{
            $operate_notice = new Sync();
            $operate_notice->run();
            // $this->monitorLog(Sync::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(Sync::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
	}

}
