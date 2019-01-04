<?php
/**
 * 观点包开始收费  update by zwg 20160215 停止使用  观点包收费审核通过后 直接向普通消息队列发送
 */
class PkgCharge {
	
	const CRON_NO = 1311; //任务代码
	
	public function __construct(){
	
	}
	/**
	 * 每5分钟执行一次
	 */
	public function sendMsg() {
		$redis_key = "lcs_package_charge_message";
		
		while($pkg_id = Yii::app()->redis_w->lPop($redis_key)){
			$package = Package::model()->getPackagesById($pkg_id);
			$package = isset($package[$pkg_id]) ? $package[$pkg_id] : array();
			
			if(!empty($package)) {
				//$uids = Package::model()->getCollectUid($pkg_id);
				//个性化 去掉关闭提醒的uid
				//$uids = MessageUserClose::model()->filterCloseUids($uids,1,3,1);
				//if(!empty($uids)) {
					
                    //foreach($uids as $uid) {
                        $push_data = array(
                            'type' => 'packageCharge',
                            'pkg_id' => $pkg_id,
                        );
                        Yii::app()->redis_w->rPush("lcs_common_message_queue", json_encode($push_data));
                    //}//end foreach.
					
				//}
			}//end if.
			
		}//end while
		
	}
	
	
}
