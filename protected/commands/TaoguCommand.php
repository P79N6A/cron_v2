<?php
/**淘股相关*/
Class TaoguCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.Taogu.*');
    }
    /**
     * 分红送股
    */
    public function actionShareStock(){
        try{
            $sharestock = new ShareStock();
            $share = ShareStock::getShareStock();
//            $share = "{\"code\":0,\"msg\":\"\u6210\u529f\",\"sys_time\":\"2018-08-09 09:03:09\",\"identify\":\"\",\"data\":{\"600246\":{\"symbol\":\"600246\",\"px\":\"0.27\",\"shpx\":\"0.27\",\"sg\":null,\"zz\":null,\"cqcxr\":\"2018-08-09 00:00:00\",\"type\":\"411\"},\"002910\":{\"symbol\":\"002910\",\"px\":\"0.73\",\"shpx\":\"0.73\",\"sg\":null,\"zz\":null,\"cqcxr\":\"2018-08-09 00:00:00\",\"type\":\"411\"},\"601900\":{\"symbol\":\"601900\",\"px\":\"2.048\",\"shpx\":\"2.048\",\"sg\":null,\"zz\":null,\"cqcxr\":\"2018-08-09 00:00:00\",\"type\":\"411\"},\"300291\":{\"symbol\":\"300291\",\"px\":\"0.28\",\"shpx\":\"0.28\",\"sg\":null,\"zz\":null,\"cqcxr\":\"2018-08-09 00:00:00\",\"type\":\"411\"},\"601198\":{\"symbol\":\"601198\",\"px\":\"1.5\",\"shpx\":\"1.5\",\"sg\":null,\"zz\":null,\"cqcxr\":\"2018-08-09 00:00:00\",\"type\":\"411\"},\"002202\":{\"symbol\":\"002202\",\"px\":\"2\",\"shpx\":\"2\",\"sg\":null,\"zz\":null,\"cqcxr\":\"2018-08-09 00:00:00\",\"type\":\"411\"},\"603689\":{\"symbol\":\"603689\",\"px\":\"1.2\",\"shpx\":\"1.2\",\"sg\":null,\"zz\":null,\"cqcxr\":\"2018-08-09 00:00:00\",\"type\":\"411\"},\"600993\":{\"symbol\":\"600993\",\"px\":\"2.3\",\"shpx\":\"2.3\",\"sg\":null,\"zz\":null,\"cqcxr\":\"2018-08-09 00:00:00\",\"type\":\"411\"},\"000833\":{\"symbol\":\"000833\",\"px\":\"0.5\",\"shpx\":\"0.5\",\"sg\":null,\"zz\":null,\"cqcxr\":\"2018-08-09 00:00:00\",\"type\":\"411\"},\"600865\":{\"symbol\":\"600865\",\"px\":\"0.4\",\"shpx\":\"0.4\",\"sg\":null,\"zz\":null,\"cqcxr\":\"2018-08-09 00:00:00\",\"type\":\"411\"},\"600846\":{\"symbol\":\"600846\",\"px\":\"1.3\",\"shpx\":\"1.3\",\"sg\":null,\"zz\":null,\"cqcxr\":\"2018-08-09 00:00:00\",\"type\":\"411\"},\"000423\":{\"symbol\":\"000423\",\"px\":\"9\",\"shpx\":\"9\",\"sg\":null,\"zz\":null,\"cqcxr\":\"2018-08-09 00:00:00\",\"type\":\"411\"},\"600428\":{\"symbol\":\"600428\",\"px\":\"0.2\",\"shpx\":\"0.2\",\"sg\":null,\"zz\":null,\"cqcxr\":\"2018-08-09 00:00:00\",\"type\":\"411\"},\"600288\":{\"symbol\":\"600288\",\"px\":\"0.24\",\"shpx\":\"0.24\",\"sg\":null,\"zz\":null,\"cqcxr\":\"2018-08-09 00:00:00\",\"type\":\"411\"},\"872542\":{\"symbol\":\"872542\",\"px\":\"11\",\"shpx\":\"11\",\"sg\":null,\"zz\":null,\"cqcxr\":\"2018-08-09 00:00:00\",\"type\":\"411\"}}}";
//            $share = json_decode($share,true);
//            $share = $share['data'];
            $sharestock->ModifyPrice($share);
            echo "分红更新成功！\n";
        } catch (Exception $e){
            Cron::model()->saveCronLog(ShareStock::CRON_NO_SHARE, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 处理未成交委托
     */
    public function actionDealOrder(){
        $Hi = date('Hi');
//        $Hi = '0940';
        if(($Hi >= '0930' && $Hi <='1130') || ($Hi >= '1300' && $Hi < '1501')){
            try{
                $dealorder = new DealOrder();
                $dealorder->dealOrder();
            }catch (Exception $e){
                Cron::model()->saveCronLog(DealOrder::CRON_NO_DEAL, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
            }
        }
    }

    /**
     * 系统撤销委托订单
     */
    public function actionCancelOrder(){
        if(date('Hi')<'1500'){
            return;
        }
        try{
            $cancel = new CancelOrder();
            $cancel->cancelOrders();
            echo "取消委托成功！\n";
        } catch (Exception $e){
            Cron::model()->saveCronLog(CancelOrder::CRON_NO_CANCEL, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }

    }

    /**
     * 更新股票收盘价
     */
    public function actionUpdateClose(){
        if(date('Hi') < '1600'){
            return;
        }
        try{
            $close = new UpdateClose();
            $close->ModifyClosePrice();
            echo "更新收盘价成功！\n";
        }catch (Exception $e){
            Cron::model()->saveCronLog(UpdateClose::CRON_NO_CLOSE, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 定时统计理财师淘股信息
     */
    public function actionTaoGuList(){
        try{
            $taoGu = new TaoGuList();
            $taoGu->handle();
        } catch (Exception $e){
            Cron::model()->saveCronLog(TaoGuList::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }

    }

    /**
     * 更新持
     */
    public function actionUpdateTaoGuRate(){
        $Hi = date('Hi');
        if(($Hi >= '0930' && $Hi <='1130') || ($Hi >= '1300' && $Hi < '1501')){
            try{
                $update = new UpdateRate();
                $update->updateTaoGuRate();
                $this->monitorLog(UpdateRate::CRON_NUM);
            }catch (Exception $e){
                Cron::model()->saveCronLog(UpdateRate::CRON_NUM, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
            }
        }
    }
}