<?php
/**
 * 计划止损 冻结
 * @author liyong3
 *
 */
class PlanStatus {

    const CRON_NO = 5012; //任务代码
    
    //止损 stoploss  止损冻结 stoploss_freeze   到期冻结 expire_freeze
    public $type_arr = array(
        'stoploss', 'stoploss_freeze', 'expire_freeze'
    );

    public function update() {
        //不是交易日时终止
        $trading_day = Calendar::model()->isTradeDay(date("Y-m-d"));
        if(empty($trading_day)) {
            exit();
        }
        //运行的时间范围：9:30-11:35  13:00-15:00
        if(time()<strtotime(date("Y-m-d 09:30:00")) 
           || (time()>strtotime(date("Y-m-d 11:35:10")) && time()<strtotime(date("Y-m-d 13:00:00"))) || time()>strtotime(date("Y-m-d 15:00:10"))){
            exit();
        }
        foreach($this->type_arr as $type) {
            $this->process($type);
        }
    }

    private function process($type) {
        $search_status = Plan::PLAN_STATUS_ACTIVE;//查询状态
        $freeze_status = Plan::PLAN_STATUS_STOPLOSS_FREEZE;//冻结状态
        $end_date = '';

        if($type == 'stoploss_freeze'){ //止损冻结
            $search_status = Plan::PLAN_STATUS_STOPLOSS_FREEZE;
        }
        elseif($type == 'expire_freeze'){ //到期冻结
            $search_status = $freeze_status = Plan::PLAN_STATUS_EXPIRE_FREEZE;
        }
        
        //获取计划列表
        $plans = Plan::model()->getPlanListByStatus($search_status,$end_date);
        if(!empty($plans)) {
            foreach($plans as $plan_info) {
                $plan_info = Plan::model()->getPlanInfoById($plan_info['pln_id']);
                if(empty($plan_info)){
                	$log_info[] = $plan_info['pln_id'].':计划不存在';
                    continue;
                }
                //获取计划持仓市值(市值 + 持仓信息)
                $_asset = Plan::model()->getPlanAssetMarkValue($plan_info['pln_id']);
                $asset_mark_value = isset($_asset['mark_value']) ? $_asset['mark_value'] : 0;
                $assets = isset($_asset['assets']) ? $_asset['assets'] : array();
                
                if($asset_mark_value>=0){
                    //计划的总市值(持仓市值+可用资金+冻结资金)
                    $plan_mark_value = $asset_mark_value + $plan_info['available_value'] + $plan_info['warrant_value'];
                    //当前收益
                    $curr_ror = ($plan_mark_value/$plan_info['init_value'])-1;

                    $plan_status = 0;
                    //止损
                    if($type=="stoploss" && $curr_ror<=$plan_info['stop_loss']){
                        $plan_status = Plan::PLAN_STATUS_FAIL;
                    }
                    //成功计划(到期、冻结)
                    elseif($type!="stoploss" && $curr_ror>=$plan_info['target_ror']){
                        $plan_status = Plan::PLAN_STATUS_SUCCESS;
                    }
                    //失败计划(到期、冻结)
                    elseif($type!="stoploss" && $curr_ror<=$plan_info['target_ror']){
                        $plan_status = Plan::PLAN_STATUS_FAIL;
                    }
                    if($plan_status>0){
                        $transaction = Yii::app()->lcs_w->beginTransaction();
                        try {
                            //撤销订单（并返回撤销的卖出的股票代码和数量）
                            $revoke = Plan::model()->RevokePlanOrder($plan_info['pln_id']);
                            if($revoke['counter'] <= 0){
                                throw new Exception("REVOKE ORDER ERROR");
                            }
                            $asset_count_before = count($assets); //所有的持仓数量
                            $_asset_mark_value = 0; //可卖的持仓市值
                            $_freeze_asset_mark_value = 0;//冻结的持仓市值（不可卖的）
                            $_freeze_asset_value = 0;//冻结的资产（不可卖的，算仓位用）
                            $is_sell_all = true;
                            //删除不能卖的股票信息
                            foreach($assets as $key=>$val){
                                //更新撤单后的可卖量
                                if(isset($revoke['revoke_sell'][$val['symbol']])){
                                    $_asset['assets'][$key]['available_sell_amount'] += $revoke['revoke_sell'][$val['symbol']];
                                    $assets[$key]['available_sell_amount'] += $revoke['revoke_sell'][$val['symbol']];
                                    $val['available_sell_amount'] += $revoke['revoke_sell'][$val['symbol']];
                                }

                                //判断是否可以全部卖出
                                if($val['amount'] != $val['available_sell_amount']){
                                    $is_sell_all = false;
                                }
                                if($val['is_sell']!=1){
                                    //冻结的持仓市值
                                    $_freeze_asset_mark_value += $val['new_price']*$val['amount'];
                                    //冻结的资产
                                    $_freeze_asset_value += $val['hold_avg_cost']*$val['amount'];
                                    unset($assets[$key]);
                                }else{
                                    //可卖股票的持仓市值（减去手续费）
                                    $_asset_mark_value += $val['new_price']*$val['available_sell_amount']-Plan::model()->getTransactionCost($val['symbol'],$val['new_pric
                                            e'],$val['available_sell_amount']);
                                    //冻结的持仓市值（最新价*不可卖的数量）
                                    $_freeze_asset_mark_value += $val['new_price']*($val['amount']-$val['available_sell_amount']);
                                    //冻结的资产
                                    $_freeze_asset_value += $val['hold_avg_cost']*($val['amount']-$val['available_sell_amount']);
                                }
                            }//end foreach.
                            $asset_count_after = count($assets);//去掉不能卖的股票后的持仓数量

                            if(count($assets) > 0){ //当前有持仓
                                //平仓
                                $asset_counter = Plan::model()->assetUnwinding($plan_info['pln_id'],$assets);
                                //echo $asset_counter;
                                if($asset_counter != count($assets)){
                                    throw new Exception("ASSET UNWINDING ERROR");
                                }

                                //增加交易动态（发交易提醒）
                                $transaction_counter = Plan::model()->savePlanTransaction($plan_info,$_asset['assets'],$type);
                                if($transaction_counter != count($assets)){
                                    throw new Exception("ADD TRANSACTION ERROR");
                                }
                            }

                            //修改计划信息(发收益提醒)
                            $_plan = array(
                                    'curr_ror'=>$curr_ror,
                                    'max_profit'=>$curr_ror > $plan_info['max_profit'] ? $curr_ror : $plan_info['max_profit'],
                                    'min_profit'=>$curr_ror < $plan_info['min_profit'] ? $curr_ror : $plan_info['min_profit'],
                                    'weight' => $_freeze_asset_value/($_freeze_asset_value+$_asset_mark_value + $plan_info['available_value'] + $plan_info['warrant_value']),
                                    'available_value'=>$_asset_mark_value + $plan_info['available_value'] + $plan_info['warrant_value'],
                                    'warrant_value'=>0,
                                    'market_value'=>$_freeze_asset_mark_value + $_asset_mark_value + $plan_info['available_value'] + $plan_info['warrant_value'],
                                    'status'=>($asset_count_before==$asset_count_after && $is_sell_all) ? $plan_status : $freeze_status,
                                    'operate_time'=>date("Y-m-d H:i:s"),
                                    'u_time'=>date("Y-m-d H:i:s")
                            );
                            //成功或失败计划更新结束时间
                            if($_plan['status'] == Plan::PLAN_STATUS_FAIL || $_plan['status'] == Plan::PLAN_STATUS_SUCCESS) {
                                $_plan['real_end_time'] = date("Y-m-d H:i:s");
                            }
                            //止损或到期的冻结状态，更新冻结时间
                            if(in_array($type,array('stoploss','expire')) && in_array($_plan['status'],array(6,7))){
                                $_plan['freeze_time'] = date("Y-m-d H:i:s");
                            }

                            $plan_counter = Plan::model()->updatePlanInfo($plan_info['pln_id'],$plan_info['name'],$plan_info['target_ror'],$_plan);
                            if($plan_counter <= 0){
                                throw new Exception("UPDATE PLAN INFO ERROR");
                            }

                            //成功计划统计理财师为用户赚取的钱数，和平台为用户赚取的钱数
                            if($_plan['status'] == Plan::PLAN_STATUS_SUCCESS){
                                Plan::model()->statPlanMakeMoneyTotal($plan_info['pln_id'],$plan_info['p_uid'],$plan_info['init_value'],$curr_ror);
                            }

                            $transaction->commit();
                            


                        }catch(Exception $e) {
                            $transaction->rollBack();
                            $log_info[] = $plan_info['pln_id'].':'.$e->getMessage();
                        }
                    }
                    //运行中 并且 没到止损线的计划 更新当前收益
                    else if($type=='stoploss'){
                        //修改计划信息
                        $_plan = array(
                                'curr_ror'=>$curr_ror,
                                'max_profit'=>$curr_ror > $plan_info['max_profit'] ? $curr_ror : $plan_info['max_profit'],
                                'min_profit'=>$curr_ror < $plan_info['min_profit'] ? $curr_ror : $plan_info['min_profit'],
                                'market_value'=>$plan_mark_value,
                                'status'=>Plan::PLAN_STATUS_ACTIVE,
                                'is_succeeding'=> ($plan_info['target_ror']>0 && $curr_ror/$plan_info['target_ror']>0.8) ? 1 : 0,
                                'u_time'=>date("Y-m-d H:i:s")
                        );
                        $plan_counter = Plan::model()->updatePlanInfo($plan_info['pln_id'],'','',$_plan);
                        if($plan_counter <= 0){
                            //echo date("Y-m-d")."--更新计划收益错误！--".json_encode($plan_info,JSON_UNESCAPED_UNICODE);
                            $log_info[] = $plan_info['pln_id'].":更新计划收益错误！";
                        }
                    }
                    
                }//end if.


            }//end foreach.
            
            if(!empty($log_info)) {
            	Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, json_encode($log_info));
            }
        }//end if.
        return true;
    }


}
