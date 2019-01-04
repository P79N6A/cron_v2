<?php
/**
 * 统计数据的入口, 运营相关的数据
 * User: songyao
 * Date: 2015/6/26
 */

class ExportTouJiaoCommand extends LcsConsoleCommand 
{
	public function init()
	{
		Yii::import('application.commands.exportTouJiao.*');
	}
	public function actionExportTouJiaoData()
	{
		try
		{
			$count = new ExportTouJiaoData();
			$count->Exports(); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(ExportTouJiaoData::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

	public function actionExportPass()
	{
		try
		{
			$count = new ExportPass();
			$count->Exports(); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(ExportPass::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}


}