<?php
/*
 * @Purpose 理财师数据库的数据统计
 * @Author songyao@
 */ 

class StatBase extends CActiveRecord
{
	private $_day_begin;
	private $_day_end;

	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}


	public function __construct()
	{
		$this->_day_end = date("Y-m-d 23:59:59", strtotime("yesterday"));
		$this->_day_begin = date("Y-m-d 00:00:00", strtotime("yesterday")); 
	}

	/*
	 * @Purpose get planner's number 
	 * @Param day_begin: stat begin time; day_end: stat end time
	 */
	public function getPlannerNum($today=0)
	{
		if ($today == 0)
		{
			$sql = "SELECT COUNT(*) as num FROM lcs_planner";
			$ret = Yii::app()->lcs_standby_r->createCommand($sql)->queryRow();
		}
		else
		{
			$sql = "SELECT COUNT(*) as num FROM lcs_planner  WHERE c_time like ':c_time%'";
			$cmd = Yii::app()->lcs_standby_r->createCommand($sql);
			$cmd->bindParam(':c_time',$day_begin,PDO::PARAM_STR);
			$ret = $cmd->queryRow();
		}
		return isset($ret['num']) ? $ret['num'] : 0;	
	}

	/**
	 * get the view number for total or days.
	 * @param string $type
	 * @param string $st
	 */
	public function countViewBase($type, $st, $day_time = '') {
		$sql = 'select count(p_uid) as num from lcs_view ';
		$has_w = 0;
		
		switch ($st) {
			case 'pubview_planner_num':
				$sql = 'select count(distinct(p_uid)) as num from lcs_view ';	break;
			case 'view_num':
				$sql .= ' ';	break;
			case 'has_pay_num':
				$sql .= 'where content_pay!="" ';	$has_w = 1;	break;
			case 'has_tag_num':
				$sql .= 'where tags!="" ';	$has_w = 1;	break;
			case 'has_quote_num':
				$sql .= 'where quote_id!=0 ';	$has_w = 1;	break;
			case 'app_num':
				$sql .= 'where source="f_client" ';	$has_w = 1;	break;
			default:
		}
		switch ($type) {
			case 'total':
				$cmd = Yii::app()->lcs_standby_r->createCommand($sql);
				break;
			case 'days':
				$this->_day_begin = $day_time ." 00:00:00";
				$this->_day_end = $day_time ." 23:59:59";
				$str_where = $has_w == 1 ? " AND " : " WHERE ";
				$sql .= $str_where." c_time>=:c_time_b and c_time<=:c_time_e";
				$cmd = Yii::app()->lcs_standby_r->createCommand($sql);
				$cmd->bindParam(':c_time_b', $this->_day_begin, PDO::PARAM_STR);
				$cmd->bindParam(':c_time_e', $this->_day_end, PDO::PARAM_STR);
				break;
		}
		
		$ret = $cmd->queryRow();
		return isset($ret['num']) ? $ret['num'] : 0;
	}

	/*
	 * @get the question number for total or days 
	 */
	public function countAskBase($type, $st, $day_time = '')
	{
		$sql = "no";
		$has_w = 0;
		switch ($st)
		{
		case "question_num": 	$sql = "SELECT count(id) as num FROM lcs_ask_question "; break;
		case "answer_num": 	$sql = "SELECT count(id) as num FROM lcs_ask_answer "; break;
		case "open_lcs_num": 	$sql = "SELECT count(s_uid) as num FROM lcs_ask_planner where is_open=1 "; $has_w = 1; break;
		case "recive_lcs_num": 	$sql = "SELECT count(distinct(p_uid)) as num FROM lcs_ask_question "; break;
		case "answer_lcs_num": 	$sql = "SELECT count(distinct(p_uid)) as num FROM lcs_ask_answer "; break;
		case "close_num": 	$sql = "SELECT count(id) as num FROM lcs_ask_question where status=-1 "; $has_w = 1;break;
		case "refuse_num": 	$sql = "SELECT count(id) as num FROM lcs_ask_question where no_answer_puid!='' "; $has_w = 1;break;
		case "timeout_num": 	$sql = "SELECT count(id) as num FROM lcs_ask_question where end_time<now() "; $has_w = 1;break;
		case "timeout_answer_num": $sql = "SELECT count(id) as num FROM lcs_ask_question where end_time<now() and status>=3 "; $has_w = 1;break;
		case "client_question_num":   $sql = "SELECT count(id) as num FROM lcs_ask_question where source='f_client' "; $has_w = 1; break;
		case "client_answer_num":   $sql = "SELECT count(id) as num FROM lcs_ask_answer where source='f_client' "; $has_w = 1; break;
		}

		if ($sql != "no")	
		{
			switch ($type)
			{
			case 'total': 
				$sql .= "";
				$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
				break;
			case 'days': 
				$this->_day_begin = $day_time ." 00:00:00";
				$this->_day_end = $day_time ." 23:59:59";
				$str_where = $has_w == 1 ? " AND " : " WHERE ";
				$sql .= $str_where." c_time>=:c_time_b and c_time<=:c_time_e";
				$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
				$cmd->bindParam(':c_time_b', $this->_day_begin, PDO::PARAM_STR);
				$cmd->bindParam(':c_time_e', $this->_day_end, PDO::PARAM_STR);
				break;
			}
		}
		$ret = $cmd->queryRow();
		return isset($ret['num']) ? $ret['num'] : 0;
	}
	
	public function countAskBaseOther($type)
	{
		$sql = "SELECT AVG((satisfaction_num/(q_score_num*5))*100) as satisfy, AVG((resp_time_num/q_num)/60) as avgtime from lcs_ask_planner";

			switch ($type)
			{
			case 'total': 
				$sql .= "";
				$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
				break;
			case 'days': 
				$sql .= " WHERE c_time>=:c_time_b and c_time<=:c_time_e";
				$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
				$cmd->bindParam(':c_time_b', $this->_day_begin, PDO::PARAM_STR);
				$cmd->bindParam(':c_time_e', $this->_day_end, PDO::PARAM_STR);
				break;
			}
		$ret = $cmd->queryRow();
		return $ret;
	}

	
	
	/*
	 * @ insert new data into stat_base table 
	 */
	public function insertStatBase($day_arr) {
		if(empty($day_arr)) {
			return false;
		}
		$cmd = Yii::app()->stat_w->createCommand();
		$ret = $cmd->insert('stat_base', $day_arr);
		return $ret;
	}
}

