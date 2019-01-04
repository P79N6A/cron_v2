<?php
/**
 * 计划服务类
 */

class PlanService {
	
	/**
	 * 获取交易手续费
	 *
	 * @param unknown_type $symbol
	 * @param unknown_type $price
	 * @param unknown_type $volume
	 * @param unknown_type $type 1买2卖
	 * @return unknown
	 */
	public static function geCostOld($symbol,$price,$volume,$type){

		$res = array('total'=>0,'stamp_tax'=>0,'commission'=>0);//总费用印花税佣金
		$cost = $price*$volume*0.0003;//券商手续费,最低五元
		$cost = $cost<5 ? 5 : $cost;
		$res['commission'] = $cost;
		if($type == 2){//卖出时千一印花税
			$cost += $price*$volume*0.001;
		}

		if(substr($symbol,0,2) == 'sh'){//上海股票1000股一块钱转让费
			$fee = $volume/1000;
			$fee = $fee > 1 ? $fee : 1;
			$cost = $cost + $fee;
		}
		
		$res['total'] =  round($cost,2);
		$res['stamp_tax'] =  $res['total']-$res['commission'];
		return $res;
	}

	public static function geCost($symbol,$price,$volume,$type){		
		$res = array('total'=>0,'stamp_tax'=>0,'commission'=>0);
		$cost = $price*$volume*0.0003;
		$cost = $cost >= 5 ? $cost : 5;
		$res['commission'] = $cost;				
		$res['total'] =  round($cost,2);		
		return $res;
	}
	/**
	 * 计划成交订单
	 *
	 * @param unknown_type $pln_id
	 * @param unknown_type $order_id
	 * @param unknown_type $symbol
	 * @param unknown_type $price
	 * @param unknown_type $volume
	 * @param unknown_type $type 1买 2卖
	 */
	 public static function dealPlanOrder($pln_id,$order_id,$symbol,$price,$volume,$type,$deal_time){
	 	
	 	//订单信息
	 	$order_info = PlanOrder::model()->getPlanOrder($pln_id,$order_id,'w');
		if(empty($order_info) || $order_info['is_handled'] == 1 || $order_info['status'] != 1 ){//没有这条记录或者已经被处理
			return 'order info error';
		}
	 	//计划信息
	 	$plan_info = Plan::model()->getPlanInfoByIds($pln_id);//计划信息
	 	
	 	if(isset($plan_info["$pln_id"])){
	 		$plan_info = $plan_info["$pln_id"];
	 	}
	 	if(!is_array($plan_info) || empty($plan_info) || $plan_info['status'] !=3){
	 		return 'plan_info info error';
	 	}
	 	//持有现金
	 	$cash_money = $plan_info['available_value'] + $plan_info['warrant_value'];
	 	//该股票持仓信息
	 	$asset_info = PlanAsset::model()->getUserSymbol($pln_id);
	 	//股票市值
	 	$symbol_money = 0;
	 	//当前股票的持有均价
	 	$hold_avg_cost = 0;
	 	$hold_amount = 0;
	 	$asset_has_symbol = false;//持仓表中是否已经有这个股票记录
	 	if(is_array($asset_info) && sizeof($asset_info) >0 ){
	 		foreach ($asset_info as $val){
	 			$symbol_money += $val['hold_avg_cost']*$val['amount'];
	 			if($val['symbol'] == $symbol){
	 				$hold_avg_cost = $val['hold_avg_cost'];
	 				$hold_amount = $val['amount'];
	 				$asset_has_symbol = true;
	 			}
	 		}
	 	}
		//手续费
		$cost = self::geCost($symbol,$price,$volume,$type);
		$now = date('Y-m-d H:i:s');
		//更新计划表
		$upd_plan = array('operate_time'=>$now);
		//更新持仓信息
		$upd_asset = array();
		//更新订单表
		$upd_order = array('deal_amount'=>$volume,'deal_time'=>$now,'status'=>2,'is_handled'=>1,'u_time'=>$now);
		//添加交易记录
		$upd_trans = array('pln_id'=>$pln_id,'symbol'=>$symbol,'ind_id'=>1,'type'=>$type,'status'=>1,
							'deal_price'=>$price,'deal_amount'=>$volume,'transaction_cost'=>$cost['total'],
							'reason'=>$order_info['reason'],'c_time'=>$now);
		//增加资金变动记录
		$statement_type = $type == 1?2:3;
		$upd_statement = array('pln_id'=>$pln_id,'symbol'=>$symbol,'statement_type'=>$statement_type,'type'=>$type,
							'deal_price'=>$price,'deal_amount'=>$volume,'commission'=>$cost['commission'],
							'transfer_fee'=>$cost['stamp_tax'],'deal_time'=>$deal_time,'c_time'=>$now);
		//操作前前仓位
		$upd_trans['wgt_before'] = round($symbol_money/($cash_money+$symbol_money),4);//交易前仓位
		if($type == 1){//买入操作，需要更新买入均价，
			
			//从冻结资金中减去花掉的钱，并把
			$costs = self::geCost($order_info['symbol'],$order_info['order_price'],$order_info['order_amount'],1);
			$warrant_value = $order_info['order_price']*$order_info['order_amount']+$costs['total'];
			//成交资金
			$real_value = $cost['total']+$price*$volume;
			//成交金额和交易后的剩余资金
			$upd_statement['change_fund'] = 0-$real_value;
			$upd_statement['rest_fund'] = $plan_info['available_value']+$plan_info['warrant_value']-$real_value;
			$upd_plan['warrant_value'] = 0-$warrant_value;//冻结资金全部解冻
			//如果冻结资金没花完,返还给可用资金
			if($real_value < $warrant_value){
				$upd_plan['available_value'] = $warrant_value-$real_value;
			}
			$upd_asset['hold_avg_cost'] = $upd_trans['hold_avg_cost'] = round(($price*$volume+$cost['total']+ ($hold_avg_cost*$hold_amount))/($volume+$hold_amount),6);
			$upd_asset['amount'] = $volume;
			$symbol_money = $symbol_money + $upd_trans['hold_avg_cost']*$volume;
			$cash_money = $cash_money-$price*$volume-$cost['total'];
		}elseif ($type == 2){
			$upd_plan['available_value'] = $upd_statement['change_fund'] =$price*$volume-$cost['total'];
			$upd_statement['rest_fund'] = $plan_info['available_value']+$plan_info['warrant_value']+$upd_statement['change_fund'];
			$upd_asset['amount'] = 0-$volume;
			$upd_asset['hold_avg_cost'] = $upd_trans['hold_avg_cost'] = $hold_avg_cost;
			$symbol_money = $symbol_money - $hold_avg_cost*$volume;
			$cash_money = $cash_money+$price*$volume-$cost['total'];
			$upd_trans['profit'] = ($price-$hold_avg_cost)*$volume-$cost['total'];//卖出盈利
		}
		$upd_plan['weight'] = $upd_trans['wgt_after'] = round($symbol_money/($cash_money+$symbol_money),4);//交易后仓位
		
		//更新持仓表
		if($asset_has_symbol){
			PlanAsset::model()->updateAsset($pln_id,$symbol,$upd_asset['hold_avg_cost'],$upd_asset['amount'],0);
		}else{
			$add_array = array('pln_id'=>$pln_id,'symbol'=>$symbol,'amount'=>$upd_asset['amount'],'hold_avg_cost'=>$upd_asset['hold_avg_cost'],
								'buy_avg_cost'=>$upd_asset['hold_avg_cost'],'buy_total_amount'=>$upd_asset['amount'],'c_time'=>$now);
			PlanAsset::model()->addAsset($add_array);
		}
		//更新计划表
		Plan::model()->updPlanInfo($pln_id,$upd_plan);
		//更新订单表
		PlanOrder::model()->updateOrder($order_id,$upd_order);
		//插入交易记录
		$tran_id = PlanOrder::model()->addPlanTrans($upd_trans);
		if($tran_id > 0 ){//队列写入消息
			$msg_data['type'] = 'planTransaction';
			$msg_data['tran_id'] = $tran_id;
			$redis_key='lcs_fast_message_queue';
            Yii::app()->redis_w->rPush($redis_key,json_encode($msg_data,JSON_UNESCAPED_UNICODE));
            if(($type == 2 && $upd_trans['profit']>0)){
                //lixiang29 将交易动态写入lcs_planner_active中
                Common::model()->saveAction($plan_info['p_uid'],4,$tran_id);
            }elseif ($type == 1){
				//chaoyi add 将买入的动态写入lcs_planner_active中
				Common::model()->saveAction($plan_info['p_uid'],5,$tran_id);
			}
        }
		//插入一条资金日志记录
		PlanOrder::model()->addPlanStatement($upd_statement);
		return 'ok';
	 	
	 }
	 
	  /**
	   * 撤单
	   *
	   * @param unknown_type $pln_id
	   * @param unknown_type $order_id
	   */
	  public static function cancelOrder($pln_id,$order_id){
	  	//订单信息
	 	$order_info = PlanOrder::model()->getPlanOrder($pln_id,$order_id,'w');
		if(empty($order_info) || $order_info['is_handled'] == 1 || $order_info['status'] != 1 ){//没有这条记录或者已经被处理
			return false;
		}
	 	//计划信息
	 	$plan_info = Plan::model()->getPlanInfoByIds($pln_id);//计划信息
	 	
	 	if(isset($plan_info["$pln_id"])){
	 		$plan_info = $plan_info["$pln_id"];
	 	}
	 	if(!is_array($plan_info) || empty($plan_info) || $plan_info['status'] !=3){
	 		return false;
	 	}
	 	
	 	$cost = self::geCost($order_info['symbol'],$order_info['order_price'],$order_info['order_amount'],$order_info['type']);
		$now = date('Y-m-d H:i:s');
		//更新计划表 买单把冻结资金还回去
		$upd_plan = array('operate_time'=>$now);
		//更新订单表
		$upd_order = array('status'=>3,'is_handled'=>1,'u_time'=>$now);
		$upd_order_cancel = array('is_handled'=>1,'u_time'=>$now);
		//更新订单表
		PlanOrder::model()->updateOrder($order_id,$upd_order);
		//更新撤单的表
		PlanOrder::model()->updateOrderCancel($order_id,$upd_order_cancel);
		$res = '';
		if($order_info['type'] == 1){//买单
			$upd_plan['available_value'] = $order_info['order_price']*$order_info['order_amount']+$cost['total'];
			$upd_plan['warrant_value'] = 0-$upd_plan['available_value'];		
			//更新计划表
			$res = Plan::model()->updPlanInfo($order_info['pln_id'],$upd_plan);		
		}elseif ($order_info['type'] == 2){//卖单 卖单恢复可卖数
			$res = PlanAsset::model()->updateAsset($order_info['pln_id'],$order_info['symbol'],0,0,$order_info['order_amount']);
		}
		return 'ok'.$res;
		
	 	
	  }
	  
	  public static function getPlanAssetMarkValue($pln_id){
	  	
	  	$pln_id = intval($pln_id);
        $return = array(
            'mark_value'=>0, //市值
            'assets'=>array() //持仓信息
        );

        //上证指数（确保上证指数的更新时间是30秒之内，否则退出）
        $sz = $_sz = Yii::app()->curl->get("http://hq.sinajs.cn/format=text&rm=".time()."&list=sh000001");
        try{
            $sz = explode('=',$sz);
            $sz[1] = explode(',',$sz[1]);
            $sz_time = $sz[1][30]." ".$sz[1][31];
            if(abs(strtotime($sz_time)-time()) > 30){
                echo date("Y-m-d H:i:s")."(".mb_convert_encoding($_sz,"UTF-8","GBK").")(上证指数)行情串接口过期！\n";
                $return['mark_value'] = -1;
                return $return;
            }
        }catch (Exception $e){
            echo date("Y-m-d H:i:s")."(上证指数)行情串接口调用失败！\n";
            $return['mark_value'] = -1;
            return $return;
        }

        //获取当前持仓信息
        $assets = PlanAsset::model()->getUserSymbol($pln_id,1);

        if(!empty($assets)){
            $symbols = array();
            foreach ($assets as $val){
            	$symbols[] = $val['symbol'];
            }


            //通过股票代码获取最新信息
            $stocks = Yii::app()->curl->get("http://hq.sinajs.cn/format=text&rm=".time()."&list=".strtolower(implode(",",$symbols)));
            if(!empty($stocks)){
                $stocks = array_filter(explode("\n",$stocks));
                /*
                $stocks 格式：
                Array
                (
                [0] => sz000404=华意压缩,6.59,6.60,6.58,6.63,6.55,6.58,6.59,4052703,26682842
                        .52,11899,6.58,105200,6.57,300600,6.56,106300,6.55,92500,6.54,185021,6.59,266009
                        ,6.60,145550,6.61,148100,6.62,197980,6.63,2014-11-20,14:43:18,00
                )*/
                //获得最新价
                $new_value = array();
                foreach($stocks as $key=>$val){
                    $val = explode('=',$val);
                    if(isset($val[1]) && !empty($val[1])){
                        $val[1] = explode(',',$val[1]);
                        if(count($val[1])>=33){//判断行情串长度
                            $new_price = $val[1][3]>0 ? $val[1][3] : $val[1][2]; //最新价

                            if($new_price<=0){//停牌股票并且最新价为0时，读日K表
                                $log_msg = date("Y-m-d H:i:s")."(".mb_convert_encoding($val[0],"UTF-8","GBK").")（".$pln_id."）停牌股票最新价为0，从日K表中读取\n";
                                $_price = QuotesDB::model()->getDailyK($val[0],date("Y-m-d"));
                                if(isset($_price[$val[0]]) && $_price[$val[0]]>0){
                                    $new_price = $_price[$val[0]];
                                }else{
                                    $log_msg = date("Y-m-d H:i:s")."（".$pln_id."）停牌股票最新价为0，从日K表中读取最新价失败！\n";
                                    $return['mark_value'] = -1;
                                    return $return;
                                }
                            }

                            $new_value[$val[0]]['stock_name'] = $val[1][0];//$new_value['sz000404']['stock_name']=华意压缩
                            $new_value[$val[0]]['price'] = $new_price;//$new_value['sz000404']['price']=6.59
                            $new_value[$val[0]]['deal_time'] = $val[1][30]." ".$val[1][31];//$new_value['sz000404']['deal_time']=2014-11-11 12:12:12
                            $new_value[$val[0]]['is_sell'] = ($val[1][32]=='00' && $new_price>sprintf("%.2f",$val[1][2]*0.9)) ? 1 : 0;// 是否可卖 (满足 非停牌 并且 非跌停)

                        }else{
                            $log_msg = date("Y-m-d H:i:s")."(".mb_convert_encoding($stocks[$key],"UTF-8","GBK").")（".$pln_id."）行情串数据长度错误！\n";
                            $return['mark_value'] = -1;
                            return $return;
                        }
                    }
                    else{ //获取不到行情串（从日K表中取最近的价格）
                        $log_msg = date("Y-m-d H:i:s")."(".mb_convert_encoding($val[0],"UTF-8","GBK").")（".$pln_id."）获取行情串失败，从日K表中读取最新价\n";
                        $_price = QuotesDB::model()->getDailyK($val[0],date("Y-m-d"));
                        if(isset($_price[$val[0]])){
                            $new_value[$val[0]]['stock_name'] = $val[0];
                            $new_value[$val[0]]['price'] = $_price[$val[0]];
                            $new_value[$val[0]]['deal_time'] = date("Y-m-d H:i:s");
                            $new_value[$val[0]]['is_sell'] = 0;
                        }else{
                            $log_msg = date("Y-m-d H:i:s")."（".$pln_id."）获取行情串失败，从数据库中读取最价失败！\n";
                            $return['mark_value'] = -1;
                            return $return;
                        }
                    }

                }

                //计算持仓市值
                foreach($assets as $key=>$asset_info){
                    if(isset($new_value[$asset_info['symbol']])){
                        $return['mark_value'] += $asset_info['amount']*$new_value[$asset_info['symbol']]['price'];

                        $assets[$key]['stock_name'] = $new_value[$asset_info['symbol']]['stock_name'];
                        $assets[$key]['new_price'] = $new_value[$asset_info['symbol']]['price'];
                        $assets[$key]['deal_time'] = $new_value[$asset_info['symbol']]['deal_time'];
                        $assets[$key]['is_sell'] = $new_value[$asset_info['symbol']]['is_sell'];
                    }else{
                        $log_msg = date("Y-m-d H:i:s")."（".$pln_id."）系统错误！\n";
                        $return['mark_value'] = -1;
                        return $return;
                    }
                }

                $return['assets'] = $assets;

            }else{//请求接口失败的操作
                $log_msg = date("Y-m-d H:i:s")."（".$pln_id."）行情串接口调用失败！\n";
                $return['mark_value'] = -1;
            }
        }

        return $return;
	  }
	  
      
      public static function tmpdealPlanOrder($pln_id,$order_id,$symbol,$price,$volume,$type,$deal_time){
	 	
	 	//订单信息
//	 	$order_info = PlanOrder::model()->getPlanOrder($pln_id,$order_id,'w');
//		if(empty($order_info) || $order_info['is_handled'] == 1 || $order_info['status'] != 1 ){//没有这条记录或者已经被处理
//			return 'order info error';
//		}
	 	//计划信息
	 	$plan_info = Plan::model()->getPlanInfoByIds($pln_id,null,'w');//计划信息
	 	
	 	if(isset($plan_info["$pln_id"])){
	 		$plan_info = $plan_info["$pln_id"];
	 	}
//	 	if(!is_array($plan_info) || empty($plan_info) || $plan_info['status'] !=3){
//	 		return 'plan_info info error';
//	 	}
	 	//持有现金
	 	$cash_money = $plan_info['available_value'] + $plan_info['warrant_value'];
	 	//该股票持仓信息
	 	$asset_info = PlanAsset::model()->getUserSymbol($pln_id);
	 	//股票市值
	 	$symbol_money = 0;
	 	//当前股票的持有均价
	 	$hold_avg_cost = 0;
	 	$hold_amount = 0;
	 	$asset_has_symbol = false;//持仓表中是否已经有这个股票记录
	 	if(is_array($asset_info) && sizeof($asset_info) >0 ){
	 		foreach ($asset_info as $val){
	 			$symbol_money += $val['hold_avg_cost']*$val['amount'];
	 			if($val['symbol'] == $symbol){
	 				$hold_avg_cost = $val['hold_avg_cost'];
	 				$hold_amount = $val['amount'];
	 				$asset_has_symbol = true;
	 			}
	 		}
	 	}
		//手续费
		$cost = self::geCost($symbol,$price,$volume,$type);
		$now = date('Y-m-d H:i:s');
		//更新计划表
		$upd_plan = array('operate_time'=>$now);
		//更新持仓信息
		$upd_asset = array();
		//更新订单表
		$upd_order = array('deal_amount'=>$volume,'deal_time'=>$now,'status'=>2,'is_handled'=>1,'u_time'=>$now);
		//添加交易记录
		$upd_trans = array('pln_id'=>$pln_id,'symbol'=>$symbol,'ind_id'=>1,'type'=>$type,'status'=>1,
							'deal_price'=>$price,'deal_amount'=>$volume,'transaction_cost'=>$cost['total'],
							'reason'=>'','c_time'=>$now);
		//增加资金变动记录
		$statement_type = $type == 1?2:3;
		$upd_statement = array('pln_id'=>$pln_id,'symbol'=>$symbol,'statement_type'=>$statement_type,'type'=>$type,
							'deal_price'=>$price,'deal_amount'=>$volume,'commission'=>$cost['commission'],
							'transfer_fee'=>$cost['stamp_tax'],'deal_time'=>$deal_time,'c_time'=>$now);
		//操作前前仓位
		$upd_trans['wgt_before'] = round($symbol_money/($cash_money+$symbol_money),4);//交易前仓位
		if($type == 1){//买入操作，需要更新买入均价，
			
			//从冻结资金中减去花掉的钱，并把
			$costs = self::geCost($symbol,$price,$volume,1);
			$warrant_value = $price*$volume+$costs['total'];
			//成交资金
			$real_value = $cost['total']+$price*$volume;
			//成交金额和交易后的剩余资金
			$upd_statement['change_fund'] = 0-$real_value;
			$upd_statement['rest_fund'] = $plan_info['available_value']+$plan_info['warrant_value']-$real_value;
			$upd_plan['warrant_value'] = 0-$warrant_value;//冻结资金全部解冻
			//如果冻结资金没花完,返还给可用资金
			if($real_value < $warrant_value){
				$upd_plan['available_value'] = $warrant_value-$real_value;
			}
			$upd_asset['hold_avg_cost'] = $upd_trans['hold_avg_cost'] = round(($price*$volume+$cost['total']+ ($hold_avg_cost*$hold_amount))/($volume+$hold_amount),6);
			$upd_asset['amount'] = $volume;
			$symbol_money = $symbol_money + $upd_trans['hold_avg_cost']*$volume;
			$cash_money = $cash_money-$price*$volume-$cost['total'];
		}elseif ($type == 2){
			$upd_plan['available_value'] = $upd_statement['change_fund'] =$price*$volume-$cost['total'];
			$upd_statement['rest_fund'] = $plan_info['available_value']+$plan_info['warrant_value']+$upd_statement['change_fund'];
			$upd_asset['amount'] = 0-$volume;
			$upd_asset['hold_avg_cost'] = $upd_trans['hold_avg_cost'] = $hold_avg_cost;
			$symbol_money = $symbol_money - $hold_avg_cost*$volume;
			$cash_money = $cash_money+$price*$volume-$cost['total'];
			$upd_trans['profit'] = ($price-$hold_avg_cost)*$volume-$cost['total'];//卖出盈利
		}
		$upd_plan['weight'] = $upd_trans['wgt_after'] = round($symbol_money/($cash_money+$symbol_money),4);//交易后仓位
		
		//更新持仓表
		if($asset_has_symbol){
			PlanAsset::model()->updateAsset($pln_id,$symbol,$upd_asset['hold_avg_cost'],$upd_asset['amount'],0);
		}else{
			$add_array = array('pln_id'=>$pln_id,'symbol'=>$symbol,'amount'=>$upd_asset['amount'],'hold_avg_cost'=>$upd_asset['hold_avg_cost'],
								'buy_avg_cost'=>$upd_asset['hold_avg_cost'],'buy_total_amount'=>$upd_asset['amount'],'c_time'=>$now);
            print_r($add_array);
			PlanAsset::model()->addAsset($add_array);
		}
		//更新计划表
		Plan::model()->updPlanInfo($pln_id,$upd_plan);
		//更新订单表
//		PlanOrder::model()->updateOrder($order_id,$upd_order);
//		//插入交易记录
//		$tran_id = PlanOrder::model()->addPlanTrans($upd_trans);
//		if($tran_id > 0 ){//队列写入消息
//			$msg_data['type'] = 'planTransaction';
//			$msg_data['tran_id'] = $tran_id;
//			$redis_key='lcs_fast_message_queue';
//			Yii::app()->redis_w->rPush($redis_key,json_encode($msg_data,JSON_UNESCAPED_UNICODE));
//		}
//		//插入一条资金日志记录
//		PlanOrder::model()->addPlanStatement($upd_statement);
		return 'ok';
	 	
	 }

    /**
     * 获取指定时间段内的周收益
     * @param $pln_id
     * @param int $monday_date 周一时间戳
     * @param int $friday_date 周五
     * @return null
     */
	 public static function getWeekProfit($pln_id, $monday_date, $friday_date) {
         //查询周一的累计收益
         $pln_id_arr = (array) $pln_id;
         $mon_stat = Plan::model()->getPlanHistoryProfit($pln_id_arr, $monday_date);
         $mon_profit = $mon_stat[$pln_id];

         //查询周五的累计收益
         $fri_stat = Plan::model()->getPlanHistoryProfit($pln_id_arr, $friday_date);
         $fri_profit = $fri_stat[$pln_id];

         if ($mon_profit != 0 and $fri_profit != 0) {
             $rate = $fri_profit - $mon_profit;
             return $rate;
         } else {
             return null;
         }
     }

    /**
     * 增加订阅记录，并且记录过期时间
     * @param $uid
     * @param $pln_id
     * @param $expire_time
     * @param int $status
     * @return false|string|void
     * @throws Exception
     */
    public static function addPlanSub($uid, $pln_id, $expire_time, $status = 2)
    {
        //验证计划的状态
        $p_info = Plan::model()->getPlanInfoByFields($pln_id, 'pln_id,start_date, status');
        if (empty($p_info) || $p_info['status'] < 2) {
            throw new Exception("计划不存在或状态错误");
        }


        //计算开始时间和过期时间
        //如果计划还没有开始运行，使用运行时间，否则使用当前时间
        //过期时间未订阅时间加速有效时间
        $start_time = date("Y-m-d H:i:s");
        $start_time = $p_info['start_date'] > $start_time ? $p_info['start_date'] : $start_time;
        $expire_time = date("Y-m-d H:i:s", strtotime($expire_time));

        $data = array(
            'uid' => $uid,
            'pln_id' => $pln_id,
            'sub_fee' => 0,
            'sub_start_date' => $start_time,
            'expire_time' => $expire_time,
            'c_time' => date("Y-m-d H:i:s"),
            'u_time' => date("Y-m-d H:i:s"),
            'status' => $status
        );

        //更新订阅数
        Plan::model()->updateNumber($pln_id);
        //我的说说加入在大家说列表里
        //PlanComment::model()->updateCommentDisplay($pln_id,$uid);
        //取消观察
        if (Collect::model()->delUserCollect($pln_id,$uid) > 0) {
            //观察数-1
            Plan::model()->updateNumber($pln_id,'reader_count','');
        }

        $add_res = PlanSubscription::model()->savePlanSub($data);

        //删除用户订阅的计划id缓存
        CacheUtils::delUserSubscriptionIds($uid);

        return $add_res;
    }

}
