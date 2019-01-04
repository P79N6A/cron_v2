<?php
/**
 * 板块数据同步
 */

class StockPlateCommand extends LcsConsoleCommand 
{
	public function init()
	{
		Yii::import('application.commands.stockPlate.*');
	}

    /**
      * 导入上海的板块数据
      */
	public function actionImport()
	{
		try
		{
			$stock= new ImportStockPlate();
			$stock->process(); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(ImportStockPlate::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
}
