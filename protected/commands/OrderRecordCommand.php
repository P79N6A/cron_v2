<?php
/**
 * 课程、锦囊、体验卡订单历史记录
 */

class OrderRecordCommand extends LcsConsoleCommand {

	public function init(){
		Yii::import('application.commands.orderRecord.*');
	}

	/**
	 * 投教订单信息接口
	 */
	public function actionOrdersRecord(){
		try{
			$obj = new OrdersRecord();
            $obj->SaveOrdersRecord();
            $this->monitorLog(OrdersRecord::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(OrdersRecord::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

}