<?php
/*
 *  @Purpose 
 *  @Author songyao
 */

class StatDaily
{
	const CRON_NO = 8001; //任务代码
	private $_redis_key = "lcs_v_c_";

	public function __construct()
	{
	}	
	/**
	 *  从redis中取阅读数更新数据库
	 *
	 */
	public function UpdateViewNum()
	{
		try
		{
			$db_w = Yii::app()->lcs_w;
			$db_r = Yii::app()->lcs_r;
			$redis_r = Yii::app()->redis_r;
			$redis_w = Yii::app()->redis_w;
			$view_sql = "select id,p_uid from lcs_view ";
			$view_result = $db_r->createCommand($view_sql)->queryAll();

			$view_key_array = array();
			if (is_array($view_result))
			{
				foreach($view_result as $key => $value)
				{
					$view_key_array[] = $this->_redis_key.$value["id"];
					$view_id[] = $value['id'];
				}
			}

			$view_num_arr = $redis_r->mget($view_key_array);

			if (is_array($view_num_arr))
			{
				foreach ($view_num_arr as $k => $v)
				{
					$upview_sql = "update lcs_view set view_num=".$v.", u_time=NOW()  where id=".$view_id[$k];	
					//echo $upview_sql."\n";
					$exec_view = $db_w->createCommand($upview_sql)->execute();
				}
			}

		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}

	}


	/*
	 * 统计人的观点的阅读数
	 * @param string date 
	 */
	public function StatViewNum()
	{
		try
		{
			$db_r = Yii::app()->lcs_standby_r;

			$sql_view = "select v.id,v.pkg_id,v.title,v.view_num,p.name,p.phone,p.s_uid,v.tags,v.quote_id from lcs_view v, lcs_planner p where v.p_uid=p.s_uid order by v.view_num desc";
			$view_arr = $db_r->createCommand($sql_view)->queryAll();
			$sql_quote = "select id,s_url from lcs_quote ";
			$quote_ret = $db_r->createCommand($sql_quote)->queryAll();
			if (is_array($quote_ret))
			{
				foreach($quote_ret as $qk => $qv)
				{
					$quote_arr[$qv['id']] = $qv['s_url'];
				}
			}

			$txt_str = "所有观点的总阅读数统计";
			$excel_array[] = array("理财师姓名","手机号","微博ID","观点ID","观点标题","观点阅读总数","标签","引用链接");
			$i = 0;
			if (is_array($view_arr))
			{
				foreach($view_arr as $k => $v)
				{
					$quote_url = isset($quote_arr[$v['quote_id']]) ? $quote_arr[$v['quote_id']]: "";
					$excel_array[] = array($v['name'],$v['phone'],$v['s_uid'],$v['id'],$v['title'],$v['view_num'],$v['tags'],$quote_url);	
					$i++;
					if ($i > 10000) break;
				}
			}
			$toexcel = $this->toExcel("观点_阅读数", $txt_str, $excel_array);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}
	/*
	 * 统计人的观点的阅读数 当天的，昨天12点到今天12点
	 * @param string date 
	 */
	public function StatViewNumToday($dt)
	{
		try
		{
			$db_r = Yii::app()->lcs_standby_r;
			if ($dt =="today")
			{
				$day_end =  date("Y-m-d 12:00:00");
				$day_begin = date("Y-m-d 12:00:00", strtotime($day_end) - 24*3600);
				$show_date = date("Y-m-d");
				$yes_day_end =  $day_begin;
				$yes_day_begin = date("Y-m-d 12:00:00", strtotime($yes_day_end) - 24*3600);
				$yes_show_date = date("Y-m-d",strtotime($yes_day_end));

			}
			else
			{
				$day_end =  $dt." 12:00:00";
				$day_begin = date("Y-m-d 12:00:00", strtotime($day_end) - 24*3600);
				$show_date = $dt;
				$yes_day_end =  $day_begin;
				$yes_day_begin = date("Y-m-d 12:00:00", strtotime($yes_day_end) - 24*3600);
				$yes_show_date = date("Y-m-d",strtotime($yes_day_end));

			}

			$sql_view = "select v.id,v.pkg_id,v.title,v.view_num,p.name,p.phone,p.s_uid,v.tags,v.quote_id from lcs_view v, lcs_planner p where v.p_uid=p.s_uid and v.p_time>'".$day_begin."' and v.p_time<'".$day_end."' order by v.view_num desc";
			$view_arr = $db_r->createCommand($sql_view)->queryAll();
			$sql_quote = "select id,s_url from lcs_quote ";
			$quote_ret = $db_r->createCommand($sql_quote)->queryAll();
			if (is_array($quote_ret))
			{
				foreach($quote_ret as $qk => $qv)
				{
					$quote_arr[$qv['id']] = $qv['s_url'];
				}
			}

			$txt_str = "统计 ".$day_begin."～".$day_end." 时间段发布的观点阅读数情况";
			$excel_array[] = array("理财师姓名","手机号","微博ID","观点ID","观点标题","观点阅读总数","标签","引用链接");
			if (is_array($view_arr))
			{
				foreach($view_arr as $k => $v)
				{
					$quote_url = isset($quote_arr[$v['quote_id']]) ? $quote_arr[$v['quote_id']]: "";
					$excel_array[] = array($v['name'],$v['phone'],$v['s_uid'],$v['id'],$v['title'],$v['view_num'],$v['tags'],$quote_url);	
				}
			}
			$toexcel = $this->toExcel("观点（今日）_阅读数", $txt_str, $excel_array);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}

	/*
	 * 统计人的观点包的订阅数
	 * @param string date 
	 */
	public function StatPackageSub()
	{
		try
		{
			$db_r = Yii::app()->lcs_standby_r;

			$sql_view = "select sum(view_num) as v_num, pkg_id from lcs_view group by pkg_id order by v_num desc";
			$view_arr = $db_r->createCommand($sql_view)->queryAll();
			if (is_array($view_arr))
			{
				foreach($view_arr as $kk => $vv)
				{
					$pkg_view[$vv['pkg_id']] = $vv['v_num'];
				}
			}


			$sql_pkg = "select k.title,k.id,k.sub_num,p.name,p.phone,p.s_uid from lcs_package k, lcs_planner p where k.p_uid=p.s_uid order by k.sub_num desc";
			$pkg_arr = $db_r->createCommand($sql_pkg)->queryAll();

			$txt_str = "观点包全部订阅数量统计";
			$excel_array[] = array("理财师姓名","手机号","微博ID","观点包ID","观点包名","观点包订阅数","观点包内观点总阅读数");
			if (is_array($pkg_arr))
			{
				foreach($pkg_arr as $k => $v)
				{
					$pkg_view_all = isset($pkg_view[$v['id']]) ? $pkg_view[$v['id']] : 0;
					$excel_array[] = array($v['name'],$v['phone'],$v['s_uid'],$v['id'],$v['title'],$v['sub_num'],$pkg_view_all);	
				}
			}
			$toexcel = $this->toExcel("观点包_全部订阅数", $txt_str, $excel_array);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}


	/*
	 * 统计观点包今天的订阅数
	 * @param string date 
	 */
	public function StatPackageSubToday($dt)
	{
		try
		{
			$db_r = Yii::app()->lcs_standby_r;
			if ($dt =="today")
			{
				$day_end =  date("Y-m-d 12:00:00");
				$day_begin = date("Y-m-d 12:00:00", strtotime($day_end) - 24*3600);
				$show_date = date("Y-m-d");
				$yes_day_end =  $day_begin;
				$yes_day_begin = date("Y-m-d 12:00:00", strtotime($yes_day_end) - 24*3600);
				$yes_show_date = date("Y-m-d",strtotime($yes_day_end));

			}
			else
			{
				$day_end =  $dt." 12:00:00";
				$day_begin = date("Y-m-d 12:00:00", strtotime($day_end) - 24*3600);
				$show_date = $dt;
				$yes_day_end =  $day_begin;
				$yes_day_begin = date("Y-m-d 12:00:00", strtotime($yes_day_end) - 24*3600);
				$yes_show_date = date("Y-m-d",strtotime($yes_day_end));

			}

			$sql_view = "select sum(view_num) as v_num, pkg_id from lcs_view group by pkg_id order by v_num desc";
			$view_arr = $db_r->createCommand($sql_view)->queryAll();
			if (is_array($view_arr))
			{
				foreach($view_arr as $kk => $vv)
				{
					$pkg_view[$vv['pkg_id']] = $vv['v_num'];
				}
			}
			$sql_planner= "select s_uid,name,phone from lcs_planner";
			$planner_arr= $db_r->createCommand($sql_planner)->queryAll();
			if (is_array($planner_arr))
			{
				foreach($planner_arr as $kkk => $vvv)
				{
					$pkg_planner[$vvv['s_uid']]['name'] = $vvv['name'];
					$pkg_planner[$vvv['s_uid']]['phone'] = $vvv['phone'];
				}
			}


			$sql_sub = "select count(*) as num,p.id,p.title,p.p_uid from lcs_subscription s,lcs_package p where s.pkg_id=p.id and s.c_time>'".$day_begin."' and s.c_time<'".$day_end."' group by s.pkg_id order by num desc";
			$pkg_arr = $db_r->createCommand($sql_sub)->queryAll();

			$txt_str = "统计 ".$day_begin."～".$day_end." 时间段观点包的订阅数及这些观点包下所有观点的总的阅读数情况";
			$excel_array[] = array("理财师姓名","手机号","微博ID","观点包ID","观点包名","观点包今天的订阅数","观点包内观点历史总阅读数");
			if (is_array($pkg_arr))
			{
				foreach($pkg_arr as $k => $v)
				{
					$pkg_view_all = isset($pkg_view[$v['id']]) ? $pkg_view[$v['id']] : 0;

					$excel_array[] = array($pkg_planner[$v['p_uid']]['name'],$pkg_planner[$v['p_uid']]['phone'],$v['p_uid'],$v['id'],$v['title'],$v['num'],$pkg_view_all);	
				}
			}
			$toexcel = $this->toExcel("观点包_今日订阅数", $txt_str, $excel_array);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}

	/*
	 * 统计人发观点数目
	 * @param string date 
	 */
	public function StatPlannerViews($dt,$inc)
	{
		try
		{
			$db_r = Yii::app()->lcs_standby_r;
			if ($dt =="today")
			{
				$day_end =  date("Y-m-d 12:00:00");
				$day_begin = date("Y-m-d 12:00:00", strtotime($day_end) - 24*3600);
				$show_date = date("Y-m-d");
				$yes_day_end =  $day_begin;
				$yes_day_begin = date("Y-m-d 12:00:00", strtotime($yes_day_end) - 24*3600);
				$yes_show_date = date("Y-m-d",strtotime($yes_day_end));

			}
			else
			{
				$day_end =  $dt." 12:00:00";
				$day_begin = date("Y-m-d 12:00:00", strtotime($day_end) - 24*3600);
				$show_date = $dt;
				$yes_day_end =  $day_begin;
				$yes_day_begin = date("Y-m-d 12:00:00", strtotime($yes_day_end) - 24*3600);
				$yes_show_date = date("Y-m-d",strtotime($yes_day_end));

			}
			$day_1 = date("Y-m-d 12:00:00", strtotime($yes_day_end) - 48*3600);
			$day_2 = date("Y-m-d 12:00:00", strtotime($yes_day_end) - 72*3600);
			$day_3 = date("Y-m-d 12:00:00", strtotime($yes_day_end) - 96*3600);
			$day_show_1 = date("Y-m-d",strtotime($yes_day_begin));
			$day_show_2 = date("Y-m-d",strtotime($day_1));
			$day_show_3 = date("Y-m-d",strtotime($day_2));


			$sql = "SELECT p.name,p.phone,v.p_uid,count(v.id) as v_num from lcs_view v,lcs_planner p where v.p_uid=p.s_uid and v.c_time>'".$day_begin."' and v.c_time<'".$day_end."' group by v.p_uid order by v_num desc";

			$s_cmd = $db_r->createCommand($sql);
			$record_result = $s_cmd->queryAll();
			$yes_sql = "SELECT p.name,p.phone,v.p_uid,count(v.id) as v_num from lcs_view v,lcs_planner p where v.p_uid=p.s_uid and v.c_time>'".$yes_day_begin."' and v.c_time<'".$yes_day_end."' group by v.p_uid order by v_num desc";

			$yes_result = $db_r->createCommand($yes_sql)->queryAll();

			$yes_sql = "SELECT p.name,p.phone,v.p_uid,count(v.id) as v_num from lcs_view v,lcs_planner p where v.p_uid=p.s_uid and v.c_time>'".$day_1."' and v.c_time<'".$yes_day_begin."' group by v.p_uid order by v_num desc";

			$yes_1 = $db_r->createCommand($yes_sql)->queryAll();
			$yes_sql = "SELECT p.name,p.phone,v.p_uid,count(v.id) as v_num from lcs_view v,lcs_planner p where v.p_uid=p.s_uid and v.c_time>'".$day_2."' and v.c_time<'".$day_1."' group by v.p_uid order by v_num desc";

			$yes_2 = $db_r->createCommand($yes_sql)->queryAll();
			$yes_sql = "SELECT p.name,p.phone,v.p_uid,count(v.id) as v_num from lcs_view v,lcs_planner p where v.p_uid=p.s_uid and v.c_time>'".$day_3."' and v.c_time<'".$day_2."' group by v.p_uid order by v_num desc";

			$yes_3 = $db_r->createCommand($yes_sql)->queryAll();

			$txt_str = $inc==1 ? $day_begin."后新增的理财师统计，时间是头天中午到当天中午" : "统计 头天中午到当天中午 时间段发布观点的数量";
			$excel_array[] = array("理财师姓名","手机号","微博ID","公司","行业","邀请码",$show_date."发布观点数",$yes_show_date,$day_show_1,$day_show_2,$day_show_3,"最近两日变动");
			$planner_yet = array();
			if (is_array($record_result) and sizeof($record_result) > 0)
			{
				foreach($record_result as $key => $value)
				{
					$planner_yet[$value['p_uid']] = $value;
				}
			}
			else
			{
				Yii::log("no record need to sum!", CLogger::LEVEL_INFO, 'command.tmpStat.statPlannerViews');
			}
			if (is_array($yes_result) and sizeof($yes_result) > 0)
			{
				foreach($yes_result as $kk => $vv)
				{
					$yes_planner_yet[$vv['p_uid']] = $vv;
				}
			}
			else
			{
				Yii::log("no record need to sum!", CLogger::LEVEL_INFO, 'command.tmpStat.statPlannerViews');
			}
			if (is_array($yes_1) and sizeof($yes_1) > 0)
			{
				unset($kk);
				unset($vv);
				foreach($yes_1 as $kk => $vv)
				{
					$yes_planner_1[$vv['p_uid']] = $vv;
				}
			}
			if (is_array($yes_2) and sizeof($yes_2) > 0)
			{
				unset($kk);
				unset($vv);
				foreach($yes_2 as $kk => $vv)
				{
					$yes_planner_2[$vv['p_uid']] = $vv;
				}
			}
			if (is_array($yes_3) and sizeof($yes_3) > 0)
			{
				unset($kk);
				unset($vv);
				foreach($yes_3 as $kk => $vv)
				{
					$yes_planner_3[$vv['p_uid']] = $vv;
				}
			}

			if ($inc==1)
			{
				$sql_planner = "select p.name,p.phone,p.s_uid,p.ind_id,c.name as cname from lcs_planner p, lcs_company c where p.company_id=c.id and p.c_time> '".$day_begin."'";
			}
			else
			{
				$sql_planner = "select p.name,p.phone,p.s_uid,p.ind_id,c.name as cname from lcs_planner p, lcs_company c where p.company_id=c.id ";
			}
			$planner_arr = $db_r->createCommand($sql_planner)->queryAll();
			if (is_array($planner_arr) and sizeof($planner_arr) > 0)
			{
				foreach($planner_arr as $k => $v)
				{
					$nowday = isset($planner_yet[$v['s_uid']]) ? $planner_yet[$v['s_uid']]['v_num'] : 0;
					$yesterday = isset($yes_planner_yet[$v['s_uid']]) ? $yes_planner_yet[$v['s_uid']]['v_num'] : 0;
					$day1 = isset($yes_planner_1[$v['s_uid']]) ? $yes_planner_1[$v['s_uid']]['v_num'] : 0;
					$day2 = isset($yes_planner_2[$v['s_uid']]) ? $yes_planner_2[$v['s_uid']]['v_num'] : 0;
					$day3 = isset($yes_planner_3[$v['s_uid']]) ? $yes_planner_3[$v['s_uid']]['v_num'] : 0;
					$diff = $nowday - $yesterday;
					$diff_sign = "--";
					if (0 != $diff)
					{
						$diff_sign = $diff > 0 ? "增加" : "减少";
					}
					$sql_ind = "select name from lcs_industry where id=".$v["ind_id"];
					$ind_name_ret = $db_r->createCommand($sql_ind)->queryRow();
					$ind_name = isset($ind_name_ret['name']) ? $ind_name_ret['name'] : "--";
					$sql_code= "select code from lcs_invitation_code where uid=".$v["s_uid"];
					$inv_code_ret = $db_r->createCommand($sql_code)->queryRow();
					$inv_code = isset($inv_code_ret['code']) ? $inv_code_ret['code'] : "--";

					$excel_array[] = array($v["name"],$v["phone"],$v["s_uid"],$v["cname"],$ind_name,$inv_code,$nowday,$yesterday,$day1,$day2,$day3,$diff_sign); 
				}
			}

			$toexcel = $this->toExcel("理财师_全部", $txt_str, $excel_array);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}

	/*
	 * 统计推荐热词被创建观点的比例
	 * @param string date 
	 */
	public function StatHotTagsRate($dt)
	{ 

	}
	/*
	 * 统计搜索热词及搜索数量
	 * @param string date 
	 */
	public function StatSearchHot()
	{ 
		try
		{
			$db_r = Yii::app()->lcs_standby_r;		

			$srch_sql = "select keywords, count(*) as num from lcs_search_his group by keywords order by num desc";
			$srch_result = $db_r->createCommand($srch_sql)->queryAll();

			$txt_str = "数据统计截止时间：".date("Y-m-d H:i:s");
			$excel_array[] = array("搜索词","搜索数量");
			if (is_array($srch_result))
			{
				foreach($srch_result as $key => $value)
				{
					$excel_array[] = array($value['keywords'], $value['num']);	
				}
			}

			$toexcel = $this->toExcel("搜索热词", $txt_str, $excel_array);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}
	/*
	 * 基础统计 
	 * @param string date 
	 */
	public function StatBase()
	{ 
		try
		{
			$db_r = Yii::app()->lcs_standby_r;		
			$today_to_now = date("Y-m-d 00:00:00");
			$day_end =  date("Y-m-d 12:00:00");
			$day_begin = date("Y-m-d 12:00:00", strtotime($day_end) - 24*3600);
			$sql1 = "select count(*) as num from lcs_planner";
			$ret1 = $db_r->createCommand($sql1)->queryRow();
			$sql2 = "select count(*) as num from lcs_planner where c_time > '".$day_begin."'";
			$ret2 = $db_r->createCommand($sql2)->queryRow();
			$sql3 = "select count(distinct(p_uid)) as num from lcs_view";
			$ret3 = $db_r->createCommand($sql3)->queryRow();
			$sql4 = "select count(distinct(p_uid)) as num from lcs_view where p_time > '".$day_begin."'";
			$ret4 = $db_r->createCommand($sql4)->queryRow();
			$sql5 = "select count(*) as num from lcs_view";
			$ret5 = $db_r->createCommand($sql5)->queryRow();
			$sql6 = "select count(*) as num from lcs_view where p_time > '".$day_begin."'";
			$ret6 = $db_r->createCommand($sql6)->queryRow();
			$sql7 = "select count(*) as num from lcs_view where content_pay!=''";
			$ret7 = $db_r->createCommand($sql7)->queryRow();
			$sql8 = "select count(*) as num from lcs_view where tags!='' ";
			$ret8 = $db_r->createCommand($sql8)->queryRow();
			$sql9 = "select count(*) as num from lcs_view where quote_title!='' ";
			$ret9 = $db_r->createCommand($sql9)->queryRow();
			$sql10 = "select count(*) as num from lcs_view where content_pay!='' and  p_time > '".$day_begin."'";
			$ret10 = $db_r->createCommand($sql10)->queryRow();
			$sql11 = "select count(*) as num from lcs_view where tags!='' and  p_time > '".$day_begin."'";
			$ret11 = $db_r->createCommand($sql11)->queryRow();
			$sql12 = "select count(*) as num from lcs_view where quote_title!='' and  p_time > '".$day_begin."'";
			$ret12 = $db_r->createCommand($sql12)->queryRow();
			$sql13 = "SELECT COUNT(p_uid) as views_num FROM lcs_view WHERE source = 'f_client';";
			$ret13 = $db_r->createCommand($sql13)->queryRow();
			$sql14 = "select count(p_uid) as num from lcs_view where source = 'f_client' and p_time > '".$day_begin."'";
			$ret14 = $db_r->createCommand($sql14)->queryRow();



			$txt_str = "数据统计截止时间：".date("Y-m-d H:i:s");
			$to_now_all = isset($ret1['num']) ? $ret1['num'] : 0;
			$yes_to_now_all = isset($ret2['num']) ? $ret2['num'] : 0;
			$to_now_pub_all = isset($ret3['num']) ? $ret3['num'] : 0;
			$yes_to_now_pub_all = isset($ret4['num']) ? $ret4['num'] : 0;
			$to_now_view_num = isset($ret5['num']) ? $ret5['num'] : 0;
			$yes_to_now_view_num = isset($ret6['num']) ? $ret6['num'] : 0;
			$have_pay = isset($ret7['num']) ? $ret7['num'] : 0;
			$have_tags = isset($ret8['num']) ? $ret8['num'] : 0;
			$have_quote = isset($ret9['num']) ? $ret9['num'] : 0;
			$yes_have_pay = isset($ret10['num']) ? $ret10['num'] : 0;
			$yes_have_tags = isset($ret11['num']) ? $ret11['num'] : 0;
			$yes_have_quote = isset($ret12['num']) ? $ret12['num'] : 0;
			$client_view_num = isset($ret13['views_num']) ? $ret13['views_num'] : 0;
			$yes_client_view_num = isset($ret14['num']) ? $ret14['num'] : 0;


			$excel_array[] = array("到目前为止认证的理财师数", $to_now_all." 人");
			$excel_array[] = array("到目前为止发了观点的理财师数", $to_now_pub_all." 人");
			$excel_array[] = array("到目前为止观点总数", $to_now_view_num ." 条");
			$excel_array[] = array("观点填写了付费内容的数量及占比", $have_pay." 条，".number_format($have_pay/$to_now_view_num*100, 2)."%");
			$excel_array[] = array("观点填写了标签的数量和占比", $have_tags." 条，".number_format($have_tags/$to_now_view_num*100, 2)."%");
			$excel_array[] = array("观点填写了引用的数量和占比", $have_quote." 条，".number_format($have_quote/$to_now_view_num*100, 2)."%");
			$excel_array[] = array("到目前为止通过APP发布的观点数量", $client_view_num." 条");


			$excel_array[] = array("昨天中午12点到目前为止认证的理财师数", $yes_to_now_all." 人");
			$excel_array[] = array("昨天中午12点到目前为止发了观点的理财师数", $yes_to_now_pub_all." 人");
			$excel_array[] = array("昨天中午12点到目前为止观点总数", $yes_to_now_view_num ." 条");
			$excel_array[] = array("昨天中午12点到目前为止的观点中填写了付费内容的数量及占比", $yes_have_pay." 条，".number_format($yes_have_pay/$yes_to_now_view_num*100, 2)."%");
			$excel_array[] = array("昨天中午12点到目前为止的观点中填写了标签的数量和占比", $yes_have_tags." 条，".number_format($yes_have_tags/$yes_to_now_view_num*100, 2)."%");
			$excel_array[] = array("昨天中午12点到目前为止的观点中填写了引用的数量和占比", $yes_have_quote." 条，".number_format($yes_have_quote/$yes_to_now_view_num*100, 2)."%");
			$excel_array[] = array("昨天中午12点到目前为止通过APP发布的观点数量", $yes_client_view_num ." 条");
			//$excel_array[] = array("昨天中午12点到目前为止设置收费的理财师数", $yes_price_num." ");

			$question_num =  StatData::model()->countAskBase("total", "question_num");
			$answer_num =  StatData::model()->countAskBase("total", "answer_num");
			$open_lcs_num =  StatData::model()->countAskBase("total", "open_lcs_num");
			$recive_lcs_num =  StatData::model()->countAskBase("total", "recive_lcs_num");
			$answer_lcs_num =  StatData::model()->countAskBase("total", "answer_lcs_num");
			$close_num =  StatData::model()->countAskBase("total", "close_num");
			$refuse_num =  StatData::model()->countAskBase("total", "refuse_num");
			$timeout_num =  StatData::model()->countAskBase("total", "timeout_num");
			$timeout_answer_num =  StatData::model()->countAskBase("total", "timeout_answer_num");

			$days_question_num =  StatData::model()->countAskBase("days", "question_num");
			$days_answer_num =  StatData::model()->countAskBase("days", "answer_num");
			$days_open_lcs_num =  StatData::model()->countAskBase("days", "open_lcs_num");
			$days_recive_lcs_num =  StatData::model()->countAskBase("days", "recive_lcs_num");
			$days_answer_lcs_num =  StatData::model()->countAskBase("days", "answer_lcs_num");
			$days_close_num =  StatData::model()->countAskBase("days", "close_num");
			$days_refuse_num =  StatData::model()->countAskBase("days", "refuse_num");
			$days_timeout_num =  StatData::model()->countAskBase("days", "timeout_num");
			$days_timeout_answer_num =  StatData::model()->countAskBase("days", "timeout_answer_num");

			$other_ask =  StatData::model()->countAskBaseOther("total");
			$days_other_ask =  StatData::model()->countAskBaseOther("days");

			$client_question_num = StatData::model()->countAskBase("total", "client_question_num");
			$days_client_question_num = StatData::model()->countAskBase("days", "client_question_num");
			$lcs_client_question_num = StatData::model()->countAskBase("total", "lcs_client_question_num");
			$days_lcs_client_question_num = StatData::model()->countAskBase("days", "lcs_client_question_num");
			$ios_client_question_num = StatData::model()->countAskBase("total", "ios_client_question_num");
			$days_ios_client_question_num = StatData::model()->countAskBase("days", "ios_client_question_num");

			$client_answer_num = StatData::model()->countAskBase("total", "client_answer_num");
			$days_client_answer_num = StatData::model()->countAskBase("days", "client_answer_num");
			$lcs_client_answer_num = StatData::model()->countAskBase("total", "lcs_client_answer_num");
			$days_lcs_client_answer_num = StatData::model()->countAskBase("days", "lcs_client_answer_num");
			$ios_client_answer_num = StatData::model()->countAskBase("total", "ios_client_answer_num");
			$days_ios_client_answer_num = StatData::model()->countAskBase("days", "ios_client_answer_num");

			$price_answer_num = StatData::model()->countAskBase("total", "price_answer_num");
			$days_price_answer_num = StatData::model()->countAskBase("days", "price_answer_num");
			$price_unlock_num = StatData::model()->countAskBase("total", "price_unlock_num");
			$days_price_unlock_num = StatData::model()->countAskBase("days", "price_unlock_num");
			$earn_planner_num = StatData::model()->countAskBase("total", "earn_planner_num");
			$days_earn_planner_num = StatData::model()->countAskBase("days", "earn_planner_num");
			//说说数量
			$comment_num = StatData::model()->countAskBase("total", "comment_num");
			$lcs_comment_num = StatData::model()->countAskBase("total", "lcs_comment_num");
			$ios_comment_num = StatData::model()->countAskBase("total", "ios_comment_num");
			$days_comment_num = StatData::model()->countAskBase("days", "comment_num");
			$days_lcs_comment_num = StatData::model()->countAskBase("days", "lcs_comment_num");
			$days_ios_comment_num = StatData::model()->countAskBase("days", "ios_comment_num");
			//20150330 付费观点
			$weixin_user_num = StatData::model()->countAskBase("total", "weixin_user_num");
			$android_user_num = StatData::model()->countAskBase("total", "android_user_num");
			$ios_user_num = StatData::model()->countAskBase("total", "ios_user_num");
			$buy_package_num = StatData::model()->countAskBase("total", "buy_package_num");
			$buy_singleview_num = StatData::model()->countAskBase("total", "buy_singleview_num");
			$day_buy_package_num = StatData::model()->countAskBase("days", "buy_package_num");
			$day_buy_singleview_num = StatData::model()->countAskBase("days", "buy_singleview_num");



			$p_price_group = StatData::model()->groupPlannerPrice();
			$have_price_num = '';
			$price_planner_num = 0;
			if($p_price_group) {
				foreach ($p_price_group as $row) {
					$have_price_num .= $row['answer_price'].'元：'.$row['num'].'人，'.$row['percent'].'%；';
					$price_planner_num += $row['num'];
				}
			}

			$excel_array[] = array("到目前为止问题数", $question_num." 条");
			$excel_array[] = array("到目前为止回答数", $answer_num." 条");
			$excel_array[] = array("到目前为止开启问答的理财师数", $open_lcs_num." 人");
			$excel_array[] = array("到目前为止接收到问题的理财师数", $recive_lcs_num." 人");
			$excel_array[] = array("到目前为止回答过问题的理财师数", $answer_lcs_num." 人");
			$excel_array[] = array("到目前为止用户不问了的问题数", $close_num." 条");
			$excel_array[] = array("到目前为止理财师拒绝的问题数", $refuse_num." 条");
			$excel_array[] = array("到目前为止超时的问题数", $timeout_num." 条");
			$excel_array[] = array("到目前为止超时后被回答的问题数", $timeout_answer_num." 条");
			$excel_array[] = array("到目前为止理财师平均满意度", round($other_ask['satisfy'], 2)." %");
			$excel_array[] = array("到目前为止理财师平均响应时间", round($other_ask['avgtime'], 2)." 小时");
			$excel_array[] = array("到目前为止新浪财经APP提问数", $client_question_num." 条");
			$excel_array[] = array("到目前为止新浪财经APP回答数", $client_answer_num." 条");
			$excel_array[] = array("到目前为止理财师APP提问数", ($lcs_client_question_num+$ios_client_question_num)." 条, 其中 Android: ".$lcs_client_question_num."，IOS: ".$ios_client_question_num);
			$excel_array[] = array("到目前为止理财师APP回答数", ($lcs_client_answer_num+$ios_client_answer_num)." 条, 其中 Android: ".$lcs_client_answer_num."，IOS: ".$ios_client_answer_num);

			$excel_array[] = array("到目前为止设置收费的理财师数", $price_planner_num." 人，其中: ". $have_price_num);
			$excel_array[] = array("到目前为止付费提问并被回答的问题数", $price_answer_num." 条");
			$excel_array[] = array("到目前为止付费解锁的个数", $price_unlock_num." 条");
			$excel_array[] = array("到目前为止有收入的理财师人数", $earn_planner_num." 人");
			$excel_array[] = array("到目前为止大家说数量(客户端从20150306有数据)", $comment_num." 条, 其中 Android: ".$lcs_comment_num." ，IOS: ".$ios_comment_num);
			//20150330
			$excel_array[] = array("到目前为止微信绑定过的人数", $weixin_user_num." 人");
			$excel_array[] = array("到目前为止Android客户端登陆过的人数", $android_user_num." 人");
			$excel_array[] = array("到目前为止IOS客户端登陆过的人数", $ios_user_num." 人");

			$excel_array[] = array("到目前为止观点包购买次数", $buy_package_num." 次");
			$excel_array[] = array("到目前为止单条观点购买次数", $buy_singleview_num." 次");



			$excel_array[] = array("昨天中午12点到目前为止问题数", $days_question_num." 条");
			$excel_array[] = array("昨天中午12点到目前为止回答数", $days_answer_num." 条");
			$excel_array[] = array("昨天中午12点到目前为止开启问答的理财师数", $days_open_lcs_num." 人");
			$excel_array[] = array("昨天中午12点到目前为止接收到问题的理财师数", $days_recive_lcs_num." 人");
			$excel_array[] = array("昨天中午12点到目前为止回答过问题的理财师数", $days_answer_lcs_num." 人");
			$excel_array[] = array("昨天中午12点到目前为止用户不问了的问题数", $days_close_num." 条");
			$excel_array[] = array("昨天中午12点到目前为止理财师拒绝的问题数", $days_refuse_num." 条");
			$excel_array[] = array("昨天中午12点到目前为止超时的问题数", $days_timeout_num." 条");
			$excel_array[] = array("昨天中午12点到目前为止超时后被回答的问题数", $days_timeout_answer_num." 条");
			//		$excel_array[] = array("昨天中午12点到目前为止理财师平均满意度", round($days_other_ask['satisfy'], 2)." %");
			//		$excel_array[] = array("昨天中午12点到目前为止理财师平均响应时间", round($days_other_ask['avgtime'], 2)." 小时");
			$excel_array[] = array("昨天中午12点到目前为止新浪财经APP问题数", $days_client_question_num." 条");
			$excel_array[] = array("昨天中午12点到目前为止新浪财经APP回答数", $days_client_answer_num." 条");
			$excel_array[] = array("昨天中午12点到目前为止理财师APP问题数", ($days_lcs_client_question_num+$days_ios_client_question_num)." 条, 其中 Android: ".$days_lcs_client_question_num."，IOS: ".$days_ios_client_question_num);
			$excel_array[] = array("昨天中午12点到目前为止理财师APP回答数",  ($days_lcs_client_answer_num+$days_ios_client_answer_num)." 条, 其中 Android ".$days_lcs_client_answer_num.", IOS: ".$days_ios_client_answer_num);

			$excel_array[] = array("昨天中午12点到目前为止付费提问并被回答的问题数", $days_price_answer_num." 条");
			$excel_array[] = array("昨天中午12点到目前为止付费解锁的个数", $days_price_unlock_num." 条");
			$excel_array[] = array("昨天中午12点到目前为止有收入的理财师人数统计", $days_earn_planner_num." 人");
			$excel_array[] = array("昨天中午12点到目前为止大家说数量", $days_comment_num." 条, 其中 Android: ".$days_lcs_comment_num." ，IOS: ".$days_ios_comment_num);
			//20150330
			$excel_array[] = array("昨天中午12点到目前为止观点包购买次数", $day_buy_package_num." 次");
			$excel_array[] = array("昨天中午12点到目前为止单条观点购买次数", $day_buy_singleview_num." 次");


			$toexcel = $this->toExcel("基础数据", $txt_str, $excel_array);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}

	/*
	 * 引用连接及观点关系 
	 * @param string date 
	 */
	public function StatQuote()
	{ 
		try
		{
			$db_r = Yii::app()->lcs_standby_r;		

			$sql = "select v.id,v.title,q.s_url from lcs_view v ,lcs_quote q where q.id=v.quote_id ";
			$ret = $db_r->createCommand($sql)->queryAll();

			$quote_arr = array();
			if (is_array($ret))
			{
				foreach ($ret as $k => $v)
				{
					$quote_arr[$v['s_url']][] = $v['id']." : ".$v['title'];  
				}
			}


			$txt_str = "数据统计截止时间：".date("Y-m-d H:i:s");
			$excel_array[] = array("被引用的链接","引用它的观点数","引用它的观点ID:标题");

			foreach($quote_arr as $kk => $vv)
			{
				$excel_array[] = array($kk,count($vv),$this->concatArr($vv));	
			}
			$toexcel = $this->toExcel("引用", $txt_str, $excel_array);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}
	/*
	 * 理财师流失情况
	 * @param string date 
	 */
	public function StatPlannerLost($d)
	{ 
		try
		{
			$db_r = Yii::app()->lcs_standby_r;		

			$day_today = date("Y-m-d 12:00:00");
			$day_begin = date("Y-m-d 12:00:00", strtotime($day_today) - $d*24*3600);

			$sql = "select s_uid,phone,company_id,name,u_time,c_time from lcs_planner where u_time<'".$day_begin."'";
			$ret = $db_r->createCommand($sql)->queryAll();

			$company_arr = $this->company();

			$txt_str = $day_begin." 后未登录的理财师用户";
			$excel_array[] = array("理财师微博ID","姓名","手机号","所属公司","认证创建时间","最后登录时间","登录与否");

			if (is_array($ret))
			{
				foreach($ret as $kk => $vv)
				{
					$is_login = ($vv['c_time'] == $vv['u_time']) ? "" : "登录过";
					$company_name = isset($company_arr[$vv['company_id']]) ? $company_arr[$vv['company_id']] : "";
					$excel_array[] = array($vv['s_uid'],$vv['name'],$vv['phone'],$company_name,$vv['c_time'],$vv['u_time'],$is_login);	
				}
			}
			$toexcel = $this->toExcel("理财师_流失", $txt_str, $excel_array);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}
	/*
	 * 特定标签理财师列表
	 * @param string
	 */
	public function StatPlannerTags($t)
	{
		try
		{
			$db_r = Yii::app()->lcs_standby_r;		
			if ($t == 'us')
			{
				$str = "美股、美国市场、美国股市、欧美市场、欧美财经、欧洲股市、欧股、德股、英股、法股、国际市场、环球市场、全球市场、新兴市场、前沿市场";
				$str_arr = explode("、", $str);
			}
			else
			{
				exit;	
			}

			$planner_arr = array();
			if (is_array($str_arr))
			{
				foreach($str_arr as $k=>$v)
				{
					$sql = "select s_uid, name,phone,company_id,tags from lcs_planner where tags like '%".$v."%'";
					$ret = $db_r->createCommand($sql)->queryAll();
					$planner_arr = array_merge($ret,$planner_arr); 	
				}
			}

			$txt_str = "包含 ".$str." 的理财师列表：\n";
			$excel_array[] = array('理财师微博ID','姓名','手机号','所属公司','标签');
			$company_arr = $this->company();
			if (isset($planner_arr))
			{
				foreach($planner_arr as $kk => $vv)
				{
					$company_name = isset($company_arr[$vv['company_id']]) ? $company_arr[$vv['company_id']] : "";
					$excel_array[] = array($vv['s_uid'],$vv['name'],$vv['phone'],$company_name,$vv['tags']);
				}
			}
			$toexcel = $this->toExcel("理财师_擅长美股", $txt_str, $excel_array);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}

	/**
	 * 统计财经组的
	 *
	 */
	public function Staff($st)
	{
		try
		{

			$res = file_get_contents("/usr/home/finance/projects/cron/licaishi/data/lake_src.txt");
			$stat_str = "";
			$txt_str = "财经频道拉客统计表";
			if ($st == 'month')
			{
				if (date('d') == '01')
					$stat_begin_time = date('Y-m-d 12:00:00', mktime(12, 0,0, date('m')-1, date('d'), date('Y'))); 
				else
					$stat_begin_time = date("Y-m-01 12:00:00");
				$stat_str = " and c_time>'".$stat_begin_time."'";
				$txt_str = "财经频道拉客统计表(从 ".$stat_begin_time." 至今)";
			}

			$rows = explode("\n",$res);
			$excel_array[] = array("组","姓名","拉客认证总数","认证理财师名","已发观点理财师名","已经观点理财师人数","发观点/理财师认证总数");
			foreach($rows as $val){
				$val = trim($val);
				if(!empty($val)){
					$f = explode("\t",$val);
					$apply_num = 0;
					$planner_num = 0;
					$planner = '';
					$active_p = '';
					$view_num = 0;
					if(!empty($f[2])){
						$f[2] = trim($f[2],'"');
						$f[2] = trim($f[2]);
						$f[2] = rtrim($f[2],',');
						$sql = "select s_uid,status from lcs_p_replay where invit_code in ($f[2])";
						//echo $sql,"\n";
						$cmd = Yii::app()->lcs_standby_r->createCommand($sql);
						$res = $cmd->queryAll();

						if(!empty($res)){
							$apply_num = sizeof($res);
							$s_uid = array();
							foreach ($res as $vals){
								$s_uid[] = $vals['s_uid'];
							}
							$sql = "select s_uid,name from lcs_planner where s_uid in(".implode(',',$s_uid).")".$stat_str;
							$res = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
							$planner_num = sizeof($res);
							if($planner_num > 0){
								$p_uid = array();
								$p_map = array();
								foreach($res as $valss){
									$p_map[$valss['s_uid']] = $valss['name'];
									$p_uid[] = $valss['s_uid'];
									$planner .= $valss['name'].',';
								}
								$sql = "select distinct(p_uid) from lcs_view where  p_uid in(".implode(',',$p_uid).")";
								$views = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
								$view_num = sizeof($views);
								if(is_array($views) && sizeof($views) > 0){
									foreach ($views as $v){
										$active_p .= $p_map[$v['p_uid']].',';
									}
								}

							}
						}
					}

					$rate = $planner_num != 0 ? round($view_num/$planner_num, 2) : "--";
					$excel_array[] = array($f[0],$f[1],$planner_num,$planner,$active_p,$view_num,$rate);
				}
			}
			$toexcel = $this->toExcel("拉客表", $txt_str, $excel_array);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}


	/*
	 * 统计各行业每日观点发布情况
	 * @param string
	 */
	public function StatIndData($dt)
	{
		try
		{
			$db_r = Yii::app()->lcs_standby_r;	
			if ($dt == 'today')
			{
				$day_today = date("Y-m-d 12:00:00");
				$day_begin = date("Y-m-d 12:00:00", strtotime($day_today) - 24*3600);
			}
			else
			{
				$day_today = date($dt." 12:00:00");
				$day_begin = date("Y-m-d 12:00:00", strtotime($day_today) - 24*3600);
			}

			$sql = "select ind_id,count(id) as num from lcs_view where c_time>'".$day_begin."' and c_time<'".$day_today."' group by ind_id order by ind_id"; 
			$ind_ret = $db_r->createCommand($sql)->queryAll();

			$ind_array = array("A股","基金","期货","金银油","其他理财","美股","港股","保险");
			$excel_array[] = array("行业",date('Y-m-d')."发布观点数");

			if (is_array($ind_ret))
			{
				foreach($ind_ret as $key => $value)
				{
					$excel_array[] =  array($ind_array[$value['ind_id']-1], $value['num']);
				}
			}

			$txt_str = "各行业今日（".$day_begin."～".$day_today."）观点发布数量：";
			$toexcel = $this->toExcel("行业每日观点数", $txt_str, $excel_array);	
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}
	
	/*
	 * 月统计
	 */
	public function StatMonth($m, $type)
	{
		try
		{
			$db_r = Yii::app()->lcs_standby_r;	
			$month = str_pad($m, 2, "0", STR_PAD_LEFT);
			$ind_excel_array[] = array("日期","A股","基金","期货","金银油","其他理财","美股","港股","保险");
			$view_excel_array[] = array("日期","当日发布观点理财师数","当日发布观点数","当日新增认证理财师");

			for ($i=1; $i<=31; $i++)		
			{
				$days  = str_pad($i, 2, "0", STR_PAD_LEFT);
				$this_date = "2014-".$month."-".$days;
				$day_end = date($this_date." 12:00:00");
				$day_begin = date("Y-m-d 12:00:00", strtotime($day_end) - 24*3600);

				if ($type=='ind')
				{
					//行业每日数据
					$sql = "select ind_id,count(id) as num from lcs_view where c_time>'".$day_begin."' and c_time<'".$day_end."' group by ind_id order by ind_id"; 
					$ind_ret = $db_r->createCommand($sql)->queryAll();	
					if (is_array($ind_ret))
					{
						foreach($ind_ret as $key => $value)
						{
							$ind_line[$value['ind_id']] =  $value['num'];
						}
					}
					for($j=1;$j<9;$j++)
					{
						$ind_line_new[$j] = isset($ind_line[$j]) ? $ind_line[$j] : 0;
					}

					$ind_excel_array[] = array($this_date, $ind_line_new[1],$ind_line_new[2],$ind_line_new[3],$ind_line_new[4],$ind_line_new[5],$ind_line_new[6],$ind_line_new[7],$ind_line_new[8]);
				}
				elseif ($type == 'view')
				{
					//基础数据
					$sql2 = "select count(*) as num from lcs_planner where c_time > '".$day_begin."'";
					$ret2 = $db_r->createCommand($sql2)->queryRow();
					$sql4 = "select count(distinct(p_uid)) as num from lcs_view where p_time > '".$day_begin."' and p_time<'".$day_end."'";
					$ret4 = $db_r->createCommand($sql4)->queryRow();
					$sql6 = "select count(*) as num from lcs_view where p_time > '".$day_begin."' and p_time < '".$day_end."'";
					$ret6 = $db_r->createCommand($sql6)->queryRow();
					$yes_to_now_all = isset($ret2['num']) ? $ret2['num'] : 0;
					$yes_to_now_pub_all = isset($ret4['num']) ? $ret4['num'] : 0;
					$yes_to_now_view_num = isset($ret6['num']) ? $ret6['num'] : 0;
					$view_excel_array[] = array($this_date, $yes_to_now_pub_all, $yes_to_now_view_num,$yes_to_now_all);
				}
			}
			if ($type == "ind")
				$toexcel_ind = $this->toExcel("行业每日观点数", "", $ind_excel_array);
			elseif ($type == 'view')
				$toexcel_view = $this->toExcel("当日基础数据", "", $view_excel_array);

		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}

	/*
	 * 临时导库 
	 * @param string
	 */
	public function TmpOutput()
	{
		$db_r = Yii::app()->lcs_standby_r;		
		$sql = "select p.s_uid, p.name as pname,p.phone,p.cert_number,c.name as cname,p.position_id from lcs_planner p,lcs_certification c where c.id=p.cert_id and p.cert_id in (4,6) order by p.c_time";
		$planner_arr = $db_r->createCommand($sql)->queryAll();
		$sql1 = "select id, name from lcs_position ";
		$ret1 = $db_r->createCommand($sql1)->queryAll();
		$position_arr = array();
		if (is_array($ret1))
		{
			foreach($ret1 as $s=>$y)
			{
				$position_arr[$y['id']] = $y['name'];
			}
		}

		$txt_str = "黄金投资分析师职业资格  和  理财规划师职业资格 理财师";
		$excel_array[] = array('理财师微博ID','姓名','手机号','资格种类','所在岗位','资格证号码');
		if (isset($planner_arr))
		{
			foreach($planner_arr as $kk => $vv)
			{
				$positionname = isset($position_arr[$vv['position_id']])  ? $position_arr[$vv['position_id']] : "--";
				$excel_array[] = array($vv['s_uid'],$vv['pname'],$vv['phone'],$vv['cname'],$positionname,"'".$vv['cert_number']."'");

			}
		}
		$toexcel = $this->toExcel("理财师_错误", $txt_str, $excel_array);
	}

    /**
     * 导出理财师银行卡信息
     */
	public function ExportPlannerBankInfo(){
		$location_code = array("北京市"=>"010","上海市"=>"021","天津市"=>"022","重庆市"=>"023","香港"=>"852","澳门"=>"853","邯郸市"=>"0310","石家庄"=>"0311","保定市"=>"0312","张家口"=>"0313","承德市"=>"0314","唐山市"=>"0315","廊坊市"=>"0316","沧州市"=>"0317","衡水市"=>"0318","邢台市"=>"0319","秦皇岛"=>"0335","衢州市"=>"0570","杭州市"=>"0571","湖州市"=>"0572","嘉兴市"=>"0573","宁波市"=>"0574","绍兴市"=>"0575","台州市"=>"0576","温州市"=>"0577","丽水市"=>"0578","金华市"=>"0579","舟山市"=>"0580","沈阳市"=>"024","铁岭市"=>"0410","大连市"=>"0411","鞍山市"=>"0412","抚顺市"=>"0413","本溪市"=>"0414","丹东市"=>"0415","锦州市"=>"0416","营口市"=>"0417","阜新市"=>"0418","辽阳市"=>"0419","朝阳市"=>"0421","盘锦市"=>"0427","葫芦岛"=>"0429","武汉市"=>"027","襄城市"=>"0710","鄂州市"=>"0711","孝感市"=>"0712","黄州市"=>"0713","黄石市"=>"0714","咸宁市"=>"0715","荆沙市"=>"0716","宜昌市"=>"0717","恩施市"=>"0718","十堰市"=>"0719","随枣市"=>"0722","荆门市"=>"0724","江汉市"=>"0728","南京市"=>"025","无锡市"=>"0510","镇江市"=>"0511","苏州市"=>"0512","南通市"=>"0513","扬州市"=>"0514","盐城市"=>"0515","徐州市"=>"0516","淮阴市"=>"0517","淮安市"=>"0517","连云港"=>"0518","常州市"=>"0519","泰州市"=>"0523","海拉尔"=>"0470","呼和浩特"=>"0471","包头市"=>"0472","乌海市"=>"0473","集宁市"=>"0474","通辽市"=>"0475","赤峰市"=>"0476","东胜市"=>"0477","临河市"=>"0478","锡林浩特"=>"0479","乌兰浩特"=>"0482","阿拉善左旗"=>"0483","新余市"=>"0790","南昌市"=>"0791","九江市"=>"0792","上饶市"=>"0793","临川市"=>"0794","宜春市"=>"0795","吉安市"=>"0796","赣州市"=>"0797","景德镇"=>"0798","萍乡市"=>"0799","鹰潭市"=>"0701","忻州市"=>"0350","太原市"=>"0351","大同市"=>"0352","阳泉市"=>"0353","榆次市"=>"0354","长治市"=>"0355","晋城市"=>"0356","临汾市"=>"0357","离石市"=>"0358","运城市"=>"0359","临夏市"=>"0930","兰州市"=>"0931","定西市"=>"0932","平凉市"=>"0933","西峰市"=>"0934","武威市"=>"0935","张掖市"=>"0936","酒泉市"=>"0937","天水市"=>"0938","甘南州"=>"0941","白银市"=>"0943","菏泽市"=>"0530","济南市"=>"0531","青岛市"=>"0532","淄博市"=>"0533","德州市"=>"0534","烟台市"=>"0535","淮坊市"=>"0536","济宁市"=>"0537","泰安市"=>"0538","临沂市"=>"0539","阿城市"=>"0450","哈尔滨"=>"0451","齐齐哈尔"=>"0452","牡丹江"=>"0453","佳木斯"=>"0454","绥化市"=>"0455","黑河市"=>"0456","加格达奇"=>"0457","伊春市"=>"0458","大庆市"=>"0459","福州市"=>"0591","厦门市"=>"0592","宁德市"=>"0593","莆田市"=>"0594","泉州市"=>"0595","晋江市"=>"0595","漳州市"=>"0596","龙岩市"=>"0597","三明市"=>"0598","南平市"=>"0599","广州市"=>"020","韶关市"=>"0751","惠州市"=>"0752","梅州市"=>"0753","汕头市"=>"0754","深圳市"=>"0755","珠海市"=>"0756","佛山市"=>"0757","肇庆市"=>"0758","湛江市"=>"0759","中山市"=>"0760","河源市"=>"0762","清远市"=>"0763","顺德市"=>"0765","云浮市"=>"0766","潮州市"=>"0768","东莞市"=>"0769","汕尾市"=>"0660","潮阳市"=>"0661","阳江市"=>"0662","揭西市"=>"0663","成都市"=>"028","涪陵市"=>"0810","重庆市"=>"0811","攀枝花"=>"0812","自贡市"=>"0813","永川市"=>"0814","绵阳市"=>"0816","南充市"=>"0817","达县市"=>"0818","万县市"=>"0819","遂宁市"=>"0825","广安市"=>"0826","巴中市"=>"0827","泸州市"=>"0830","宜宾市"=>"0831","内江市"=>"0832","乐山市"=>"0833","西昌市"=>"0834","雅安市"=>"0835","康定市"=>"0836","马尔康"=>"0837","德阳市"=>"0838","广元市"=>"0839","泸州市"=>"0840","岳阳市"=>"0730","长沙市"=>"0731","湘潭市"=>"0732","株州市"=>"0733","衡阳市"=>"0734","郴州市"=>"0735","常德市"=>"0736","益阳市"=>"0737","娄底市"=>"0738","邵阳市"=>"0739","吉首市"=>"0743","张家界"=>"0744","怀化市"=>"0745","永州冷"=>"0746","商丘市"=>"0370","郑州市"=>"0371","安阳市"=>"0372","新乡市"=>"0373","许昌市"=>"0374","平顶山"=>"0375","信阳市"=>"0376","南阳市"=>"0377","开封市"=>"0378","洛阳市"=>"0379","焦作市"=>"0391","鹤壁市"=>"0392","濮阳市"=>"0393","周口市"=>"0394","漯河市"=>"0395","驻马店"=>"0396","三门峡"=>"0398","昭通市"=>"0870","昆明市"=>"0871","大理市"=>"0872","个旧市"=>"0873","曲靖市"=>"0874","保山市"=>"0875","文山市"=>"0876","玉溪市"=>"0877","楚雄市"=>"0878","思茅市"=>"0879","景洪市"=>"0691","潞西市"=>"0692","东川市"=>"0881","临沧市"=>"0883","六库市"=>"0886","中甸市"=>"0887","丽江市"=>"0888","滁州市"=>"0550","合肥市"=>"0551","蚌埠市"=>"0552","芜湖市"=>"0553","淮南市"=>"0554","马鞍山"=>"0555","安庆市"=>"0556","宿州市"=>"0557","阜阳市"=>"0558","黄山市"=>"0559","淮北市"=>"0561","铜陵市"=>"0562","宣城市"=>"0563","六安市"=>"0564","巢湖市"=>"0565","贵池市"=>"0566","银川市"=>"0951","石嘴山"=>"0952","吴忠市"=>"0953","固原市"=>"0954","长春市"=>"0431","吉林市"=>"0432","延吉市"=>"0433","四平市"=>"0434","通化市"=>"0435","白城市"=>"0436","辽源市"=>"0437","松原市"=>"0438","浑江市"=>"0439","珲春市"=>"0440","防城港"=>"0770","南宁市"=>"0771","柳州市"=>"0772","桂林市"=>"0773","梧州市"=>"0774","玉林市"=>"0775","百色市"=>"0776","钦州市"=>"0777","河池市"=>"0778","北海市"=>"0779","贵阳市"=>"0851","遵义市"=>"0852","安顺市"=>"0853","都均市"=>"0854","凯里市"=>"0855","铜仁市"=>"0856","毕节市"=>"0857","六盘水"=>"0858","兴义市"=>"0859","西安市"=>"029","咸阳市"=>"0910","延安市"=>"0911","榆林市"=>"0912","渭南市"=>"0913","商洛市"=>"0914","安康市"=>"0915","汉中市"=>"0916","宝鸡市"=>"0917","铜川市"=>"0919","西宁市"=>"0971","海东市"=>"0972","同仁市"=>"0973","共和市"=>"0974","玛沁市"=>"0975","玉树市"=>"0976","德令哈"=>"0977","儋州市"=>"0890","海口市"=>"0898","三亚市"=>"0899","拉萨市"=>"0891","日喀则"=>"0892","山南市"=>"0893","乌鲁木齐市"=>"0991","克拉玛依市"=>"0990","吐鲁番地区"=>"0995","哈密地区"=>"0902","昌吉回族自治州"=>"0994","博尔塔拉蒙古自治州"=>"0909","巴音郭楞蒙古自治州"=>"0996","阿克苏地区"=>"0997","克孜勒苏柯尔克孜自治州"=>"0908","喀什地区"=>"0998","和田地区"=>"0903","伊犁哈萨克自治州"=>"0999","塔城地区"=>"0901","阿勒泰地区"=>"0906");
		$s_uid = "(1000712807,1007272242,1014989264,1026254961,1026334481,1027455973,1030855062,1047728007,1047780631,1047912533,1049032861,1063694297,1075556514,1079389303,1091917711,1095939213,1103881382,1116724044,1136867923,1148776637,1149778013,1157658273,1162085032,1163206782,1164008050,1170446465,1212596892,1213188214,1221819937,1221998720,1222214343,1230759713,1235581170,1237443863,1239417764,1255130500,1259491593,1267337881,1267562884,1278118843,1287093631,1304532452,1320507971,1326281731,1336073953,1336897485,1361317865,1368777231,1374482241,1401126361,1402814403,1426345024,1443571992,1444531442,1449880822,1450945982,1453016257,1456285801,1458785671,1465042921,1506197863,1536205605,1543842185,1557623697,1558463007,1559666657,1561647551,1562682837,1562994374,1569179292,1572376091,1577660782,1578739417,1590382765,1593981223,1599335331,1601479843,1603147504,1605593470,1609469470,1615998150,1623562535,1623748143,1625542770,1635618475,1639376232,1642681683,1646404082,1647363587,1649008381,1651654985,1651726735,1652144340,1653714293,1653772521,1655163410,1657765690,1658754931,1659188395,1670404053,1677972383,1680447540,1680621737,1684200315,1686814741,1694981395,1697340633,1698032973,1698798217,1700099301,1701217130,1706872571,1711157941,1712512974,1722446470,1723959730,1725183933,1725614664,1728689901,1729928632,1732759982,1743049167,1762216851,1765781047,1770420792,1773751933,1773920424,1775355313,1785834924,1787502387,1791375820,1796603757,1798938121,1804544777,1807004440,1812123862,1813592103,1821177901,1828057330,1830340692,1841806407,1849532175,1853846724,1859176970,1860333920,1866955222,1870613733,1875822527,1877585645,1878557142,1878877402,1879731170,1885546462,1886168793,1887555373,1891946851,1900494685,1901507073,1901993317,1904236340,1913087902,1919170037,1919951931,1921278140,1924124741,1928138782,1931153540,1932791611,1943386355,1948464735,1950769265,1963145094,1974434247,1975482567,1976775624,1992384760,1993970341,2000918304,2005207531,2011230053,2017564545,2038995070,2043616061,2047166073,2069940943,2077224802,2080719421,2091102674,2105742822,2108549195,2111370255,2126592000,2127778450,2129315490,2153244301,2160702963,2174782647,2177007684,2181027713,2183699314,2185879262,2190448107,2204014491,2205708840,2206052525,2219791173,2230665195,2235132562,2244751674,2253554863,2270146067,2271516673,2272009295,2273318077,2274075561,2311430154,2312682605,2315128847,2325051187,2351063507,2368224831,2373488354,2373637277,2391665097,2400257041,2404068233,2408750860,2430429404,2433071021,2436963121,2457681405,2461446103,2474040954,2476909985,2489840034,2526703083,2543520855,2548806315,2549981715,2562486790,2610578057,2611542243,2620383855,2635934313,2639902422,2648161040,2651241970,2653137880,2669010251,2702196524,2711473207,2722589421,2729188171,2740406095,2788120035,2796594834,2808662004,2816173194,2834295264,2848635522,2860256671,2902520775,2926842762,2941018262,2951684571,2998820457,3005738131,3014303841,3025397074,3040436650,3047909241,3053620305,3053974207,3054484522,3059247927,3095874413,3099263271,3100159033,3100796395,3105972913,3106256745,3155015332,3172941067,3183888650,3208007370,3215877627,3216792272,3218778153,3221307142,3252613607,3459566260,3467291190,3484281273,3508565954,3514653251,3514768932,3516113863,3523970600,3530482407,3535394087,3556296294,3568324631,3583878272,3608437534,3657566937,3658205321,3672428027,3672658521,3698264602,3700332043,3817369006,3858879947,3904863517,3907431007,3947649761,5033106156,5058053444,5066303597,5092401443,5114983820,5114989410,5115221371,5119788110,5122548730,5124312649,5124682449,5125623866,5133601508,5133695225,5150067851,5154638027,5177061992,5182416630,5183698186,5183698870,5189441063,5196268271,5199244646,5200715146,5201002004,5203688943,5207743558,5212873118,5213647728,5215137497,5257629956,5264519800,5275445907,5295669143,5303346161,5305159598,5353193181,5365028103,5379806568,5382032698,5412435187,5426369697,5458208173,5493202550,5496503969,5517288747,5517425274,5530051885,5530392853,5553609378,5591939542,5594208889,5601960383)";

		$sql = "select id,uid,u_type,card_number,name,id_number,province,city,location,bank_name,branch_name,c_time,u_time from lcs_bank_card WHERE uid in $s_uid";
		$cards = Yii::app()->lcs_r->createCommand($sql)->queryAll();

		$excel_arr[] = array(
			'p_uid','writername', 'dept',
			'cardtype', 'idcard',
			'bankname', 'bankaccount',
			'bankprovince', 'bankcity',
			'city',
			'bankareacode',
			'hasofficeposition',
			'hasotherwork',
			'othercompany',
			'hasinsurance',
			'insurancecompany',
			'isstudent',
			'ingreat',
			'workdescription'
		);

		foreach($cards as $card){
			$location = explode("-",$card['location']);
			$excel_arr[] = array(
				$card['uid'],
				$card['name'], 9,
				1, "'".$card['id_number'],
				$card['bank_name']=='招商银行' ? $card['bank_name'] : $card['bank_name']." ".$card['branch_name'],
				"'".$card['card_number'],
				isset($location[0]) ? str_replace(array('省','市','县'),'',$location[0]) : '',
				isset($location[1]) ? str_replace(array('省','市','县'),'',$location[1]) : '',
				isset($location[2]) ? str_replace(array('省','市','县'),'',$location[2]) : '',
				array_key_exists($location[1],$location_code) ? "'".(strlen($location_code[$location[1]])<4 ? '0'.$location_code[$location[1]] : $location_code[$location[1]]) : 0,
				0,
				0,
				0,
				0,
				0,
				0,
				0,
				'理财师'
			);
	}

	$toexcel = $this->toExcel("理财师_银行卡信息","理财师_银行卡信息", $excel_arr);

	}


	private function concatArr($arr)
	{
		$str = "";
		if (is_array($arr))
		{
			foreach($arr as $k=>$v)
			{
				$str .= $v."   |   ";
			}
		}
		return $str;
	}

	private function company()
	{
		$db_r = Yii::app()->lcs_r;		
		$sql1 = "select id,name from lcs_company ";
		$ret1 = $db_r->createCommand($sql1)->queryAll();
		$company_arr = array();
		if (is_array($ret1))
		{
			foreach($ret1 as $s=>$y)
			{
				$company_arr[$y['id']] = $y['name'];
			}
		}
		return $company_arr;
	}

	private function toExcel($filename,$title,  $arr)
	{
		try
		{
		$objxls = new PHPExcel();
		$objxls->setActiveSheetIndex(0);
		$active = $objxls->getActiveSheet();
		$col_symbol = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');

		$active->setCellValue('A1', $title);	
		if (is_array($arr))
		{
			foreach ($arr as $k => $v)
			{
				$col_num = count($v);
				for ($i=0; $i<$col_num; $i++)
				{
					$active->setCellValue($col_symbol[$i].($k+2), $v[$i]);
				}
			}
		}

		header('Content-Type : application/vnd.ms-excel');
		header('Content-Disposition:attachment;filename="'.$filename.'"');
		$objWriter= PHPExcel_IOFactory::createWriter($objxls,'Excel5');
		$objWriter->save('php://output');
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}

	public function SendMail2($at)
	{
		try
		{
		$week_zh = array('一','二','三','四','五','六','日');
		$title = date("Y-m-d"). "（周".$week_zh[date('N')-1]."） 统计数据";
		$msg = date("Y-m-d H:i:s");
		$tos = array("zhangqi4@staff.sina.com.cn","zhaochen2@staff.sina.com.cn","xingyu1@staff.sina.com.cn","songyao@staff.sina.com.cn","guobing1@staff.sina.com.cn","weiguang3@staff.sina.com.cn","liyong3@staff.sina.com.cn");
		$attachs = array($at);
		$sm = new NewSendMail($title, $msg, $tos, $attachs);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}
    

}
