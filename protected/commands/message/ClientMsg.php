<?php
/**
 * 
 */
class ClientMsg {
	const CRON_NO = 1309; //任务代码
	
	public function __construct(){
	
	}
	/**
	 * 每天15:30执行
	 */
	public function sendForPlannerDay() {
	    $this->clientSMSMessage(0, 1);
	}
	
	/**
	 * 7-21点执行；发给理财师
	 */
	public function sendForPlanner() {
		$this->clientSMSMessage(1, 1);
	}
	/**
	 * 7-21点执行；发给用户
	 */
	public function sendForUser() {
		$this->clientSMSMessage(1, 2);
	}
	
	/**
	 * 已支付问题给理财师的短信提醒
	 * @param number $type 0 每天提醒一次;   1 每分钟执行一次
	 * @param number $ask_type 1 提问； 2 回答
	 */
	private function clientSMSMessage($type = 1, $ask_type = 1) {
		$q_ids = array();
		$_cur_time = date("Y-m-d H:i:s");
		$err_info = array();
		$log = array();
		$q_ids = $this->getQuestionIds($type, $_cur_time, $ask_type);
		if(!empty($q_ids)) {
			$q_list = Question::model()->getQuestionById($q_ids);
			if($q_list && (2==$ask_type)) {
				foreach ($q_list as $row) {
					$uids[] = $row['uid'];
				}
				$u_list = User::model()->getSuidByUids($uids);
			}
			foreach($q_ids as $q_id) {
				if(1 == $ask_type) {
					$uid = $q_list[$q_id]['p_uid'];
				}elseif(2 == $ask_type) {
					$uid = $u_list[$q_list[$q_id]['uid']];
				}
				$data = array(
					'id' => $q_id,
					'title' => mb_substr($q_list[$q_id]['content'], 0, 20, 'utf-8').'...',
					'type' => $ask_type,
					'uid' => $uid
				);
				try {
					//todo api
					$api_url = 'http://money.finance.sina.com.cn/portfolio3/view/PushQaSend.php';
					//$result = trim(Yii::app()->curl->post($api_url, $data));
					$result = '{"result":{"status":{"code":0,"msg":"success"}}}';
					
					if($result) {
						$result_body = json_decode($result, true);
						//print_r($result_body);
						if(!isset($result_body['result'])) {
							throw new Exception("api error");
						}
						$result = $result_body['result'];
						if($result['status']['code']!='0') {
							throw new Exception($result['status']['msg'], $result['status']['code']);
						}else {
							$log[] = 'q_id:'.$q_id .'#uid:'.$uid." success!\n";
						}
					}
				}catch (Exception $e) {
					$err_info[] = 'q_id:'. $q_id."\n" . $e->getCode().':'. $e->getMessage();
					//throw new Exception("api error:q_id=".$q_id);
				}
				
			}//end foreach.
			
		}//end if.
        if(!empty($err_info)) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, json_encode($err_info,JSON_UNESCAPED_UNICODE));
        }
        if(!empty($log)) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, json_encode($log,JSON_UNESCAPED_UNICODE));
        }
	}
	
	/**
	 * 
	 * @param number $type 0 每天提醒一次;   1 每分钟提醒一次
	 * @param string $cur_time
	 * @param number $ask_type
	 * @return array
	 */
	private function getQuestionIds($type = 1, $cur_time = '', $ask_type = 1) {
		$q_ids = array();
		if(1 == $type) {
			$redis_key = "lcs_pay_question_sms_client_last_time_".$ask_type;
			$_last_time = Yii::app()->redis_r->get($redis_key);
		
			if($_last_time === false){
				$_last_time = date("Y-m-d H:i:s",(strtotime($cur_time)-60));
			}
			Yii::app()->redis_w->set($redis_key, $cur_time);
			if(1 == $ask_type) {
				$sql = "select id from lcs_ask_question
				where (status=1 or status=4) and c_time>'$_last_time' and c_time<='$cur_time'";
			}elseif(2 == $ask_type) {
				$sql = "select id from lcs_ask_question
					where (status=3 or status=5) and u_time>'$_last_time' and u_time<='$cur_time'";
			}
			//echo $sql."\n";
		}else{
			$b_time = date("Y-m-d H:i:s",time()-86400);
			if(1 == $ask_type) {
				$sql = "select id from lcs_ask_question
						where (status=1 or status=4) and c_time>'$b_time' and c_time<='$cur_time'";
			}elseif(2 == $ask_type) {
				$sql = "select id from lcs_ask_question
					where (status=3 or status=5) and u_time>'$b_time' and u_time<='$cur_time'";
			}
		}
		$q_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
		$q_ids = array_unique($q_ids);
		return $q_ids;
	}
}
