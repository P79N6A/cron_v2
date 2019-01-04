<?php
/*
 * @Purpose 理财师数据库的用户数据统计
 * @Author songyao@
 */ 

class StatUserMonth extends CActiveRecord
{
	private $_day_begin;
	private $_day_end;
	private $_date_end;

	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}


	public function __construct()
	{
		$this->_day_begin = "2015-08-01 00:00:00";
		$this->_day_end = "2015-08-31 23:59:59";
		$this->_date_end= "2015-08-31";
	}


	public function countDistinctUser($type)
	{
		$pay_where = " and pay_time between :c_time_b and :c_time_e";
		$sql = "select count(distinct(uid)) as num from lcs_orders where type=".$type." and status>=2 ".$pay_where;

		$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
		$cmd->bindParam(':c_time_b', $this->_day_begin, PDO::PARAM_STR);
		$cmd->bindParam(':c_time_e', $this->_day_end, PDO::PARAM_STR);
		$ret = $cmd->queryRow();

		return isset($ret['num']) ? $ret['num'] : 0;
	}
	
	public function countDistinctUserCover($type)
	{
		switch ($type)
		{
		//观点包和观点付费重合独立用户数
		case "view": 
			$sql = "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where type=32 and status>=2  and pay_time between :c_time_b and :c_time_e) and type=31 and status>=2 and pay_time between :c_time_b and :c_time_e";
			break;
		//提问和解锁付费重合的独立用户数：
		case "ask": 
			$sql = "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where type=12 and status>=2 and pay_time between :c_time_b and :c_time_e) and type=11 and status>=2 and pay_time between :c_time_b and :c_time_e";
			break;
		//观点+问答付费重合独立用户数：
		case "viewask":
			$sql = "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where (type=12 or type=11) and status>=2 and pay_time between :c_time_b and :c_time_e) and (type=31 or type=32) and status>=2 and pay_time between :c_time_b and :c_time_e";
			break;
		//观点+计划付费重合独立用户数：
		case "viewplan":
			$sql = "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where type=21 and status>=2 and pay_time between :c_time_b and :c_time_e) and (type=31 or type=32) and status>=2 and pay_time between :c_time_b and :c_time_e";
			break;
		//计划+问答付费重合独立用户数：
		case "planask": 
			$sql = "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where (type=12 or type=11) and status>=2 and pay_time between :c_time_b and :c_time_e) and type=21 and status>=2 and pay_time between :c_time_b and :c_time_e";
			break;
		//计划+问答+观点付费重合独立用户数：
		case "viewplanask": 
			$sql = "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where uid in (select distinct(uid) from lcs_orders where (type=12 or type=11) and status>=2 and pay_time between :c_time_b and :c_time_e) and type=21 and status>=2 and pay_time between :c_time_b and :c_time_e) and (type=31 or type=32) and status>=2 and pay_time between :c_time_b and :c_time_e";
			break;
		}
		$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
		$cmd->bindParam(':c_time_b', $this->_day_begin, PDO::PARAM_STR);
		$cmd->bindParam(':c_time_e', $this->_day_end, PDO::PARAM_STR);
		$ret = $cmd->queryRow();
		return isset($ret['num']) ? $ret['num'] : 0;
	}


	public function countOtherUser($type)
	{
		switch($type)
		{
		case "packagesub": 
			$sql = "select count(distinct(uid)) as num from lcs_package_subscription where end_time >:c_time_e  and c_time<=:c_time_e";
			$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
			$cmd->bindParam(':c_time_e', $this->_day_end, PDO::PARAM_STR);
			break;
		case "packagesubview":
			$sql = "select count(distinct(uid)) as num from lcs_package_subscription where end_time >:c_time_e  and c_time<=:c_time_e and uid in (select distinct(uid) from lcs_orders where type=32 and status>=2 and pay_time between :c_time_b and :c_time_e); "; 
			$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
			$cmd->bindParam(':c_time_e', $this->_day_end, PDO::PARAM_STR);
			$cmd->bindParam(':c_time_b', $this->_day_begin, PDO::PARAM_STR);
			break;
		case "runplan": 
			$sql = "select count(distinct(uid)) as num from lcs_plan_subscription where pln_id in (select distinct(pln_id) from lcs_plan_info where (status=3 and start_date<=:c_time_d) or (real_end_time>=:c_time_d and start_date<=:c_time_d  and status>3))"; 
			$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
			$cmd->bindParam(':c_time_d', $this->_date_end, PDO::PARAM_STR);
			break;
		case "beforerunplan": 
			$sql = "select count(distinct(uid)) as num from lcs_plan_subscription where c_time<=:c_time_e and c_time!='0000-00-00' and pln_id in (select distinct(pln_id) from lcs_plan_info where  start_date>:c_time_d)"; 
			$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
			$cmd->bindParam(':c_time_e', $this->_day_end, PDO::PARAM_STR);
			$cmd->bindParam(':c_time_d', $this->_date_end, PDO::PARAM_STR);
			break;
		case "beforeandrun":
			$sql = "select count(distinct(uid)) as num from lcs_plan_subscription where  c_time<:c_time_e and c_time!='0000-00-00' and pln_id in (select distinct(pln_id) from lcs_plan_info where  start_date>:c_time_d) and uid in (select distinct(uid) from lcs_plan_subscription where pln_id in (select distinct(pln_id) from lcs_plan_info where (status=3 and start_date<=:c_time_d) or (real_end_time>=:c_time_d and start_date<=:c_time_d  and status>3)))";
			$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
			$cmd->bindParam(':c_time_e', $this->_day_end, PDO::PARAM_STR);
			$cmd->bindParam(':c_time_d', $this->_date_end, PDO::PARAM_STR);
			break; 
		}
		$ret = $cmd->queryRow();
		return isset($ret['num']) ? $ret['num'] : 0;		
	}
}

