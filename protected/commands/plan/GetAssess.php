<?php

/**
 * 计划的交易水平透视数据
 */
class  GetAssess{
	
	const CRON_NO = 5004; //任务代码
	
	public function __construct() {
	}
	public function GetAssess(){
		
		try{
			$now = date('Y-m-d');
			$sql = "select * from lcs_plan_info where status=3 or (real_end_time>'$now 09:00:00' and real_end_time<'$now 16:00:00')";
			$pln_list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
			foreach ($pln_list as $val){
				$this->assess($val);
				$this->hold_days($val);
			}
			
		}catch (Exception $e){
			throw LcsException::errorHandlerOfException($e);
		}
	}
	
	/**
	 * 更新持仓天数和仓位累计，不判断是否是交易日
	 *
	 * @param unknown_type $pln_info
	 */
	public function hold_days($pln_info){

		$pln_id = $pln_info['pln_id'];
		$data = array();
		$pln_assess = PlanAssess::model()->getAssessInfo($pln_id);
		if($pln_info['weight'] >0){
			$data['hold_days'] = $pln_assess['hold_days']+1;
			$data['hold_total_weight'] = $pln_assess['hold_total_weight']+$pln_info['weight'];
			if($pln_info['weight'] > $pln_assess['max_weight']){
				$data['max_weight'] = $pln_info['weight'];
			}
			if($pln_info['weight'] < $pln_assess['min_weight']){
				$data['min_weight'] = $pln_info['weight'];
			}
		}
		//更新
		if(!empty($data)){
			$data['u_time'] = date('Y-m-d H:i:s');
			PlanAssess::model()->updateAssess($data,$pln_id);
		}
	}

	/**
	 * 计算其他的数据
	 *
	 * @param unknown_type $pln_info
	 */
	public function assess($pln_info){

		if(!Calendar::model()->isTradeDay()){//非交易日不计算
			return ;
		}
		$pln_id = $pln_info['pln_id'];

		$pln_assess = PlanAssess::model()->getAssessInfo($pln_id);
		$trans_list = PlanTransactions::model()->getTransList($pln_id);
		//获取所有个股
		$asset_list = PlanAsset::model()->getPlanAsset($pln_id,0);
		$asset_symbol = array();
		$asset_data = array();

		if(!empty($asset_list)){
			foreach ($asset_list as $asset_val){
				$asset_symbol[] = $asset_val['symbol'];
				$asset_data["$asset_val[symbol]"] = array('buy_total_amount'=>$asset_val['buy_total_amount'],'amount'=>$asset_val['amount'],'total_value'=>0,'buy_avg_cost'=>$asset_val['buy_avg_cost'],'buy_money'=>0);
			}
		}


		$data = array();

		//获取今天的盈利笔数和亏损笔数
		$profit_num = $loss_num = 0;
		//买入交易总金额
		$total_trans_value = 0;
		//买入笔数
		$buy_num = 0;
		//卖出总盈利
		$sell_total_profit = 0;
		//手续费总额
		$transaction_cost = 0;
		//单笔最大盈利和亏损
		$max_loss = 0;
		$max_profit = 0;
		//获取最大连续盈利和最大连续亏损
		$max_profit_num = 0;
		$max_loss_num = 0;
		$profit_flag = $loss_flag = true;
		$i = $j = 0;
		//获取笔均盈利和笔均亏损 笔均盈利=（平仓盈利总金额/盈利笔数）/初始资金%
		$total_profit_num = $total_profit = $total_loss_num = $total_loss = 0;
		foreach ($trans_list as $trans){
			if(!in_array($trans['symbol'],$asset_symbol)){
				continue;
			}
			$symbol = $trans['symbol'];
			if($trans['type'] == 1){
				$asset_data["$symbol"]['buy_money'] += $trans['deal_price']*$trans['deal_amount'];
               	$asset_data["$symbol"]['buy_money'] += $trans['transaction_cost'];
               	
				$total_trans_value +=$trans['deal_price']*$trans['deal_amount'];
				$buy_num++;
			}elseif($trans['type'] == 2){
				if($trans['profit'] >= 0 ){
					$profit_num++;
					$max_profit = $trans['profit'] > $max_profit ? $trans['profit']:$max_profit;
				}elseif($trans['profit'] < 0){
					$loss_num++;
					$max_loss = $trans['profit'] < $max_loss ? $trans['profit']:$max_loss;
				}
				$sell_total_profit +=$trans['profit'];
			}
			$transaction_cost +=$trans['transaction_cost'];
			if($trans['type'] == 2){
				//计算最大连续盈利和亏损
				!$loss_flag ? $j = 0:'';
				!$profit_flag ? $i = 0:'';
				if($trans['profit'] >= 0 ){
					$i += $trans['profit'];
					$profit_flag = true;
					$loss_flag = false;
					$total_profit_num++;
					$total_profit += $trans['profit'];
				}else{
					$j +=$trans['profit'];
					$loss_flag = true;
					$profit_flag = false;
					$total_loss_num++;
					$total_loss += $trans['profit'];
				}

				//计算个股收益和收益贡献
				$asset_data["$symbol"]['total_value'] += ($trans['deal_price']*$trans['deal_amount']-$trans['transaction_cost']);
			}
			$max_profit_num = $i>$max_profit_num?$i:$max_profit_num;
			$max_loss_num = $j<$max_loss_num?$j:$max_loss_num;
		}
		$data['profit_num'] = $profit_num;
		$data['loss_num'] = $loss_num;
		$data['total_trans_value'] = $total_trans_value;
		$data['buy_num'] = $buy_num;
		$data['sell_total_profit'] = $sell_total_profit;
		$data['total_cost'] = $transaction_cost;

		$data['avg_profit'] = $total_profit_num > 0 ? round($total_profit/$total_profit_num,4):0;
		$data['avg_loss'] = $total_loss_num > 0 ? round($total_loss/$total_loss_num,4):0;

		$data['max_profit'] = $max_profit;
		$data['max_loss'] = $max_loss;

		//最大连续盈利和亏损
		$data['max_profit_num'] = $max_profit_num;
		$data['max_loss_num'] = $max_loss_num;

		//更新
		if(!empty($data)){
			$data['u_time'] = date('Y-m-d H:i:s');
			PlanAssess::model()->updateAssess($data,$pln_id);
		}
		//更新个股收益和收益贡献
		if(!empty($asset_data)){
			foreach ($asset_data as $ass_key=>$asset){
				if($asset['buy_total_amount'] > 0 && $asset['amount'] == 0 && $asset['buy_money']!=0){
					$ass_profit = ($asset['total_value']-$asset['buy_money'])/$asset['buy_money'];
                    $ass_profit_weight = ($asset['total_value']-$asset['buy_money'])/$pln_info['init_value'];
					PlanAsset::model()->updateAssetArray(array('profit'=>$ass_profit,'profit_weight'=>$ass_profit_weight,'u_time'=>date('Y-m-d H:i:s')),$pln_id,$ass_key);
				}
			}
		}

	}
}
