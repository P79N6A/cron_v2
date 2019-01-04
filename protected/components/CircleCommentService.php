<?php

/**
 * 圈子说说
 *
 * add by zhihao6 2017/01/06
 */

class CircleCommentService
{
    //======================================================================
    //===[公共方法部分]
    //======================================================================
    
    // 圈子说说类型定义
    // discussion_type: 0未知 1计划 2观点 3观点包 4问答 5理财师 6话题
    // media_type: 1图片 2音频 3视频
    public static $msg_type = [
        "normal_type" => "LCSG:IM:TXT", // 文本
        "discussion_type_1" => "LCSG:IM:PLAN",
        "discussion_type_2" => "LCSG:IM:VIEW",
        "discussion_type_3" => "LCSG:IM:PKG",
        "discussion_type_4" => "LCSG:IM:ASK",
        "discussion_type_5" => "LCSG:IM:PLANNER",
        "discussion_type_6" => "LCSG:IM:TOPIC",
        "media_type_1" => "LCSG:IM:IMG",
        "media_type_2" => "LCSG:IM:VOICE",
        "media_type_3" => "LCSG:IM:VIDEO",
    ];

    /**
     * 根据说说信息判断说说类型
     * @param array $msg 说说信息
     */
    public static function setCircleMsgType(& $msg)
    {
        if (empty($msg)) return ;

        $msg['msg_type'] = self::$msg_type["normal_type"];

        if (!empty($msg['media_list'])) {
            $msg['msg_type'] = self::$msg_type["media_type_{$msg['media_list']['0']['type']}"];
        }
        if (!empty($msg['discussion_type'])) {
            $msg['msg_type'] = self::$msg_type["discussion_type_{$msg['discussion_type']}"];
        }

        return ;
    }

    /**
     * 圈子时间格式转换器
     * @param  string $time 待格式化的时间
     * @return string       格式化后的时间
     */
    public static function circleTimeFormatConverter($to_f_time)
    {
        $to_f_day_ts = strtotime(date("Y-m-d", strtotime($to_f_time)));
        $to_f_time_ts = strtotime(date("H:i:s", strtotime($to_f_time)));

        $curr_day_ts = strtotime(date("Y-m-d"));
        $yest_day_ts = strtotime(date("Y-m-d", strtotime("-1 day")));
        $last_week_day_ts = strtotime(date("Y-m-d 23:59:59", strtotime("last Sunday")));
        $curr_year_day_tx = strtotime(date("Y-01-01 00:00:00"));
        $weekarray = array("日","一","二","三","四","五","六");
        
        $fmt = "";
        if ($to_f_day_ts == $curr_day_ts) {
            // 当日
        } elseif ($to_f_day_ts == $yest_day_ts) {
            $fmt .= " 昨天";
        } elseif ($to_f_day_ts > $last_week_day_ts) {
            $fmt .= " 周" . $weekarray[date('w', $to_f_day_ts)];
        } elseif ($to_f_day_ts > $curr_year_day_tx) {
            $fmt .= " " . date("m月d日", $to_f_day_ts);
        } else {
            $fmt .= " " . date("Y年m月d日", $to_f_day_ts);
        }

        $fmt .= " " . date("H:i", $to_f_time_ts);

        return trim($fmt);
    }

    //======================================================================
    //===[圈子信息部分]
    //======================================================================
    
    /**
     * 获取圈子信息，并判断指定用户是否在圈子内
     * @param  int $circle_id 圈子id
     * @return array            圈子信息
     */
    public static function getCircleInfo($circle_id)
    {
        $circle_info_map = Circle::model()->getCircleInfoMapByCircleids($circle_id);
        if (isset($circle_info_map[$circle_id])) {
            return $circle_info_map[$circle_id];
        } else {
            return null;
        }
    }

    //======================================================================
    //===[圈子说说部分]
    //======================================================================
    public static function getCircleCommentInfo($cmn_type, $relation_id, $cmn_id)
    {
        $crc32_id = CommonUtils::getCRC32($cmn_type . '_' . $relation_id);
        $cmn_tb_index = $crc32_id % NewComment::COMMENT_TABLE_NUMS;

        print_r("{$cmn_tb_index}\n");

        $cmn_list = NewComment::model()->getCommentInfoFromNormal($cmn_tb_index, $cmn_id);
        if(empty($cmn_list)){
            return null;
        }

        self::handleCommentList($cmn_list);

        return reset($cmn_list);
    }
    /**
     * 公共的圈子消息列表处理方法
     * @param  array  &$cmn_list 圈子列表数据
     */
    public static function handleCommentList(&$cmn_list)
    {
        if (!is_array($cmn_list) || empty($cmn_list)) {
            return ;
        }

        $p_ids = array();
        $media_map_ids = array();
        foreach ($cmn_list as $item) {
            if ($item['u_type'] == 2) {
                $p_ids[] = $item['uid'];
            } else {}

            $crc32_id = CommonUtils::getCRC32($item['cmn_type'] . '_' . $item['relation_id']);
            $media_map_ids[$crc32_id][] = $item['cmn_id'];
        }
        $planners_map = !empty($p_ids) ? Planner::model()->getPlannerById(array_unique($p_ids)) : array();
        $media_map = !empty($media_map_ids) ? NewComment::model()->getMediaByCrc32Cmnids($media_map_ids) : array();

        foreach ($cmn_list as &$item) {
            if ($item['u_type'] == 1) {
                $cmn_user_info = User::model()->getUserInfoByUid($item['uid']);
            } elseif ($item['u_type'] == 2) {
                $cmn_user_info = isset($planners_map[$item['uid']]) ? $planners_map[$item['uid']] : null;
            }
            if (!empty($cmn_user_info)) {
                $item['name'] = $cmn_user_info['name'];
                $item['image'] = $cmn_user_info['image'];
            } else {
                $item['name'] = "系统";
                $item['image'] = "http://www.sinaimg.cn/cj/licaishi/avatar/180/21481168853.jpg";
            }

            // 媒体资源信息
            $crc32_id = CommonUtils::getCRC32($item['cmn_type'] . '_' . $item['relation_id']);
            $item['media_list'] = isset($media_map[$crc32_id][$item['cmn_id']]) ? $media_map[$crc32_id][$item['cmn_id']] : [];

            $item['c_time_fmt'] = self::circleTimeFormatConverter($item['c_time']);

            // 设置消息类型
            self::setCircleMsgType($item);
        }

        return ;
    }
    	/**
	 * 更新圈子消息数量
	 * @param  int $circle_id 圈子id
	 * @param  int $num       数量
	 */
	public static function updateCircleCommentNum($circle_id, $num) {
		// 更新redis
		$redis_key = RedisKeyHelper::buildKey(10003, ["circle_id" => $circle_id, "day_time" => date("Ymd")]);
		$old_val = Yii::app()->redis_r->get($redis_key);
		if (empty($old_val)) {
			Yii::app()->redis_w->incrBy($redis_key, $num);
			Yii::app()->redis_w->expire($redis_key, 172800); // 过期时间2天
		} else {
			Yii::app()->redis_w->incrBy($redis_key, $num);
		}

		// 更新数据库
		Circle::model()->updateCircleInc($circle_id, 'comment_num', $num);
    }
    	/**
	 * 加入消息推送队列
	 *
	 * lcs_common_message_queue
	 * {"type":"plannerCircle","cmn_type":71,"relation_id":24201,"cmn_id":16}
	 *
	 * @param  array $cmn_info 说说内容
	 */
	public static function pushCommentToQueue($cmn_info) {
		$push_data['type'] = "plannerCircle";
		$push_data['cmn_type'] = $cmn_info['cmn_type'];
		$push_data['relation_id'] = $cmn_info['relation_id'];
		$push_data['cmn_id'] = $cmn_info['id'];
		Message::model()->addMessageToQueue(Message::MESSAGE_QUEUE_COMMENT, $push_data);
    }
    
    	/**
	 * 判断用户是否采用理财师身份
	 * @param  array  $user_info 用户信息
	 * @param  string  $from      来源
	 * @return boolean            true|false
	 */
	public static function isUsePlannerIdentity($user_info, $from,$server) {
		$refer = $server;
        $refer_info = parse_url($refer);
		$is_admin = false;
		$is_admin = (stripos($refer_info['path'], '/admin2/') === 0) ? true : false;

		$is_planner_clent = in_array($from, array(CommonUtils::FR_LCS_PLANNER_CLIENT, CommonUtils::FR_LCS_PLANNER_CLIENT_IOS)) ? true : false;

		if (($is_admin || $is_planner_clent) && $user_info['is_p']) {
			return true;
		} else {
			return false;
		}
    }
    

    	/**
	 * 更新指定用户的圈子在线时间
	 * @param  array $u_type_info       用户类型及uid
	 * @param  int $circle_id 圈子id
	 * @param  string $u_time    更新时间
	 */
	public static function updateUserCircleTime($u_type_info, $circle_id, $u_time) {
		$uid = $u_type_info['uid'];
		$redis_key = RedisKeyHelper::buildKey(10001, ["uid" => $uid, "circle_id" => $circle_id]);
		$old_time = Yii::app()->redis_r->get($redis_key);


		if ($u_time !== '0000-00-00 00:00:00') {
			Yii::app()->redis_w->setex($redis_key, 1800, $u_time); // 30分钟有效期

			if (empty($old_time) || ((strtotime($u_time) - strtotime($old_time)) > 10)) { // 防止频繁更新数据库
				// 更新用户在线时间
				$columns['is_online'] = 1;
				$columns['u_time'] = $u_time;
				$conditions = 'uid=:uid and circle_id=:circle_id';
				$params['uid'] = $uid;
				$params['circle_id'] = $circle_id;
				CircleUser::model()->updateCircleUser($columns, $conditions, $params);

			}
		} else {
			if (!empty($old_time)) {
				Yii::app()->redis_w->delete($redis_key); // 立即清除缓存

				$columns['is_online'] = 0;
				$columns['u_time'] = date("Y-m-d H:i:s");
				$update_time = $columns['u_time'];
				$conditions = 'uid=:uid and circle_id=:circle_id';
				$params['uid'] = $uid;
				$params['circle_id'] = $circle_id;
				CircleUser::model()->updateCircleUser($columns, $conditions, $params);

			}
		}
	}

    //======================================================================
    //===[圈子公告部分]
    //======================================================================
    public static function getCircleNotice($notice_id)
    {
        $notice_info = Circle::model()->getCircleNotice($notice_id);
         if(empty($notice_info)){
            return null;
        }

        $notice_list = array($notice_info);
        self::handleNoticeList($notice_list);

        return reset($notice_list);
    }
    /**
     * 公共的圈子公告列表处理方法
     * @param  array &$notice_list 公告列表
     */
    public static function handleNoticeList(&$notice_list)
    {
        if (!is_array($notice_list) || empty($notice_list)) {
            return ;
        }

        $p_ids = [];
        foreach ($notice_list as $notice) {
            if ($notice['u_type'] == 2) {
                $p_ids[] = $notice['uid'];
            } else {
            }
        }
        $planners_map = !empty($p_ids) ? Planner::model()->getPlannerById(array_unique($p_ids)) : array();

        foreach ($notice_list as &$notice) {
            if ($notice['u_type'] == 2) {
                $notice['publisher_info'] = isset($planners_map[$notice['uid']]) ? $planners_map[$notice['uid']] : null;
            } else {
                $notice['publisher_info'] = null;
            }

            $notice_type = Circle::$notice_type[$notice['type']];
            $notice["{$notice_type}_notice_info"] = json_decode($notice['notice'], true);

            $notice['c_time_fmt'] = self::circleTimeFormatConverter($notice['c_time']);

            // 无用字段去除
            unset($notice['notice']);
        }

        return ;
    }
    ///添加推送到队列
    public static function pushToUser($push_data){
        $redis_key = "lcs_common_message_queue";
        $data = json_encode(array(
            'type'=>'circlePushToUser',
            'data'=>$push_data
        ));
        $wx_xiaochengxu_data = json_encode(
            array(
                'type'=>'CirleReplyXcx',
                'p_uid'=>$push_data['plannerId'],
                'uid'=>$push_data['userId'],
                'message'=>array(
                    'content'=>$push_data['pushContent'],
                    'time'=>date('Y-m-d H:i:s',time()),
                    'name'=>$push_data['plannerName'],
                ),
            )
        );
        // {"type":"circlePushToUser","data":{"plannerName":"李宁理财师","userId":"171429906"}}
        Yii::app()->redis_w->lpush($redis_key,$data);
        Yii::app()->redis_w->lpush($redis_key,$wx_xiaochengxu_data);        
    }
}
