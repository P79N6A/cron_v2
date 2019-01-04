<?php

class BalaCommand extends LcsConsoleCommand {

    public function init() {
        Yii::import('application.commands.balasync.*');
    }

    public function actionTest(){
        echo 'hello lining';
    }
    
    /**
     * balaComment 异步定时任务接口
     */
    public function actionBalaSync() {
        try {
            $balasync = new BalaSyncHandler();
            $res = $balasync->process();
            //记录任务结束时间
            // $this->monitorLog(BalaSyncHandler::CRON_NO);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

}
