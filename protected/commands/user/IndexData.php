<?php
/**
 * 保存用户信息到es
 */
class IndexData
{

	//任务代码
	const CRON_NO=14007 ;
	/**
	 * 入口
	 */
	public function SaveUsers(){		
		$end = time() + 50;
		while(time()<$end){
			$key = "lcs_u_es";
			$uid = Yii::app()->redis_w->lpop($key);
			if(!$uid){
				sleep(2);
				continue;
			}
			$userInfo= User::model()->getUserInfo($uid);
			$userInfo['c_time']=date(DATE_RFC3339,strtotime($userInfo['c_time']));
			$userInfo['u_time']=date(DATE_RFC3339,strtotime($userInfo['u_time']));
			$userInfo['client_time']=date(DATE_RFC3339,strtotime($userInfo['client_time']));
			$userInfo['r_time']=date(DATE_RFC3339,strtotime($userInfo['r_time']));
			$userInfo['name_u_time']=date(DATE_RFC3339,strtotime($userInfo['name_u_time']));
			$type_name=Common::TYPE_USER_NAME;
			$index_name=Common::INDEX_USER_NAME;
			$data= json_encode($userInfo) ;
			CommonUtils::esdata($index_name,$type_name,$data);
		}
		
	}
}