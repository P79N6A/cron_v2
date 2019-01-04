<?php

/**
 * Desc  : 
 * Author: meixin
 * Date  : 2015-11-13 16:49:52
 */
class PayAccount {

	const CRON_NO = 8003; //任务代码

	public function insertPayAccount(){
		$fpath = DATA_PATH."/payAccount";
		if(!is_dir($fpath)){
			$res = CommonUtils::createPath(DATA_PATH, '/payAccount');
			
		}
		$fname = "/pay_".date('Ymd').".txt";
		$handle = fopen ($fpath.$fname,"r");
		$insert_num = 0;
		$sql = "";
		$i = 1 ;
		while ($data = fgets($handle, 1000)) {
			#var_dump($data);exit;
			$data = eval('return '.iconv('gbk','utf-8',var_export($data,true)).';');
			$data = explode("\t" , trim($data));
			$num = count ($data);
			#print_r($data);
			#exit;
			$insert_vals = "('";
			for ($c = 1; $c < $num; $c++) {
				if($c==$num-1){
					$insert_vals = $insert_vals.$data[$c]."'),";
					$sql .= $insert_vals; 
					break;
				}
				$insert_vals = $insert_vals.$data[$c]."','";
			}
			if($i == 2000){
				$sql = substr($sql, 0 , -1);
				$insert_num += TestPayAccount::model() -> insertAccountDetail($sql);	
				$sql = "";	
			} 
			$i++;
		}
		fclose($handle);
		$sql = substr($sql, 0 , -1);
		$insert_num += TestPayAccount::model() -> insertAccountDetail($sql);	
		$result['insert_num'] = $insert_num;
		#print_r($result);
		return json_encode($result);
	} 
}
