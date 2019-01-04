<?php

class ExportTouJiaoData
{
	const CRON_NO = ''; //任务代码

	public function __construct()
	{

	}

	public function Exports()
	{
		$txt_str = "统计后台开通VIP客户数量";
		$excel_array[] = array('日期','史月波','王健');
		$db_r = Yii::app()->lcs_r;		
		$sql = "select id,price,uid,p_uid,c_time from lcs_set_subscription where price=0 order by id asc limit 1";
		$data = $db_r->createCommand($sql)->queryRow();
		$c_time=date("Y-m-d",strtotime($data['c_time']));
		for($i=0;strtotime($c_time)<=strtotime('2018-04-03');$i++){
			$sql = "select count(id) from lcs_set_subscription where price=0 and p_uid=1451326947 and c_time>='".$c_time."' and c_time <='".$c_time." 23:59:59'";
			$sybcount = $db_r->createCommand($sql)->queryScalar();
			$sql = "select count(id) from lcs_set_subscription where price=0 and p_uid=6150188584 and c_time>='".$c_time."' and c_time <='".$c_time." 23:59:59'";
			$wjcount = $db_r->createCommand($sql)->queryScalar();
			$excel_array[]=array($c_time,$sybcount,$wjcount);
			$c_time=strtotime($c_time);
			$c_time=date('Y-m-d',strtotime("+1 day",$c_time));	

		}
		$toexcel = $this->toExcel("统计后台开通VIP客户数量", $txt_str, $excel_array);
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
}