<?php
/**
 * 同步财经数据库数据到理财师库，如交易日、日K等
 * User: songyao
 * Date: 2015/6/26
 */

class SyncCJDataCommand extends LcsConsoleCommand 
{
	public function init()
	{
		ini_set('memory_limit', '512M');
		Yii::import('application.commands.sync_cj.*');
	}

	//同步交易日
	public function actionTradeDay()
	{
		try
		{
			$count = new QuotesDBSync();
			$count->SyncTradeDay(); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(QuotesDBSync::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

	//同步日K数据
	public function actionDailyK()
	{
		try
		{
			$count = new QuotesDBSync();
			$count->SyncDailyK(); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(QuotesDBSync::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
    /**
     * 同步股票信息
     * CRON_NO 8102
     * @param type $type
     */
    public function actionImportSymbol() {
		try {
			echo date('Y-m-d H:i:s') . "\r\n";
			//$type_arr = array('stock_cn', 'fund_open', 'fund_close', 'fund_etf', 'fund_lof', 'future_inner', 'stock_hk');
			$s = new ImportSymbol();
			$type_arr = array_keys($s->symbol_url);
			foreach ($type_arr as $type) {
				try {
					$s->doSymbol($type);
				} catch (Exception $e) {
					echo "[" . $e->getCode() . "] " . $e->getMessage() . " in file [" . $e->getFile() . "] at line " . $e->getLine() . " \r\n";
				}
			}
            $this->monitorLog(ImportSymbol::CRON_NO);
			echo "\r\n";
		} catch (Exception $e) {
            Cron::model()->saveCronLog(ImportSymbol::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
    /**
     * 同步问答股票
     * CRON_NO 8103
     * @param type $type
     */
    public function actionImportAskSymbol(){
        try{
            $type_arr = array('stock_cn','stock_us','fund_open', 'fund_close', 'fund_etf', 'fund_money', 'fund_qdii', 'fund_lof');
            $s = new ImportSymbol();
            foreach ($type_arr as $type){
                $s->doAskSymbol($type);
            }            
            $this->monitorLog(ImportSymbol::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(ImportSymbol::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 创建股票相关的股票和问答信息  并且同步最新的数据到newJs 供财经页面使用
     *  CRON_NO 8104
     */
    public function actionNewJsInfoOfSymbol(){
        try{
            $newJsInfoOfSymbol = new NewJsInfoOfSymbol();
            $newJsInfoOfSymbol->createRelationData();
            #$newJsInfoOfSymbol->syncDataToNewJs();
            $this->monitorLog(NewJsInfoOfSymbol::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(NewJsInfoOfSymbol::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
     /**
     * 创建股票相关的股票和问答信息  并且同步最新的数据到newJs 供财经页面使用
     *  CRON_NO 8105
     */
    public function actionNewJsInfoOfSymbolNew(){
        try{
            $newJsInfoOfSymbol = new NewJsInfoOfSymbolNew();
            $newJsInfoOfSymbol->syncDataToNewJs();
            $this->monitorLog(NewJsInfoOfSymbolNew::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(NewJsInfoOfSymbolNew::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 生成搜索数据 并且推送到搜索部门
     * CRON_NO 8105
     */
    public function actionSearchData(){
        try{
            $searchData = new SearchData();
            $view_num = $searchData->actionSyncViewData();
            $ask_num = $searchData->actionSyncAskData();
            $this->monitorLog(SearchData::CRON_NO);
            if($view_num>0 || $ask_num>0){
                Cron::model()->saveCronLog(SearchData::CRON_NO, CLogger::LEVEL_INFO, 'searchData view:'.$view_num.' ask:'.$ask_num);
            }
        } catch (Exception $e) {
            Cron::model()->saveCronLog(SearchData::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 更新观点数据，去掉tags字段
     * cron_no 8106 临时任务
     */
    public function actionSyncView() {
    	try {
    		$sv = new SyncView();
    		$sv->process();
    	}catch (Exception $e) {
    		Cron::model()->saveCronLog(SearchData::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }
    /**
     * 生成用户包含的所有自选股 并且推送到上海
     * CRON_NO 8107
     */
    public function actionPushUserStock(){
        try{
            $pushData = new PushUserStock();
            $pushData->pushProcess();
            $this->monitorLog(PushUserStock::CRON_NO);
        }catch (Exception $e){
            Cron::model()->saveCronLog(PushUserStock::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
}
