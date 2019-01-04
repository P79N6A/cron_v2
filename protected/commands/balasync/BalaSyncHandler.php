<?php 
/**
 * 发送消息异步处理 
 *
 */
class BalaSyncHandler
{
	const CRON_NO = 20181121;
	private $redis_data;

	private $_u_info;
	private $_max_length = 500;
	//
	private $_grp;
	private $_url;
	// 说说属性
	private $_cmn_type;
	private $_relation_id;
	private $_content;
	private $_reply_id;
	private $_root_reply_id;
	private $_mark_words;
	private $_source;
	private $_discussion_type;
	private $_discussion_id;
	private $_up_down;
	private $u_type_info;
	private $_is_anonymous;
	private $_media_type;
	private $_media;
	private $_duration;
	private $_is_good;
	private $_getForm;
	private $_server;
	private $_global_id;

	public function process(){
		$start = time();
        $end = time()+60;
        $tick = 0;
        while ($start<$end) {
            // 读取队列$order_no
            $sync_bala_key = MEM_PRE_KEY."Bala_Sync_List";
            $val = Yii::app()->redis_w->lpop($sync_bala_key);

            //设置超时时间
            if($tick%10==0){
                Yii::app()->lcs_r->setActive(false);
                Yii::app()->lcs_r->setActive(true);

                Yii::app()->lcs_w->setActive(false);
                Yii::app()->lcs_w->setActive(true);

                Yii::app()->lcs_comment_r->setActive(false);
                Yii::app()->lcs_comment_r->setActive(true);

                Yii::app()->lcs_comment_w->setActive(false);
                Yii::app()->lcs_comment_w->setActive(true);

                Yii::app()->lcs_standby_r->setActive(false);
                Yii::app()->lcs_standby_r->setActive(true);
            }
			$tick = $tick + 1;
						
            //Common::model()->saveLog($val, 'buy');
            if(!$val){
                echo "没有要同步的数据\n";
                sleep(2);
            }else{
            	$this->redis_data = $val;
                //订单号
                $this->begin();
            }
            $start = time();
        }
	}
	

	public function begin(){
		$this->initBalaData();
		try{
			$cmn_data = BalaCommentService::saveBalaComment(
					$this->_cmn_type, $this->_relation_id, $this->_u_info, $this->u_type_info, $this->_content, $this->_reply_id, $this->_root_reply_id, $this->_mark_words, $this->_source, $this->_discussion_type, $this->_discussion_id, $this->_up_down, 0, $this->_is_anonymous, $this->_is_good, $this->_global_id
			);
			var_dump($this->_global_id);
			Common::model()->saveLog(json_encode($cmn_data),"info","cronV2.BalaSyncHandler");
			//免费礼物进行热度值计算
			if ($this->_discussion_type == 8) {
				$user_gitf = CircleHot::model()->getUserLastSend($this->_relation_id, $this->_u_info['uid'], 1);
				$user_free_send_time = time() - strtotime($user_gitf['c_time']);
				//礼物时间限制修复(2018-07-19)
				if ($user_free_send_time < 28800) {
					echo '未过时间限制';
					return;
				}
				//送出礼物计算热度值
				$data['g_id'] = $this->_discussion_id;
				$gift = Gift::model()->getGiftInfoByIds($data['g_id']);
				$data['hot'] = $gift[$data['g_id']]['price'] * 8;
				$data['uid'] = $this->_u_info['uid'];
				$data['circle_id'] = $this->_relation_id;
				CircleHot::model()->saveCircleHot($data, 1);
			}

			// 保存媒体资源
			$cmn_data['cmn_data'] = BalaCommentService::saveCommentMedia($cmn_data['cmn_data'], $this->_media_type, $this->_media, $this->_duration);

			if (!empty($cmn_data) && !empty($cmn_data['cmn_data'])) {
				$cmn_list = array($cmn_data['cmn_data']);
				CircleCommentService::handleCommentList($cmn_list);
				$cmn_data['cmn_data'] = reset($cmn_list);
				// cronV2.BalaSyncHandler
				Common::model()->pushGoim($this->_relation_id, 1, $cmn_data['cmn_data']);
				Common::model()->pushGoimToPlanner($this->_relation_id, 1, $cmn_data['cmn_data']);
				echo "成功!";
			} else {
				echo '保存评论失败';
			}

			// 回复类消息进行推送
			if(!empty($cmn_data['cmn_data']['reply_info'])){
				$pushData = array();
				$pushData['plannerName'] = $cmn_data['cmn_data']['name'];
				$pushData['userId'] = $cmn_data['cmn_data']['reply_info']['uid'];
				$pushData['pushContent'] = $this->_content;
				$pushData['circle_id'] = $this->_relation_id;
				CircleCommentService::pushToUser($pushData);
			}

			// 记录redis说说数据
			CircleCommentService::updateCircleCommentNum($this->_relation_id, 1);
			// 加入消息队列推送
			CircleCommentService::pushCommentToQueue($cmn_data['cmn_data']);
			// 最新一条消息加入到redis中(理财师最后一条消息)
			if (!empty($this->_u_info['is_p'])) {
				NewComment::model()->saveLastCommentData($cmn_data['cmn_data']);
			}

			// 更新用户在线时间
			if (CircleCommentService::isUsePlannerIdentity($this->_u_info, $this->_getForm,$this->_server)) {
				CircleCommentService::updateUserCircleTime(["u_type" => 2, "uid" => $this->_u_info['s_uid']], $this->_relation_id, date("Y-m-d H:i:s"));
			} else {
				CircleCommentService::updateUserCircleTime(["u_type" => 1, "uid" => $this->_u_info['uid']], $this->_relation_id, date("Y-m-d H:i:s"));
			}
			echo "处理完毕~";
		}catch(Exception $e){
			//记录日志
			Common::model()->saveLog(json_encode($e->getTrace()),"error","cronV2.BalaSyncHandler");
			// var_dump($e->getTrace());
			var_dump($e->getMessage());
		}
	}

	private function initBalaData(){
		$data = json_decode($this->redis_data,true);
		// $this->_incr_id = $data['_incr_id'];
		$this->_cmn_type = $data['_cmn_type'];
		$this->_relation_id = $data['_relation_id'];
		$this->_content = $data['_content'];
		$this->_reply_id = $data['_reply_id'];
		$this->_root_reply_id = $data['_root_reply_id'];
		$this->_mark_words = $data['_mark_words'];
		$this->_source = $data['_source'];
		$this->_discussion_type = $data['_discussion_type'];
		$this->_discussion_id = $data['_discussion_id'];
		$this->u_type_info = $data['u_type_info'];
		$this->_is_anonymous = $data['_is_anonymous'];
		$this->_media_type = $data['_media_type'];
		$this->_media = $data['_media'];
		$this->_duration = $data['_duration'];
		$this->_up_down = $data['_up_down'];
		$this->_is_good = $data['_good'];
		$this->_getForm = $data['_getform'];
		$this->_server = $data['_server'];
		$this->_global_id = $data['_global_id'];
	}
}