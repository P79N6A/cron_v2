<?php

/*
 * Function: 对账功能 
 * Desc: 牛币对账规则
 * Author: meixin@staff.sina.com.cn
 * Date: 2015/08/28
 */

class CheckAccountCommand extends LcsConsoleCommand {


    public function init(){
		Yii::import('application.commands.checkAccount.*');
	}
    /*
     * 所有用户的对账 普通牛币充值 和 ios充牛币
	 * 每天（充值-消费+退牛币）+ 昨天的总和= 今天的总和 
     *
     */
    public function actionSumAccount() {
        try{
            $acount_obj = new SumAccount();
            $check_res = $acount_obj->check();
            //记录任务结束时间
            $this->monitorLog(SumAccount::CRON_NO);  //update  cron监控任务表 
			Cron::model()->saveCronLog(SumAccount::CRON_NO, CLogger::LEVEL_INFO, '用户牛币消费对账: '.$check_res);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(SumAccount::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
	/**
	 * 微博支付 对账内容：
	 * 查询一段时间范围内的 每六个小时 
	 * 理财师平台流水号 是否 全部与 微博平台流水对上
	 * 包括状态，价格
	 */
	public function actionCheckWeiboPay(){
        try{
            $check_obj = new CheckWeiboPay();
            $check_res = $check_obj->check();
            //记录任务结束时间
            $this->monitorLog(CheckWeiboPay::CRON_NO);  //update  cron监控任务表 
			Cron::model()->saveCronLog(CheckWeiboPay::CRON_NO, CLogger::LEVEL_INFO, '微博流水对账: '.$check_res);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(CheckWeiboPay::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }		
	}



}
