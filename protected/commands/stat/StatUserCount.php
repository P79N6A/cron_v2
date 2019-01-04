<?php
/*
 *  @Purpose : 给运营统计付费用户的数量，各功能模块独立和重合的
 *  @Author songyao
 */

class StatUserCount
{
	const CRON_NO = 8002; //任务代码

	public function __construct()
	{
	}
	/**
	 * 统计独立用户数 
	 *
	 */
	public function DistinctUser()
	{
		try{
			$txt_str = "付费用户统计 截至23:55分";
			$excel_array[] = array("日期","观点包付费独立用户数","单条观点付费独立用户数","观点包与单条重合独立用户数","提问付费独立用户数","解锁付费独立用户数","提问和解锁付费重合的独立用户数","计划付费独立用户数","观点+问答付费重合独立用户数","观点+计划付费重合独立用户数","计划+问答付费重合独立用户数","计划+问答+观点付费重合独立用户数");

			$this_date = strtotime("-1 week",strtotime(date("Y-m-d")));
			$end_date = strtotime(date("Y-m-d"));
			while ($this_date <= $end_date)
			{
				$date = date("Y-m-d",$this_date);
				$num = array();
				$num[] = $date;
				$num[] = StatUser::model()->countDistinctUser(31, $date);//观点包付费独立用户数
				$num[] = StatUser::model()->countDistinctUser(32, $date);//观点付费独立用户数
				$num[] = StatUser::model()->countDistinctUserCover('view', $date);//观点包和观点付费重合独立用户数
				$num[] = StatUser::model()->countDistinctUser(11, $date);//提问付费独立用户数
				$num[] = StatUser::model()->countDistinctUser(12, $date);//解锁付费独立用户数
				$num[] = StatUser::model()->countDistinctUserCover('ask', $date);//提问和解锁付费重合的独立用户数
				$num[] = StatUser::model()->countDistinctUser(21, $date);//计划付费独立用户数
				$num[] = StatUser::model()->countDistinctUserCover('viewask', $date);//观点+问答付费重合独立用户数
				$num[] = StatUser::model()->countDistinctUserCover('viewplan', $date);//观点+计划付费重合独立用户数
				$num[] = StatUser::model()->countDistinctUserCover('planask', $date);//计划+问答付费重合独立用户数
				$num[] = StatUser::model()->countDistinctUserCover('viewplanask', $date);//计划+问答+观点付费重合独立用户数

				$this_date = strtotime("+1 days",$this_date);
				$excel_array[] = $num;
			}

			$toexcel = $this->toExcel("基础数据", $txt_str, $excel_array);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}

	public function DistinctUserToday()
	{
		try
		{
			$txt_str = "付费用户当日统计 截至23:55分";
			$excel_array[] = array("日期","观点包当日付费独立用户数","观点包当前时刻未到期独立用户数","单条观点当日付费独立用户数","观点包与单条当日重合独立用户数","观点包未到期与单条观点当日重合独立用户数","提问付费当日独立用户数","解锁当日付费独立用户数","提问和解锁当日付费重合的独立用户数","计划付费当日独立用户数","当日运行中计划的独立付费用户数","当日待运行计划的独立付费用户数","运行中与待运行重合独立付费用户数","观点+问答当日付费重合独立用户数","观点+计划当日付费重合独立用户数","计划+问答付费当日重合独立用户数","计划+问答+观点付费当日重合独立用户数");

			$this_date = strtotime("-1 week",strtotime(date("Y-m-d")));
			$end_date = strtotime(date("Y-m-d"));
			while ($this_date <= $end_date)
			{
				$date = date("Y-m-d",$this_date);
				$num = array();
				$num[] = $date;
				$num[] = StatUser::model()->countDistinctUser(31, $date,1);//观点包付费独立用户数
				$num[] = StatUser::model()->countOtherUser('packagesub', $date);//观点包当前时刻未到期独立用户数
				$num[] = StatUser::model()->countDistinctUser(32, $date,1);//观点付费独立用户数
				$num[] = StatUser::model()->countDistinctUserCover('view', $date,1);//观点包和观点付费重合独立用户数
				$num[] = StatUser::model()->countOtherUser('packagesubview', $date);//观点包未到期与单条观点当日重合独立用户数
				$num[] = StatUser::model()->countDistinctUser(11, $date,1);//提问付费独立用户数
				$num[] = StatUser::model()->countDistinctUser(12, $date,1);//解锁付费独立用户数
				$num[] = StatUser::model()->countDistinctUserCover('ask', $date,1);//提问和解锁付费重合的独立用户数
				$num[] = StatUser::model()->countDistinctUser(21, $date,1);//计划付费独立用户数
				$num[] = StatUser::model()->countOtherUser('runplan', $date);//当日运行中计划的独立付费用户数
				$num[] = StatUser::model()->countOtherUser('beforerunplan', $date);//当日待运行计划的独立付费用户数
				$num[] = StatUser::model()->countOtherUser('beforeandrun', $date);//运行中与待运行重合独立付费用户数
				$num[] = StatUser::model()->countDistinctUserCover('viewask', $date,1);//观点+问答付费重合独立用户数
				$num[] = StatUser::model()->countDistinctUserCover('viewplan', $date,1);//观点+计划付费重合独立用户数
				$num[] = StatUser::model()->countDistinctUserCover('planask', $date,1);//计划+问答付费重合独立用户数
				$num[] = StatUser::model()->countDistinctUserCover('viewplanask', $date,1);//计划+问答+观点付费重合独立用户数

				$this_date = strtotime("+1 days",$this_date);
				$excel_array[] = $num;
			}

			$toexcel = $this->toExcel("基础数据", $txt_str, $excel_array);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}

	//统计每月的付费用户数，临时统计
	public function DistinctUserMonth()
	{
		try{
			$txt_str = "";
			$excel_array[] = array("月份","观点包付费独立用户数","单条观点付费独立用户数","观点包与单条重合独立用户数","提问付费独立用户数","解锁付费独立用户数","提问和解锁付费重合的独立用户数","计划付费独立用户数","观点+问答付费重合独立用户数","观点+计划付费重合独立用户数","计划+问答付费重合独立用户数","计划+问答+观点付费重合独立用户数");

				$num = array();
				$num[] = "8月";
				$num[] = StatUserMonth::model()->countDistinctUser(31);//观点包付费独立用户数
				$num[] = StatUserMonth::model()->countDistinctUser(32);//观点付费独立用户数
				$num[] = StatUserMonth::model()->countDistinctUserCover('view');//观点包和观点付费重合独立用户数
				$num[] = StatUserMonth::model()->countDistinctUser(11);//提问付费独立用户数
				$num[] = StatUserMonth::model()->countDistinctUser(12);//解锁付费独立用户数
				$num[] = StatUserMonth::model()->countDistinctUserCover('ask');//提问和解锁付费重合的独立用户数
				$num[] = StatUserMonth::model()->countDistinctUser(21);//计划付费独立用户数
				$num[] = StatUserMonth::model()->countDistinctUserCover('viewask');//观点+问答付费重合独立用户数
				$num[] = StatUserMonth::model()->countDistinctUserCover('viewplan');//观点+计划付费重合独立用户数
				$num[] = StatUserMonth::model()->countDistinctUserCover('planask');//计划+问答付费重合独立用户数
				$num[] = StatUserMonth::model()->countDistinctUserCover('viewplanask');//计划+问答+观点付费重合独立用户数

				$excel_array[] = $num;

			$toexcel = $this->toExcel("基础数据", $txt_str, $excel_array);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
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

	public function SendMail($at)
	{
		try
		{
			$week_zh = array('一','二','三','四','五','六','日');
			$title = date("Y-m-d"). "（周".$week_zh[date('N')-1]."） 付费用户统计数据";
			$msg = date("Y-m-d H:i:s");
			$tos = array("zhangqi4@staff.sina.com.cn","zhaochen2@staff.sina.com.cn","xingyu1@staff.sina.com.cn","songyao@staff.sina.com.cn");
			$attachs = array($at);
			$sm = new NewSendMail($title, $msg, $tos, $attachs);
		}
		catch (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}


}
