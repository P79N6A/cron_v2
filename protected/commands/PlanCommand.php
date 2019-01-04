<?php
/**
 * 计划定时任务入口
 */

class PlanCommand extends LcsConsoleCommand {

	public function init(){
		Yii::import('application.commands.plan.*');
	}
	
	/**
	 * 订阅撮合系统的订单信息5001
	 *
	 */
	public function actionOrderSub(){
		try{
			$obj = new OrderSub();
            $obj->OrderSub();
            $this->monitorLog(OrderSub::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(OrderSub::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	
	/**
	 * 订阅撮合系统的订单信息5001
	 *
	 */
	public function actionOrderSubBack(){
		try{
			$obj = new OrderSubBack();
            $obj->OrderSub();
            $this->monitorLog(OrderSubBack::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(OrderSubBack::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	
	/**
	 * 处理计划的订单
	 *
	 * @param unknown_type $type cancel buy sell 撤单 买卖
	 * @param unknown_type $pln_id 计划id
	 * @param unknown_type $order_id 订单id
	 * @param unknown_type $symbol 股票代码
	 * @param unknown_type $price  成交价
	 * @param unknown_type $volume 成交量
	 * @param unknown_type $time 成交时间
	 * @param unknown_type $is_back 是否是备份脚本
	 */
	public function actionDealCuohe($type,$pln_id,$order_id,$symbol,$price,$volume,$time,$seqid,$is_back=0){
		
		if($is_back){
			usleep(50000);
		}
		//主和备份同时在跑，所以加一个乐观锁 
		$redis_key = "lcs_plan_cuohe_$type"."_$pln_id"."_$order_id";
		Yii::app()->redis_w->watch($redis_key);	
		if(Yii::app()->redis_w->get($redis_key)){//落后了
			Yii::app()->redis_w->unwatch();
			exit;
		}
		
		$redis_res = Yii::app()->redis_w->multi()->setex($redis_key,10,1)->exec();//有效期设为10秒
		if(!is_array($redis_res) || !$redis_res[0]){//同时走到这但是慢了导致失败
			Cron::model()->saveCronLog(OrderSub::CRON_NO,'info',"redis is lock:is_back=$is_back:$type:seqid=$seqid--pln_id=$pln_id--order_id=$order_id");
			exit;
		}
		
		
		
		$time = date('Y-m-d H:i:s',$time);
		$res = 'no';
		if($type == 'cancel'){
			$res = PlanService::cancelOrder($pln_id,$order_id);
		}elseif ($type == 'sell'){
			$res = PlanService::dealPlanOrder($pln_id,$order_id,$symbol,$price,$volume,2,$time);
		}elseif ($type == 'buy'){
			$res = PlanService::dealPlanOrder($pln_id,$order_id,$symbol,$price,$volume,1,$time);
		}
		Cron::model()->saveCronLog(OrderSub::CRON_NO,'info',"is_back=$is_back:$type:seqid=$seqid--pln_id=$pln_id--order_id=$order_id--$symbol--$price--$volume--$time--$res");
	}
	
	/**
	 * 每个交易日的9点26，把订单统一统一推送到撮合系统 5002
	 */
	public function actionSubmitOrder(){
		try{
			$obj = new SubmitOrder();
            $obj->SubmitOrder();
            $this->monitorLog(SubmitOrder::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(OrderSub::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	
	/**
	 * 股票的分红送股 5003
	 *
	 */
	public function actionShareStock(){
		try{
			$obj = new ShareStock();
            $obj->ShareStock();
            $this->monitorLog(ShareStock::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(ShareStock::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	
	/**
	 * 计划的交易水平透视 5004
	 *
	 */
	public function actionPlanAssess(){
		try{
			$obj = new GetAssess();
            $obj->GetAssess();
            $this->monitorLog(GetAssess::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(GetAssess::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	
	/**
	 * 统计理财师的一些计划指标5005
	 *
	 */
	public function actionPlannerExt(){
		try{
			$obj = new PlanPlannerExt();
            $obj->PlanPlannerExt();
            $this->monitorLog(PlanPlannerExt::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(PlanPlannerExt::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	

	/**
     * 5006 更新沪深300指数增长率
     */
    public function actionDailyK() {
        try{
            $obj = new DailyK();
            $obj->update();
            $this->monitorLog(DailyK::CRON_NO);
	
        }catch(Exception $e) {
            Cron::model()->saveCronLog(DailyK::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
    /**
	 * 5007更新计划的状态和剩余时间
	 *
	 */
	public function actionUpdStatus(){
		try{
			$obj = new UpdPlanStatus();
            $obj->UpdPlanStatus();
            $this->monitorLog(UpdPlanStatus::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(UpdPlanStatus::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}
	
	/**
	 * 5008 更新计划搜索数据
	 *
	 */
	public function actionAddSearch(){
		try{
			$obj = new SymbolAddSearch();
            $obj->addSearch();
            $this->monitorLog(UpdPlanStatus::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(SymbolAddSearch::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}


    /**
     * 5101 计划开始提醒 每天9点开始
     */
    public function actionPlanStartMessage(){
        try{
            $obj = new PlanStartMessage();
            $result = $obj->planStart();
            $this->monitorLog(PlanStartMessage::CRON_NO);
            Cron::model()->saveCronLog(PlanStartMessage::CRON_NO, CLogger::LEVEL_INFO, '计划开始提醒:'.json_encode($result));

        }catch(Exception $e) {
            Cron::model()->saveCronLog(PlanStartMessage::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 5102 计划结束提醒 没分钟执行
     */
    public function actionPlanEndMessage(){
        try{
            $obj = new PlanEndMessage();
            $result = $obj->planEnd();
            $this->monitorLog(PlanEndMessage::CRON_NO);
            if($result){
                Cron::model()->saveCronLog(PlanEndMessage::CRON_NO, CLogger::LEVEL_INFO, '计划结束提醒:'.json_encode($result));
            }

        }catch(Exception $e) {
            Cron::model()->saveCronLog(PlanEndMessage::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
    /**
     * 更新计划收益,市值,统计计划历史收益,清理未成交的单子 5009
     * 计算运行中计划的最大回撤
     *
     */
    public function actionIncome(){
    	try{
			$obj = new PlanIncome();
            $obj->incomeAndClear();
            sleep(3);
            $obj->maxBack();

            $this->monitorLog(PlanIncome::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(PlanIncome::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
    }
    /**
     * 收盘后更新计划收益和状态 5010
     *
     */
    public function actionUptRor(){
    	try{
			$obj = new UpdPlanRor();
            $obj->updPlanRor();
            $this->monitorLog(UpdPlanRor::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(UpdPlanRor::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
    }
    
    /**
     * 更新计划表的rundays 20181025
     *
     */
    public function actionUptPlanRunDays(){
    	
    	try{
			$obj = new UpdPlanRunDays();
            $obj->updPlanRunDays();
            $this->monitorLog(UpdPlanRunDays::CRON_NO);
		}catch(Exception $e) {
			Cron::model()->saveCronLog(UpdPlanRunDays::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
    }
    
    /**
     * 更新计划表的字段 5011
     *
     */
    public function actionUptPlanField(){
    	
    	try{
			$obj = new UpdPlanField();
            $obj->updPlanField();
            $this->monitorLog(UpdPlanField::CRON_NO);
			
		}catch(Exception $e) {
			Cron::model()->saveCronLog(UpdPlanField::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
    }

    /**
     * 5012 计划止损  止损冻结 到期冻结
     * 交易时间5分钟执行一次
     */
    public function actionPlanStatus() {
    	try{
    		$obj = new PlanStatus();
    		$obj->update();
    		$this->monitorLog(PlanStatus::CRON_NO);
    			
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(PlanStatus::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }

    /**
     * 5013 计划到期
     * 14:58 执行
     */
    public function actionPlanExpire() {
    	try{
    		$obj = new PlanExpire();
    		$obj->update();
    		$this->monitorLog(PlanExpire::CRON_NO);
    		 
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(PlanExpire::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }
    
    /**
     * 5014 修改订阅计划的退款状态
     * 00:01 执行
     */
    public function actionPlanRefundStatus() {
    	try{
    		$po = new PlanRefundStatus();
    		$po->process();
    		$this->monitorLog(PlanRefundStatus::CRON_NO);
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(PlanRefundStatus::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }
    
    /**
     * 5015 计划订阅过期处理  每小时更新一次数据
     * 
     */
    public function actionPlanSubscriptionExpire(){
        try {
            $obj = new PlanSubscriptionExpire();
            $result = $obj->planSubExpire();
            Cron::model()->saveCronLog(PlanSubscriptionExpire::CRON_NO, CLogger::LEVEL_INFO, ''.empty($result)?"无":json_encode($result));
            $this->monitorLog(PlanSubscriptionExpire::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(PlanSubscriptionExpire::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
       
    }
    
    public function actionDealTmpTask(){
        $sql = "select pln_id,symbol,deal_price,deal_amount,type,c_time,transaction_cost from lcs_plan_transactions where pln_id=29230 order by id asc";
        $list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
//        print_r($list);die;
        foreach ($list as $item){
            $pln_id = 29230;
            $symbol = $item['symbol'];
            $price = $item['deal_price'];
            $volume = $item['deal_amount'];
            $time = $item['c_time'];
            $type = $item['type'];
            if($type == 1){
                $warrant_value = $price * $volume+$item['transaction_cost'];
                $u_sql = "update lcs_plan_info set warrant_value={$warrant_value} where pln_id=29230 and p_uid=1789578644";
                Yii::app()->lcs_w->createCommand($u_sql)->execute();
            }
            $res = PlanService::tmpdealPlanOrder($pln_id,0,$symbol,$price,$volume,$type,$time);
            print_r($res);
        }
        
    }
    
    /**
     * 计划大赛数据更新
     */
    public function actionPlanMatch($pln_ids=""){
        try {
            $obj = new PlanMatch();
            $result = $obj->process($pln_ids);
            Cron::model()->saveCronLog(PlanMatch::CRON_NO, CLogger::LEVEL_INFO, ''.empty($result)?"无":json_encode($result));
            $this->monitorLog(PlanMatch::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(PlanMatch::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
    /**
     * 5017 平台体验卡
     */
    public function actionPtTyk(){
        try {
            $start_time = microtime(true);
            $tyk =  new PlanPropTyk();
            $result = $tyk->TykData();
            $end_time = microtime(true);
            $run_time = $end_time - $start_time;
            $this->monitorLog(PlanPropTyk::CRON_NO);
            $msg = "更新平台体验卡,日期：" . date('Y-m-d H:i:s')."运行时间:".$run_time;
            
            $msg.= " 新插入:".$result['affect_rows_new_satisfy'];
            $msg.= " 符合条件更新:".$result['affect_rows_exist_satisfy'];
            $msg.= " 不符合条件更新:".$result['affect_rows_exist_not_satisfy'];
            
            Cron::model()->saveCronLog(PlanPropTyk::CRON_NO,CLogger::LEVEL_INFO, $msg);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(PlanPropTyk::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
}
