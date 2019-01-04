<?php
/**
 * 统计数据的入口, 运营相关的数据，其他前台使用数据归划到相应的业务模块中去， 比如理财师的影响力统计，就归属再planner中 
 * User: songyao
 * Date: 2015/6/26
 */

class StatCommand extends LcsConsoleCommand 
{
	public function init()
	{
		Yii::import('application.commands.stat.*');
	}

	public function actionUser()
	{
		try
		{
			$count = new StatUserCount();
			$count->DistinctUser(); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(StatUserCount::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}


	public function actionUserToday()
	{
		try
		{
			$count = new StatUserCount();
			$count->DistinctUserToday(); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(StatUserCount::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

	public function actionUserMonth()
	{
		try
		{
			$count = new StatUserCount();
			$count->DistinctUserMonth(); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(StatUserCount::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

	

	public function actionSendMail($at)
	{
		try
		{
			$count = new StatUserCount();
			$count->SendMail($at); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(StatUserCount::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

	public function actionSendMail2($at)
	{
		try
		{
			$count = new StatDaily();
			$count->SendMail2($at); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(StatDaily::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

	public function actionStatBase()
	{
		try
		{
			$count = new StatDaily();
			$count->StatBase(); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(StatDaily::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	public function actionStatSearchHot()
	{
		try
		{
			$count = new StatDaily();
			$count->StatSearchHot(); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(StatDaily::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	public function actionStatPlannerViews($dt,$inc)
	{
		try
		{
			$count = new StatDaily();
			$count->StatPlannerViews($dt,$inc); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(StatDaily::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	public function actionUpdateViewNum()
	{
		try
		{
			$count = new StatDaily();
			$count->UpdateViewNum(); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(StatDaily::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	public function actionStatPackageSub()
	{
		try
		{
			$count = new StatDaily();
			$count->StatPackageSub(); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(StatDaily::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	public function actionStatPackageSubToday($dt)
	{
		try
		{
			$count = new StatDaily();
			$count->StatPackageSubToday($dt); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(StatDaily::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	public function actionStaff($st)
	{
		try
		{
			$count = new StatDaily();
			$count->Staff($st); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(StatDaily::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	public function actionStatPlannerLost($d)
	{
		try
		{
			$count = new StatDaily();
			$count->StatPlannerLost($d); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(StatDaily::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	public function actionStatIndData($dt)
	{
		try
		{
			$count = new StatDaily();
			$count->StatIndData($dt); 
		}
		catch (Exception $e) 
		{
			Cron::model()->saveCronLog(StatDaily::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
    public function actionStatHotStocks(){
        try{
            $S = new StatHotStocks();
            $S->getHotStocks();
            //记录任务结束时间
            $this->monitorLog(StatHotStocks::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(StatHotStocks::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

   /**
    * 保存访问日志
    */ 
    public function actionSaveAccessLog(){
        try{
            $S = new StatAccessLog();
            $S->process();
            $this->monitorLog(StatAccessLog::CRON_NO);
        } catch (Exception $e) {
            var_dump($e->getMessage());
            Cron::model()->saveCronLog(StatAccessLog::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

	/**
	 * 保存admin2访问日志
	 */
	public function actionSaveAdmin2Log()
	{
		try{
			$S = new StatAccessLog();
			$S->saveAdmin2Log();
			$this->monitorLog(StatAccessLog::CRON_NO);
		} catch (Exception $e) {
			Cron::model()->saveCronLog(StatAccessLog::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	
	
    public function actionCreateIndex(){
        $obj= new Common();
        $url = $obj->url;
        $url.=Common::INDEX_LOG_NAME;
        CommonUtils::initEs($url);
    }
    public function actionCreateMapping(){
        $obj= new Common();
        $url = $obj->url;
        $url .= Common::INDEX_LOG_NAME."/_default_/_mapping?pretty";
        $data='{
            "_default_": {
                "properties": {
                    "uid": {"type": "integer"},
                    "action": {"type": "text","analyzer": "ik_max_word","search_analyzer": "ik_max_word"},
                    "sina_global": {"type": "text","analyzer": "ik_max_word","search_analyzer": "ik_max_word"},
                    "start_time": {"type": "text"},
                    "end_time": {"type": "text"},
                    "url": {"type": "text"},
                    "ip": {"type": "text"},
                    "referer": {"type": "text"},
                    "deviceid": {"type": "text"},
                    "ua": {"type": "text"},
                    "response": {"type": "text"},
                    "c_time": {"type": "date"}
                }
            }
        }';
        CommonUtils::initEs($url,$data);
    }

   /**
    * 保存机构访问日志
    */ 
    public function actionSaveOrgAccessLog(){
        try{
            $S = new StatAccessLog();
            $S->process("org");
            $this->monitorLog(StatAccessLog::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(StatAccessLog::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 处理访问数据,汇总理财师统计数据
     */
    public function actionPlannerInfo($stat_date = null){
        try{
            if(empty($stat_date)){
                $stat_date = date("Y-m-d",strtotime("-1 day"));
            }
            $S = new StatAccessLog();
           	$S->PlannerInfo($stat_date);
            $this->monitorLog(StatAccessLog::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(StatAccessLog::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 处理访问数据,汇总统计数据
     */
    public function actionProductInfo($stat_date = null){
        try{
            if(empty($stat_date)){
                $stat_date = date("Y-m-d",strtotime("-1 day"));
            }
            $S = new StatAccessLog();
           	$S->ProductInfo($stat_date);
           	#$S->ProductList();
            $this->monitorLog(StatAccessLog::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(StatAccessLog::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 处理访问数据,汇总统计数据
     */
    public function actionDumpUser($stat_date = null){
        try{
            if(empty($stat_date)){
                $stat_date = date("Y-m-d",strtotime("-1 day"));
            }
            $S = new StatAccessLog();
           	$S->DumpUser();
            $this->monitorLog(StatAccessLog::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(StatAccessLog::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 处理访问数据,汇总统计数据
     */
    public function actionStatisticLog($stat_date = null){
        try{
            if(empty($stat_date)){
                $stat_date = date("Y-m-d",strtotime("-1 day"));
            }
            $S = new StatAccessLog();
           	$S->StatLog($stat_date);
           	#$S->DumpUser();
            $this->monitorLog(StatAccessLog::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(StatAccessLog::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    
    /**
     * 删除部分数据
     */
    public function actionClearAccessLog(){
        try{
            $S = new StatAccessLog();
            $S->ClearLog();
            $this->monitorLog(StatAccessLog::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(StatAccessLog::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 将数据存放到es中
     */
    public function actionSaveLogES(){
        try{
            $s = new SaveEsLog();
            $s->saveLog();
            $this->monitorLog(StatAccessLog::CRON_NO);
        }catch(Exception $e){
            Cron::model()->saveCronLog(StatAccessLog::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

	/**
	 * 将数据存放到es中
	 */
	public function actionSaveErrorLog(){
		try{
			$s = new StatAccessLog();
			$s->saveErrorLog();
			$this->monitorLog(StatAccessLog::CRON_NO);
		}catch(Exception $e){
			Cron::model()->saveCronLog(StatAccessLog::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
}
