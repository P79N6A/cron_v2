<?php

/**
 * 订单相关操作
 *
 */
class OrderCommand extends LcsConsoleCommand {

    public function init() {
        Yii::import('application.commands.order.*');
    }

    /**
     * 关闭超过24小时未支付的订单
     * order_no:8301
     */
    public function actionCloseOrders() {

        try {
            $closeOrders = new Close24HOrders();
            $closeOrders->CloseOrders();
            $this->monitorLog(Close24HOrders::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(Close24HOrders::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
    /**
     * 修复订单(微博回调延时)
     * order_no:8302
     */
    public function actionRepair(){
    	 try {
            $closeOrders = new RepairOrder();
            $closeOrders->repairOrder();
            $this->monitorLog(RepairOrder::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(RepairOrder::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
    /**
     * 修复订单(微博回调延时)
     * order_no:8302
     */
    public function actionRepairOrderV2(){
    	 try {
            $closeOrders = new RepairOrderV2();
            $closeOrders->repairOrder();
            Cron::model()->saveCronLog(RepairOrderV2::CRON_NO, CLogger::LEVEL_INFO, 'res:true');
            $this->monitorLog(RepairOrderV2::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(RepairOrderV2::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 充值增量
     * @param type $start_time 开始
     * @param type $end_time 介绍
     * @param type $is_sync 是否推送
     */
    public function actionRechargeIncr($start_time = '', $end_time = '') {
        try {
            $auto = new AutoIncrement($start_time, $end_time);
            $rows = $auto->autoFill();
            Cron::model()->saveCronLog(AutoIncrement::CRON_NO, CLogger::LEVEL_INFO, 'update records:' . $rows);
            $this->monitorLog(AutoIncrement::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(AutoIncrement::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 消费增量
     * @param type $start_time 开始时间
     * @param type $end_time 结束时间
     * @param type $is_sync 是否推送
     */
    public function actionConsumerIncr($start_time = '', $end_time = '') {
        try {
            $auto = new AutoIncrement($start_time, $end_time);
            $rows = $auto->autoOrder();
            Cron::model()->saveCronLog(AutoIncrement::CRON_NO, CLogger::LEVEL_INFO, 'update records:' . $rows);
            $this->monitorLog(AutoIncrement::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(AutoIncrement::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 退款增量
     * @param type $start_time
     * @param type $end_time
     * @param type $is_sync
     */
    public function actionRefundIncr($start_time = '', $end_time = '') {
        try {
            $auto = new AutoIncrement($start_time, $end_time);
            $rows = $auto->autoStrike();
            $this->monitorLog(AutoIncrement::CRON_NO);
            Cron::model()->saveCronLog(AutoIncrement::CRON_NO, CLogger::LEVEL_INFO, 'update records:' . $rows);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(AutoIncrement::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 二级产品增量信息 包括问答、观点、计划等
     * @param type $start_time
     * @param type $end_time
     * @param type $is_sync
     */
    public function actionProjectIncr($start_time = '', $end_time = '',$flag = 1) {
        try {
            $auto = new AutoIncrement($start_time, $end_time);
            $rows = $auto->autoProject($flag);
            $this->monitorLog(AutoIncrement::CRON_NO);
            Cron::model()->saveCronLog(AutoIncrement::CRON_NO, CLogger::LEVEL_INFO, 'update records:' . $rows);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(AutoIncrement::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 推送充值
     * @param type $start_time
     * @param type $end_time
     */
    public function actionPushFill($start_time = '', $end_time = '') {
        try {
            $pay_type = '1,2';                              
            $push = new PushOrders($start_time, $end_time);
            $push->pushIncrFill($pay_type);
            $this->monitorLog(PushOrders::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(PushOrders::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 推送订单
     * @param type $start_time
     * @param type $end_time
     */
    public function actionPushOrder($start_time = '', $end_time = '') {
        try {
            $pay_type = '1,2,3';             
            $push = new PushOrders($start_time, $end_time);
            $push->pushIncrOrder($pay_type);
            $this->monitorLog(PushOrders::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(PushOrders::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 推送退款
     * @param type $start_time
     * @param type $end_time
     */
    public function actionPushStrike($start_time = '', $end_time = '') {
        try {
            $pay_type = '1,2,3'; //,4,6            
            $push = new PushOrders($start_time, $end_time);
            $push->pushIncrStrike($pay_type);
            $this->monitorLog(PushOrders::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(PushOrders::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 推送理财师信息
     */
    public function actionPushAuthor() {
        try {            
            $push = new PushOrders();
            $push->PushAuthor();
            $this->monitorLog(PushOrders::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(PushOrders::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 推送项目信息
     */
    public function actionPushProject($start_time = '', $end_time = '') {        
        try {            
            $push = new PushOrders($start_time, $end_time);
            $push->pushIncrProject();
            $this->monitorLog(PushOrders::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(PushOrders::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 根据已购订单自动添加订阅关系
     * 8106
     */
    public function actionAutoSubByOrder() {
        try {
            $op = new AutoSubByOrder();
            $op->process();
            $this->monitorLog(AutoSubByOrder::CRON_NO);
        } catch (Exception $e) {
            echo $e->getMessage();
            Cron::model()->saveCronLog(AutoSubByOrder::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    public function actionTotal(){
        $t = new OrderDailyStat();                
        print_r($t->all());
        print_r($t->all(date('Y-m-d',strtotime('-1 days'))));
    }


}
