<?php
/*
 * @Purpose 理财师数据库的用户数据统计
 * @Author songyao@
 */ 

class StatUser extends CActiveRecord
{
	private $_day_begin;
	private $_day_end;

	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}


	public function __construct()
	{
		$this->_day_end = date("Y-m-d 23:55:00");
		$this->_day_begin = date("Y-m-d 23:55:00", strtotime($this->_day_end) - 24*3600); 
	}


	public function countDistinctUser($type, $date='', $days=0)
	{
		if ('' != $date)
		{
			$this->_day_end = $date.' 23:55:00';
			$this->_day_begin = date("Y-m-d 23:55:00", strtotime($this->_day_end) - 24*3600); 
		}
		if (1==$days)
		{
			$pay_where = '' != $date ? " and pay_time between :c_time_b and :c_time_e" : "";
		}
		else
		{
			$pay_where = '' != $date ? " and pay_time <= :c_time_e" : "";
		}
		$sql = "select count(distinct(uid)) as num from lcs_orders where type=".$type." and status>=2 ".$pay_where;

		$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
		if (1==$days)
			$cmd->bindParam(':c_time_b', $this->_day_begin, PDO::PARAM_STR);
		$cmd->bindParam(':c_time_e', $this->_day_end, PDO::PARAM_STR);
		$ret = $cmd->queryRow();

		return isset($ret['num']) ? $ret['num'] : 0;
	}

	//added at 2016-01-05 统计所有累计付费用户数排重，每日
	public function countDistinctUserTotal($date='')
	{
		$sql = "select count(distinct(uid)) as num from lcs_orders where status>=2 and date_format(pay_time,'%Y-%m-%d')<='".$date."'";

		$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
		$ret = $cmd->queryRow();

		return isset($ret['num']) ? $ret['num'] : 0;

	}

	public function countDistinctUserCover($type, $date='',$days=0)
	{
		switch ($type)
		{
		//观点包和观点付费重合独立用户数
		case "view": 
			if (1==$days)
			{
				$sql = '' != $date ? "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where type=32 and status>=2  and pay_time between :c_time_b and :c_time_e) and type=31 and status>=2 and pay_time between :c_time_b and :c_time_e" : "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where type=32 and status>=2) and type=31 and status>=2";
			}
			else
			{
				$sql = '' != $date ? "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where type=32 and status>=2  and pay_time <=:c_time_e) and type=31 and status>=2 and pay_time <=:c_time_e" : "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where type=32 and status>=2) and type=31 and status>=2";
			}
			break;
		//提问和解锁付费重合的独立用户数：
		case "ask": 
			if (1==$days)
			{
				$sql = '' != $date ? "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where type=12 and status>=2 and pay_time between :c_time_b and :c_time_e) and type=11 and status>=2 and pay_time between :c_time_b and :c_time_e" : "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where type=12 and status>=2) and type=11 and status>=2";
			}
			else
			{
				$sql = '' != $date ? "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where type=12 and status>=2 and pay_time <=:c_time_e) and type=11 and status>=2 and pay_time <=:c_time_e" : "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where type=12 and status>=2) and type=11 and status>=2";
			}
			break;
		//观点+问答付费重合独立用户数：
		case "viewask":
			if (1==$days)
			{
				$sql = '' != $date ? "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where (type=12 or type=11) and status>=2 and pay_time between :c_time_b and :c_time_e) and (type=31 or type=32) and status>=2 and pay_time between :c_time_b and :c_time_e" : "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where (type=12 or type=11) and status>=2) and (type=31 or type=32) and status>=2";
			}
			else
			{
				$sql = '' != $date ? "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where (type=12 or type=11) and status>=2 and pay_time <=:c_time_e) and (type=31 or type=32) and status>=2 and pay_time <=:c_time_e" : "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where (type=12 or type=11) and status>=2) and (type=31 or type=32) and status>=2";
			}
			break;
		//观点+计划付费重合独立用户数：
		case "viewplan":
			if (1==$days)
			{
				$sql = '' != $date ? "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where type=21 and status>=2 and pay_time between :c_time_b and :c_time_e) and (type=31 or type=32) and status>=2 and pay_time between :c_time_b and :c_time_e" : "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where type=21 and status>=2) and (type=31 or type=32) and status>=2";
			}
			else
			{
				$sql = '' != $date ? "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where type=21 and status>=2 and pay_time <=:c_time_e) and (type=31 or type=32) and status>=2 and pay_time <=:c_time_e" : "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where type=21 and status>=2) and (type=31 or type=32) and status>=2";
			}
			break;
		//计划+问答付费重合独立用户数：
		case "planask": 
			if (1==$days)
			{
				$sql = '' != $date ? "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where (type=12 or type=11) and status>=2 and pay_time between :c_time_b and :c_time_e) and type=21 and status>=2 and pay_time between :c_time_b and :c_time_e" : "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where (type=12 or type=11) and status>=2) and type=21 and status>=2";
			}
			else
			{
				$sql = '' != $date ? "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where (type=12 or type=11) and status>=2 and pay_time <=:c_time_e) and type=21 and status>=2 and pay_time <=:c_time_e" : "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where (type=12 or type=11) and status>=2) and type=21 and status>=2";
			}       
			break;
		//计划+问答+观点付费重合独立用户数：
		case "viewplanask": 
			$sql = "";  
			if (1==$days)
			{
				$sql = '' != $date ? "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where uid in (select distinct(uid) from lcs_orders where (type=12 or type=11) and status>=2 and pay_time between :c_time_b and :c_time_e) and type=21 and status>=2 and pay_time between :c_time_b and :c_time_e) and (type=31 or type=32) and status>=2 and pay_time between :c_time_b and :c_time_e" : "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where uid in (select distinct(uid) from lcs_orders where (type=12 or type=11) and status>=2) and type=21 and status>=2) and (type=31 or type=32) and status>=2";
			}
			else
			{
				$sql = '' != $date ? "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where uid in    (select distinct(uid) from lcs_orders where (type=12 or type=11) and status>=2  and pay_time <=:c_time_e) and type=21 and status>=2 and pay_time <=:c_time_e) and (type=31 or type=32) and status>=2 and pay_time <=:c_time_e" : "select count(distinct(uid)) as num from lcs_orders where uid in (select distinct(uid) from lcs_orders where uid in (select distinct(uid) from lcs_orders where (type=12 or type=11) and status>=2) and type=21 and status>=2) and (type=31 or type=32) and status>=2";
			}
			break;
		}
		if ('' != $date)
		{
			$this->_day_end = $date.' 23:55:00';
			$this->_day_begin = date("Y-m-d 23:55:00", strtotime($this->_day_end) - 24*3600); 
		}
		$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
		if (1==$days)	
			$cmd->bindParam(':c_time_b', $this->_day_begin, PDO::PARAM_STR);
		$cmd->bindParam(':c_time_e', $this->_day_end, PDO::PARAM_STR);
		$ret = $cmd->queryRow();
		return isset($ret['num']) ? $ret['num'] : 0;
	}


	public function countOtherUser($type, $date='')
	{
		if ('' != $date)
		{
			$this->_day_end = $date.' 23:55:00';
			$this->_day_begin = date("Y-m-d 23:55:00", strtotime($this->_day_end) - 24*3600); 
		}
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
			$cmd->bindParam(':c_time_d', $date, PDO::PARAM_STR);
			break;
		case "beforerunplan": 
			$sql = "select count(distinct(uid)) as num from lcs_plan_subscription where c_time<=:c_time_e and c_time!='0000-00-00' and pln_id in (select distinct(pln_id) from lcs_plan_info where  start_date>:c_time_d)"; 
			$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
			$cmd->bindParam(':c_time_e', $this->_day_end, PDO::PARAM_STR);
			$cmd->bindParam(':c_time_d', $date, PDO::PARAM_STR);
			break;
		case "beforeandrun":
			$sql = "select count(distinct(uid)) as num from lcs_plan_subscription where  c_time<:c_time_e and c_time!='0000-00-00' and pln_id in (select distinct(pln_id) from lcs_plan_info where  start_date>:c_time_d) and uid in (select distinct(uid) from lcs_plan_subscription where pln_id in (select distinct(pln_id) from lcs_plan_info where (status=3 and start_date<=:c_time_d) or (real_end_time>=:c_time_d and start_date<=:c_time_d  and status>3)))";
			$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
			$cmd->bindParam(':c_time_e', $this->_day_end, PDO::PARAM_STR);
			$cmd->bindParam(':c_time_d', $date, PDO::PARAM_STR);
			break; 
		}
		$ret = $cmd->queryRow();
		return isset($ret['num']) ? $ret['num'] : 0;		
	}
}

