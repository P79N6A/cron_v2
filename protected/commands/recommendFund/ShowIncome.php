<?php

class ShowIncome{


	public static $fund_type_income = array(
			1 => array('show_income_column' => 'quarter_incratio'	, 'show_income_column_2' => 'year_incratio'),	
			2 => array('show_income_column' => 'incomeratio'		, 'show_income_column_2' => 'year_incratio'),	
			);
	public function income(){
		//获取基金的类型，更新 字段： show_income_column ， show_income_column_2 
		$totalnum = RecommendFund::model()->getRecommendTotalNum();
		if($totalnum < 0) {
			exit('空表');
		}
		$max_num = 100;
		$i = 0; 
		while($i< $totalnum) {

			$list = RecommendFund::model()->getRecommendList($i);
			if(empty($list)) exit; 
			$id_fundcode = array();
			foreach($list as $val){
				
				if(empty($val['show_income_column']) || empty($val['show_income_column_2'])) {
					$id_fundcode[$val['id']] = $val ;
					$fundcode_arr[] = $val['fund_code'];
				}
			}

			//加xincai_r数据库 配置

			$fund_info = FundInfo::model()->getBatchInfobyFundCode($fundcode_arr);
			$up_data = array();
			foreach($id_fundcode as $key => $val){
				$fund_code = $val['fund_code'];	
				echo $fund_code ."\n";
				$f_type =  1 ;  //1 普通 2 货币 
				if(isset($fund_info[$fund_code])) {
					if($fund_info[$fund_code]['fund_type'] == 2){
						
						$f_type =  2 ;  //1 普通 2 货币 
					}
				}
				
				$fund_income_arr = self::$fund_type_income[$f_type];
				$up_data['show_income_column'] = $fund_income_arr['show_income_column'];
				$up_data['show_income_column_2'] = $fund_income_arr['show_income_column_2'];

				RecommendFund::model()->updateRecommend($key, $up_data);
			}

			$i += $max_num;


		}
	}

} 
