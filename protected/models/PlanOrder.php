<?php

/**
 * 计划订单类
 */
class PlanOrder extends CActiveRecord {

	//撮合的地址
	public  $cuohe_host = '2.ss.sinajs.cn';
	public  $cuohe_host_bak = '1.ss.sinajs.cn';
	public  $cuohe_port = 5505;
	public  $cuohe_user = 'licaishi';
	public  $cuohe_passwd = 'qdds6ukapc';
	
	public static function model($className = __CLASS__) {
		
		$obj = parent::model($className);
		if(defined('ENV') && ENV == 'dev'){
            $obj->cuohe_host = '192.168.48.224';
            $obj->cuohe_host_bak = '192.168.48.224';
            $obj->cuohe_user = 'licaishi';
            $obj->cuohe_passwd = '123456';
		}
		return $obj;
	}

	/**
     * 订单表
     *
     * @return unknown
     */
	public function tableName() {
		return 'lcs_plan_order';
	}

	/**
     * 成交记录表
     * @return unknown
     */
	public function tableNameTrans() {
		return 'lcs_plan_transactions';
	}
	/**
     * 资金变更表
     *
     * @return unknown
     */
	public function tabelNameStatement(){
		return 'lcs_plan_statement';
	}

	/**
     * 更新订单信息
     *
     */
	public function updateOrder($order_id,$data){
		if(is_array($order_id) && !empty($order_id)){
			$where = "id in(".implode(',',$order_id).")";
		}else{
			$where = "id=$order_id";
		}
		$res = Yii::app()->lcs_w->createCommand()->update($this->tableName(),$data,$where);
		return $res;
	}

	public function updateOrderCancel($order_id,$data){
		$res = Yii::app()->lcs_w->createCommand()->update($this->tableName(),$data,"order_id=$order_id");
		return $res;
	}

	/**
     * 获取今天没有提交到撮合系统的所有订单
     *
     */
	public function getTodayNotSub(){
		$start_time = date('Y-m-d 00:00:01');
		$num = 10;
		$sql = "select id,pln_id,ind_id,symbol,type,order_id,order_price,deal_amount,order_amount,deal_time,is_handled,status,c_time from lcs_plan_order where c_time>'$start_time' and type in(1,2) and status=1 and is_sub=0 limit $num";
		$order_list = Yii::app()->lcs_w->createCommand($sql)->queryAll();
		return $order_list;
	}

	/**
     * 根据订单id获取订单信息
     *
     * @param unknown_type $order_id
     */
	public function getPlanOrder($pln_id,$order_id,$db_r='r'){
		$db_link = $db_r=='r'?Yii::app()->lcs_r:Yii::app()->lcs_w;
		$order_id = intval($order_id);
		$sql = "select id,pln_id,ind_id,symbol,type,order_id,order_price,deal_amount,order_amount,deal_time,is_handled,status,reason from lcs_plan_order where id=$order_id";
		$row = $db_link->createCommand($sql)->queryRow();
		return $row;
	}

	/**
     * 增加一条交易记录
     *
     * @param unknown_type $data
     */
	public function addPlanTrans($data){
		$id = false;
		if(is_array($data) && !empty($data)){
			if(Yii::app()->lcs_w->createCommand()->insert($this->tableNameTrans(),$data)){
				$id = Yii::app()->lcs_w->getLastInsertID($this->tableNameTrans());
			}
		}
		return  $id;
	}

	/**
     * 增加一条交易记录
     *
     * @param unknown_type $data
     */
	public function addPlanStatement($data){
		$res = false;
		if(is_array($data) && !empty($data)){
			$res = Yii::app()->lcs_w->createCommand()->insert($this->tabelNameStatement(),$data);
		}
		return  $res;
	}



}
