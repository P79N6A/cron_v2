<?php 
/**
 * 定时任务:
 * User: liyong3
 * Date: 2015-10-29
 */

class ProcessNotice {


    const CRON_NO = 1301; //任务代码

    public function __construct(){

    }


    /**
     * @throws LcsException
     */
    public function Process(){
        try {
        	$redis_w = Yii::app()->redis_w;
        	$success_ids = array();
        	$data = OperateNotice::model()->getNoticeByStatus(OperateNotice::PUSH_TYPE_INIT);
        	foreach ($data as $info) {
                ///webpush推送
                if($info['phone_type'] == 4 || $info['phone_type']==0){
                    $this->webPush(array("type"=>"webpush","data"=>$info));
                    $success_ids[] = $info['id'];
                    if($info['phone_type']==4){
                        continue;
                    }
                }

        	    if($info['push_type'] == 14){
        	        $relation_id = $info['id'];
        	    }else{
        	        $relation_id = $info['target_id'];
        	    }        	    
        	    $msg = array(
                    'notice_id' => $info['id'],
        	        'type' => 'operateNotice',
        	        'notice_type' => $info['push_type'],
        	        'content' => $info['content'],
        	        'relation_id' => $relation_id,
        	        'url' => $info['target_url'],
        	        'uids' => $info['account_id'],// 指定推送用户
        	        'channel' => $info['phone_type'],//手机类型 0全部 
        	        'title' => $info['title'],//标题
        	        'image' => $info['image'], //封面图
        	        'u_type' => $info['u_type'] //用户类型 
        	    );
        	
        		$rs = $redis_w->rPush("lcs_common_message_queue",json_encode($msg, JSON_UNESCAPED_UNICODE));
        		if($rs) {
        			$success_ids[] = $info['id'];  //推送成功的id
        		}
        	}//end foreach.
        	if(!empty($success_ids)) {
        		//推送成功的，改为‘已推送’
        		OperateNotice::model()->setPushState($success_ids);
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, json_encode($success_ids));
        	}
        }catch (Exception $e) {
        	throw LcsException::errorHandlerOfException($e);
        }
    }

    private function webPush($info){
        if(defined('ENV')&&ENV=='dev'){
            $url = "http://192.168.48.225:9192/1/push/room?rid=1";
        }else{
            $url = "http://47.94.221.207:9192/1/push/room?rid=1";
        }
        $param = json_encode($info);
        $headers = array(
            "Content-Type: text/plain; charset=utf-8"
            );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        try{
            $result = curl_exec($ch);
            Common::model()->saveLog("webpush推送完成".$param.$result,"info","webpush");
            return true;
        }catch(Exception $e){
            Common::model()->saveLog("webpush推送成功".$e->getMessage().$param,"error","webpush");
            return false;
        }
    }
}
