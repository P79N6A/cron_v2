<?php

/**
 * Description of SyncStockBaseCommand
 *
 * @author Administrator
 */
class SyncStockBaseCommand extends LcsConsoleCommand
{

	public function init()
	{
		Yii::import('application.commands.syncStockBase.*');
	}

	public function actionSyncStock()
	{
		try {
			SyncStock::writeLog('start');
			SyncStock::run();
			SyncStock::writeLog('end');
			$this->monitorLog(SyncStock::CRON_NO);
			Cron::model()->saveCronLog(SyncStock::CRON_NO, CLogger::LEVEL_INFO, 'run...');
		} catch (Exception $e) {
			Cron::model()->saveCronLog(SyncStock::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

}
