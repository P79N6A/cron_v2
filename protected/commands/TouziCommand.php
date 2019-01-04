<?php
/**
 * 投资易相关的定时任务
 */

class TouziCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.touzi.*');
        Yii::import('application.components.CommonUtils*');
    }

    /**
     * 定时生成A股市场股票的B/S点数据
     */
    public function actionStocksBS() {
        try{
            $op = new StocksBS();
            $op->SaveStocksBS();
        }catch(Exception $e) {
            var_dump($e->getMessage());
            Cron::model()->saveCronLog(StocksBS::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
}
