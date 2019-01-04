<?php

/**
 * 吧啦吧啦说说
 *
 * add by zhihao6 2016/02/29
 */

class BalaCommentService
{
    public static function getCommentAuthGroup($cmn_type=51, $relation_id=8888)
    {
        if ($cmn_type != 51) {
            return array();
        }

        $auth_group = array();
        switch($relation_id){
        case 8888 :
            $auth_group = array(UserStaff::STOCK_TRADE);
            break;
        case 8889 :
            $auth_group = array(UserStaff::OIL_TRADE);
            break;
        case 8890 :
            $auth_group = array(UserStaff::US_TRADE);
            break;
        case 8891 :
            $auth_group = array(UserStaff::HKS_TRADE);
            break;
        case 8892 :
            $auth_group = array(UserStaff::QH_TRADE);
            break;
        }
        return $auth_group;
    }

	// 吧啦吧啦的 redis key
	private static function getCommentRediskey($crc32_id, $type, $time='')
	{
		$redis_key = '';

		switch ($type) {
		case 1: // 普通最近说说
			$redis_key = MEM_PRE_KEY . "balacmn_{$crc32_id}_lasttime_normal";
			break;
		case 2: // 小妹最近说说
			$redis_key = MEM_PRE_KEY . "balacmn_{$crc32_id}_lasttime_xiaomei";
			break;
		case 9: // 看涨看跌统计
            if (! in_array($crc32_id, array('635961577', '1391407231'))) { // 非美股、港股
                if (empty($time)) {
                    $the_day = date('Y-m-d');
                    $the_time = date('Y-m-d H:i:s');
                } else {
                    $the_day = date('Y-m-d', strtotime($time));
                    $the_time = date('Y-m-d H:i:s', strtotime($time));
                }
                $next_day = Yii::app()->lcs_r->createCommand("select cal_date from lcs_calendar where  cal_date >'$the_day' order by cal_date asc limit 1")->queryScalar();
                $prev_day = Yii::app()->lcs_r->createCommand("select cal_date from lcs_calendar where  cal_date <='$the_day' order by cal_date desc limit 1")->queryScalar();
                if ($the_time < date('Y-m-d 15:00:00', strtotime($prev_day))) {
                    $redis_key = MEM_PRE_KEY . "balacmn_{$crc32_id}_updownstat_".date('Ymd', strtotime($prev_day));
                } else {
                    $redis_key = MEM_PRE_KEY . "balacmn_{$crc32_id}_updownstat_".date("Ymd", strtotime($next_day));
                }

                if ( ! Yii::app()->redis_r->hget($redis_key, 'status')) {
                    Yii::app()->redis_w->hset($redis_key, 'status', 1);
                    Yii::app()->redis_w->expire($redis_key, 864000); // 10天，包括节假日
                }
            } else { // 美股、港股 按自然日处理
                if (empty($time)) {
                    $time = date('Y-m-d H:i:s');
                }
                $redis_key = MEM_PRE_KEY . "balacmn_{$crc32_id}_updownstat_".date('Ymd', strtotime($time));
                if ( ! Yii::app()->redis_r->hget($redis_key, 'status')) {
                    Yii::app()->redis_w->hset($redis_key, 'status', 1);
                    Yii::app()->redis_w->expire($redis_key, 172800); // 2天
                }
            }
			break;
		default:
			# code...
			break;
		}

		return $redis_key;
	}

    public static function getBalaUpDownStatKey($crc32_id, $time='')
    {
        return self::getCommentRediskey($crc32_id, 9, $time);
    }
	public static function getBalaUpDownStat($crc32_id)
	{
		$redis_key = self::getBalaUpDownStatKey($crc32_id);
		$res = Yii::app()->redis_r->hgetall($redis_key);
        unset($res['status']);
		if (empty($res['up'])) {
            $res['up'] = 0;
        }
        if (empty($res['down'])) {
            $res['down'] = 0;
        }
        if (empty($res['position'])) {
            $res['position'] = "5";
        }
        return $res;
	}
	public static function incBalaUpStat($crc32_id, $num=1)
	{
		$redis_key = self::getBalaUpDownStatKey($crc32_id);
		$num = Yii::app()->redis_w->hincrby($redis_key, 'up', $num);
        if ($num < 0) {
            $num = Yii::app()->redis_w->hset($redis_key, 'up', 0);
        }
        return $num;
	}
	public static function incBalaDownStat($crc32_id, $num=1)
	{
		$redis_key = self::getBalaUpDownStatKey($crc32_id);
		$num = Yii::app()->redis_w->hincrby($redis_key, 'down', $num);
        if ($num < 0) {
            $num = Yii::app()->redis_w->hset($redis_key, 'down', 0);
        }
        return $num;
	}

	public static function getCommentRecently($crc32_id, $type)
	{
        switch ($type) {
        case 1: // 普通最近说说
            $redis_key = self::getCommentRediskey($crc32_id, 1);
            break;
        case 2: // 小妹最近说说
            $redis_key = self::getCommentRediskey($crc32_id, 2);
            break;
        default:
            $redis_key = self::getCommentRediskey($crc32_id, 1);
            break;
        }
		return Yii::app()->redis_r->get($redis_key);
	}
	public static function setCommentRecently($crc32_id, $type, $time)
	{
		switch ($type) {
        case 1: // 普通最近说说
            $redis_key = self::getCommentRediskey($crc32_id, 1);
            break;
        case 2: // 小妹最近说说
            $redis_key = self::getCommentRediskey($crc32_id, 2);
            break;
        default:
            $redis_key = self::getCommentRediskey($crc32_id, 1);
            break;
        }
		return Yii::app()->redis_w->set($redis_key, $time);
	}

    public static function getUserTypeLastComment($u_type)
    {
        $cmn = NewComment::model()->getBalaCommentList(array(), array('u_type' => $u_type), array('c_time desc'), 1, 1);
        if (empty($cmn['data'])) {
            return null;
        } else {
            return $cmn['data'];
        }
    }

    /**
     * 处理说说内容回复的用户信息，包括用户和理财师
     * @param $cmn
     * @param $users_map
     * @param $planners_map
     * @param $is_realname
     */
    public static function handleCommentReplyUserInfo(&$cmn, $users_map,$planners_map, $is_realname) {
        $is_realname = true;
        if(isset($cmn['reply_u_type'])&&$cmn['reply_u_type']==1){
            if ($is_realname) {
                $cmn['reply_name'] = isset($users_map[$cmn['reply_uid']]) && !empty($users_map[$cmn['reply_uid']]) ? $users_map[$cmn['reply_uid']]['name'] : '';
            } else {
                $cmn['reply_name'] = CommonUtils::getShowName($cmn['reply_uid']);
            }
            $cmn['reply_image'] = isset($users_map[$cmn['reply_uid']]) && !empty($users_map[$cmn['reply_uid']]) ? CommonUtils::convertUserImage($users_map[$cmn['reply_uid']]['image'], 50) : '';
        }else if(isset($cmn['reply_u_type'])&&$cmn['reply_u_type']==2){
            $cmn['reply_name'] = isset($planners_map[$cmn['reply_uid']]) && !empty($planners_map[$cmn['reply_uid']]) ? $planners_map[$cmn['reply_uid']]['name'] : '';
            $cmn['reply_image'] = isset($planners_map[$cmn['reply_uid']]) && !empty($planners_map[$cmn['reply_uid']]) ? CommonUtils::convertUserImage($planners_map[$cmn['reply_uid']]['image'], 50) : '';
            $cmn['reply_company'] = isset($planners_map[$cmn['reply_uid']]) && !empty($planners_map[$cmn['reply_uid']]) ? $planners_map[$cmn['reply_uid']]['company'] : '';
            $cmn['reply_position'] = isset($planners_map[$cmn['reply_uid']]) && !empty($planners_map[$cmn['reply_uid']]) ? $planners_map[$cmn['reply_uid']]['position'] : '';
        } else if(isset($cmn['reply_u_type'])&&$cmn['reply_u_type']==3) {
            $cmn['reply_name'] = NewCommentService::$def_admin_user_name;
            $cmn['reply_image'] = NewCommentService::$def_admin_user_image;
        } else {}
    }

    // 吧啦吧啦用户参与的说说列表
    public static function getBalaMyCommentListPage($cmn_type, $relation_id, $uid, $cmn_id=0, $page=1, $page_num=10)
    {
        $fields_array = array('cmn_id','reply_id', 'root_reply_id');
        $where_array = array();
        $where_array['cmn_type'] = $cmn_type;
        $where_array['relation_id'] = $relation_id;
        $where_array['uid'] = $uid;
        $order_array = array('c_time desc');

        $page_list = NewComment::model()->getBalaCommentList($fields_array, $where_array, $order_array, 1, 700);
        if (empty($page_list['data'])) $page_list['data'] = array();

        $ids = array();
        foreach ($page_list['data'] as $key => $value) {
            if (!empty($cmn_id) && ($value['cmn_id'] == $cmn_id || (!empty($value['root_reply_id']) && $value['root_reply_id'] == $cmn_id))) {
                continue;
            }
            if (!empty($value['root_reply_id'])) {
                $ids[] = $value['root_reply_id'];
            } else {
                $ids[] = $value['cmn_id'];
            }
        }
        $ids = array_unique($ids);
        $page_list = CommonUtils::arrayPage($ids, $page_num, $page);

        $idx = NewCommentService::getCRC32TbIndex($cmn_type, $relation_id);
        $comment_map = NewComment::model()->getCommentByIds($page_list['data'], $idx['tb_index']);
        foreach ($page_list['data'] as $id) {
            if (isset($comment_map[$id])) {
                $data[] = $comment_map[$id];
            }
        }
        $page_list['data'] = $data;

        self::handleCommentList($page_list['data'], 1, 0);

        if (!empty($cmn_id)) {
            $fields_array = array('cmn_type','relation_id','cmn_id','u_type','uid','content','reply_id','root_reply_id','c_time','reply_num','praise_num','is_top','is_good','up_down','is_anonymous','child_relation_id','discussion_type','discussion_id');
            $where_array = array();
            $where_array['cmn_type']    = $cmn_type;
            $where_array['relation_id'] = $relation_id;
            $where_array['cmn_id']      = $cmn_id;
            $order_array = array();

            $page_list['cmn_info'] = NewComment::model()->getBalaCommentList($fields_array, $where_array, $order_array, 1, 1);
            self::handleCommentList($page_list['cmn_info']['data'], 1, 0);
            $page_list['cmn_info'] = $page_list['cmn_info']['data']['0'];
        }

        return $page_list;
    }

	// 吧啦吧啦说说列表数据
	public static function getBalaCommentListPage($cmn_type=array(51), $relation_id=array(array('8888','8889','8890')), $u_type=0, $cur_uid=0, $is_good=0, $up_down=0, $page=1, $per_page=20, $start_time='', $end_time='')
	{
		$apiResult = new ApiResult();

        // 从master表取普通说说列表
		$fields_array = array('cmn_type','relation_id','cmn_id','u_type','uid','content','reply_id','root_reply_id','c_time','reply_num','praise_num','is_top','is_good','up_down','is_anonymous','child_relation_id','discussion_type','discussion_id');
    	$where_array = array();
        foreach ($cmn_type as $ii => $type) {
            if (!empty($relation_id[$ii])) {
                // $where_array['cmn_type or'][] = " (cmn_type='{$type}' and relation_id in ('".implode("','", $relation_id[$ii])."')) ";
                // 改用crc32值处理
                $tmp_crc32_ids = array();
                foreach ($relation_id[$ii] as $r_d) {
                    $tmp_crc32_ids[] = CommonUtils::getCRC32($type . '_' . $r_d);
                }
                $where_array['cmn_type or'][] = " (crc32_id in ('".implode("','", $tmp_crc32_ids)."')) ";
            } else {
                $where_array['cmn_type or'][] = " (cmn_type='{$type}') ";
            }
        }
    	$where_array['root_reply_id'] = 0;
        $where_array['is_top !='] = 1;
    	if (in_array($u_type, array(1,2,3))) {
    		$where_array['u_type'] = $u_type;
    	}
    	if ($is_good === 1) {
    		$where_array['is_good'] = $is_good;
    	}
    	if (in_array($up_down, array(1,-1))) {
    		$where_array['up_down'] = $up_down;
    	}
    	if (!empty($start_time)) {
    		if (empty($end_time)) {
    			$end_time = date('Y-m-d H:i:s');
    		}
    		$where_array['c_time >'] = $start_time;
    		$where_array['c_time <='] = $end_time;
    	}
    	$order_array = array('c_time desc');

    	$page_list = NewComment::model()->getBalaCommentList($fields_array, $where_array, $order_array, $page, $per_page);
    	if (empty($page_list['data'])) $page_list['data'] = array();

        // 第一页取置顶说说
        if ($page == 1) {
            $fields_array = array('cmn_type','relation_id','cmn_id','u_type','uid','content','reply_id','root_reply_id','c_time','reply_num','praise_num','1 as is_top','is_good','up_down','is_anonymous','child_relation_id','discussion_type','discussion_id');
            unset($where_array['is_top !=']);
            $order_array = array('u_time desc');
            $quality_list = NewComment::model()->getBalaCommentList($fields_array, $where_array, $order_array, 1, 0, 2);
            
            if (!empty($quality_list['data'])) {
                $page_list['data'] = array_merge($quality_list['data'], $page_list['data']);
            }
            unset($quality_list);
        }

    	self::handleCommentList($page_list['data'], 1, $cur_uid);

    	$apiResult->setData($page_list);
		return $apiResult;
	}
	// 吧啦吧啦二级说说列表数据
	public static function getSecondBalaCommentList($cmn_type, $relation_id, $root_reply_id, $cur_uid=0, $has_root=0, $page=1, $per_page=10)
	{
		$fields_array = array('cmn_type','relation_id','cmn_id','u_type','uid','content','reply_id','root_reply_id','c_time','reply_num','praise_num','is_top','is_good','up_down','is_anonymous','child_relation_id','discussion_type','discussion_id');
        $where_array = array();
		$where_array['cmn_type']      = $cmn_type;
		$where_array['relation_id']   = $relation_id;
		$where_array['root_reply_id'] = $root_reply_id;
    	$order_array = array('c_time desc');

    	$page_list = NewComment::model()->getBalaCommentList($fields_array, $where_array, $order_array, $page, $per_page);
    	if (empty($page_list['data'])) $page_list['data'] = array();

        self::handleReplyCommnetUser($cmn_type, $relation_id, $page_list['data']);
    	self::handleCommentList($page_list['data'], 2, $cur_uid);

    	if ($has_root) {
    		$where_array = array();
			$where_array['cmn_type']    = $cmn_type;
			$where_array['relation_id'] = $relation_id;
			$where_array['cmn_id']      = $root_reply_id;
			$order_array = array();

    		$page_list['root_cmn'] = NewComment::model()->getBalaCommentList($fields_array, $where_array, $order_array, 1, 1);
    		self::handleCommentList($page_list['root_cmn']['data'], 2, $cur_uid);
    		$page_list['root_cmn'] = $page_list['root_cmn']['data']['0'];
    	}

        return $page_list;
	}
	// 处理说说列表详情
	// $list_type: 1:列表；2：二级列表
	public static function handleCommentList(&$cmn_list, $list_type=1, $cur_uid=0)
	{
        if (!is_array($cmn_list) || empty($cmn_list)) {
            return ;
        }

		$u_ids = array();
		$p_ids = array();
        $c_r_ids = array();
        $media_map_ids = array();
    	foreach ($cmn_list as $item) {
    		if ($item['u_type'] == 1) {
    			$u_ids[] = $item['uid'];
    		} elseif ($item['u_type'] == 2) {
    			$p_ids[] = $item['uid'];
    		} else {}

            if (isset($item['reply_u_type']) && $item['reply_u_type'] == 1) {
                $u_ids[] = $item['reply_uid'];
            } elseif (isset($item['reply_u_type']) && $item['reply_u_type'] == 2) {
                $p_ids[] = $item['reply_uid'];
            } else {}

            // 财经相关页面过来的说说处理
            if (!empty($item['child_relation_id'])) {
                $c_r_ids[] = $item['child_relation_id'];
            }

            $crc32_id = CommonUtils::getCRC32($item['cmn_type'] . '_' . $item['relation_id']);
            $media_map_ids[$crc32_id][] = $item['cmn_id'];
    	}

    	$users_map = !empty($u_ids) ? User::model()->getUserInfoById(array_unique($u_ids)) : array();
        // $planners_map = !empty($p_ids) ? Planner::model()->getPlannerById(array_unique($p_ids)) : array();
        // 用以下新方法获取同时获取理财师评级信息
        $planners_map = !empty($p_ids) ? Planner::model()->getPlannerByIdsNew(array_unique($p_ids), 35) : array();
        $child_relation_map = !empty($c_r_ids) ? Quote::model()->getQuotebyids(array_unique($c_r_ids)) : array();

        $media_map = !empty($media_map_ids) ? NewComment::model()->getMediaByCrc32Cmnids($media_map_ids) : array();

        $comment_service = new CommentService();
    	foreach ($cmn_list as &$item) {
    		if ($item['u_type'] == 1) {
                NewCommentService::handleCommentUserInfo($item, $users_map, FALSE);
            } else if ($item['u_type'] == 2) {
                NewCommentService::handleCommentPlannerInfo($item, $planners_map);
            } else {
                $item['name'] = NewCommentService::$def_admin_user_name;
                $item['image'] = NewCommentService::$def_admin_user_image;
            }

            self::handleCommentReplyUserInfo($item, $users_map, $planners_map, false);

            // 财经相关页面过来的说说处理
            if (!empty($item['child_relation_id'])) {
                $item['relation_info'] = !empty($child_relation_map) ? $child_relation_map[$item['child_relation_id']] : null;
            } else {
                $item['relation_info'] = null;
            }

            // 媒体资源信息
            $crc32_id = CommonUtils::getCRC32($item['cmn_type'] . '_' . $item['relation_id']);
            $item['media_list'] = isset($media_map[$crc32_id][$item['cmn_id']]) ? $media_map[$crc32_id][$item['cmn_id']] : null;
    		
    		// 用户是否点赞
    		$idx = NewCommentService::getCRC32TbIndex($item['cmn_type'], $item['relation_id']);
    		$item['is_praise'] = NewComment::model()->getUserPraise($idx['tb_index'], $item['cmn_id'], $cur_uid);

    		if ($list_type == 1) {
    			// 二级说说列表
    			// $item['last_replays'] = self::getSecondBalaCommentList($item['cmn_type'], $item['relation_id'], $item['cmn_id'], $cur_uid, 0, 1, 2);
    			// 另一种实现：用现有方法
				$cache_field = MEM_PRE_KEY.'cmn_new_lasted_'.$idx['tb_index'];
				$last_reply_ids = Yii::app()->redis_r->hget($cache_field, $item['cmn_id']);
				if (empty($last_reply_ids)) {
					$item['last_replays'] = null;
				} else {
					$last_reply_ids = explode(',', $last_reply_ids);
					$item['last_replays'] = NewComment::model()->getCommentByIds($last_reply_ids, $idx['tb_index']);
                    if(!empty($item['last_replays'])){
                        krsort($item['last_replays'],1);
                        $item['last_replays'] = array_values($item['last_replays']);
                    }else{
                        $item['last_replays'] = array();
                    }

                    // $item['last_replays'] = array_reverse($item['last_replays']);
                    self::handleReplyCommnetUser($item['cmn_type'], $item['relation_id'], $item['last_replays']);
                    self::handleCommentList($item['last_replays'], 2, $cur_uid);
				}

				//组装分享的信息
                if (!empty($item['discussion_type']) && !empty($item['discussion_id'])) {
                    $discussion_info = $comment_service->getDiscussionInfo($item['discussion_type'], $item['discussion_id'], true, $cur_uid,$item['is_anonymous']?false:true);
                    if (!empty($discussion_info)) {
                        $item[CommentNew::$discussion_field[$item['discussion_type']]] = $discussion_info;
                    }
                }
    		}

            NewCommentService::touchOldColumn($item);
    	}
	}

    // 处理回复说说用户
    private static function handleReplyCommnetUser($cmn_type, $relation_id, &$cmn_list)
    {
        if (!is_array($cmn_list) || empty($cmn_list)) {
            return ;
        }

        $r_ids = array();
        foreach ($cmn_list as $item) {
            if (!empty($item['reply_id'])) {
                $r_ids[] = $item['reply_id'];
            }
        }
        if (empty($r_ids)) {
            return ;
        }

        $idx = NewCommentService::getCRC32TbIndex($cmn_type, $relation_id);
        $reply_cmn_map = NewComment::model()->getCommentByIds($r_ids, $idx['tb_index']);

        foreach ($cmn_list as &$item) {
            $item['reply_u_type'] = !empty($item['reply_id']) && isset($reply_cmn_map[$item['reply_id']]) ? $reply_cmn_map[$item['reply_id']]['u_type'] : 0;
            $item['reply_uid'] = !empty($item['reply_id']) && isset($reply_cmn_map[$item['reply_id']]) ? $reply_cmn_map[$item['reply_id']]['uid'] : 0;
        }
    }


	/**
	 * 发表说说
	 * @param type $cmn_type
	 * @param type $relation_id
	 * @param type $u_info
	 * @param type $u_type_info
	 * @param type $content
	 * @param type $reply_id
	 * @param type $root_reply_id
	 * @param type $mark_words
	 * @param type $source
	 * @param type $discussion_type
	 * @param type $discussion_id
	 * @param type $up_down
	 * @param type $child_relation_id
	 * @param type $is_anonymous
	 * @return type
	 */
	public static function saveBalaComment($cmn_type, $relation_id, $u_info, $u_type_info, $content, $reply_id, $root_reply_id, $mark_words, $source, $discussion_type = '', $discussion_id = '', $up_down = 0, $child_relation_id = 0, $is_anonymous = 0, $is_good = 0, $global_id = 0) {
        $tbindex_info      = NewCommentService::getTbIndex($cmn_type ,$relation_id);
        $match_search = NewCommentService::matchStock($relation_id, $content, $content_html);
		$is_display        = 1;        
        $head_ids          = '';
        $child_relation_id = $child_relation_id;
        $is_privilege      = 0;
        $content = CommonUtils::formatConetentStock($content, true, $tbindex_info['crc32_id']);

		if($u_type_info['is_planner']==1 && in_array($source, array(CommonUtils::FR_LCS_CLIENT, CommonUtils::FR_LCS_CLIENT_IOS))){
            $u_type_info['u_type']=1;
            $u_type_info['comment_uid'] = $u_info['uid'];
        }

        $transaction = Yii::app()->licaishi_comment_w->beginTransaction();
        try {
	        $cmn_data = NewCommentService::saveComment($tbindex_info, $content, $u_type_info['u_type'], $u_type_info['comment_uid'], $reply_id, $is_display, $is_anonymous, $source, $head_ids, $child_relation_id, $mark_words, $is_privilege, $match_search, $discussion_type, $discussion_id, $root_reply_id, $up_down, $is_good, $global_id);
			if(!$cmn_data){
	            return array();
	        }

			$cmn_data['name']            = $u_type_info['name'];
			$cmn_data['image']           = $u_type_info['image'];
			$cmn_data['company']         = $u_type_info['u_type']==2 && isset($u_type_info['company']) ? $u_type_info['company'] : '';
			$cmn_data['admin_user_info'] = $u_type_info['admin_user_info'];
			$cmn_data['content_html']    = $content_html;

            $tmp_cmn_data = array($cmn_data);
            self::handleReplyCommnetUser($cmn_type, $relation_id, $tmp_cmn_data);
            self::handleCommentList($tmp_cmn_data, 2, 0);
            $cmn_data = $tmp_cmn_data['0'];

        	//总说说数加1
        	$is_planner = (2 == $cmn_data['u_type']) ? 1 : 0;
        	NewComment::model()->updatetbIndexNum($cmn_data['crc32_id'] , 'add' ,$is_planner);          	
			$transaction->commit();
            if(!empty($cmn_data['master_id'])&&$tbindex_info['cmn_type']==71){
                $params=array('id'=>$cmn_data['master_id'],'type'=>2);
                Yii::app()->redis_w->rpush('lcs_push_report',json_encode($params));
                Yii::app()->redis_w->rpush('lcs_c_comment_bala',$cmn_data['master_id']);
            }
        } catch(exception $e) {
            $transaction->rollback();
            return array();
        }

        //更新不同crc32_id模块下用户发表说频次
		$crc32info = NewCommentService::getCRC32TbIndex($cmn_type, $relation_id);
        $cmn_pub_times[] = time();
        $key = MEM_PRE_KEY . "cmn_rate_".$crc32info['tb_index']."_" . $u_info['uid'];
        Yii::app()->cache->set($key, $cmn_pub_times, NewCommentService::COMMENT_RATE_RANGE*60);

        //给用户发提醒 用户回复自己的不发提醒
        $reply_info = NewCommentService::getCommentInfo($cmn_type, $relation_id, $reply_id);
        if (!empty($reply_info) && $reply_id > 0 && ($reply_info['u_type'] == 1 || $reply_info['u_type'] == 2) && ($reply_info['uid'] != $u_info['uid'])) {
            $reply_info['cmn_id'] = $root_reply_id; // 取根说说id
            ///NewCommentService::replyMessageToQueue($cmn_data , $reply_info , 'replayCommentNew');
        }

        // 更新看涨看跌
        switch ($up_down) {
    	case -1:
    		self::incBalaDownStat($crc32info['crc32_id']);
    		break;
    	case 1:
    		self::incBalaUpStat($crc32info['crc32_id']);
    		break;
    	default:
    		# code...
    		break;
        }
        
        // 更新最近说说时间
        if (empty($root_reply_id)) {
            switch ($u_type_info['u_type']) {
            case 3: // 小妹，没有break
                BalaCommentService::setCommentRecently($crc32info['crc32_id'], 2, date('Y-m-d H:i:s'));
            default:
                BalaCommentService::setCommentRecently($crc32info['crc32_id'], 1, date('Y-m-d H:i:s'));
                break;
            }
        }

        $cmn_data['c_time_fmt'] = CommonUtils::formatDate($cmn_data['c_time'],'web');        		
        return array(
            'cmn_data' => $cmn_data,
            'up_down_stat' => self::getBalaUpDownStat($crc32info['crc32_id']),
        );
	}
    
    /**
     * 保存说说媒体资源
     * @param type $cmn_data
     * @param type $media_type
     * @param type $media 
     * @param type $duration 音频时长
     * @return type 
     * @throws Exception 
     * 
     */
    public static function saveCommentMedia($cmn_data, $media_type, $media,$duration)
    {
        if ( ! isset($cmn_data['crc32_id']) || ! isset($cmn_data['id']) || 
                ! isset($cmn_data['cmn_type']) || ! isset($cmn_data['relation_id']) || 
                empty($media)) {
            return $cmn_data;
        }

        $curr_time = date("Y-m-d H:i:s");
        $insert_d = array();

        $transaction = Yii::app()->licaishi_comment_w->beginTransaction();
        try {
            foreach ($media as $m) {
                $data = array(
                    "crc32_id"    => $cmn_data['crc32_id'],
                    "cmn_id"      => $cmn_data['id'],
                    "cmn_type"    => $cmn_data['cmn_type'],
                    "relation_id" => $cmn_data['relation_id'],
                    "type"        => $media_type,
                    "url"         => $m,
                    "summary"     => "",
                    "c_time"      => $curr_time,
                    "u_time"      => $curr_time,
                    "duration"    =>$duration
                );

                $data['id'] = NewComment::model()->saveCommentMedia($data);
                if ($data['id']) {
                    $insert_d[] = $data;
                } else {
                    throw new Exception("保存媒体资源出错", RespCode::DEF_ERROR);
                }
            }
            $cmn_data['media_list'] = $insert_d;
            $transaction->commit();
        } catch(exception $e) {
            $transaction->rollback();
            Common::model()->saveLog($e->getMessage(),$level='info',$category='BalaCommentService::saveCommentMedia');
        }

        return $cmn_data;
    }

	/**
	 * 检查用户是否被禁言
	 * @param type $circleId
	 * @param type $UserId
	 * @return boolean
	 */
	public static function checkCircleUserIsDisabled($circleId, $UserId) {
		//写入数据(redis-hash-map)
		$key = MEM_PRE_KEY . "circle_" . $circleId . "_disabled_user";
		//检查数据
		$redis = Yii::app()->redis_r;
		if ($redis->hget($key, $UserId)) {
			return true;
		} else {
			return false;
		}
	}

	public static function getBalaCommentInfo($cmn_type, $circleId, $cmn_id, $field = " * ") {
		return NewComment::model()->getBalaCommentInfo($cmn_type, $circleId, $cmn_id, $field);
	}

}
