<?php

class ExportPass
{
	const CRON_NO = ''; //任务代码

	public function __construct()
	{

	}

	public function Exports()
	{
		//获取童笑笑的审核信息
		$this->ShenHeInfo('童笑笑');
		$this->ShenHeInfo('李佳晨');
		$this->ShenHeInfo('巩志昊');
		$this->ShenHeInfo('周丽华');
	}

	private function shenHeInfo($staff_uid){
		$db_r = Yii::app()->lcs_standby_r;
		$start_time=date('Y-m-01',strtotime('-1 month'));
        $end_time=date('Y-m-01',strtotime(date("Y-m-d")));
		$sql = "select id,staff_uid,category,opt_data from lcs_manage_opt_log where staff_uid='$staff_uid' and c_time>='".$start_time."' and c_time<'".$end_time."' and (category='circle/pass' or category='circle/disabled')";
		$data = $db_r->createCommand($sql)->queryAll();
		$shenhe=0;
		$pass=0;
		$nopass=0;
		$disabled=0;
		if(!empty($data)){
			foreach($data as $v){
                $opt_data= json_decode($v['opt_data'],true);
                if(isset($opt_data['is_good'])&&$opt_data['is_good']==0){
                	if(is_array($opt_data['ids'])){
                        $shenhe+=count($opt_data['ids']);
                        $pass+=count($opt_data['ids']);
                	}else{
                    
                		$shenhe++;
                		$pass++;
                	}

                }else if(isset($opt_data['is_good'])&&$opt_data['is_good']==1){
                	if(is_array($opt_data['ids'])){
                        $shenhe+=count($opt_data['ids']);
                        $nopass+=count($opt_data['ids']);
                	}else{
                		$shenhe++;
                		$nopass++;
                	}

                }else{
                	$shenhe++;
                	$disabled++;
                }
			}
		}
        echo $staff_uid.'---'.$shenhe.'---'.$pass.'---'.$nopass.'---'.$disabled .'</br>';
	}
}