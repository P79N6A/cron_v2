<?php

/**
 * Description of GetGlobalnewsCommand
 *
 * @author Administrator
 */
class GetGlobalnewsCommand extends LcsConsoleCommand
{

	public function init()
	{
		Yii::import('application.commands.getGlobalnews.*');
	}

	/**
	 * getNews
	 * @command yiic GetGlobalnew GetNews
	 */
	public function actionGetNews()
	{
		try {
			//记录任务结束时间
			$this->monitorLog(GetGlobalnews::CRON_NO);
			$res = GetGlobalnews :: start('f');
			if ($res) {
				Cron::model()->saveCronLog(GetGlobalnews::CRON_NO, CLogger::LEVEL_INFO, 'running');
			}
		} catch (Exception $e) {
			Cron::model()->saveCronLog(GetGlobalnews::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

}
