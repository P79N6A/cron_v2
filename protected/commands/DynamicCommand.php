<?php
/**
 * 动态微观点相关定时任务
 */

class DynamicCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.dynamic.*');
    }

    /**
     * 识别文本内容
     */
    public function actionParseContent(){
        try{
            $Dynamic = new ParseContent();
            $Dynamic->Process();
            $this->monitorLog(ParseContent::CRON_NO);
        }catch (Exception $e) {
            var_dump($e->getMessage());
            Cron::model()->saveCronLog(ParseContent::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    public function actionTest()
    {
        $id = 12;
        $dynamic_list = Dynamic::model()->getDynamicByIds(array($id));
        if(isset($dynamic_list[$id])){
            $dynamic_info = $dynamic_list[$id];

            //解析股票信息
            SymbolService::parseSymbol($dynamic_info);
        }
    }
}
