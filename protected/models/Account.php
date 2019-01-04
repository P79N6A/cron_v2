<?php

/*
 * Function: 牛币对账数据层 
 * Desc: 用户 balance , 订单记录 
 * Author: meixin@staff.sina.com.cn
 * Date: 2015/08/28
 */

class Account extends CActiveRecord {

    private $startdate;
    private $enddate;

    public static $toMailer = array(
        'lixiang23@staff.sina.com.cn',
        'guobing1@staff.sina.com.cn',
        'zhihao6@staff.sina.com.cn',
        'hailin3@staff.sina.com.cn',
        'danxian@staff.sina.com.cn',
    );

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }


    //用户牛币余额表
    public function tableName_user() {
        return TABLE_PREFIX . 'user_account';
    }

    //订单表
    public function tableName_order() {
        return TABLE_PREFIX . 'orders';
    }


    public function getUserNum() {
        $sql = "SELECT count(1) as num FROM " . $this->tableName_user();
        $userNum = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        return $userNum;
    }

    //用户昨日剩余牛币
    public function getbeforeAccount() {
        $where = " where c_time >'" . $this->startdate . "'";
        $sql = "SELECT uid, account, balance, c_time, u_time FROM " . $this->tableName_accountCheck() . $where;
        $useraccount = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $userInfo_before = array();
        if (empty($useraccount))
            return $userInfo_before;
        foreach ($useraccount as $v) {
            $userInfo_before[$v['uid']] = $v;
        }
        return $userInfo_before;
    }

    //用户当前剩余牛币
    public function getUserBalance($start, $offset) {
        $sql = "SELECT uid, balance, c_time, u_time FROM " . $this->tableName_user() . " limit ${start}, ${offset} ";
        $useraccount = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $userInfo = array();
        if (empty($useraccount))
            return $userInfo;
        foreach ($useraccount as $v) {
            $userInfo[$v['uid']] = $v;
        }
        return $userInfo;
    }

    //牛币充值金额  ---前一天的交易 
    public function getRechargebyUid($uid) {
        $where = " WHERE type = 41 and status =2 and uid=${uid} ";
        $where .= " and u_time between '" . $this->startdate . "' and '" . $this->enddate . "'";
        $sql = "SELECT sum(price) as recharge FROM " . $this->tableName_order() . $where;
        $res = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        $recharge = empty($res[0]) ? '0.00' : $res[0];
        return $recharge;
    }

    //牛币消费金额
    public function getCostbyUid($uid) {
        $where = " WHERE  status = 2  and pay_type=3 and uid=${uid}";
        $where .= " and u_time between '" . $this->startdate . "' and '" . $this->enddate . "'";
        $sql = "SELECT sum(price) as cost FROM " . $this->tableName_order() . $where;
        $res = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        $cost = empty($res[0]) ? '0.00' : $res[0];
        return $cost;
    }

    //牛币退款金额
    public function getRefundbyUid($uid) {
        $where = " WHERE  status = 4  and pay_type=3 and uid=${uid}";
        $where .= " and u_time between '" . $this->startdate . "' and '" . $this->enddate . "'";
        $sql = "SELECT sum(price) as refund FROM " . $this->tableName_order() . $where;
        $res = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        $refund = empty($res[0]) ? '0.00' : $res[0];
        return $refund;
    }
  
    /**
     * 牛币充值  
     * @param type $pay_type  1 微博支付  4 ios 支付 5 ios 沙箱支付 8新浪支付宝 9 新浪微信支付
     * @param type $start_time
     * @param type $end_time
     * @return type
     */
	public function getSumRecharge($pay_type , $start_time='' , $end_time=''){
		$where = "";
		if(!empty($start_time)){
			$where = " and u_time > '${start_time}'";
		}
        if($pay_type == '1,2,8,9'){
            $column = 'price';
        }else{
            $column = 'amount';
        }		
        $pay_type = " and pay_type in(${pay_type})";
        $sql = "SELECT sum(${column}) FROM `lcs_orders` where type = 41 and status = 2 " .$pay_type. $where; 
        
        $recharge = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        return $recharge; 

	}

    /**
     * 牛币消费
     * @param type $pay_type  3 普通冲牛币消费   6 ios 冲牛币消费
     * @param type $start_time
     * @param type $end_time
     * @return type
     */
	public function getSumCost($pay_type , $start_time='' , $end_time=''){
		$where = "";
		if(!empty($start_time)){
			$where = " and u_time > '${start_time}'";

		} 
        $sql = "SELECT sum(price) FROM `lcs_orders` where status = 2 and pay_type in (${pay_type}) " . $where; 
        $cost = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        return $cost; 
		
	}

    /**
     * 牛币退款
     * @param type $pay_type   3 普通冲牛币消费   6 ios 冲牛币消费
     * @param type $start_time
     * @param type $end_time
     * @return type
     */
    public function getSumRefund($pay_type ,$start_time='' , $end_time=''){
		if(!empty($start_time)){
			$where = " and u_time > '${start_time}'";
		}
        $sql = "SELECT sum(price) FROM `lcs_orders` where status = 4 and pay_type in (${pay_type}) ". $where ;
        $refund = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        return $refund; 
    }

    /**
     * 用户剩余牛币
     * @param type $column   balance | balance_ios
     * @return type
     */
	public function getSumBalance($column = 'balance'){

		$sql = "SELECT sum(".$column.") FROM `lcs_user_account` ";
        $balance = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        return $balance; 
		
	}
    
    /**
     * 根据订单号获取订单信息
     * @param type $order_nos
     * @return type
     */
	public function getOrders($pay_nos){

        $pay_number= implode("','" , $pay_nos);
		$sql = "select id,order_no,type,relation_id,price,amount,uid,p_uid,status,refund_lock,c_time,u_time,pay_type,pay_number,pay_time from ". $this->tableName_order() ." where pay_number in( '".$pay_number ."') ";
		$res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		$order_infos = array();
		if(sizeof($res)>0){
			foreach($res as $val){
				$order_infos[$val['pay_number']] = $val;
			}
		}
		return $order_infos;
	}     


}
