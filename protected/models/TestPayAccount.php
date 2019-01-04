<?php

/*
 * Function: 支付宝流水导入测试库 
 * Desc:  
 * Author: meixin@staff.sina.com.cn
 * Date: 2015/11/13
 */
class TestPayAccount extends CActiveRecord {

    private $startdate;
    private $enddate;

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    //支付宝流水表
    public function tableName_user() {
        return TABLE_PREFIX . 'account_detail';
    }

	public function insertAccountDetail($insert_vals){
		if(empty($insert_vals)) {
			return false;	
		}
	
		$sql = "insert ignore into lcs_account_detail 
			(`c_time` , `alipay_trade_no`, `alipay_number` , `shop_order_no`,`trade_type`,`income`,`spend`,
			`account_balance` , `service_fee`,`pay_source`,`product`,`trade_account`,`trade_name`,`bank_order_no`,
			`product_name`,`remark`) values";
		$sql .= $insert_vals;
		#echo $sql."\n";
		#exit;
        $insert_num = Yii::app()->account_w->createCommand($sql)->execute();
		return $insert_num;
	} 
}


