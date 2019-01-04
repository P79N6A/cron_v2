<?php 
/**
 * 基金信息表 
 * author: meixin@staff.sina.com.cn 
 * Date : 2016/04/18
 */

class FundInfo extends CActiveRecord {


	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'xc_fund_info';
	}

	public function getBatchInfobyFundCode($fundcode_arr){

		$fundcode_arr = (array)$fundcode_arr;

		$sql = "select id, fund_code, fund_name, fund_type from ". $this->tableName() . " where fund_code in (" .implode(',' , $fundcode_arr) . ")"; 	

		$result = Yii::app()->xincai_r->createCommand($sql)->queryAll();
		$fundinfo = array();
		if(empty($result)){
			return $fundinfo ; 
		}

		foreach($result as $v){
			$fundinfo[$v['fund_code']] = $v;
		}

		return $fundinfo;

	}


}
