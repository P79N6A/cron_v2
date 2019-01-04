<?php
/*
 * @Purpose 理财师数据库的数据统计
 * @Author songyao@
 */ 

class StatData extends CActiveRecord
{
	private $_day_begin;
	private $_day_end;

	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}


	public function __construct()
	{
		$this->_day_end = date("Y-m-d 12:00:00");
		$this->_day_begin = date("Y-m-d 12:00:00", strtotime($this->_day_end) - 24*3600); 
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


	/*
	 * @get the question number for total or days 
	 */
	public function countAskBase($type, $st)
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
		case "timeout_answer_num": 	$sql = "SELECT count(id) as num FROM lcs_ask_question where end_time<now() and status>=3 "; $has_w = 1;break;
		case "client_question_num":   	$sql = "SELECT count(id) as num FROM lcs_ask_question where source='f_client' "; $has_w = 1; break;
		case "client_answer_num":   	$sql = "SELECT count(id) as num FROM lcs_ask_answer where source='f_client' "; $has_w = 1; break;
		//20150306 新增对理财师客户端的统计
		case "lcs_client_question_num": $sql = "SELECT count(id) as num FROM lcs_ask_question where source='lcs_client' "; $has_w = 1; break;
		case "lcs_client_answer_num":   $sql = "SELECT count(id) as num FROM lcs_ask_answer where source='lcs_client' "; $has_w = 1; break;
		case "ios_client_question_num": $sql = "SELECT count(id) as num FROM lcs_ask_question where source='lcs_client_ios' "; $has_w = 1; break;
		case "ios_client_answer_num":   $sql = "SELECT count(id) as num FROM lcs_ask_answer where source='lcs_client_ios' "; $has_w = 1; break;
		case "price_answer_num":  $sql = "select count(id) as num from lcs_ask_question where is_price=1 and status>=3 "; $has_w = 1; break;
		case "price_unlock_num":  $sql = "select count(id) as num from lcs_orders WHERE type=12 and status>1 "; $has_w = 1; break;
		case "earn_planner_num":  $sql = "select count(distinct(p_uid)) as num from lcs_ask_question where is_price=1 and status>2 "; $has_w = 1; break;
		//20150306增加说说的统计
		case "comment_num":  $sql = "select count(cmn_id) as num from lcs_comment";  break;
		case "lcs_comment_num":  $sql = "select count(cmn_id) as num from lcs_comment where source='lcs_client' "; $has_w = 1; break;
		case "ios_comment_num":  $sql = "select count(cmn_id) as num from lcs_comment where source='lcs_client_ios' "; $has_w = 1; break;
		//20150330增加付费观点包及客户端用户绑定统计
		case "weixin_user_num":  $sql = "select count(distinct(uid)) as num from lcs_message_channel_user where channel_type=1";   $has_w = 1;break;
		case "android_user_num":  $sql = "select count(distinct(uid)) as num from lcs_message_channel_user where channel_type=2"; $has_w = 1; break;
		case "ios_user_num":  $sql = " select count(distinct(uid)) as num from lcs_message_channel_user where channel_type=3"; $has_w = 1; break;
		case "buy_package_num":  $sql = " select count(id) as num from lcs_package_subscription "; break;
		case "buy_singleview_num":  $sql = " select count(id) as num from lcs_view_subscription where subscription_price>0"; $has_w = 1; break;
	
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
				$str_where = $has_w == 1 ? " AND " : " WHERE ";
				$sql .= $str_where." c_time>:c_time_b and c_time<=:c_time_e";
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
				$sql .= " WHERE c_time>:c_time_b and c_time<=:c_time_e";
				$cmd = Yii::app()->lcs_standby_r->createCommand($sql);	
				$cmd->bindParam(':c_time_b', $this->_day_begin, PDO::PARAM_STR);
				$cmd->bindParam(':c_time_e', $this->_day_end, PDO::PARAM_STR);
				break;
			}
		$ret = $cmd->queryRow();
		return $ret;
	}

	public function groupPlannerPrice() {
		$db_r = Yii::app()->lcs_standby_r;
		$sql = 'select count(1) as num from lcs_ask_planner a  where  a.answer_price!=0; ';
		$ret_total = $db_r->createCommand($sql)->queryScalar();
		
		$sql = 'select a.answer_price,count(1) as num from lcs_ask_planner a  where  a.answer_price!=0 
				group by answer_price order by a.answer_price desc; ';
		$ret = $db_r->createCommand($sql)->queryAll();
		
		if($ret) {
			foreach ($ret as &$row) {
			    $row['percent'] = round(($row['num']*100)/$ret_total, 2);
			}
 		}
 		return $ret;
	}
	
	/*
	 * @ insert new data into stat_base table 
	 */
	public function insertStatBase($day_arr)
	{
		$sql = "INSERT INTO stat_base (stat_date,total_planner,total_planner_pubview,total_view,total_has_pay,total_has_tag,total_has_quote,days_planner,days_planner_pubview,days_view,days_has_pay,days_has_tag,days_has_quote) VALUES (:stat_date,:total_planner,:total_planner_pubview,:total_view,:total_has_pay,:total_has_tag,:total_has_quote,:days_planner,:days_planner_pubview,:days_view,:days_has_pay,:days_has_tag,:days_has_quote)";
		$cmd = Yii::app()->stat_w->createCommand($sql);
		$cmd->bindParam(':stat_date', $day_arr['stat_date'], PDO::PARAM_STR); 
		$cmd->bindParam(':total_planner', $day_arr['total_planner'], PDO::PARAM_INT); 
		$cmd->bindParam(':total_planner_pubview', $day_arr['total_planner_pubview'], PDO::PARAM_INT); 
		$cmd->bindParam(':total_view', $day_arr['total_view'], PDO::PARAM_INT); 
		$cmd->bindParam(':total_has_pay', $day_arr['total_has_pay'], PDO::PARAM_INT); 
		$cmd->bindParam(':total_has_tag', $day_arr['total_has_tag'], PDO::PARAM_INT); 
		$cmd->bindParam(':total_has_quote', $day_arr['total_has_quote'], PDO::PARAM_INT); 
		$cmd->bindParam(':days_planner', $day_arr['days_planner'], PDO::PARAM_INT); 
		$cmd->bindParam(':days_planner_pubview', $day_arr['days_planner_pubview'], PDO::PARAM_INT); 
		$cmd->bindParam(':days_view', $day_arr['days_view'], PDO::PARAM_INT); 
		$cmd->bindParam(':days_has_pay', $day_arr['days_has_pay'], PDO::PARAM_INT); 
		$cmd->bindParam(':days_has_tag', $day_arr['days_has_tag'], PDO::PARAM_INT); 
		$cmd->bindParam(':days_has_quote', $day_arr['days_has_quote'], PDO::PARAM_INT); 

		$cmd->execute();
		return 0;
	}
}

