<?php

/**
 * Description of SyncUser2CRMCommand
 *
 * @author Administrator
 */
class SyncUser2CRMCommand extends LcsConsoleCommand
{

	public function init()
	{
		Yii::import('application.commands.syncUser2CRM.*');
	}

	public function actionSyncUser()
	{
		try {
			ob_start();
			self::setProcessTitle('SyncUser2CRMCommand');
			SyncUser::run();
			$echo = ob_get_clean();
			echo $echo;
			Cron::model()->saveCronLog(SyncUser::CRON_NO, CLogger::LEVEL_INFO, $echo);
			$this->monitorLog(SyncUser::CRON_NO);
		} catch (Exception $e) {
			Cron::model()->saveCronLog(SyncUser::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

	private static function setProcessTitle($title)
	{
		if (function_exists('cli_set_process_title')) {
			cli_set_process_title($title);
		} elseif (extension_loaded('proctitle') && function_exists('setproctitle')) {
			setproctitle($title);
		}
	}

}
