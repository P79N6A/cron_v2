<?php

/**
 * 理财师评论模板逻辑处理类
 * @author weiguang3
 * @version 1.0
 * @date 2015-01-10
 */
class CommentService {

	public static $def_admin_user_name = '理财小妹';
	public static $def_admin_user_image = 'http://licaishi.sina.com.cn/web_img/lcs_comment_systemuser.jpg';
	public static $cmn_type_arr = array(1 => '计划', 2 => '观点', 3 => '话题', 4 => '系统');
	public static $u_type_arr = array(1 => '用户', '2' => '理财师', 3 => '理财小妹');

	/**
	 * 保存评论
	 * @param unknown $cmn_type
	 * @param unknown $relation_id
	 * @param unknown $content
	 * @param unknown $u_type
	 * @param unknown $uid
	 * @param number $replay_id
	 * @param number $is_display
	 * @param number $is_anonymous
	 * @param string $source
	 * @param number $parent_relation_id
	 * @param number $discussion_type
	 * @param string $discussion_id
	 * @return ApiResult
	 */
	public function saveComment($cmn_type, $relation_id, $content, $u_type, $uid, $replay_id = 0, $is_display = 1, $is_anonymous = 0, $source = '', $parent_relation_id = 0, $discussion_type = 0, $discussion_id = '') {
		$apiResult = new ApiResult();
		if (empty($cmn_type) || !array_key_exists($cmn_type, self::$cmn_type_arr)) {
			return $apiResult->setError(RespCode::PARAM_ERR, '评论的类型错误');
		}

		if (empty($u_type) || !array_key_exists($u_type, self::$u_type_arr)) {
			return $apiResult->setError(RespCode::PARAM_ERR, '用户的类型错误');
		}
		$replay_id = intval($replay_id);
		$is_anonymous = ($is_anonymous != 0) ? 1 : 0;
		$is_display = ($is_display != 0) ? 1 : 0;

		//评论的内容进行富文本验证
		require_once Yii::app()->basePath . '/extensions/xss/XssPurifyService.php';
		$_xssPurifyService = new XssPurifyService();
		$content = $_xssPurifyService->purify($content);
		if (strlen($content) == 0) {
			return $apiResult->setError(RespCode::PARAM_ERR, '回复内容不能为空');
		}
		$max_length = ($u_type == 2) ? 500 : 120;
		if ((mb_strlen($content, 'utf-8') + strlen($content)) / 4 > $max_length) {
			return $apiResult->setError(RespCode::PARAM_ERR, '内容过长');
		}

		//验证评论内容是否和上次一样 1分钟 只对用户进行限制
		if (1 == $u_type) {
			$redis_key = MEM_PRE_KEY . 'comment_lasted_crc32_' . intval($uid);
			$lasted_cnt_crc32 = Yii::app()->redis_r->get($redis_key);
			$cur_cnt_crc32 = CommonUtils::getCRC32($content);
			if (!empty($lasted_cnt_crc32) && $cur_cnt_crc32 == $lasted_cnt_crc32) {
				return $apiResult->setError(RespCode::PARAM_ERR, '操作频繁，请稍后重试');
			} else {
				Yii::app()->redis_w->setex($redis_key, 60, $cur_cnt_crc32);
			}
		}

		if (1 == $u_type && CommonUtils::hasBlackWords($content)) {
			$message['uid'] = $uid;
			$message['ip'] = $_SERVER['REMOTE_ADDR'];
			$message['content'] = $content;
			Common::model()->saveLog(json_encode($message, JSON_UNESCAPED_UNICODE), $level = 'info', $category = 'Comment.blackWords');
			return $apiResult->setError(RespCode::CONTENT_BLACKWORD, '内容包含敏感词');
		}


		$cmn_data['cmn_type'] = $cmn_type;
		$cmn_data['relation_id'] = $relation_id;
		$cmn_data['content'] = $content;
		$cmn_data['u_type'] = $u_type;
		$cmn_data['uid'] = $uid;
		$cmn_data['replay_id'] = $replay_id;
		$cmn_data['is_display'] = $is_display;
		$cmn_data['is_anonymous'] = $is_anonymous;
		$cmn_data['status'] = 0;
		$cmn_data['source'] = $source;
		$cmn_data['c_time'] = date('Y-m-d H:i:s');
		$cmn_data['u_time'] = $cmn_data['c_time'];
		$cmn_data['parent_relation_id'] = $parent_relation_id;
		$cmn_data['discussion_type'] = $discussion_type;
		$cmn_data['discussion_id'] = $discussion_id;
		$cmn_id = CommentNew::model()->saveComment($cmn_data);
		if ($cmn_id) {
			$cmn_data['id'] = $cmn_id;
			$cmn_data['cmn_id'] = $cmn_id;
			//楼层  公开显示或是子评论需要计算楼层
			if ($is_display == 1 || !empty($replay_id)) {
				$redis_key_floor = MEM_PRE_KEY . 'cmn_floor_' . $cmn_type . '_' . $relation_id;
				$floor_num = 1;
				$hash_key = '0'; //一级评论
				if (!empty($cmn_id) && !empty($replay_id)) {
					//二级评论的楼层
					$hash_key = $replay_id;
				}
				$floor_num = Yii::app()->redis_w->hIncrBy($redis_key_floor, $hash_key, 1);

				//$floor_num = CommentNew::model()->getCommentFloorNum($cmn_id,$cmn_type, $relation_id, $replay_id);
				CommentNew::model()->updateComment($cmn_id, array('floor_num' => $floor_num));
			}

			//记录最新的说说ID和发布时间  公开的一级说说
			if ($is_display == 1 && empty($replay_id) && ($cmn_type == CommentNew::CMN_TYPE_PLAN || $cmn_type == CommentNew::CMN_TYPE_VIEW)) {
				$redis_key_new = MEM_PRE_KEY . 'cmn_new_' . $cmn_type . '_' . $relation_id;
				if ($cmn_type == CommentNew::CMN_TYPE_VIEW) {
					$redis_key_new = MEM_PRE_KEY . 'cmn_new_' . $cmn_type . '_' . $parent_relation_id;
				}
				Yii::app()->redis_w->hset($redis_key_new, 'lasted_id', $cmn_id);
				Yii::app()->redis_w->hset($redis_key_new, 'lasted_time', time());
			}

			//评论数量
			if (!empty($replay_id)) {
				CommentNew::model()->updateCommentInc($replay_id, 'replay_num', 1);

				// 记录最新的评论ID
				$redis_key = MEM_PRE_KEY . 'cmn_lasted_' . $replay_id;
				Yii::app()->redis_w->lPush($redis_key, intval($cmn_id));
				Yii::app()->redis_w->ltrim($redis_key, 0, 1);
			}
			$cmn_data['floor_num'] = $floor_num;
			$cmn_data['replay_num'] = 0;
			$cmn_data['parise_num'] = 0;
			$cmn_data['c_time_fmt'] = CommonUtils::formatDate($cmn_data['c_time'], 'web');
			$cmn_data['is_parise'] = 0;
			return $apiResult->setSuccess($cmn_data);
		} else {
			return $apiResult->setError(RespCode::DEF_ERROR, '保存评论失败');
		}
	}

	public function deleteComment($cmn_id, $reply_id) {
		$apiResult = new ApiResult();
		$res = CommentNew::model()->updateComment($cmn_id, array('status' => -1, 'u_time' => date("Y-m-d H:i:s")));
		if ($res) {
			if (empty($reply_id)) {
				//删除子评论ID
				CommentNew::model()->updateCommentByReplayId($cmn_id, array('status' => -1, 'u_time' => date("Y-m-d H:i:s")));
			}

			if (!empty($reply_id)) {
				//redis 评论列表 去除ID
				$cmn_ids = CommentNew::model()->getLastedCommentIds($reply_id, 2);
				if (!empty($cmn_ids)) {
					$redis_key = MEM_PRE_KEY . 'cmn_lasted_' . $reply_id;
					//返回的数据反转后放入redis 在保留最后两个ID
					$cmn_ids = array_reverse($cmn_ids);
					foreach ($cmn_ids as $cmn) {
						Yii::app()->redis_w->lPush($redis_key, intval($cmn['cmn_id']));
					}
					Yii::app()->redis_w->ltrim($redis_key, 0, 1);
				}
				//评论数量减一
				CommentNew::model()->updateCommentInc($reply_id, 'replay_num', -1);
			}
		} else {
			$apiResult->setError(RespCode::DEF_ERROR, '删除评论失败');
		}
		return $apiResult;
	}

	/**
	 * 获取用户发布评论列表
	 * @param unknown $cmn_id
	 * @param unknown $relation_id
	 * @param unknown $u_type
	 * @param unknown $uid
	 * @param number $page
	 * @param number $num
	 * @param is_anonymous 私密的内容是否需要进行私密处理
	 */
	public function getCommentPageByUid($cmn_type, $relation_id, $u_type, $uid, $page = 1, $num = 15, $is_anonymous = true, $cur_uid = null, $parent_relation_id = 0) {
		$apiResult = new ApiResult();

		if ($u_type == 2 || $u_type == 3) { //包含回复的说说
			$commentPage = CommentNew::model()->getMyCommentPage($cmn_type, $relation_id, $u_type, $uid, $page, $num, true, $parent_relation_id);
		} else if ($u_type == 1) { //只是发布的一级说说
			$commentPage = CommentNew::model()->getCommentPage($cmn_type, $relation_id, $u_type, $uid, null, $page, $num, $parent_relation_id);
		} else {
			$commentPage = CommonUtils::getPage(array(), $page, $num, 0);
		}

		//获取的数据为空直接返回
		if (empty($commentPage['data'])) {
			return $apiResult->setSuccess($commentPage);
		}

		$sub_comment_map = array();
		$users_map = array();
		$planners_map = array();
		$this->getOtherInfo($commentPage, $sub_comment_map, $users_map, $planners_map);
		//处理评论的内容
		foreach ($commentPage['data'] as & $cmn) {

			$this->assembleCommentInfo($cmn, $cur_uid, $users_map, $planners_map, ($uid == $cmn['uid']), ($u_type == 2 && $is_anonymous && $cmn['is_anonymous'] == 1));
			// 处理二级评论
			if (!empty($cmn['last_replays'])) {
				$sub_cmns = array();
				foreach ($cmn['last_replays'] as $sub_cmn_id) {
					$sub_cmn = isset($sub_comment_map[$sub_cmn_id]) && !empty($sub_comment_map[$sub_cmn_id]) ? $sub_comment_map[$sub_cmn_id] : array();
					if (empty($sub_cmn)) {
						continue;
					}

					$this->assembleCommentInfo($sub_cmn, $cur_uid, $users_map, $planners_map, ($uid == $sub_cmn['uid']), ($u_type == 2 && $is_anonymous && $sub_cmn['is_anonymous'] == 1));

					$sub_cmns[] = $sub_cmn;
				}

				$cmn['last_replays'] = $sub_cmns;
			}
		}


		return $apiResult->setSuccess($commentPage);
	}

	/**
	 * 根据说说的类型判断该用户是否购买了对应的观点包或者计划,默认为false
	 * @param unknown $cur_uid 用户id
	 * @param $cmn 说说数据
	 */
	public function getIsSub($cmn, $cur_uid) {
		$is_sub = false; //判断当前用户是否购买了该观点包或者计划，默认为false

		if ($cmn['cmn_type'] == CommentNew::CMN_TYPE_PLAN) { //如果说说属于计划
			$pln_id = !empty($cmn) && isset($cmn['relation_id']) ? $cmn['relation_id'] : '';
			//当前用户是否订阅了该计划
			if (!empty($pln_id) && !empty($cur_uid)) {
				$is_sub = PlanSubscription::model()->isSubscriptionByPlanId($cur_uid, $pln_id) ? true : false;
			}
			if (!$is_sub && !empty($cur_uid)) {
				$user_info = User::model()->getUserInfo();
				if (!empty($user_info) && $user_info['is_p'] == 1) {
					$plans = Plan::model()->getPlanInfo(array($pln_id));
					if (isset($plans[$pln_id]) && $plans[$pln_id]['p_uid'] == $user_info['s_uid']) {
						$is_sub = 1;
					}
				}
			}
		}
		if ($cmn['cmn_type'] == CommentNew::CMN_TYPE_VIEW) {
			//当前用户是否订阅了该观点包，观点包是否收费
			if ($cmn['relation_id'] > 0) {
				$view_info = View::model()->getViewById($cmn['relation_id']);
				$pkg_id = $view_info[$cmn['relation_id']]['pkg_id'];
			} else {
				$pkg_id = isset($cmn['parent_relation_id']) ? $cmn['parent_relation_id'] : '';
			}
			if (!empty($cur_uid) && !empty($pkg_id)) {
				$is_sub = PackageSubscription::model()->isSubscription($cur_uid, $pkg_id) ? true : false;
			}

			if (!$is_sub && !empty($cur_uid)) {
				$user_info = User::model()->getUserInfo();
				if (!empty($user_info) && $user_info['is_p'] == 1) {
					$packages = Package::model()->getPackagesById(array($pkg_id));
					if (isset($packages[$pkg_id]) && $packages[$pkg_id]['p_uid'] == $user_info['s_uid']) {
						$is_sub = 1;
					}
				}
			}
		}
		return $is_sub;
	}

	/**
	 * 根据说说判断说说来源的计划或者观点是否收费
	 * @param $cmn说说
	 * @return bool|int
	 */
	public function isCharge($cmn) {
		$is_charge = true; //默认为收费

		if ($cmn['cmn_type'] == CommentNew::CMN_TYPE_PLAN) {//如果是计划，肯定是收费的
			$is_charge = true;
		}
		if ($cmn['cmn_type'] == CommentNew::CMN_TYPE_VIEW) {//如果是观点需要判断是否收费
			if ($cmn['relation_id'] > 0) {
				$view_info = View::model()->getViewById($cmn['relation_id']);
				$is_charge = $view_info[$cmn['relation_id']]['subscription_price'] > 0 ? 1 : 0; // 是否收费
			} else {
				$pkg_id = !empty($cmn['parent_relation_id']) ? $cmn['parent_relation_id'] : '';
				if (!empty($pkg_id)) {
					$pkg_info = Package::model()->getPackagesById($pkg_id);
					$pkg_info = !empty($pkg_info) ? $pkg_info[$pkg_id] : null;
					$is_charge = ($pkg_info['subscription_price'] > 0 && date('Y-m-d H:i:s') > $pkg_info['charge_time']) ? 1 : 0; // 是否收费
				}
			}
		}
		return $is_charge;
	}

	/*	 * 判断该评论来自于计划、观点、话题等
	 * @param $comment 评论
	 */

	public function getRelation_title(& $comment) {
		$comment['relation_title'] = '';
		if ($comment['cmn_type'] == 1 && $comment['relation_id'] != 0) {//来自于计划
			$planInfo = Plan::model()->getPlanInfo($comment['relation_id']);
			$comment['relation_title'] = !empty($planInfo) && isset($planInfo[$comment['relation_id']]['name']) ? $planInfo[$comment['relation_id']]['name'] : '';
		} else if ($comment['cmn_type'] == 2 && $comment['relation_id'] != 0) {  //来自于观点
			$viewInfo = View::model()->getViewById($comment['relation_id']);
			$comment['relation_title'] = !empty($viewInfo) && isset($viewInfo[$comment['relation_id']]['title']) ? $viewInfo[$comment['relation_id']]['title'] : '';
		} else if ($comment['cmn_type'] == 2 && $comment['relation_id'] == 0 && $comment['parent_relation_id'] != 0) { //来自观点包
			$packInfo = Package::model()->getPackagesById($comment['parent_relation_id']);
			$comment['relation_title'] = !empty($packInfo) && isset($packInfo[$comment['parent_relation_id']]['title']) ? $packInfo[$comment['parent_relation_id']]['title'] : '';
		} else if ($comment['cmn_type'] == 3 && $comment['relation_id'] != 0) {//来自话题
			$topicInfo = Topics::model()->getInfoIds(array($comment['relation_id']));
			$comment['relation_title'] = !empty($topicInfo) && isset($topicInfo[$comment['relation_id']]['title']) ? $topicInfo[$comment['relation_id']]['title'] : '';
		} else {
			$comment['relation_title'] = '';
		}
		return $comment;
	}

	/**
	 * 获取公开的热门说说page
	 * @param number $page
	 * @param number $num默认10条
	 * @param cur_uid
	 */
	public function getHotCommentPage($cur_uid, $type, $is_anonymous = true, $page = 1, $num = 10) {
		$apiResult = new ApiResult();
		//运营和72小时内的热门说说
		$commentPage = CommentNew::model()->getHotCommentList(259200, $page, $num, $type);
		if (empty($commentPage['data'])) {
			return $apiResult->setSuccess($commentPage);
		}
		$sub_comment_map = array();
		$users_map = array();
		$planners_map = array();
		$this->getOtherInfo($commentPage, $sub_comment_map, $users_map, $planners_map);
		foreach ($commentPage['data'] as & $cmn) {
			//是否订阅计划或者观点包
			$is_sub = $this->getIsSub($cmn, $cur_uid);
			$is_anonymous = !$is_sub;
			//观点包是否收费
			$is_charge = $this->isCharge($cmn);
			$this->assembleCommentInfo($cmn, $cur_uid, $users_map, $planners_map, false, ($is_anonymous && $cmn['is_anonymous'] == 1));
			//一级评论是否能够回复点赞
			$cmn['able_parise'] = 0;
			if ($is_sub || $cmn['uid'] == $cur_uid || !$is_charge) {
				$cmn['able_parise'] = 1;
			}
			//处理二级评论
			if (!empty($cmn['last_replays'])) {
				$sub_cmns = array();
				foreach ($cmn['last_replays'] as $sub_cmn_id) {

					$sub_cmn = isset($sub_comment_map[$sub_cmn_id]) && !empty($sub_comment_map[$sub_cmn_id]) ? $sub_comment_map[$sub_cmn_id] : array();
					if (empty($sub_cmn)) {
						continue;
					}
					$this->assembleCommentInfo($sub_cmn, $cur_uid, $users_map, $planners_map, false, ($is_anonymous && $sub_cmn['is_anonymous'] == 1));
					$sub_cmns[] = $sub_cmn;
				}
				$cmn['last_replays'] = $sub_cmns;
			}
		}
		return $apiResult->setSuccess($commentPage);
	}

	/**
	 * 获取公开的最新说说page
	 * @param number $page
	 * @param number $num
	 * @param cur_uid
	 */
	public function getLastCommentPage($page = 1, $num = 15, $cur_uid, $type, $is_anonymous = false, $is_realName = true) {
		$apiResult = new ApiResult();
		//72小时内的热门说说
		$commentPage = CommentNew::model()->getLastCommentList($page, $num, $type);
		if (empty($commentPage['data'])) {
			return $apiResult->setSuccess($commentPage);
		}
		$sub_comment_map = array();
		$users_map = array();
		$planners_map = array();
		$this->getOtherInfo($commentPage, $sub_comment_map, $users_map, $planners_map);

		foreach ($commentPage['data'] as & $cmn) {
			$this->assembleCommentInfo($cmn, $cur_uid, $users_map, $planners_map, $is_realName, false);
			$cmn['able_parise'] = 1;
			//处理二级评论
			if (!empty($cmn['last_replays'])) {
				$sub_cmns = array();
				foreach ($cmn['last_replays'] as $sub_cmn_id) {

					$sub_cmn = isset($sub_comment_map[$sub_cmn_id]) && !empty($sub_comment_map[$sub_cmn_id]) ? $sub_comment_map[$sub_cmn_id] : array();
					if (empty($sub_cmn)) {
						continue;
					}
					$this->assembleCommentInfo($sub_cmn, $cur_uid, $users_map, $planners_map, $is_realName, ($is_anonymous && $sub_cmn['is_anonymous'] == 1));
					$sub_cmns[] = $sub_cmn;
				}
				$cmn['last_replays'] = $sub_cmns;
			}
		}
		return $apiResult->setSuccess($commentPage);
	}

	/*	 * 获取话题的最新n条说说
	 * @param $topicId 话题id
	 * @param $number 获取话题说说个数
	 * @param $u_id 用户id
	 */

	public function getTopicComments($topicIds, $u_id, $number) {
		$apiResult = new ApiResult();
		$comments = array();
        if(empty($topicIds)){
            return $comments;
        }
		foreach ($topicIds as $ids) {
			$comments[$ids] = CommentNew::model()->getTopicCommentsById($ids, $number);
		}
		foreach ($comments as & $result) {
			//获取详情
			if (!empty($result['data'])) {
				$ids = array();
				foreach ($result['data'] as $row) {
					$ids[] = $row['id'];
				}
				$data = array();
				$hotcomment_map = CommentNew::model()->getCommentByIds($ids);
				foreach ($result['data'] as $row) {
					if (isset($hotcomment_map[$row['id']])) {
						$data[] = $hotcomment_map[$row['id']];
					}
				}
				$result['data'] = $data;
				$sub_comment_map = array();
				$users_map = array();
				$planners_map = array();
				$this->getOtherInfo($result, $sub_comment_map, $users_map, $planners_map);
				$cur_uid = $u_id;
				foreach ($result['data'] as & $cmn) {
					$this->assembleCommentInfo($cmn, $cur_uid, $users_map, $planners_map, false, false);
					//处理二级说说
					if (!empty($cmn['last_replays'])) {
						$sub_cmns = array();
						foreach ($cmn['last_replays'] as $sub_cmn_id) {
							$sub_cmn = isset($sub_comment_map[$sub_cmn_id]) && !empty($sub_comment_map[$sub_cmn_id]) ? $sub_comment_map[$sub_cmn_id] : array();
							if (empty($sub_cmn)) {
								continue;
							}

							$this->assembleCommentInfo($sub_cmn, $cur_uid, $users_map, $planners_map, false, false);
							$sub_cmns[] = $sub_cmn;
						}
						$cmn['last_replays'] = $sub_cmns;
					}
				}
			}
		}
		return $comments;
	}

	/**
	 * 获取公开的评论
	 * @param unknown $cmn_type
	 * @param unknown $relation_id
	 * @param number $page
	 * @param number $num
	 * @param is_anonymous 私密的内容是否需要进行私密处理
	 */
	public function getCommentPage($cmn_type, $relation_id, $page = 1, $num = 15, $is_anonymous = true, $is_display = 1, $cur_uid = null, $is_top = true, $parent_relation_id = 0) {
		$apiResult = new ApiResult();
		$commentPage = CommentNew::model()->getCommentPage($cmn_type, $relation_id, null, null, $is_display, $page, $num, $parent_relation_id);

		//如果需要显示置顶消息并且是第一页的时候 把置顶的评论 放到分页数据中
		if ($is_top && $page == 1) {
			$top_cmns = CommentNew::model()->getTopComments($cmn_type, $relation_id, $parent_relation_id);
			if (!empty($top_cmns)) {
				if (empty($commentPage['data'])) {
					$commentPage['data'] = $top_cmns;
				} else {
					$commentPage['data'] = array_merge($top_cmns, $commentPage['data']);
				}
			}
		}

		//获取的数据为空直接返回
		if (empty($commentPage['data'])) {
			return $apiResult->setSuccess($commentPage);
		}

		$sub_comment_map = array();
		$users_map = array();
		$planners_map = array();
		$this->getOtherInfo($commentPage, $sub_comment_map, $users_map, $planners_map);
		//处理评论的内容
		foreach ($commentPage['data'] as & $cmn) {
			$this->assembleCommentInfo($cmn, $cur_uid, $users_map, $planners_map, false, ($is_anonymous && $cmn['is_anonymous'] == 1));

			// 处理二级评论
			if (!empty($cmn['last_replays'])) {
				$sub_cmns = array();
				foreach ($cmn['last_replays'] as $sub_cmn_id) {
					$sub_cmn = isset($sub_comment_map[$sub_cmn_id]) && !empty($sub_comment_map[$sub_cmn_id]) ? $sub_comment_map[$sub_cmn_id] : array();
					if (empty($sub_cmn)) {
						continue;
					}

					$this->assembleCommentInfo($sub_cmn, $cur_uid, $users_map, $planners_map, false, ($is_anonymous && $sub_cmn['is_anonymous'] == 1));
					$sub_cmns[] = $sub_cmn;
				}

				$cmn['last_replays'] = $sub_cmns;
			}
		}


		return $apiResult->setSuccess($commentPage);
	}

	/**
	 * 获取其他信息
	 * @param unknown $commentPage
	 * @param unknown $sub_comment_map
	 * @param unknown $users_map
	 * @param unknown $planners_map
	 */
	private function getOtherInfo(& $commentPage, & $sub_comment_map, & $users_map, & $planners_map) {
		$u_ids = array(); //用户ID
		$p_ids = array(); //理财师ID
		$sub_cmn_ids = array(); //二级评论ID
		foreach ($commentPage['data'] as & $cmn) {
			if ($cmn['u_type'] == 1) {
				$u_ids[] = $cmn['uid'];
			} else if ($cmn['u_type'] == 2) {
				$p_ids[] = $cmn['uid'];
			}
			if (empty($cmn['replay_id'])) {
				//从redis中获取最新的两个二级评论ID
				$redis_key = MEM_PRE_KEY . 'cmn_lasted_' . $cmn['id'];
				$sub_cmn_lasted_ids = Yii::app()->redis_r->lrange($redis_key, 0, 1);
				if (!empty($sub_cmn_lasted_ids)) {
					$cmn['last_replays'] = array_values($sub_cmn_lasted_ids);

					$sub_cmn_ids = array_merge($sub_cmn_ids, $cmn['last_replays']);
				} else {
					$cmn['last_replays'] = array();
				}
			} else {
				$cmn['last_replays'] = array();
			}
		}

		//排除重复的ID
		$sub_cmn_ids = array_unique($sub_cmn_ids);
		$sub_comment_map = !empty($sub_cmn_ids) ? CommentNew::model()->getCommentByIds($sub_cmn_ids) : array();
		if (!empty($sub_comment_map)) {
			foreach ($sub_comment_map as $key => $val) {
				if ($sub_comment_map[$key]['u_type'] == 1) {
					$u_ids[] = $sub_comment_map[$key]['uid'];
				} else if ($sub_comment_map[$key]['u_type'] == 2) {
					$p_ids[] = $sub_comment_map[$key]['uid'];
				}
			}
		}

		//排除重复的ID
		$u_ids = array_unique($u_ids);
		$p_ids = array_unique($p_ids);

		//获取 用户 理财师  二级评论 的信息
		$users_map = !empty($u_ids) ? User::model()->getUserInfoById($u_ids) : array();
		$planners_map = !empty($p_ids) ? Planner::model()->getPlannerById($p_ids) : array();
	}

	/**
	 * 组装评论信息
	 * @param unknown $cmn
	 * @param unknown $cur_uid
	 * @param unknown $users_map
	 * @param unknown $planners_map
	 * @param string $is_realName  //是否显示真是的用户名称 当用户类型为1
	 * @param string $is_anonymous //是否要隐藏评论内容
	 *
	 */
	private function assembleCommentInfo(& $cmn, $cur_uid, & $users_map, & $planners_map, $is_realName = false, $is_anonymous = true) {
		//时间格式化
		$cmn['c_time_fmt'] = CommonUtils::formatDate($cmn['c_time'], 'web');

		//是否赞
		$is_parise = 0;
		if (!empty($cur_uid)) {
			$redis_key = MEM_PRE_KEY . 'cmn_parise_' . intval($cmn['cmn_id']);
			$parise = Yii::app()->redis_r->hget($redis_key, intval($cur_uid));
			$is_parise = !empty($parise) ? 1 : 0;
		}
		$cmn['is_parise'] = $is_parise;

		if ($cmn['u_type'] == 1) { //用户全部显示系统代号
			//$cmn['name'] = CommonUtils::getShowName($cmn['uid']);
			if ($is_realName) {// || in_array($cmn['cmn_type'],array(3,4)) update by zwg 20151016
				$cmn['name'] = isset($users_map[$cmn['uid']]) && !empty($users_map[$cmn['uid']]) ? $users_map[$cmn['uid']]['name'] : '';
			} else {
				$cmn['name'] = CommonUtils::getShowName($cmn['uid']);
			}
			$cmn['image'] = isset($users_map[$cmn['uid']]) && !empty($users_map[$cmn['uid']]) ? CommonUtils::convertUserImage($users_map[$cmn['uid']]['image'], 50) : '';
		} else if ($cmn['u_type'] == 2) {
			$cmn['name'] = isset($planners_map[$cmn['uid']]) && !empty($planners_map[$cmn['uid']]) ? $planners_map[$cmn['uid']]['name'] : '';
			$cmn['image'] = isset($planners_map[$cmn['uid']]) && !empty($planners_map[$cmn['uid']]) ? CommonUtils::convertUserImage($planners_map[$cmn['uid']]['image'], 50) : '';
			$cmn['company'] = isset($planners_map[$cmn['uid']]) && !empty($planners_map[$cmn['uid']]) ? $planners_map[$cmn['uid']]['company'] : '';
			$cmn['position'] = isset($planners_map[$cmn['uid']]) && !empty($planners_map[$cmn['uid']]) ? $planners_map[$cmn['uid']]['position'] : '';
		} else if ($cmn['u_type'] == 3) {
			$cmn['name'] = self::$def_admin_user_name;
			$cmn['image'] = self::$def_admin_user_image;
		}
		//接口指定需要隐藏内容，并且当前评论的内容设置了是否要隐藏
		if ($is_anonymous) {
			$cmn['content'] = CommonUtils::getPlanPrivCommentComment($cmn);
		}
		//组装讨论信息
		if (!empty($cmn['discussion_type']) && !empty($cmn['discussion_id'])) {
			$discussion_info = $this->getDiscussionInfo($cmn['discussion_type'], $cmn['discussion_id'], true, $cur_uid, $is_anonymous ? false : true);
			if (!empty($discussion_info)) {
				$cmn[CommentNew::$discussion_field[$cmn['discussion_type']]] = $discussion_info;
			}
		}

		return $cmn;
	}

	/**
	 * 获取二级评论
	 * @param $cmn_id
	 * @param $uid
	 * @param $page
	 * @param $num
	 * @param $is_anonymous //私密的内容是否需要进行私密处理
	 * @param $is_self //如果当前评论的用户是自己，是否要显示真是姓名
	 * @return page
	 */
	public function getSubCommentPage($cmn_id, $uid, $page = 1, $num = 15, $is_anonymous = true, $is_self = false, $has_parent = false) {
		$apiResult = new ApiResult();
		$commentPage = CommentNew::model()->getCommentPageByReplayId($cmn_id, $page, $num);
		//获取的数据为空直接返回  update by zwg 20150518 如果数据为空，并且不获取父说说数据时直接返回
		if (!$has_parent && empty($commentPage['data'])) {
			return $apiResult->setSuccess($commentPage);
		}

		if ($has_parent) {
			$comment_info = CommentNew::model()->getCommentByIds($cmn_id);
			if (!empty($comment_info) && isset($comment_info[$cmn_id])) {
				$commentPage['data'][] = $comment_info[$cmn_id];
			}
		}

		$sub_comment_map = array();
		$users_map = array();
		$planners_map = array();
		$this->getOtherInfo($commentPage, $sub_comment_map, $users_map, $planners_map);
		//处理评论的内容
		$data_list = array();
		$parent_cmn = array();
		foreach ($commentPage['data'] as $cmn) {
			$this->assembleCommentInfo($cmn, $uid, $users_map, $planners_map, ($is_self && $uid == $cmn['uid']), ($is_anonymous && $cmn['is_anonymous'] == 1));
			if ($cmn['cmn_id'] == $cmn_id) {
				$cmn['last_replays'] = array();
				$parent_cmn = $cmn;
			} else {
				$data_list[] = $cmn;
			}
		}

		$commentPage['data'] = $data_list;
		$commentPage['parent_cmn'] = $parent_cmn;

		return $apiResult->setSuccess($commentPage);
	}

	/**
	 * 对评论进行赞
	 * @param unknown $cmn_id
	 * @param number $is_parise  1赞  其他为取消赞
	 */
	public function pariseComment($cmn_id, $uid, $is_parise = 1) {
		$apiResult = new ApiResult();
		$redis_key = MEM_PRE_KEY . 'cmn_parise_' . intval($cmn_id);
		$parise = Yii::app()->redis_r->hget($redis_key, intval($uid));
		if ($is_parise == 1) {
			if (empty($parise)) {
				Yii::app()->redis_w->hset($redis_key, intval($uid), time());
				CommentNew::model()->updateCommentInc($cmn_id, 'parise_num', 1);
			}
		} else {
			if (!empty($parise)) {
				Yii::app()->redis_w->hdel($redis_key, intval($uid));
				CommentNew::model()->updateCommentInc($cmn_id, 'parise_num', -1);
			}
		}

		return $apiResult->setSuccess();
	}

	/**
	 * 指定评论
	 * @param unknown $cmn_id
	 * @param number $is_top
	 * @return ApiResult
	 */
	public function topComment($cmn_id, $is_top = 2) {
		$apiResult = new ApiResult();

		$cmn_info = CommentNew::model()->getCommentByIds($cmn_id);
		$cmn_info = !empty($cmn_info) ? $cmn_info["$cmn_id"] : null;

		if (empty($cmn_info) || 0 != $cmn_info['status']) {
			$apiResult->setError(RespCode::NOT_EXISTS, '不存在或者已经删除');
			return;
		}

		if (!empty($cmn_info['replay_id'])) {
			$apiResult->setError(RespCode::DEF_ERROR, '不是一级评论不能置顶');
			return;
		}

		$upd_data = array();
		if ($is_top == 2) { //置顶  修改时间使用当前时间，在置顶的排序中使用
			$upd_data['is_display'] = 2;
			$upd_data['u_time'] = date("Y-m-d H:i:s");
		} else { //取消置顶，修改时间等于创建时间，在大家说说中恢复默认顺序
			$upd_data['is_display'] = 1;
			$upd_data['u_time'] = $cmn_info['c_time'];
		}

		$res = CommentNew::model()->updateComment($cmn_id, $upd_data);
		if ($res) {
			$apiResult->setSuccess();
		} else {
			$apiResult->setError(RespCode::DEF_ERROR, '评论评论失败');
		}
		return $apiResult;
	}

	/**
	 * 获取讨论的信息
	 * @param $discussion_type //说说讨论类型：0未知 1计划 2观点 3观点包 4问答 5理财师 6话题
	 * @param $discussion_id
	 * @param $has_planner  //是否获理财师信息
	 * @param $uid
	 * @param $is_sub_plan //是否订阅计划
	 * @return array
	 */
	public function getDiscussionInfo($discussion_type, $discussion_id, $has_planner = false, $uid = 0, $is_sub_plan = false) {
		//优先走缓存(300秒)
		$redis_key = MEM_PRE_KEY . md5(implode('|', func_get_args()));
		$result = Yii::app()->redis_r->get($redis_key);
		if (!empty($result)) {
			return json_decode($result, TRUE);
		}

		$result = array();
		if (CommentNew::DISCUSSION_TYPE_PLAN == $discussion_type) {
			//ID 名称 目标收益 当前收益 止损  天数 已经运行天数  费用  状态  同期沪深300收益 历史年化 日均仓位  理财师信息：id 名称 头像 公司 职位
			$plan_map = Plan::model()->getPlanInfo($discussion_id);
			if (!empty($plan_map) && isset($plan_map[$discussion_id])) {
				$plan['id'] = $plan_map[$discussion_id]['pln_id'];
				$plan['pln_id'] = $plan_map[$discussion_id]['pln_id'];
				$plan['name'] = $plan_map[$discussion_id]['name'];
				$plan['image'] = $plan_map[$discussion_id]['image'];
				$plan['p_uid'] = $plan_map[$discussion_id]['p_uid'];
				$plan['subscription_price'] = $plan_map[$discussion_id]['subscription_price'];
				$plan['target_ror'] = $plan_map[$discussion_id]['target_ror'];
				$plan['curr_ror'] = $plan_map[$discussion_id]['curr_ror'];
				$plan['stop_loss'] = $plan_map[$discussion_id]['stop_loss'];
				$plan['invest_days'] = $plan_map[$discussion_id]['invest_days'];
				$plan['run_days'] = $plan_map[$discussion_id]['run_days'];
				$plan['status'] = $plan_map[$discussion_id]['status'];
				$plan['hs300'] = $plan_map[$discussion_id]['hs300'];
				$result = $plan;
			}
		} else if (CommentNew::DISCUSSION_TYPE_REWARD == $discussion_type) {
			////圈子打赏礼物内容
			$gift_info = Gift::model()->getGiftInfoByIds(array($discussion_id));
			if (isset($gift_info[$discussion_id])) {
				$result = $gift_info[$discussion_id];
			}
		} else if (CommentNew::DISCUSSION_TYPE_VIEW == $discussion_type) {
			$view_map = View::model()->getViewById($discussion_id);
			if (!empty($view_map) && isset($view_map[$discussion_id])) {
				$view_click = View::model()->getViewClick($discussion_id);

				$view['id'] = $view_map[$discussion_id]['id'];
				$view['title'] = $view_map[$discussion_id]['title'];
				$view['view_num'] = $view_map[$discussion_id]['view_num'];
				$view['p_time'] = $view_map[$discussion_id]['p_time'];
				$view['click'] = isset($view_click[$discussion_id]) ? (int) $view_click[$discussion_id] : 0;
				$view['view_num'] = $view['click']; // 取 click 的值
				$view['p_uid'] = $view_map[$discussion_id]['p_uid'];
				$view['summary'] = $view_map[$discussion_id]['summary'];
				$result = $view;
			}
		} else if (CommentNew::DISCUSSION_TYPE_PACKAGE == $discussion_type) {
			//id 图片 名称 关注数  观点数  理财师信息：id 名称 头像 公司 职位
			$package_map = Package::model()->getPackagesById($discussion_id);
			if (!empty($package_map) && isset($package_map[$discussion_id])) {
				$package['id'] = $package_map[$discussion_id]['id'];
				$package['pkg_id'] = $package_map[$discussion_id]['pkg_id'];
				$package['p_uid'] = $package_map[$discussion_id]['p_uid'];
				$package['title'] = $package_map[$discussion_id]['title'];
				$package['summary'] = $package_map[$discussion_id]['summary'];
				$package['image'] = $package_map[$discussion_id]['image'];
				$package['view_num'] = $package_map[$discussion_id]['view_num'];
				$package['sub_num'] = $package_map[$discussion_id]['sub_num'];
				$package['collect_num'] = $package_map[$discussion_id]['collect_num'];
				$result = $package;
			}
		} else if (CommentNew::DISCUSSION_TYPE_ASK == $discussion_type) {
			//id 内容 状态 理财师信息：id 名称 头像 公司 职位
			$question_map = Question::model()->getQuestionById($discussion_id);
			if (!empty($question_map) && isset($question_map[$discussion_id])) {
				$question['id'] = $question_map[$discussion_id]['id'];
				$question['q_id'] = $question_map[$discussion_id]['q_id'];
				$question['p_uid'] = $question_map[$discussion_id]['p_uid'];
				$question['content'] = $question_map[$discussion_id]['content'];
				$question['status'] = $question_map[$discussion_id]['status'];
				$question['price'] = $question_map[$discussion_id]['price'];
				$question['a_summary'] = $question_map[$discussion_id]['a_summary_lock'] ? '请见私密内容' : $question_map[$discussion_id]['a_summary'];
				$result = $question;
			}
		} else if (CommentNew::DISCUSSION_TYPE_PLANNER == $discussion_type) {
			$planner_map = Planner::model()->getPlannerByIdsNew($discussion_id, 3);
			if (!empty($planner_map) && isset($planner_map[$discussion_id])) {
				$result['s_uid'] = $planner_map[$discussion_id]['p_uid'];
				$result['name'] = $planner_map[$discussion_id]['name'];
				$result['image'] = $planner_map[$discussion_id]['image'];
				$result['company_id'] = $planner_map[$discussion_id]['company_id'];
				$result['company_name'] = $planner_map[$discussion_id]['company_name'];
				$result['summary'] = $planner_map[$discussion_id]['summary'];
			}
			$planner_ext_map = Planner::model()->getPlannerExtByIds($discussion_id);
			if (!empty($planner_ext_map) && isset($planner_ext_map[$discussion_id])) {
				$result['pln_year_rate'] = $planner_ext_map[$discussion_id]['pln_year_rate'];
				$result['card_page'] = $planner_ext_map[$discussion_id]['card_page'];
			} else {
				$result['pln_year_rate'] = "0";
				$result['card_page'] = "0";
			}
			$view_num = View::model()->getViewNumByPuid($discussion_id);
			$result['view_num'] = empty($view_num) ? "0" : $view_num;

			$planner_ask_map = Planner::model()->getPlannerAskInfoById($discussion_id);
			if (!empty($planner_ask_map) && isset($planner_ask_map[$discussion_id])) {
				$result['q_num'] = $planner_ask_map[$discussion_id]['q_num'];
			} else {
				$result['q_num'] = "0";
			}
		} else if (CommentNew::DISCUSSION_TYPE_TOPIC == $discussion_type) {

		} else if (CommentNew::DISCUSSION_TYPE_COURSE == $discussion_type) {
			//课程 discussion_id 对应的课程id
			$course_map = Course::model()->getCourseByIds($discussion_id);
			if (!empty($course_map) && isset($course_map[$discussion_id])) {
				$question['id'] = $course_map[$discussion_id]['id'];
				$question['title'] = $course_map[$discussion_id]['title'];
				$question['type'] = $course_map[$discussion_id]['type'];
				if ($course_map[$discussion_id]['type'] == 5) {
					$question['start_date'] = $course_map[$discussion_id]['start_date'];
					$question['end_date'] = $course_map[$discussion_id]['end_date'];
					$question['order_time'] = $course_map[$discussion_id]['order_time'];
                		}
				$question['last_utime'] = $course_map[$discussion_id]['last_utime'];
				$question['subscription_price'] = $course_map[$discussion_id]['subscription_price'];
				$result = $question;
			}
		} else if (CommentNew::DISCUSSION_TYPE_SILK == $discussion_type) {
			//锦囊 discussion_id 对应的锦囊id
			$silk_map = Silk::model()->getSilkByIds($discussion_id);
			if (!empty($silk_map) && isset($silk_map[$discussion_id])) {
				$question['id'] = $silk_map[$discussion_id]['id'];
				$question['title'] = $silk_map[$discussion_id]['title'];
				$question['summary'] = $silk_map[$discussion_id]['summary'];
				$question['subscription_price'] = $silk_map[$discussion_id]['subscription_price'];
				$question['start_time'] = $silk_map[$discussion_id]['start_time'];
				$question['end_time'] = $silk_map[$discussion_id]['end_time'];
				$question['sys_time'] = date('Y-m-d H:i:s');
				$result = $question;
			}
        } else if (CommentNew::DISCUSSION_TYPE_DYNAMIC == $discussion_type) {
			//动态 discussion_id 对应的动态id
			$dynamic_map = Dynamic::model()->getDynamicByIds(array($discussion_id));
			if (!empty($dynamic_map) && isset($dynamic_map[$discussion_id])) {
				$question['id'] = $dynamic_map[$discussion_id]['id'];
				$question['content'] = $dynamic_map[$discussion_id]['content'];
				$question['p_uid'] = $dynamic_map[$discussion_id]['p_uid'];
				$question['imgurl'] = $dynamic_map[$discussion_id]['imgurl'];
				$question['imgurls'] = DynamicService::parseUrl($dynamic_map[$discussion_id]['imgurl']);
				$question['radio_url'] = $dynamic_map[$discussion_id]['radio_url'];
				$question['radio_length'] = $dynamic_map[$discussion_id]['radio_length'];
				$question['is_vip'] = $dynamic_map[$discussion_id]['is_vip_service'];
				$question['sys_time'] = date('Y-m-d H:i:s');
				// $question['login_user_service'] = $login_user_service;
				$result = $question;
			}
        }else if (CommentNew::DISCUSSION_TYPE_SILK_ARTICLE == $discussion_type) {
            //锦囊文章 discussion_id 对应的锦囊文章id  by dingpeng 20180529
			$article_map = Silk::model()->getArticleById(array($discussion_id));
			$article_map = $article_map[0];
			if(!empty($article_map) && isset($article_map['id']) && $article_map['status'] == 0){
				$question['id'] = $article_map['id'];
				$question['title'] = $article_map['title'];
                $question['summary'] = $article_map['summary'];
                $question['content'] = $article_map['content'];
                $silk_map = Silk::model()->getSilkByIds($article_map['silk_id']);
                if (!empty($silk_map) && isset($silk_map[$article_map['silk_id']])) {
                	$silk = $silk_map[$article_map['silk_id']];
                	$question['silk_id'] = $silk['id'];
                    $question['silk_name'] = $silk['title'];
                    $question['summary'] = $silk['summary'];
                }
                $result = $question;
			}
		} else if (CommentNew::DISCUSSION_TYPE_PLAN_TRANS == $discussion_type) {
			$trans_map = Plan::model()->getTransactionsByIds($discussion_id);
			//id,pln_id,symbol,type,deal_price,deal_amount,status,profit,wgt_before,wgt_after,reason,c_time
			if (!empty($trans_map) && isset($trans_map[$discussion_id])) {
				$tran = $trans_map[$discussion_id];
				$transaction['id'] = $tran['id'];
				$transaction['is_encrypt'] = 0;
				$transaction['symbol'] = $tran['symbol'];
				$transaction['stock_name'] = '';
				$transaction['type'] = $tran['type'];
				$transaction['deal_price'] = $tran['deal_price'];
				$transaction['deal_amount'] = $tran['deal_amount'];
				$transaction['wgt_before'] = $tran['wgt_before'];
				$transaction['wgt_after'] = $tran['wgt_after'];
				$transaction['reason'] = $tran['reason'];
				$transaction['c_time'] = $tran['c_time'];
				$transaction['single_profit'] = 0;
				$transaction['total_profit'] = 0;


				$plan_info = null;
				//卖出计算收益
				if ($transaction['type'] == 2) {
					if ($tran['hold_avg_cost'] > 0 && $tran['deal_amount'] > 0) {
						$transaction['single_profit'] = sprintf("%.4f", ($tran['deal_price'] * $tran['deal_amount'] - $tran['transaction_cost']) / ($tran['hold_avg_cost'] * $tran['deal_amount']) - 1);
					}

					$plan_map = Plan::model()->getPlanInfo($tran['pln_id']);
					$plan_info = !empty($plan_map) && isset($plan_map[$tran['pln_id']]) ? $plan_map[$tran['pln_id']] : array();
					if (isset($plan_info['init_value']) && $plan_info['init_value'] > 0) {
						$transaction['total_profit'] = sprintf("%.4f", $tran['profit'] / $plan_info['init_value']);
					}
				}

				//获取股票名称
				if (!empty($transaction['symbol'])) {
					$stocks = AskTags::model()->getTagsBySymbol('stock_cn', array($transaction['symbol']));
					if (!empty($stocks) && isset($stocks[$transaction['symbol']])) {
						$transaction['stock_name'] = $stocks[$transaction['symbol']]['name'];
					}
				}

				//买入  只有购买用户可看  并且在10分钟之内加密数据
				if ($transaction['type'] == 1) {
					$is_subscription = $is_sub_plan ? 1 : 0;
					/* if (!empty($uid)) {
					  $plan_subscription = PlanSubscription::model()->getPlanSubscriptionInfo($uid, $tran['pln_id']);
					  if (!empty($plan_subscription)) {
					  $is_subscription = 1;
					  } else {
					  //
					  if (empty($plan_info)) {
					  $plan_map = Plan::model()->getPlanInfo($tran['pln_id']);
					  $plan_info = !empty($plan_map) && isset($plan_map[$tran['pln_id']]) ? $plan_map[$tran['pln_id']] : array();
					  }
					  if (!empty($plan_info)) {
					  $planer_uid = User::model()->getUidIndex('s_uid', $plan_info['p_uid']);
					  if ($planer_uid == $uid) {
					  $is_subscription = 1;
					  }
					  }
					  }
					  } */

					if ($is_subscription == 1) {
						//加密数据
						if (strtotime($tran['c_time']) + 600 > time()) {
							$transaction['is_encrypt'] = 1;
							$tran = array('symbol' => $transaction['symbol'], 'stock_name' => $transaction['stock_name'], 'deal_price' => $transaction['deal_price'], 'deal_amount' => $transaction['deal_amount']);
							$transaction['symbol'] = CommonUtils::encrypt3DES($transaction['symbol'], CommonUtils::$key_3des_symbol);
							$transaction['stock_name'] = CommonUtils::encrypt3DES($transaction['stock_name'], CommonUtils::$key_3des_symbol);
							$transaction['deal_price'] = CommonUtils::encrypt3DES($transaction['deal_price'], CommonUtils::$key_3des_symbol);
							$transaction['deal_amount'] = CommonUtils::encrypt3DES($transaction['deal_amount'], CommonUtils::$key_3des_symbol);
							$transaction['crypt_data'] = CommonUtils::encrypt3DES(base64_encode(json_encode($tran)), CommonUtils::$key_3des_symbol);
						}
					} else {
						$transaction['symbol'] = '';
						$transaction['stock_name'] = '';
						$transaction['deal_price'] = '';
						$transaction['deal_amount'] = '';
					}
				}



				$result = $transaction;
			}
		}

		//添加理财师信息
//        if ($has_planner && !empty($result) && isset($result['p_uid'])) {
//            $planner_map = Planner::model()->getPlannerByIdsNew($result['p_uid']);
//            if (!empty($planner_map) && isset($planner_map[$result['p_uid']])) {
//                $planner['p_uid'] = $planner_map[$result['p_uid']]['p_uid'];
//                $planner['name'] = $planner_map[$result['p_uid']]['name'];
//                $planner['image'] = $planner_map[$result['p_uid']]['image'];
//                $planner['company_id'] = $planner_map[$result['p_uid']]['company_id'];
//                $planner['company_name'] = $planner_map[$result['p_uid']]['company_name'];
//                $planner['position_id'] = $planner_map[$result['p_uid']]['position_id'];
//                $planner['position_name'] = $planner_map[$result['p_uid']]['position_name'];
//                $result['planner_info'] = $planner;
//            }
//        }
		//添加理财师的计划年化收益率  日均仓位  定期用户是否观察 购买计划
		if (CommentNew::DISCUSSION_TYPE_PLAN == $discussion_type && isset($result['planner_info'])) {
			//计划年化收益率
			$planner_ext_map = Planner::model()->getPlannerExt(array($result['p_uid']));
			if (!empty($planner_ext_map) && isset($planner_ext_map[$result['p_uid']])) {
				$result['planner_info']['pln_year_rate'] = $planner_ext_map[$result['p_uid']]['pln_year_rate'];
			}
			//日均仓位
			//计划评估信息
			$plan_assess_map = PlanAssess::model()->getAssessInfo($discussion_id);
			$plan_assess = isset($plan_assess_map[$discussion_id]) ? $plan_assess_map[$discussion_id] : array();
			if (!empty($plan_assess)) {
				//平均仓位
				$result['avg_weight'] = $plan_assess['hold_days'] > 0 ? $plan_assess['hold_total_weight'] / $plan_assess['hold_days'] : 0;
			}

			//是否订阅观察计划
			$result['is_subscription'] = 0;
			$result['is_attention'] = 0;
			if (!empty($uid)) {
				//订阅
				$plan_subscription = PlanSubscription::model()->getPlanSubscriptionInfo($uid, $discussion_id);
				if (!empty($plan_subscription)) {
					$result['is_subscription'] = 1;
				}
				//观察
				$attention_info = Collect::model()->getUserCollectById($uid, $discussion_id);
				if (!empty($attention_info)) {
					$result['is_attention'] = 1;
				}
			}
		}

		Yii::app()->redis_r->setex($redis_key, 300, json_encode($result));
		return $result;
	}

	/**
	 * 根据评论编号获取相应的评论信息
	 * @param type $cmn_ids
	 */
	public function getCommentByCmnid($cmn_ids) {

		$temp_comment = CommentNew::model()->getCommentByIds($cmn_ids);
		$comment['data'] = $temp_comment;
		//获取的数据为空直接返回
		if (empty($comment['data'])) {
			return NULL;
		}

		$sub_comment_map = array();
		$users_map = array();
		$planners_map = array();
		$this->getOtherInfo($comment, $sub_comment_map, $users_map, $planners_map);
		//处理评论的内容
		foreach ($comment['data'] as & $cmn) {
			$this->assembleCommentInfo($cmn, $cur_uid, $users_map, $planners_map, false, ($is_anonymous && $cmn['is_anonymous'] == 1));
		}
		$res = Array();
		foreach ($comment['data'] as &$item) {
			$this->getRelation_title($item);
			$res[] = $item;
		}
		return $res;
	}

	/**
	 * 获取时内容号数组
	*/
    public static function getNeiRongHao(){
        $neirong = Yii::app()->redis_r->get("lcs_NeiRongHao_puids");
        $neirong = json_decode($neirong,true);
        return $neirong;
    }

    /**
	 * 根据起止日期返回期间有几周，每周的第一个交易日和最后一个交易日
	 * @param '0000-00-00' $start_day
     * @param '0000-00-00' $end_day
    */
    public static function GetWeek($start_day,$end_day){
    	$start_time = strtotime($start_day);
    	$end_time = strtotime($end_day);
    	$week = array();
    	$num = 0;
        $week_num = 0;
    	while($start_time < $end_time){
    	    $day = date('Y-m-d',$start_time);
    	    $pre_day = date("Y-m-d",($start_time - 86400));
            $is_trade = Calendar::model()->isTradeDay($day);
    		if(date("w",$start_time) < 6){
    			if($week_num == 0){
    				if(!empty($is_trade)){
    					$week[$num]['start_day'] = $day;
    					$week_num++;
					}
                }
                if (empty($is_trade)) {
                    if (empty($week[$num]['end_day'])) {
                        $week[$num]['end_day'] = $pre_day;
                    }
                }
			}else{
    			$week_num = 0;
    			$num++;
			}
			$start_time = $start_time + 86400;
		}
		return $week;
	}
}

?>
