<?php
/**
 * 同步投教rcm的定时任务入口  任务编号  1901 - 1999
 * User: lining
 * Date: 2018/1/28
 * Time: 14:15
 */

class InvestmentCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.investment.*');
    }

    /**
     * 1901 购买记录接口
     *
     */
    public function actionBuyNotice(){
        try {
            $buy = new BuyNotice();
            $buy->run();
    	}catch (Exception $e) {
    		var_dump($e->getMessage());   
    	}
    }
    /**
     * 1902 更改用户分类
     * 
     */
    public function actionChangNotice(){
    	try {
            $chang = new ChangNotice();
            $chang->run();
    	}catch (Exception $e) {
    		var_dump($e->getMessage());
    	}
    }
    /**
     * 购买记录接口v2
     */
    public function actionBuyNoticeV2(){
        try {
            $buy = new BuyNoticeV2();
            $buy->run();
        }catch (Exception $e) {
            Common::model()->saveLog(sprintf("同步异常%s,文件:,行数:%s,追踪:%s",$e->getMessage(),$e->getFile(),$e->Line(),json_encode($e->getTrace())), 'error','Lcs-Cron-Buy-Notice-V2');
            var_dump($e->getMessage());
        }
    }
    /**
     * 1902 更改用户分类
     * 
    */
    public function actionChangNoticeV2(){
        try {
            $chang = new ChangNoticeV2();
            $chang->run();
        }catch (Exception $e) {
            Common::model()->saveLog(sprintf("同步异常%s,文件:,行数:%s,追踪:%s",$e->getMessage(),$e->getFile(),$e->Line(),json_encode($e->getTrace())), 'error','Lcs-Cron-Chang-Notice-V2');
            var_dump($e->getMessage());
        }
    }
}