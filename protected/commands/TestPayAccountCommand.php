<?php

/*
 * Function: 支付宝流水导入测试库 
 * Desc: 
 * Author: meixin@staff.sina.com.cn
 * Date: 2015/11/13
 */

class TestPayAccountCommand extends LcsConsoleCommand {


    public function init(){
		Yii::import('application.commands.testpayaccount.*');
	}

    /*
     * 支付宝流水导入测试库 
	 * 
     *
     */
    public function actionPayAccount() {
        try{
            $acount_obj = new PayAccount();
            $insert_res = $acount_obj->insertPayAccount();
            //记录任务结束时间
            #$this->monitorLog(PayAccount::CRON_NO);  //update  cron监控任务表 
			Cron::model()->saveCronLog(PayAccount::CRON_NO, CLogger::LEVEL_INFO, '支付宝流水导入测试库: '.$insert_res);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(PayAccount::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
	}
}
