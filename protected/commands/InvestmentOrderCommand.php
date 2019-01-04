<?php
/**
 * 投教订单报表定时任务入口
 */

class InvestmentOrderCommand extends LcsConsoleCommand {

	public function init(){
		Yii::import('application.commands.investmentOrder.*');
	}

	/**
	 * 投教订单信息接口
	 */
	public function actionTouJiaoOrder(){
		try{
			$obj = new TouJiaoOrder();
            $obj->TouJiaoOrders();
            $this->monitorLog(TouJiaoOrder::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(TouJiaoOrder::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	/**
	 * 投教订单信息接口
	 */
	public function actionOrderHistory(){
		try{
			$obj = new OrderHistory();
            $obj->option();
            $this->monitorLog(OrderHistory::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(OrderHistory::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

    /**
     * 投诉信息推送
     */
    public function actionPushReportInfo(){
        try{
            $obj = new PushReportInfo();
            $obj->option();
            $this->monitorLog(PushReportInfo::CRON_NO);

        }catch(Exception $e) {
            Cron::model()->saveCronLog(PushReportInfo::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 投教订单信息接口
     */
    public function actionReportHistory(){
        try{
            $obj = new ReportHistory();
            $obj->option();
            $this->monitorLog(ReportHistory::CRON_NO);

        }catch(Exception $e) {
            Cron::model()->saveCronLog(ReportHistory::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
}