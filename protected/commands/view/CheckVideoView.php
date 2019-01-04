<?php 
/**
 * 定时任务:
 * Date: 2016-04-14
 */

class CheckVideoView {


    const CRON_NO = 1207; //任务代码

    public function __construct(){

    }

    /**
     * 
     * @author liyong3
     * @throws LcsException
     */
    public function process() {
        try {
        	$b_time = date('Y-m-d H:i:s', strtotime("-1 day"));
        	$view_list = $this->getViewList($b_time);
        	$v_ids = array();
            $fail_v_ids = array();
        	if(!empty($view_list)) {
        		foreach ($view_list as $row) {
        			try {
        			    $video_id = (int)$this->getVideoId($row['media_url']);
        			    $video_status = $this->getVideoStatus($video_id);
        			    if($video_status == 1) {
        			        //TODO 处理视频观点
        			        $rs = $this->processView($row['v_id'], $row['p_uid']);
        			        if($rs) {
        			            $v_ids[] = $row['v_id'];
        			        }
        			    }elseif($video_status == 3) {
                            //TODO 转码失败 转码异常
                            $this->failedView($row['v_id']);
                            $fail_v_ids[] = $row['v_id'];
                        }
        			}catch (Exception $e) {
        				Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
                        $fail_v_ids[] = $row['v_id'];
        				continue;
        			}
        		}
        	}
        	if(!empty($v_ids)) {
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '更新数目：'.count($v_ids).';v_ids:'.implode(',', $v_ids));
            }
            if(!empty($fail_v_ids)) {
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, 'failed v_ids:'.implode(',', $fail_v_ids));
            }
        }catch (Exception $e) {
        	throw LcsException::errorHandlerOfException($e);
        }
    }

    /**
     * 取转码中的观点
     * @param DateTime $b_time
     */
    private function getViewList($b_time) {
    	$sql = "select id,id as v_id,p_uid,type,media_url from lcs_view_draft where (status=2 or status=1) and (type=2 or type=3) and c_time>='".$b_time."'";
    	$db_r = Yii::app()->lcs_r;
    	$cmd = $db_r->createCommand($sql);
    	$result = $cmd->queryAll();
    	return $result;
    }
    
    /**
     * 从视频url中截取视频ID
     */
    private function getVideoId($video_url) {
        $video_url = trim(strip_tags($video_url));
        if($video_url == '' || strlen($video_url) < 12) {
            return '';
        }
        preg_match('/\S+video_id=([0-9]+)/i', $video_url, $arr);
        if(isset($arr[1])&& $arr[1]) {
            return $arr[1];
        }else{
            return '';
        }
    }
    /**
     * 取最新的视频转码状态
     * @param  $video_id
     * @return 0待转码 1：转码完成  3:异常
     */
    private function getVideoStatus($video_id) {
    	$result = 0;
    	$api_host = 'http://i.s.video.sina.com.cn/video/info'; //内网地址
    	//$api_host = 'http://s.video.sina.com.cn/video/play'; //外网地址（结果缓存10分钟）
    	
    	try {
    		$curl = Yii::app()->curl;
    		$api_rs = $curl->get($api_host."?video_id=".$video_id."&appname=ivms&player=app");
    		$api_rs = json_decode($api_rs, true);
            if($api_rs['code'] == 1) {
    			$result = $api_rs['data']['transcode_status'];
    		}
    	}catch (Exception $e) {
    		Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    	return $result;
    }
    
    /**
     * step1:改观点状态；step2:调接口，发布视频观点
     */
    private function processView($v_id, $p_uid) {
    	$api_host = LCS_WEB_URL.'/admin2/api/publishView';

    	if($v_id > 0) {
    		$data = array(
    			'status' => 1,
    			'u_time' => date('Y-m-d H:i:s')
    		);
    		$up_rs = Yii::app()->lcs_w->createCommand()->update('lcs_view_draft', $data, "id=".$v_id);

    		//发布观点
    		$curl = Yii::app()->curl;
    		$params = array(
    			'v_id' => $v_id,
    			'p_uid' => $p_uid,
    			'secret_key' => md5($p_uid . 'from_cron_v2_2016')
    		);
    		try{
                $api_rs = $curl->post($api_host, $params);
            }catch(Exception $e) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
    		$api_rs = json_decode($api_rs, true);
    		if($api_rs['code'] == 0) {
    			return true;
    		}else{
    			return false;
    		}
    	}else{
    		return false;
    	}
    	
    }
    /**
     * 处理失败观点
     */
    private function failedView($v_id) {
        if($v_id > 0) {
            $data = array(
                'status' => 3,
                'u_time' => date('Y-m-d H:i:s')
            );
            $up_rs = Yii::app()->lcs_w->createCommand()->update('lcs_view_draft', $data, "id=".$v_id);
        }
    }
    
}
