<?php

/**
* 新增用户尊享号
*/
class AddSpecial
{
	
	//任务代码
	const CRON_NO=14041 ;
	/**
	 * 入口
	 */
	public function AddUsersSpecialInfo(){
		$end = time() + 50;
        	$userInfo='';
		while(time()<$end){
			$uids=array();
            		$end2 = time() + 3;
			while(time()<$end2){
				$key = "lcs_sp_user_number_add_list";
                		$uid = Yii::app()->redis_w->lpop($key);
				if($uid){
					$uids[]=$uid;
				}
			}
			echo '<pre>';var_dump($uids);
			if(empty($uids)){
				sleep(2);
				continue;
			}
			if(!empty($uids)){
				$uids = array_unique($uids);
				foreach($uids as $uid){
					if(!is_numeric($uid)){
						continue;
					}
					$userInfo= User::model()->getUserInfo($uid);
					if(!empty($userInfo)){
						UserSpecial::model()->saveSpecialInfoByUid($uid);
					}
		
				}
				
			}		
			
		}
		
	}
	
}
