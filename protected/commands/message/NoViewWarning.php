<?php 
/**
 * 定时任务:
 * User: liyong3
 * Date: 2015-10-29
 */

class NoViewWarning {


    const CRON_NO = 1302; //任务代码

    public function __construct(){

    }


    /**
     * 5天未发布收费观点的警告短信
     * 每天执行。
     * @throws LcsException
     */
    public function process(){
        try {
        	//TODO 从观点包表查已经开始收费的观点包的作者
        	$p_uids = Package::model()->getNoViewUpdatedPuids(5);
        	$s = 0;  //成功数
        	$f = 0;  //失败数
        	$f_rs = array();
        	if(!empty($p_uids)) {
        		//TODO 获取理财师信息
        		$p_infos = Planner::model()->getPlannerById($p_uids);
        		$content = "尊敬的理财师，您已经连续5天未发布新的付费观点了，为了保证您的观点包服务质量，减少不必要的用户投诉及退款，请您按时发布新的付费观点。详询021-36129996【新浪理财师】。";
        		$content_gb = iconv("UTF-8", "GB2312//IGNORE", $content);
        		foreach ($p_infos as $p_info) {
        			$phone = $p_info['phone'];
        			$rs = CommonUtils::sendSms($phone, urlencode($content_gb));
        			if($rs > 0) {
        				$s++;
        			}else{
        				$f++;
        				$f_rs[$phone] = $rs;
        			}
        		}
        	}
        	if($f > 0) {
        		Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '成功:'.$s.';'.'失败:'.$f.';'.json_encode($f_rs));
        	}else{
        		Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '成功:'.$s.';'.'失败:'.$f.';');
        	}
        	
        }catch (Exception $e) {
        	throw LcsException::errorHandlerOfException($e);
        }
    }

}