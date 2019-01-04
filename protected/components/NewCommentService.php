<?php

/**
 * 理财师新版大家说模板逻辑处理类
 * @author meixin 
 * @date 2015-10-14
 */
class NewCommentService {

    public static $tb_nums = 100;  //大家说分表数量
    public static $def_admin_user_name = '理财小妹';
    public static $def_admin_user_image = 'http://licaishi.sina.com.cn/web_img/lcs_comment_systemuser.jpg';
    public static $old_cmn_type = array(1, 2, 3, 4); //1 计划 2 观点 3 话题 4广场
    public static $cmn_type_group = array(
        1 => array(1), 2 => array(2), 3 => array(3), 4 => array(4),
        5 => array(11, 12, 13, 21, 22, 23, 24, 25, 26, 31, 32)
    ); //禁言分类
    public static $u_type_arr = array(1 => '用户', '2' => '理财师', 3 => '理财小妹');
    //铁粉
    public static $user_follow = array('1'=>['title'=>'班长','color'=>'#F59388'],'2'=>['title'=>'副班长','color'=>'#ECB976'],'3'=>['title'=>'学习委员','color'=>'#D1B5E7'],'4'=>['title'=>'纪律委员','color'=>'#A9C2EC'],
    );


    const COMMENT_RATE_RANGE = 10; //时长范围
    const COMMENT_RATE = 10; //时间范围内发表次数
    const COMMENT_FLOOR_MAX_NUM = 50; //回复说的最多层数
    const COMMENT_TOP_MAX_NUM = 50; //置顶的最多条数
    const SYMBOLKEY = 'abc';

    /*
      stock_cn A股, stock_us
      fund_open 开基,fund_etf etf基金,fund_close 封闭基金,fund_lof lof基金,
      future_inner 内盘期货,future_global 盘期货
     */

    public static $symbol_type = array(
        'stock_cn' => 11,
        'stock_hk' => 12,
        'stock_us' => 13,
        'fund' => 20,
        'fund_open' => 21,
        'fund_close' => 22,
        'fund_etf' => 23,
        'fund_money' => 24,
        'fund_qdii' => 25,
        'fund_lof' => 26,
        'future_inner' => 31,
        'future_global' => 32,
        'live' => 40,
        'live_image' => 41, // 图文直播-财友互动
    );

    // 交易时间(聚合)的说说类型
    public static $trade_type = array(
        'stock_cn'          => 11, // 见 $symbol_type
        'trade'             => 51, // 新增 交易时间
        'live_image_common' => 41, // 图文直播-财友互动
        'live_image'        => 42, // 图文直播-理财师直播
        'circle'            => 71, // 圈子说说
        'course'            => 72, // 课程评论
    );
    // 交易时间的说说类型
    public static $trade_type_relation_id = array(
        'trade_cn'  => '8888',  // 新增 交易时间 A股
        'trade_jyy' => '8889',  // 新增 交易时间 金银油
        'trade_us'  => '8890',  // 新增 交易时间 美股
        'trade_hks' => '8891',  // 新增 交易时间 港股
        'trade_qh'  => '8892',  // 新增 交易时间 期货
        'trade_jj'  => '8893',  // 新增 交易时间 基金
    );


    /**
     * 根据sign判断股票是否存在
     * @param type $type 股票类型
     * @param type $symbol 股票代码
     * @param type $sign 签名
     * @return boolean
     */
    public static function isExistSymbol($type, $symbol, $sign) {
        if (empty($type) || empty($symbol) || empty($sign)) {
            return FALSE;
        }
        if (md5($type . $symbol . self::SYMBOLKEY) == $sign) {
            return TRUE;
        }
        return FALSE;
    }

    /*
     * 计算CRC32 与评论表索引id
     * @param unknown $cmn_type 
     * @param unknown $relation_id 
     */

    public static function getCRC32TbIndex($cmn_type, $relation_id) {

        $crc32_id = CommonUtils::getCRC32($cmn_type . '_' . $relation_id);
        $cmn_tb_nums = NewComment::COMMENT_TABLE_NUMS;
        $cmn_tb_index = $crc32_id % $cmn_tb_nums;
        return array('crc32_id' => $crc32_id, 'tb_index' => $cmn_tb_index);
    }

    /*
     * 验证行情信息||旧版说id 是否在索引表中
     * 如果旧版说插入该表中
     *
     */

    public static function getTbIndex($cmn_type, $relation_id) {
        $crc32_index = self::getCRC32TbIndex($cmn_type, $relation_id);
        //print_r($crc32_index);exit;
        $res = NewComment::model()->getCommentTbIndexNum($crc32_index['crc32_id']);
        if ($res && sizeof($res) > 0 && $crc32_index['tb_index'] == $res['tb_index']) {
            return $res;
        } else {
            //不在表中先插进去
            $datas = array(
                'cmn_type' => $cmn_type,
                'relation_id' => $relation_id,
                'comment_num' => 0,
                'planner_comment_num' => 0
            );
            $res = array_merge($datas, $crc32_index);
            NewComment::model()->insertTbIndexNum($res);
            return $res;
        }
    }

    /**
     * 判断用户冻结情况
     * @param unknown $uid
     * @param unknown $type 功能模块 
     * return  true 已冻结  false 正常用户
     */
    public static function checkUserFreeze($uid, $type) {
        $freezeInfo = User::model()->getUserFreeze($uid, $type);
        if ($freezeInfo) {
            return true;
            //判断禁言是否过期
            //if(1 == $freezeInfo['freeze_type']){
            //  return true;
            //      
            //}else{
            //  if($freezeInfo['end_time'] > date('Y-m-d H:i:s')){
            //      return true;    
            //  }else{
            //      //这里暂时不给用户做解冻   
            //      return false;   
            //  }
            //} 
        } else {
            return false;
        }
    }

    /**
     * 验证发表说数量是否超限
     * @param unknown $uid
     */
    public static function checkCommentRate($index_id, $uid) {
        $mc_key = MEM_PRE_KEY . "cmn_rate_" . $index_id . "_" . $uid;
        $times = Yii::app()->cache->get($mc_key);
        if ($times && is_array($times)) {
            foreach ($times as $k => $v) {
                if ($v < time() - self::COMMENT_RATE_RANGE * 60) {
                    unset($times[$k]);
                }
            }
            return $times;
        } else {
            return array();
        }
    }

    /**
     * xss过滤
     * @param type $content
     * @return type
     */
    public static function xssFilter($content) {
        require_once Yii::app()->basePath . '/extensions/xss/XssPurifyService.php';
        $_xssPurifyService = new XssPurifyService();
        $content = $_xssPurifyService->purify($content);
        return $content;
    }

    /**
     * 词汇过滤 (a)统一词汇过滤， (b)平台特殊词汇过滤
     * @param type $content
     * @return boolean
     */
    public static function checkSensitiveWords($content) {
        return true;
        $res = self::antispam($content);
        return $res;
    }

    // 方法有误，没地方用，注释掉
    // public static function checkPubTimesValidate() {
    //     $apiResult = new ApiResult();
    //     $cmn_pub_times = NewCommentService::checkCommentRate($crc32info['tb_index'], $u_info['uid']);

    //     if (false === $cmn_pub_times) {
    //         session_start();
    //         if (empty($validate_code)) {
    //             return $apiResult->setError(RespCode::COMMENT_MAXRATE_ALLOW, '您当前发布说说的频次已超限');
    //         } elseif (strtolower($validate_code) != $_SESSION['newcomment_validatecode']) {

    //             return $apiResult->setError(RespCode::VALIDATE_CODE_ERR, '验证码错误');
    //         }
    //     }
    //     return $apiResult;
    // }

    public static function ContentPurifyandFilter(&$content, $is_planner, $uid , $u_type=1) {
        $apiResult = new ApiResult();
        require_once Yii::app()->basePath . '/extensions/xss/XssPurifyService.php';
        $_xssPurifyService = new XssPurifyService();
        $content = $_xssPurifyService->purify($content);
        //评论的内容验证
        if (strlen($content) == 0) {
            return $apiResult->setError(RespCode::PARAMS_LOSE, '回复内容不能为空');
        }
        $max_length = $is_planner ? 500 : 120;
        if ((mb_strlen($content, 'utf-8') + strlen($content)) / 4 > $max_length) {
            return $apiResult->setError(RespCode::PARAMS_LOSE, '内容过长');
        }

        //验证评论内容是否和上次一样 1分钟  ，20160405日只对普通用户进行验证
        if($u_type == 1){
            $redis_key = MEM_PRE_KEY . 'comment_lasted_crc32_' . intval($uid);
            $lasted_cnt_crc32 = Yii::app()->redis_r->get($redis_key);
            $cur_cnt_crc32 = CommonUtils::getCRC32($content);
            if (!empty($lasted_cnt_crc32) && $cur_cnt_crc32 == $lasted_cnt_crc32) {

                return $apiResult->setError(RespCode::PARAM_ERR, '操作频繁，请稍后重试');
            } else {
                Yii::app()->redis_w->setex($redis_key, 60, $cur_cnt_crc32);
            }
        }

        //反垃圾词汇过滤
        $mark_words = '';
        $sens_res = self::checkSensitiveWords($content);
        if ($sens_res) {
            if (!empty($sens_res['key_words'])) {
                //冻结用户
                $reason = '发布违禁词汇||' . $sens_res['key_words'];
                self::freezeUser($uid, $sens_res['key_words'], User::FREEZE_TYPE_PLAN_CMN, "system");
                return $apiResult->setError(RespCode::CONTENT_BLACKWORD, '内容包含敏感词,账号将被冻结！');
            } elseif (!empty($sens_res['mark_words'])) {
                $mark_words = $sens_res['mark_words'];
            }
        }
        return $apiResult->setSuccess($mark_words);
    }

    /**
     * 保存评论
     * @param type $tbindex_info
     * @param type $content
     * @param type $u_type
     * @param type $uid
     * @param type $reply_id
     * @param type $is_display
     * @param type $is_anonymous
     * @param type $source
     * @param type $head_ids
     * @param type $child_relation_id
     * @param type $mark_words
     * @param type $is_privilege
     * @param type $content_html
     * @param type $discussion_type
     * @param type $discussion_id
     * @param type $root_reply_id
     * @param type $up_down
     * @return boolean
     */
    public static function saveComment($tbindex_info, $content, $u_type, $uid, $reply_id = 0, $is_display = 1, $is_anonymous = 0, $source = '', $head_ids = '', $child_relation_id = 0, $mark_words = '', $is_privilege = 0, $content_html = '', $discussion_type = 0, $discussion_id = '', $root_reply_id = 0, $up_down = 0, $is_good = 0, $global_id = 0) {

        if($reply_id!=0 && $root_reply_id == 0) {
            $root_reply_id = $reply_id;
        }

        $cmn_data['cmn_type'] = $tbindex_info['cmn_type'];
        $cmn_data['relation_id'] = $tbindex_info['relation_id'];
        $cmn_data['crc32_id'] = $tbindex_info['crc32_id'];
        $cmn_data['content'] = $content;
        $cmn_data['head_ids'] = $head_ids;
        $cmn_data['u_type'] = $u_type;
        $cmn_data['uid'] = $uid;
        $cmn_data['reply_id'] = $reply_id;
        $cmn_data['is_display'] = $is_display;
        $cmn_data['is_anonymous'] = $is_anonymous;
        $cmn_data['source'] = $source;
        $cmn_data['c_time'] = date('Y-m-d H:i:s');
        $cmn_data['u_time'] = $cmn_data['c_time'];
        $cmn_data['child_relation_id'] = $child_relation_id;
        $cmn_data['is_privilege'] = $is_privilege;
        $cmn_data['match_search'] = $content_html;
        $cmn_data['discussion_type'] = $discussion_type;
        $cmn_data['discussion_id'] = $discussion_id;
        $cmn_data['root_reply_id'] = $root_reply_id;
        $cmn_data['up_down'] = $up_down;
        $cmn_data['is_good'] = $is_good;
        $cmn_data['global_id'] = $global_id;
        //echo "<pre>";
        //print_r($cmn_data);exit;
        $cmn_id = NewComment::model()->saveComment($tbindex_info['tb_index'], $cmn_data);
        $is_old_cmn_type = in_array($cmn_data['cmn_type'], NewCommentService::$old_cmn_type) ? 1 : 0;
        $tmp_trade_type = array_flip(NewCommentService::$trade_type);
        $is_trade_cmn_type = isset($tmp_trade_type[$cmn_data['cmn_type']]) ? 1 : 0; // 交易时间也存redis
        $floor_num = 0;
        if ($cmn_id) {
            $cmn_data['cmn_id'] = $cmn_id;
            //楼层  公开显示或是子评论需要计算楼层
            if (($is_display == 1 || !empty($reply_id)) && $is_old_cmn_type) {
                $redis_key_floor = MEM_PRE_KEY . 'cmn_new_floor_' . $cmn_data['cmn_type'] . '_' . $cmn_data['relation_id'];
                $hash_key = '0'; //一级评论
                if (!empty($cmn_id) && !empty($reply_id)) {
                    //二级评论的楼层
                    $hash_key = $reply_id;
                }
                $floor_num = Yii::app()->redis_w->hIncrBy($redis_key_floor, $hash_key, 1);
                //楼层数只更新分表，master表不更新
                NewComment::model()->updateComment($tbindex_info['tb_index'], $cmn_id, array('floor_num' => $floor_num));
            }
            

            //评论数量
            if (!empty($root_reply_id)) {
                //被回复总数更新分表，master表，置顶表
                NewComment::model()->updateCommentInc($tbindex_info['tb_index'], $root_reply_id, 'reply_num', 1);
                NewComment::model()->updateCommentMasterInc($tbindex_info['crc32_id'], $root_reply_id, 'reply_num', 1);
                NewComment::model()->updateCommentQualityIncByCrc32($tbindex_info['crc32_id'], $root_reply_id, 'reply_num', 1);
                //旧版说缓存最新的两层二级回复逻辑
                if ($is_old_cmn_type || $is_trade_cmn_type) {
                    // 记录最新的评论ID
                    $redis_key = MEM_PRE_KEY . 'cmn_new_lasted_' . $tbindex_info['tb_index'];
                    $cmn_ids = Yii::app()->redis_r->hget($redis_key, $root_reply_id);
                    if ($cmn_ids) {
                        $cmn_ids_arr = explode(',', $cmn_ids);
                        if (count($cmn_ids_arr) == 2) {
                            array_pop($cmn_ids_arr);
                        }
                        array_unshift($cmn_ids_arr , $cmn_id);
                        Yii::app()->redis_w->hset($redis_key, $root_reply_id, implode(',', $cmn_ids_arr));
                    } else {
                        Yii::app()->redis_w->hset($redis_key, $root_reply_id, $cmn_id);
                    }
                }
            }

            $cmn_data['praise_num'] = 0;
            $cmn_data['reply_num'] = 0;
            $cmn_data['floor_num'] = $floor_num;
            $cmn_data['is_good'] = $is_good;
            //存入全量表
            $cmn_data['mark_words'] = $mark_words;

            $master_id = NewComment::model()->saveCommentMaster($cmn_data);
            
            $cmn_data['id'] = $cmn_id;
            if ($master_id) {
                $cmn_data['master_id']=$master_id;
                //记录最新的说说ID和发布时间  公开的一级说说
                if ($is_display == 1 && empty($reply_id) && 
                        ($cmn_data['cmn_type'] == CommentNew::CMN_TYPE_PLAN || $cmn_data['cmn_type'] == CommentNew::CMN_TYPE_VIEW)) {
                    $redis_key_new = MEM_PRE_KEY . 'cmn_newmsg_' . $cmn_data['cmn_type'] . '_' . $cmn_data['relation_id'];  
                    Yii::app()->redis_w->hset($redis_key_new, 'lasted_id', $master_id);
                    Yii::app()->redis_w->hset($redis_key_new, 'lasted_time', time());
                }
            } else {
                return false;
            }

            $cmn_data['c_time_fmt'] = CommonUtils::formatDate($cmn_data['c_time'], 'web');  //多久以前发布的回复             
            return $cmn_data;
        } else {
            return false;
        }
    }

    /**
     * 其他修改：置为精华is_good
     * 
     * @param type $uid
     * @param type $commentInfo
     * @param type $tb_index
     * @param type $is_good 1置精华  其他为取消赞
     * @return boolean
     */
    public static function updateComment($uid, $commentInfo, $tb_index, $crc32_id, $is_good) {
        $good_info = $commentInfo['is_good'];
        $apiResult = new ApiResult();
        if ($good_info) {
            if (0 == $is_good) {
                $transaction = Yii::app()->licaishi_comment_w->beginTransaction();
                try {
                    //更新数据库
                    $up_column = array('is_good' => 0, 'u_time' => date('Y-m-d H:i:s'));
                    NewComment::model()->updateComment($tb_index, $commentInfo['cmn_id'], $up_column);
                    NewComment::model()->updateCommentMaster($crc32_id, $commentInfo['cmn_id'], $up_column);
                    NewComment::model()->updateCommentQualityByCrc32($crc32_id, $commentInfo['cmn_id'], $up_column);

                    $transaction->commit();
                } catch (Exception $e) {
                    $transaction->rollback();
                    $apiResult->setError(RespCode::COMMENT_TOP_ERROR, '评论取消精华失败');
                    return;
                }
                $opt_data = array('tb_index' => $tb_index, 'is_good' => 0);
                Common::model()->saveOptLog($uid, 'newcomment/CommentUpgood', $commentInfo['cmn_id'], $opt_data);
            }
        } else {
            if (1 == $is_good) {
                $transaction = Yii::app()->licaishi_comment_w->beginTransaction();
                try {
                    //更新数据库
                    $up_column = array('is_good' => 1, 'u_time' => date('Y-m-d H:i:s'));
                    NewComment::model()->updateComment($tb_index, $commentInfo['cmn_id'], $up_column);
                    NewComment::model()->updateCommentMaster($crc32_id, $commentInfo['cmn_id'], $up_column);
                    NewComment::model()->updateCommentQualityByCrc32($crc32_id, $commentInfo['cmn_id'], $up_column);
                    $transaction->commit();
                } catch (Exception $e) {
                    $transaction->rollback();
                    $apiResult->setError(RespCode::COMMENT_TOP_ERROR, '评论置为精华失败');
                    return;
                }
                $opt_data = array('tb_index' => $tb_index, 'is_good' => 1);
                Common::model()->saveOptLog($uid, 'newcomment/CommentUpgood', $commentInfo['cmn_id'], $opt_data);
            }
        }
        return true;
    }

    /**
     * 评论置顶
     * @param type $uid
     * @param type $commentInfo
     * @param type $tb_index
     * @param type $is_top
     * @return \ApiResult
     */
    public static function topComment($uid, $commentInfo, $tb_index, $crc32_id, $is_top) {
        $apiResult = new ApiResult();
        $top_info = $commentInfo['is_top'];
        if ($top_info) {
            if (0 == $is_top) {
                $transaction = Yii::app()->licaishi_comment_w->beginTransaction();
                try {
                    unset($commentInfo['id']);
                    unset($commentInfo['index_id']);
                    unset($commentInfo['is_top']);
                    $commentInfo['u_time'] = date('Y-m-d H:i:s');
                    $res = NewComment::model()->saveComment($tb_index, $commentInfo);
                    NewComment::model()->delCommentFromSpecial($commentInfo['crc32_id'], $commentInfo['cmn_id'], $tb_index);
                    $up_column = array('is_top' => 0, 'u_time' => $commentInfo['u_time']);
                    NewComment::model()->updateCommentMaster($crc32_id, $commentInfo['cmn_id'], $up_column);
                    $transaction->commit();
                } catch (Exception $e) {
                    $transaction->rollback();
                    $apiResult->setError(RespCode::COMMENT_TOP_ERROR, '评论取消置顶失败');
                    return;
                }
                $opt_data = array('tb_index' => $tb_index, 'is_top' => 0);
                Common::model()->saveOptLog($uid, 'newcomment/CommentTop', $commentInfo['cmn_id'], $opt_data);
            }
        } else {
            if (1 == $is_top) {
                $transaction = Yii::app()->licaishi_comment_w->beginTransaction();
                try {
                    $commentInfo['index_id'] = $tb_index . "_" . $commentInfo['cmn_id'];
                    $commentInfo['u_time'] = date('Y-m-d H:i:s');
                    unset($commentInfo['is_top']);
                    $res = NewComment::model()->insertCommentToSpecial($commentInfo);
                    NewComment::model()->delCommentFromNormal($tb_index, $commentInfo['cmn_id']);
                    $up_column = array('is_top' => 1, 'u_time' => $commentInfo['u_time']);
                    NewComment::model()->updateCommentMaster($crc32_id, $commentInfo['cmn_id'], $up_column);
                    $transaction->commit();
                } catch (Exception $e) {
                    $transaction->rollback();
                    $apiResult->setError(RespCode::COMMENT_CANCEL_TOP_ERROR, '评论置顶失败');
                    return;
                }
                $opt_data = array('tb_index' => $tb_index, 'cmn_id' => $commentInfo['cmn_id'], 'is_top' => 1);
                Common::model()->saveOptLog($uid, 'newcomment/CommentTop', $commentInfo['cmn_id'], $opt_data);
            }
        }
        return $apiResult;
    }

    public static function praiseComment($uid, $cmn_id, $tb_index, $is_praise, $crc32_id, $commentInfo) {
        $praise_info = NewComment::model()->getUserPraise($tb_index, $cmn_id, $uid);  
        $redis_key = MEM_PRE_KEY . 'cmn_praise_' . $tb_index;
        $is_exist = self::isMasterExist($commentInfo['c_time']);
        $praise_num = $commentInfo['praise_num'];
        //更新点赞数量
        $transaction = Yii::app()->licaishi_comment_w->beginTransaction();
        try {
            if ($praise_info) {
                if (0 == $is_praise) {
                    Yii::app()->redis_w->hdel($redis_key, $cmn_id . '_' . $uid);
                    //更新数据库
                    if( 0 == $commentInfo['is_top']){
                        NewComment::model()->updateCommentInc($tb_index, $cmn_id, 'praise_num', '-1');
                    }else{
                        NewComment::model()->updateCommentQualityInc($commentInfo['id'] , $tb_index, $cmn_id, 'praise_num', '-1');
                    }

                    if ($is_exist) {
                        NewComment::model()->updateCommentMasterInc($crc32_id, $cmn_id, 'praise_num', '-1');
                    }
                    $praise_num = $commentInfo['praise_num'] - 1;
                }
            } else {
                if (1 == $is_praise) {
                    $praise = Yii::app()->redis_w->hset($redis_key, $cmn_id . '_' . $uid, 1);
                    //更新数据库
                    if( 0 == $commentInfo['is_top']){
                        NewComment::model()->updateCommentInc($tb_index, $cmn_id, 'praise_num', 1);
                    }else{
                        NewComment::model()->updateCommentQualityInc($commentInfo['id'] , $tb_index, $cmn_id, 'praise_num', 1);
                    }
                    if ($is_exist) {
                        NewComment::model()->updateCommentMasterInc($crc32_id, $cmn_id, 'praise_num');
                    }
                    $praise_num = $commentInfo['praise_num'] + 1;
                }
            }
            $transaction->commit();
        } catch (exception $e) {
            $transaction->rollback();
            return false;
        }

        //返回该评论的获赞总数
        //$commentInfo = NewComment::model()->getCommentByIds($cmn_id, $tb_index);
        $praise_info = array('praise_num' => $praise_num);
        //var_dump($praise_info);exit;
        return $praise_info;
    }

    public static function anonymousComment($cmn_id , $commentInfo, $tb_index, $crc32_id, $is_anonymous ) {
        $anonymous_info = $commentInfo['is_anonymous'];  
        $is_exist = self::isMasterExist($commentInfo['c_time']);
        //更新点赞数量
        $transaction = Yii::app()->licaishi_comment_w->beginTransaction();
        try {
            if ($anonymous_info) {
                if (0 == $is_anonymous) {
                    //更新数据库
                    $up_column = array('is_anonymous' => 0, 'u_time' => date('Y-m-d H:i:s'));
                    if( 0 == $commentInfo['is_top']){
                        NewComment::model()->updateComment($tb_index, $cmn_id, $up_column);
                    }else{
                        NewComment::model()->updateCommentQuality($commentInfo['id'] , $tb_index, $cmn_id, $up_column);
                    }

                    if ($is_exist) {
                        NewComment::model()->updateCommentMaster($crc32_id, $cmn_id, $up_column );
                    }
                }
            } else {
                if (1 == $is_anonymous) {
                    //更新数据库
                    $up_column = array('is_anonymous' => 1, 'u_time' => date('Y-m-d H:i:s'));
                    if( 0 == $commentInfo['is_top']){
                        NewComment::model()->updateComment($tb_index, $cmn_id, $up_column);
                    }else{
                        $res = NewComment::model()->updateCommentQuality($commentInfo['id'] , $tb_index, $cmn_id, $up_column);
                    }
                    if ($is_exist) {
                        NewComment::model()->updateCommentMaster($crc32_id, $cmn_id, $up_column );
                    }
                }
            }
            $transaction->commit();
        } catch (exception $e) {
            $transaction->rollback();
            return false;
        }

        return true;
    }


    /**
     * 根据评论的c_time判断master 表里是否有数据
     * @param type $c_time
     * @return boolean
     */
    public static function isMasterExist($c_time) {
        $flag_time = strtotime(date('Y-m-d 00:00:00', strtotime(NewComment::MASTER_SAVE_TIME)));

        $b_time = strtotime(date('Y-m-d 00:00:00', strtotime($c_time)));
        if ($flag_time < $b_time) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除评论
     * @param type $cmn_id
     * @param type $root_reply_id
     * @param type $type  1 默认旧版， 2 新版
     * @return \ApiResult
     */
    public static function deleteComment($tb_index, $comment_info, $type = 1) {

        $apiResult = new ApiResult();
        $root_reply_id = $comment_info['root_reply_id'];
        $is_top = (isset($comment_info['is_top']) && $comment_info['is_top']==1) ? 1 : 0;
        unset($comment_info['is_top']);
        unset($comment_info['id']);
        $comment_info['index_id'] = $tb_index . "_" . $comment_info['cmn_id'];
        $res = NewComment::model()->insertCommentToSpecial($comment_info, 2);
        if($is_top) {
            $res = NewComment::model()->delCommentFromSpecial($comment_info['crc32_id'], $comment_info['cmn_id'] ,$tb_index );
        }else{ 
            $res = NewComment::model()->delCommentFromNormal($tb_index, $comment_info['cmn_id']);
        }
        
        if ($res) {
            $del_ids = array();
            $column_val = array();
            $comment_num = 0;
            $planner_comment_num = 0;
            if (empty($root_reply_id)) {
                //一级说 删除其所有二级子说
                //插入垃圾表
                $reply_comment_info = NewComment::model()->getAllCommentByRootReplyId($tb_index, $comment_info['cmn_id']);
                if (is_array($reply_comment_info) && sizeof($reply_comment_info) > 0) {
                    foreach ($reply_comment_info as $info) {
                        $del_ids[] = $info['cmn_id'];
                        $redis_list[$tb_index][] = $info;
                        
                    }
                    //加入删除队列 -- 做分表的删除和缓存的删除
                    $redis_key = MEM_PRE_KEY."delete_comment";
                    Yii::app()->redis_w->rPush($redis_key,json_encode($redis_list));
                    
                }
                if($comment_info['is_display'] == 1){
                    $column_val['comment_num'] = $comment_num - 1; 
                }
            }else{

                //redis 评论列表 去除ID
                $reply_ids_str = NewComment::model()->getMutiCommentLastReplys($tb_index, $root_reply_id);
                // error_log("del==={$redis_key}==={$root_reply_id}==={$reply_ids_str}\n", 3, '/tmp/bala.log');
                if ($reply_ids_str) {
                    $reply_ids_str = current($reply_ids_str);
                    $reply_ids_arr = explode(',', $reply_ids_str);
                    if (is_array($reply_ids_arr) && in_array($comment_info['cmn_id'], $reply_ids_arr)) {
                        $last_reply_ids = NewComment::model()->getCommentLastRootReplyIds($tb_index, $root_reply_id, 2);
                        // $cmn_ids = array_reverse($last_reply_ids);
                        $cmn_ids = $last_reply_ids;
                        // error_log("del==={$redis_key}==={$root_reply_id}===".json_encode($cmn_ids)."\n", 3, '/tmp/bala.log');
                        $r_ids = "";
                        foreach ($cmn_ids as $v) {
                            $r_ids .= $v['cmn_id'] . ",";
                        }
                        $r_ids = substr($r_ids, 0, -1);
                        $redis_key = MEM_PRE_KEY . 'cmn_new_lasted_' . $tb_index;
                        Yii::app()->redis_w->hset($redis_key, $root_reply_id, $r_ids);
                        // error_log("del==={$redis_key}==={$root_reply_id}==={$r_ids}\n", 3, '/tmp/bala.log');
                    }
                }

                //评论回复数减一
                NewComment::model()->updateCommentInc($tb_index, $root_reply_id, 'reply_num', -1);
                NewComment::model()->updateCommentMasterInc($comment_info['crc32_id'], $root_reply_id, 'reply_num', -1);
                NewComment::model()->updateCommentQualityIncByCrc32($comment_info['crc32_id'], $root_reply_id, 'reply_num', -1);
            }
            
            //更改理财师说的数量
            if ($comment_info['u_type'] == 2) {
                $planner_comment_num --;
                $column_val['planner_comment_num'] = $planner_comment_num;
            }
            //删master 表
            $del_ids[] = $comment_info['cmn_id'];
            NewComment::model()->delCommentFromMaster($comment_info['crc32_id'], $del_ids);
            // 删除运营后台的页面配置数据
            PageCfg::model()->deletePageCfg($tb_index, $comment_info['cmn_id']);
            $apiResult->setSuccess($column_val);
        } else {
            $apiResult->setError(RespCode::DEF_ERROR, '删除评论失败');
        }
        return $apiResult;
    }

    /**
     * 获取评论的回复列表
     * @param type $cmn_type 说说类型
     * @param type $relation_id 关联id
     * @param type $replyid 回复id
     * @param type $uid 用户id
     * @param type $u_type 用户类型
     * @param type $num 页数 
     * @param type $maxid 上次最小id
     * @return type 
     */
    public static function getCommentReplyPage($cmn_type, $relation_id, $cmn_id, $num, $maxid, $cur_uid, $action, $has_parent = 0) {
        
        $idx = self::getCRC32TbIndex($cmn_type, $relation_id);
        $tb_index = $idx['tb_index'];
        $ids = array();
        $replylist = NewComment::model()->getCommentReplyList($tb_index, $cmn_id, $num, $maxid, $action);
        if (!$has_parent && empty($replylist)) {
            return $replylist;
        }
        if ($has_parent == 1) {
            $ids[] = $cmn_id;
        }
        foreach ($replylist as $val) {
            $ids[] = $val['cmn_id'];
        }
        $list = array();
        $uids = array();
        $pids = array();
        $infomap = NewComment::model()->getCommentByIds($ids, $tb_index);
        $praisemap = NewComment::model()->getMutiUserPraise($tb_index, $ids, $cur_uid);
        $replys = array();
        foreach ($ids as $id) {
            if (isset($infomap[$id])) {
                self::handleCommentItem($list, $uids, $pids, $infomap, $replys, $id);                
            }
        }
        //获取 用户 理财师  二级评论 的信息
        $users_map = !empty($uids) ? User::model()->getUserInfoById(array_unique($uids)) : array();
        // $planners_map = !empty($pids) ? Planner::model()->getPlannerById(array_unique($pids)) : array();
        // 用以下新方法获取同时获取理财师评级信息
        $planners_map = !empty($pids) ? Planner::model()->getPlannerByIdsNew(array_unique($pids), 35) : array();
        foreach ($list as &$item) {
            if ($item['u_type'] == 1) {
                self::handleCommentUserInfo($item, $users_map, TRUE);
            } else if ($item['u_type'] == 2) {
                self::handleCommentPlannerInfo($item, $planners_map);
            } else {
                $item['name'] = self::$def_admin_user_name;
                $item['image'] = self::$def_admin_user_image;
            }
            $item['is_praise'] = empty($cur_uid) ? 0 : $praisemap[$item['cmn_id']];
        }
        $result = array();
        $result['info'] = ($has_parent == 1) ? array_shift($list) : NULL;
        $result['reply_list'] = $list;
        return $result;
    }

    /**
     * 
     * @param type $cmn_id
     * @param type $uid
     * @param type $page
     * @param type $num
     * @param type $is_anonymous
     * @param type $is_self
     * @param type $has_parent
     * @return type
     */
    public static function getCommentByReplyid($cmn_type, $relation_id, $cmn_id, $uid, $page = 1, $num = 15, $is_anonymous = true, $is_self = false, $has_parent = 0) {
        $idx = self::getCRC32TbIndex($cmn_type, $relation_id);
        $tb_index = $idx['tb_index'];
        $apiResult = new ApiResult();
        $replylist = NewComment::model()->getCommentByReplyId($tb_index, $cmn_id, $page, $num);        
        //获取的数据为空直接返回  update by zwg 20150518 如果数据为空，并且不获取父说说数据时直接返回
        if (!$has_parent && empty($replylist['data'])) {
            return $apiResult->setSuccess($replylist);
        }
        $ids = array();
        if ($has_parent == 1) {
            $ids[] = $cmn_id;
        }
        if(!empty($replylist['data'])){
            foreach ($replylist['data'] as $val) {
                $ids[] = $val['cmn_id'];
            }        
        }
        $list = array();
        $uids = array();
        $pids = array();
        $infomap = NewComment::model()->getCommentByIds($ids, $tb_index);
        $praisemap = NewComment::model()->getMutiUserPraise($tb_index, $ids, $uid);        
        $replys = array();
        foreach ($ids as $id) {
            if (isset($infomap[$id])) {
                self::handleCommentItem($list, $uids, $pids, $infomap, $replys, $id);
            }
        }
        //获取 用户 理财师  二级评论 的信息
        $users_map = !empty($uids) ? User::model()->getUserInfoById(array_unique($uids)) : array();
        // $planners_map = !empty($pids) ? Planner::model()->getPlannerById(array_unique($pids)) : array();
        // 用以下新方法获取同时获取理财师评级信息
        $planners_map = !empty($pids) ? Planner::model()->getPlannerByIdsNew(array_unique($pids), 35) : array();
        foreach ($list as &$item) {
            self::handleCommentInfo($item, $users_map, $planners_map, ($is_anonymous && $item['is_anonymous'] == 1), $is_self,$uid);
            $item['is_praise'] = empty($uid) ? 0 : $praisemap[$item['cmn_id']];
            self::touchOldColumn($item);
        }
        $replylist['parent_cmn'] = ($has_parent == 1) ? array_shift($list) : array();
        $replylist['data'] = $list;
        return $apiResult->setSuccess($replylist);
    }

    /**
     * 获取单条评论详情
     * @param type $cmn_type
     * @param type $relation_id
     * @return type
     */
    public static function getCommentInfo($cmn_type, $relation_id, $cmn_id) {
        $idx = self::getCRC32TbIndex($cmn_type, $relation_id);
        $tb_index = $idx['tb_index'];
        $detaillist = NewComment::model()->getCommentByIds($cmn_id, $tb_index);
        if(empty($detaillist)){
            return FALSE;
        }
        $info = $detaillist[$cmn_id];
        if (empty($info)) {
            return FALSE;
        }
        return $info;
    }

    /**
     * 个股详情说说列表
     * @param type $cmn_type
     * @param type $relation_id
     * @param type $u_type
     * @param type $num
     * @param type $maxid
     * @param type $cur_uid
     * @return type
     */
    public static function doCommentList($cmn_type, $relation_id, $u_type, $num, $maxid, $action, $cur_uid = 0) {
//        if(empty($maxid)){
//            $params = array(
//                $cmn_type,$relation_id,$u_type,$num,$action
//            );
//            $mckey = MEM_PRE_KEY.implode('_', $params);
//            $cacheresult = Yii::app()->cache->get($mckey);
//            if($cacheresult !== FALSE){
//                return json_decode($cacheresult,TRUE);
//            }
//        }
        $idx = self::getCRC32TbIndex($cmn_type, $relation_id);
        $tb_index = $idx['tb_index'];
        $crc32id = $idx['crc32_id'];
        $ids = array();
        $u_ids = array();
        $p_ids = array();
        $headdict = array();
        $headlist = array();
        $stocklist = NewComment::model()->getCommentList($tb_index, $crc32id, $u_type, $num, $maxid, $action);
        if (empty($stocklist)) {
            return array();
        }
        foreach ($stocklist as $val) {
            $ids[] = $val['cmn_id'];
            if (!empty($val['head_ids'])) {
                //对headids进行处理
                foreach (explode('-', $val['head_ids']) as $headid) {
                    if (intval($headid) > 0) {
                        $headdict[] = $headid;
                    }
                }
            }
        }
        $commentids = array_merge($headdict, $ids);
        $list = array();
        $reply = array();
        $infomap = NewComment::model()->getCommentByIds($commentids, $tb_index);
        $praisemap = NewComment::model()->getMutiUserPraise($tb_index, $ids, $cur_uid);
        foreach ($ids as $id) {
            if (isset($infomap[$id])) {
                self::handleCommentItem($list, $u_ids, $p_ids, $infomap, $reply, $id);
            }
        }
        foreach (array_unique($headdict) as $id) {
            if (isset($infomap[$id])) {
                $headlist[$id] = $infomap[$id];
                if ($infomap[$id]['u_type'] == 1) {
                    $u_ids[] = $infomap[$id]['uid'];
                } elseif ($infomap[$id]['u_type'] == 2) {
                    $p_ids[] = $infomap[$id]['uid'];
                }
            }
        }
        $users_map = !empty($u_ids) ? User::model()->getUserInfoById(array_unique($u_ids)) : array();
        $planners_map = !empty($p_ids) ? Planner::model()->getPlannerById(array_unique($p_ids)) : array();
        foreach ($list as &$v) {
            $v['content_html'] = self::handleMatchSearch($v['content'], $v['match_search']);
            if ($v['u_type'] == 1) {
                self::handleCommentUserInfo($v, $users_map, FALSE);
            } else if ($v['u_type'] == 2) {
                self::handleCommentPlannerInfo($v, $planners_map);
            } else {
                $v['name'] = self::$def_admin_user_name;
                $v['image'] = self::$def_admin_user_image;
            }
            $v['headitems'] = array();
            if (!empty($v['head_ids'])) {
                $headarr = explode('-', $v['head_ids']);
                rsort($headarr);
                foreach ($headarr as $id) {
                    if (!isset($headlist[$id])) {
                        $headitem = array(
                            'cmn_id' => $id,
                            'uid' => 0,
                            'name' => '财友',
                            'content' => '该条说说已被删除 ',
                            'content_html' => '该条说说已被删除'
                        );
                        $v['headitems'][] = $headitem;
                        continue;
                    }
                    $headitem = array();
                    $headitem['uid'] = $headlist[$id]['uid'];
                    if (($headlist[$id]['u_type'] == 1)) {
                        self::handleCommentUserInfo($headitem, $users_map, FALSE);
                    } else if ($headlist[$id]['u_type'] == 2) {
                        $headitem['name'] = isset($planners_map[$headlist[$id]['uid']]['name']) ? $planners_map[$headlist[$id]['uid']]['name'] : '';
                    } else if ($headlist[$id]['u_type'] == 3) {
                        $headitem['name'] = self::$def_admin_user_name;
                    }
                    $headitem['content'] = $headlist[$id]['content'];
                    $headitem['content_html'] = self::handleMatchSearch($headlist[$id]['content'], $headlist[$id]['match_search']);
                    $headitem['cmn_id'] = $id;
                    $v['headitems'][] = $headitem;
                }
            }
            $v['is_praise'] = 0;
            if (!empty($cur_uid)) {
                $v['is_praise'] = $praisemap[$v['cmn_id']];
            }
        }
//        if(empty($maxid)){
//            Yii::app()->cache->set($mckey, json_encode($list), 300);
//        }
        return $list;
    }

    /**
     * 替换股票标签
     * @param type $content
     * @param type $match_search
     * @return type
     */
    private static function handleMatchSearch($content, $match_search) {
        if (empty($match_search)) {
            return $content;
        }
        $content_html = '';
        $match = json_decode($match_search, TRUE);
        if (!empty($match)) {
            $replace_keys = array();
            $replace_values = array();
            foreach ($match as $key => $value) {
                $replace_keys[] = $key;
                $replace_values[] = "<a href='/web/searchNew?s=" . $value . "&trim_ext=1'>" . $key . "</a>";
            }
            $content_html = str_replace($replace_keys, $replace_values, $content);
        }
        return $content_html;
    }

    /**
     * 获取说说数量
     * @param type $cmn_type
     * @param type $relation_id
     * @return type
     */
    public static function doCommentTotal($cmn_type, $relation_id) {
        $idx = self::getCRC32TbIndex($cmn_type, $relation_id);
        $crc32id = $idx['crc32_id'];
        $totalinfo = NewComment::model()->getCommentTotal($crc32id);
        $data = array('comment_num' => 0, 'planner_comment_num' => 0);
        if (!empty($totalinfo)) {
            $data['comment_num'] = $totalinfo[0]['comment_num'];
            $data['planner_comment_num'] = $totalinfo[0]['planner_comment_num'];
        }
        return $data;
    }

    /**
     * 
     * @param type $cmn_type
     * @param type $relation_id
     * @param type $is_anonymous 根据用户是否购买做内容隐藏
     * @param type $cur_id 当前用户id 用来获取是否已点赞
     * @param type $page
     * @param type $num
     * @param type $u_type 1 用户 2 理财师 3 理财小妹
     * @param type $is_display 是否显示
     * @param type $uid  用户id
     * @param type $is_top 是否有置顶需求
     * @param type $child_relation_id 
     */
    public static function getCommentPage($cmn_type, $relation_id, $is_anonymous = true, $cur_uid = 0, $page, $num = 15, $u_type = 1, $is_display, $uid = 0, $is_top = false, $child_relation_id = 0) {
        $apiResult = new ApiResult();
        $idx = self::getCRC32TbIndex($cmn_type, $relation_id);
        $crc32id = $idx['crc32_id'];
        $tb_index = $idx['tb_index'];

        $order = TRUE; //是否排序
        $has_reply = FALSE; //列表是否包含自己回复的
        if ($u_type == 2 || $u_type == 3) {
            $order = FALSE;
            $has_reply = TRUE;
        }
        //获取id列表
        $condition = self::handleListCondition($cmn_type, $crc32id, $is_display, $uid, $u_type, $has_reply, $child_relation_id);
        if($has_reply){
            $result = NewComment::model()->getMyCommentPage($tb_index, $condition, $page, $num);
        }else{
            $result = NewComment::model()->getCommentPage($tb_index, $condition, $page, $num, $order);
        }

        $ids = $result['data'];
        if ($is_top && $page == 1) {
            $qualitylist = NewComment::model()->getQualityList($crc32id, 0, $u_type);
            if (!empty($qualitylist)) {
                $ids = empty($ids) ? $qualitylist : array_merge($qualitylist, $ids);
            }
        }
        //获取的数据为空直接返回
        if (empty($ids)) {
            return $apiResult->setSuccess($result);
        }        
        $sub_cmn_ids = array(); //二级评论ID            
        $cmn_replys = NewComment::model()->getMutiCommentLastReplys($tb_index, $ids);
        foreach ($cmn_replys as $cmnid => $item) {
            if (!empty($item)) {
                foreach (explode(',', $item) as $rid) {
                    $sub_cmn_ids[] = $rid;
                }
            }
        }
        $list = array();
        $uids = array();
        $pids = array();
        $commentids = array_merge($ids, $sub_cmn_ids);  
        
        $infomap = NewComment::model()->getCommentByIds($commentids, $tb_index);
        $praisemap = NewComment::model()->getMutiUserPraise($tb_index, $commentids, $cur_uid);        
        foreach ($ids as $id) {
            if (isset($infomap[$id])) {
                self::handleCommentItem($list, $uids, $pids, $infomap, $cmn_replys, $id);
            }
        }

        //获取 用户 理财师  二级评论 的信息
        $users_map = !empty($uids) ? User::model()->getUserInfoById(array_unique($uids)) : array();
        // $planners_map = !empty($pids) ? Planner::model()->getPlannerById(array_unique($pids)) : array();
        // 用以下新方法获取同时获取理财师评级信息
        $planners_map = !empty($pids) ? Planner::model()->getPlannerByIdsNew(array_unique($pids), 35) : array();
        //remove by shixi_danxian 2016/4/22
        //$list = self::fillCommentRelationTitle($list);
        foreach ($list as &$item) { 
            //是否屏蔽内容，我的说说和管理员登陆无需处理，其他情况判断是否购买
            $is_content_anonymous = $is_anonymous && $item['is_anonymous'] == 1;  
            $is_content_anonymous = empty($u_type) ? $is_content_anonymous : ($u_type == 2 && $is_content_anonymous);
            $is_realname = ($uid == $item['uid']) ? TRUE : FALSE;
            $item['is_praise'] = empty($cur_uid) ? 0 : $praisemap[$item['cmn_id']];
            //拼接用户、理财师、内容信息
            self::handleCommentInfo($item, $users_map, $planners_map, $is_content_anonymous, $is_realname,$cur_uid);
            if (!empty($item['last_replays'])) {
                foreach ($item['last_replays'] as &$replyitem) {               
                    $is_reply_anonymous = $is_anonymous && $replyitem['is_anonymous'] == 1;   
                    $is_reply_anonymous = empty($u_type) ? $is_reply_anonymous : ($u_type == 2 && $is_reply_anonymous);
                    $is_realname = ($uid == $replyitem['uid']) ? TRUE : FALSE;
                    self::handleCommentInfo($replyitem, $users_map, $planners_map, $is_reply_anonymous, $is_realname,$cur_uid);
                    $replyitem['is_praise'] = empty($cur_uid) ? 0 : $praisemap[$replyitem['cmn_id']]; 
                    self::touchOldColumn($replyitem);
                }
            }            
            self::touchOldColumn($item);
        }
        $result['data'] = $list;        
        return $apiResult->setSuccess($result);
    }

    /**
     * @description 填充评论列表用户名和用户头像数据
     * @param $cmn_list 评论列表原始数据
     *
     * @return array
     */
    public static function fillCommentUserInfo($cmn_list)
    {
        $user_ids       = array();
        $planner_ids    = array();
        if (empty($cmn_list))
        {
            return array();
        }
        //获取所有的评论者uid或p_uid
        foreach($cmn_list as $k => $v)
        {
            if ($v['u_type'] == 1)
            {
                $user_ids[] = $v['uid'];
            }
            else if($v['u_type'] == 2)
            {
                $planner_ids[] = $v['uid'];
            }
        }
        $user_ids    = array_unique($user_ids);
        $planner_ids = array_unique($planner_ids);

        $users_map    = !empty($user_ids) ? User::model()->getUserInfoById($user_ids) : array();
        $planners_map = !empty($planner_ids) ? Planner::model()->getPlannerById($planner_ids) : array();

        foreach($cmn_list as &$v)
        {
            if ($v['u_type'] == 1)
            {
                self::handleCommentUserInfo($v, $users_map, FALSE);
            }
            else if ($v['u_type'] == 2)
            {
                self::handleCommentPlannerInfo($v, $planners_map);
            }
            else
            {
                $v['name'] = self::$def_admin_user_name;
                $v['image'] = self::$def_admin_user_image;
            }
        }

        return $cmn_list;
    }

    /**
     * 根据新说说数据表数据
     * 给评论数据列表填充父级内容相关标题
     * @param $cmn_list
     *
     * @return mixed
     */
    public static function fillCommentRelationTitle($cmn_list)
    {
        if (!empty($cmn_list))
        {
            foreach($cmn_list as &$v)
            {
                $v = self::getRelationTitle($v);
            }
        }

        return $cmn_list;
    }

    /**
     * @description 为评论列表中cmn_id增加表索引
     * @param $cmn_list 评论列表
     *
     * @return array
     */
    public static function fillCommentTableIndex($cmn_list)
    {
        if (!empty($cmn_list))
        {
            foreach ($cmn_list as &$v)
            {
                $relation_id = $v['relation_id'];
                $cmn_type    = $v['cmn_type'];
                //观点评论
                if ($cmn_type == 2)
                {
                    //为观点或观点包评论时使用观点包id
                    $relation_id = $v['parent_relation_id'];
                }
                $table_idx       = NewCommentService::getCRC32TbIndex($v['cmn_type'], $relation_id);
                $table_idx       = $table_idx['tb_index'];
                $v['cmn_id_new'] = '';
                if (isset($table_idx))
                {
                    $v['cmn_id_new'] = $v['cmn_id'] . '_' . $table_idx;
                }
            }
        }
        return $cmn_list;
    }

    /**
     * 兼容旧字段
     * @param type $item
     * @param type $child_relation_id
     */
    public static function touchOldColumn(&$item){
        $item['id'] = $item['cmn_id'];
        $item['parise_num'] = $item['praise_num'];
        $item['is_parise'] = $item['is_praise'];
        $item['replay_num'] = $item['reply_num'];
        $item['replay_id'] = $item['reply_id'];        
        $item['c_time_fmt'] = CommonUtils::formatDate($item['c_time'],'web');
        $item['parent_relation_id'] = 0;
        $item['is_top'] = 0;
        if($item['is_top'] == 1){
            $item['is_display'] = 2;
        }
        if($item['cmn_type'] == NewComment::CMN_TYPE_VIEW){
            $item['parent_relation_id'] = $item['relation_id'];
            $child_relation_id = $item['child_relation_id'];
            if(!empty($child_relation_id)){                
                $item['relation_id'] = $child_relation_id;
            }else{
                $item['relation_id'] = 0;
            }
            
        }
    }

    /**
     * 获取评论详情 用户id 理财师id 最后两条回复
     * @param array $list
     * @param type $uids
     * @param type $pids
     * @param type $infomap
     * @param type $cmn_replys
     * @param type $cmnid
     */
    private static function handleCommentItem(&$list, &$uids, &$pids, &$infomap, &$cmn_replys, $cmnid) {
        $item = $infomap[$cmnid];        
        if ($item['u_type'] == 1) {
            $uids[] = $item['uid'];
        } elseif ($item['u_type'] == 2) {
            $pids[] = $item['uid'];
        }        
        $item['last_replays'] = array();
        if (isset($cmn_replys[$cmnid]) && !empty($cmn_replys[$cmnid])) {            
            $replyids = explode(',', $cmn_replys[$cmnid]);
            foreach ($replyids as $replyid) {
                if (isset($infomap[$replyid])) {
                    $item['last_replays'][] = $infomap[$replyid];
                    if ($infomap[$replyid]['u_type'] == 1) {
                        $uids[] = $infomap[$replyid]['uid'];
                    } elseif ($infomap[$replyid]['u_type'] == 2) {
                        $pids[] = $infomap[$replyid]['uid'];
                    }
                }
            }
        }
        $list[] = $item;
    }

    /**
     * 填充用户信息
     * @param type $cmn
     * @param type $users_map
     * @param type $is_realname
     */
    public static function handleCommentUserInfo(&$cmn, &$users_map, $is_realname) {
        $circle_id = $cmn['relation_id'];
        $users_corps = NewMatchService::getUserCorpsInfo($users_map);
        //圈子铁粉
        $users_follow = Circle::model()->optionCircleFollowCache($circle_id);
        //判断当前用户是否为vip
        $circle_info = Circle::model()->getCircleInfo($circle_id);
        //vip 服务
        $cmn['user_service'] = VipService::vipUserService($circle_info['p_uid'],$cmn['uid']);
        
        $is_realname = true;
        if ($is_realname) {
            $cmn['name'] = isset($users_map[$cmn['uid']]) && !empty($users_map[$cmn['uid']]) ? $users_map[$cmn['uid']]['name'] : '';
        } else {
            $cmn['name'] = CommonUtils::getShowName($cmn['uid']);
        }
        $cmn['teamName'] = isset($users_corps[$cmn['uid']]) && !empty($users_corps[$cmn['uid']]) ? $users_corps[$cmn['uid']]['planner_name']."战队" : '';
        $cmn['teamTagColor'] = isset($users_corps[$cmn['uid']]) && !empty($users_corps[$cmn['uid']]) ? $users_corps[$cmn['uid']]['color'] : '';
        $cmn['image'] = isset($users_map[$cmn['uid']]) && !empty($users_map[$cmn['uid']]) ? CommonUtils::convertUserImage($users_map[$cmn['uid']]['image'], 50) : '';
        //圈子铁粉
        $cmn['userFollow'] = isset($users_follow[$cmn['uid']]) && !empty($users_follow[$cmn['uid']]) ? self::$user_follow[$users_follow[$cmn['uid']]['type']] : null;
    }

    /**
     * 填充理财师信息
     * @param type $cmn
     * @param type $planners_map
     */
    public static function handleCommentPlannerInfo(&$cmn, &$planners_map) {
        $cmn['name'] = isset($planners_map[$cmn['uid']]) && !empty($planners_map[$cmn['uid']]) ? $planners_map[$cmn['uid']]['name'] : '';
        $cmn['image'] = isset($planners_map[$cmn['uid']]) && !empty($planners_map[$cmn['uid']]) ? CommonUtils::convertUserImage($planners_map[$cmn['uid']]['image'], 30) : '';
        $cmn['company'] = isset($planners_map[$cmn['uid']]) && !empty($planners_map[$cmn['uid']]) ? $planners_map[$cmn['uid']]['company_name'] : '';
        $cmn['position'] = isset($planners_map[$cmn['uid']]) && !empty($planners_map[$cmn['uid']]) ? $planners_map[$cmn['uid']]['position'] : '';
        // 增加理财师评级信息
        $cmn['p_grade_info'] = isset($planners_map[$cmn['uid']]['grade_info']) && !empty($planners_map[$cmn['uid']]['grade_info']) ? $planners_map[$cmn['uid']]['grade_info'] : null;
    }

    /**
     * 处理评论包括用户 理财师 是否显示真名 是否隐藏内容 用户是否点赞
     * @param type $item
     * @param type $users_map
     * @param type $planners_map
     * @param type $cache_key
     * @param type $cur_uid
     * @param type $is_anonymous
     * @param type $is_realname
     */
    private static function handleCommentInfo(&$item, &$users_map, &$planners_map, $is_anonymous, $is_realname,$cur_uid) {
        ($item['u_type'] == 1) ? self::handleCommentUserInfo($item, $users_map, $is_realname) : '';
        ($item['u_type'] == 2) ? self::handleCommentPlannerInfo($item, $planners_map) : '';
        if ($item['u_type'] == 3) {
            $item['name'] = self::$def_admin_user_name;
            $item['image'] = self::$def_admin_user_image;
        }        
        if ($is_anonymous) {
            $item['content'] = CommonUtils::getPlanPrivCommentComment($item);            
        }
        //组装讨论信息
        if (!empty($item['discussion_type']) && !empty($item['discussion_id'])) {
            $discussion_info = self::getDiscussionInfo($item['discussion_type'], $item['discussion_id'], true, $cur_uid,$is_anonymous?false:true);
            if (!empty($discussion_info)) {
                $item[CommentNew::$discussion_field[$item['discussion_type']]] = $discussion_info;
            }
        }
    }

    /**
     * 
     * @param type $cmn_type
     * @param type $relation_id
     * @param type $uid
     * @param type $u_type
     * @param type $child_relation_id
     * @param type $is_display
     * @return type
     */
    public static function doMyCommentCount($cmn_type, $relation_id, $uid = 0, $u_type, $is_display = null, $child_relation_id = 0) {
        $idx = self::getCRC32TbIndex($cmn_type, $relation_id);
        $crc32id = $idx['crc32_id'];
        $tb_index = $idx['tb_index'];
        
        
        if($u_type == 2 || $u_type == 3){
            $condition = self::handleListCondition($cmn_type, $crc32id, $is_display, $uid, $u_type,TRUE, $child_relation_id);      
            $result = NewComment::model()->getMyCommentPage($tb_index, $condition, 0, 10);
        }else{
            $condition = self::handleListCondition($cmn_type, $crc32id, $is_display, $uid, $u_type,FALSE, $child_relation_id);      
            $result = NewComment::model()->getCommentPage($tb_index, $condition, 0, 0, FALSE, TRUE);
        }
        return $result['total'];
    }

    /**
     * 获取评论条件
     * @param type $cmn_type  评论类型
     * @param type $crc32id   
     * @param type $is_display 是否显示 未购买用户不显示
     * @param type $uid 
     * @param type $u_type
     * @param type $has_reply 是否包括回复的
     * @param type $child_relation_id 
     * @return string
     */
    private static function handleListCondition($cmn_type, $crc32id, $is_display = -1, $uid = 0, $u_type = 0, $has_reply = FALSE, $child_relation_id = 0) {
        $condition = array();
        $condition[] = 'crc32_id=' . $crc32id;
        
        //观点说说
        if ($cmn_type == NewComment::CMN_TYPE_VIEW && !empty($child_relation_id)) {            
            $condition[] = 'child_relation_id=' . $child_relation_id;
        }
        if (!empty($uid)) {
            $condition[] = 'uid=' . $uid;
        }
        if (!$has_reply) {
            $condition[] = 'reply_id=0';
        }
        //用户类型
        if (!empty($u_type)) {
            $condition[] = 'u_type=' . $u_type;
        }
        if ($is_display == 0 || $is_display == 1) {
            $condition[] = 'is_display=' . $is_display;
        }
        if($u_type == 2){
            $condition[] = 'is_display=1';
        }
        return $condition;
    }

    /**
     * 判断用户类型 
     * @param type $u_info
     * @param type $real_name
     * @return type
     */
    public static function checkUserTypeInfo($u_info, $real_name = true, $relation_p_uid = '' , $auth_group = array()) {
        $auth_group = (array)$auth_group;
        array_push($auth_group , UserStaff::ALL);
        $u_type_info['admin_user_info'] = self::checkStaffAuth($u_info['uid'], $auth_group);
        if ($u_type_info['admin_user_info']) {
            //小妹
            $u_type_info['is_planner'] = 0;
            $u_type_info['name'] = NewCommentService::$def_admin_user_name;
            $u_type_info['image'] = NewCommentService::$def_admin_user_image;
            $u_type_info['u_type'] = 3;
            $u_type_info['comment_uid'] = $u_info['s_uid'];
            return $u_type_info;
        }
        if ($u_info['is_p']) {
            //理财师
            if ($real_name || $relation_p_uid == $u_info['s_uid']) {
                $u_type_info['is_planner'] = 1;
                $u_type_info['u_type'] = 2;
                $u_type_info['comment_uid'] = $u_info['s_uid'];
                $planner_info = Planner::model()->getPlannerById($u_info['s_uid']);
                if ($planner_info[$u_info['s_uid']]) {
                    $u_type_info['name'] = $planner_info[$u_info['s_uid']]['name'];
                    $u_type_info['company'] = $planner_info[$u_info['s_uid']]['company'];
                    $u_type_info['position'] = $planner_info[$u_info['s_uid']]['position'];
                    $u_type_info['image'] = $planner_info[$u_info['s_uid']]['image'];
                } else {
                    $u_type_info['name'] = $u_info['name'];
                    $u_type_info['image'] = $u_info['image'];
                }
            } else {
                $u_type_info['is_planner'] = 0;
                $u_type_info['u_type'] = 1;
                $u_type_info['comment_uid'] = $u_info['uid'];
            }
        } else {
            //普通用户
            $u_type_info['is_planner'] = 0;
            $u_type_info['u_type'] = 1;
            $u_type_info['comment_uid'] = $u_info['uid'];
        }
        if ($u_type_info['u_type'] == 1) {
            /* if($real_name) {
              $u_type_info['name'] = $u_info['name'];

              }else {
              $u_type_info['name'] = CommonUtils::getShowName($u_info['uid']);

              } */
            #$u_type_info['name'] = CommonUtils::getShowName($u_info['uid']);
            //普通用户统一用真实微博名称
            $u_type_info['name'] = $u_info['name'];
            $u_type_info['image'] = $u_info['image'];
        }

        return $u_type_info;
    }

    /**
     * 根据reply_id 获取 详情
     * @param type $reply_id
     * @param type $tb_index
     * @return boolean|string
     */
    public static function getReplyInfobyId($reply_id, $tb_index) {

        $reply_infos = NewComment::model()->getCommentByIds($reply_id, $tb_index);
        $reply_info = isset($reply_infos[$reply_id]) ? $reply_infos[$reply_id] : array();
        if (empty($reply_info)) {
            return false;
        } else {
            if (!$reply_info['head_ids']) {
                $reply_info['new_head_ids'] = $reply_id;
            } else {
                $reply_info['new_head_ids'] = $reply_info['head_ids'] . "-" . $reply_id;
            }
        }
        return $reply_info;
    }

    /**
     * 判断运营是否有操作权限 
     * @param type $uid
     * @param type $auth_group
     * @return boolean
     */
    public static function checkStaffAuth($uid, $auth_group = array()) {
        $auth_group = (array)$auth_group;
        array_push($auth_group , UserStaff::ALL);
        $auth_info = UserStaff::model()->getStaffAuth($uid);
        if (!$auth_info) {
            return false;
        }
        $auth_group_list = json_decode($auth_info['auth_group'] , true);
        $auth_info['auth_list'] = $auth_group_list;
        $have_auth = array_intersect($auth_group, $auth_group_list);
        if($have_auth) {
            return $auth_info;
        }else{
            return false;
        }        
        
    }

    /**
     * 判断新旧版说说
     * @param type $cmn_type
     * @return int 0 旧板 1 新版
     */
    public static function getCommentVersion($cmn_type) {
        $group = self::getGroup($cmn_type);
        if (in_array($group, self::$old_cmn_type)) {
            return 0;
        } else {
            return 1;
        }
    }

    public static function getGroup($cmn_type) {
        $group = "";
        foreach (self::$cmn_type_group as $k => $v) {
            if (in_array($cmn_type, $v)) {
                $group = $k;
                break;
            }
        }
        return $group;
    }

    /**
     * 反垃圾过滤
     * 
     * @param type $content
     * @return boolean|string
     */
    public static function antispam($content) {

        $anti_obj = new Antispam();
        $post_arr = array();
        $res = $anti_obj->getAntispamArray($content);

        $post_arr[] = $res;
        $antispam_res = $anti_obj->filterWords($post_arr);
        //echo "<pre>";
        //print_r($antispam_res);
        //exit;
        $all_anti = array();
        $return_words = array('key_words' => '', 'mark_words' => '');
        if($antispam_res && count($antispam_res)>0){
            foreach ($antispam_res as $v) {
                $key_words = ""; //违禁
                $sens_words = ""; // 敏感
                $mark_words = ""; //可疑
                if (!empty($v)) {
                    foreach ($v as $w_info) {
                        //print_r($w_info);
                        if ($w_info['score'] == Antispam::SCORE_HIGH) {
                            // $key_words .= $w_info['key'];
                            // $return_words['key_words'] = $key_words;
                            // return $return_words;
                            $key_words .= $w_info['key'] . ",";
                        } elseif ($w_info['score'] == Antispam::SCORE_LOW) {
                            //标记可疑说说
                            $mark_words .= $w_info['key'] . ",";
                        } elseif ($w_info['score'] == Antispam::SCORE_SENSITIVE) {
                            //昵称敏感词
                            $sens_words .= $w_info['key'] . ",";
                        }
                    }

                    //$all_anti = array_merge($all_anti ,$v);
                }
            }
        }
        // if (!empty($mark_words)) {
        //     $return_words['mark_words'] = substr($mark_words, 0, -1);
        //     return $return_words;
        // }

        // return false;
        
        if (!empty($key_words)) {
            $return_words['key_words'] = substr($key_words, 0, -1);
        }
        if (!empty($mark_words)) {
            $return_words['mark_words'] = substr($mark_words, 0, -1);
        }
        if (!empty($sens_words)) {
            $return_words['sens_words'] = substr($sens_words, 0, -1);
        }
        return $return_words;
    }

    public static function freezeUser($uid, $reason, $type, $staff_uid = "system") {

        //$cur_date = date(DATE_ISO8601);
        $cur_date = date('Y-m-d H:i:s');
        $data = array();
        $data['uid'] = $uid;
        $data['type'] = $type;
        //$data['freeze_type'] = 1;
        $data['status'] = 0;
        $data['reason'] = $reason;
        $data['staff_uid'] = $staff_uid;
        $data['c_time'] = $cur_date;
        $data['u_time'] = $cur_date;
        User::model()->saveFreezeUser($data);
    }

    /**
     * 匹配搜索相关添加链接
     * @param type $symbol
     * @param type $content
     * @param string $content_html
     * @return type
     */
    public static function matchStock($symbol, $content, &$content_html) {

        //$content = '$大连圣亚$tt';
        $match_search = '';
        $content_html = $content;
        $name = self::getPageStockInfo($symbol);
        if ($name) {
            $pattern = '/^\$' . $name . '\$/is';
            $replacement = "<a href='/web/searchNew?s=" . $symbol . "&trim_ext=1'>$" . $name . "$</a>";

            $match_res = preg_match($pattern, $content, $matches);

            if ($match_res) {
                //var_dump($matches);exit;
                $match_search_arr = array();
                $match_search_arr[$matches[0]] = $symbol;
                $match_search = json_encode($match_search_arr);
                $content_html = str_replace($matches[0], $replacement, $content);
            }
            //echo $match_search;exit;
        }
        return $match_search;
        // htmlspecialchars($content_html);
    }

    public static function getPageStockInfo($symbol) {
        if (empty($symbol)) {
            return false;
        }
        $stock_info = AskTags::model()->getTagBySymbol($symbol);
        if (!empty($stock_info) && ($stock_info['symbol'] == $symbol)) {
            //select name from lcs_symbol where symbol = $symbol;
            return $stock_info['name'];
        }
        return false;
    }
    
    public static function isCharge($cmn) {
        $is_charge = true; //默认为收费
        if ($cmn['cmn_type'] == NewComment::CMN_TYPE_PLAN) {//如果是计划，肯定是收费的
            $is_charge = true;
        }
        if ($cmn['cmn_type'] == NewComment::CMN_TYPE_VIEW) {//如果是观点需要判断是否收费
            if ($cmn['child_relation_id'] > 0) {
                $view_info = View::model()->getViewById($cmn['child_relation_id']);
                $is_charge = $view_info[$cmn['child_relation_id']]['subscription_price'] > 0 ? 1 : 0; // 是否收费
            } else {
                $pkg_id = !empty($cmn['relation_id']) ? $cmn['relation_id'] : '';
                if (!empty($pkg_id)) {
                    $pkg_info = Package::model()->getPackagesById($pkg_id);
                    $pkg_info = !empty($pkg_info) ? $pkg_info[$pkg_id] : null;
                    $is_charge = ($pkg_info['subscription_price'] > 0 && date('Y-m-d H:i:s') > $pkg_info['charge_time']) ? 1 : 0; // 是否收费
                }
            }
        }
        return $is_charge;
    }

    /**
     * 
     * @param type $cmn
     * @param type $cur_uid
     * @return int
     */
    public static function getIsSub($cmn, $cur_uid) {
        $is_sub = false; //判断当前用户是否购买了该观点包或者计划，默认为false        
        if ($cmn['cmn_type'] == NewComment::CMN_TYPE_PLAN) { //如果说说属于计划
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
        if ($cmn['cmn_type'] == NewComment::CMN_TYPE_VIEW) {            
            //当前用户是否订阅了该观点包，观点包是否收费
            if ($cmn['child_relation_id'] > 0) {
                $view_info = View::model()->getViewById($cmn['child_relation_id']);
                $pkg_id = $view_info[$cmn['child_relation_id']]['pkg_id'];
                if(!empty($view_info)){
                    $is_paid = ViewSubscription::model()->getViewSubscriptionInfo($cur_uid,$cmn['child_relation_id']);
                    if(!empty($is_paid)){
                        $is_sub = 1;
                    }
                }
            } else {
                $pkg_id = isset($cmn['relation_id']) ? $cmn['relation_id'] : '';
            }
            if (empty($is_sub) && !empty($cur_uid) && !empty($pkg_id)) {
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
     * 获取评论来源信息
     * @param type $comment
     * @return string  
     */
    public static function getRelationTitle(& $comment) {
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
     * 特殊评论列表 
     * @param type $action   hot 热门说说 last 最新说说
     * @param type $cur_uid
     * @param type $cmn_type
     * @param type $is_anonymous
     * @param type $is_realName
     * @param type $page
     * @param type $num
     * @return type
     */
    public static function getSpecialCommentList($action, $cur_uid, $cmn_type, $is_realname = true, $page = 1, $num = 15) {
        $apiResult = new ApiResult();
        $sub_cmnids = array();
        $ids = array();
        $result = array();
        switch ($action) {
            case 'hot':
                $result = PageCfg::model()->getHotComment($page, $num, 14);                
                foreach ($result['data'] as $row) {
                    $ids[] = $row['relation_id'];
                }
                break;
            case 'xin':
                $result = PageCfg::model()->getHotComment($page, $num, 24);                
                foreach ($result['data'] as $row) {
                    $ids[] = $row['relation_id'];
                }                
                break;
            case 'last':
                $result = NewComment::model()->getLastCommentList($page, $num, $cmn_type);                
                foreach ($result['data'] as $cmn) {
                    $tb_index = $cmn['crc32_id'] % NewComment::COMMENT_TABLE_NUMS;
                    if (empty($tb_index)) {
                        continue;
                    }
                    $ids[] = $tb_index . '_' . $cmn['cmn_id'];
                }                
                break;
            default :
        }
        if (empty($ids)) {
            return $apiResult->setSuccess($result);
        }
        //获取最后两条回复        
        $id_index_c = array();
        $reply_list = array();
        foreach ($ids as $id) {
            $foo = explode('_', $id);
            $id_index_c[$foo[0]][] = $foo[1];
            $cmn_replys = NewComment::model()->getMutiCommentLastReplys($foo[0], $foo[1]);
            if (empty($cmn_replys)) {
                continue;
            }
            foreach ($cmn_replys as $k => $v) {
                if (empty($v)) {
                    continue;
                }
                $reply_list[$foo[0] . '_' . $k] = $v;
                foreach (explode(',', $v) as $rid) {
                    $sub_cmnids[] = $foo[0] . '_' . $rid;
                    $id_index_c[$foo[0]][] = $rid;
                }
            }
        }
        //获取点赞
        $praiselist = array();
        foreach ($id_index_c as $key => $value) {
            $praisemap = NewComment::model()->getMutiUserPraise($key, $value, $cur_uid);
            foreach ($praisemap as $m => $n) {
                $praiselist[$key . '_' . $m] = $n;
            }
        }
        
        $infomap = NewComment::model()->getCommentByIndexIds(array_merge($ids, $sub_cmnids));
        $list = array();
        $uids = array();
        $pids = array();
        foreach ($ids as $cmnid) {
            if (!isset($infomap[$cmnid])) {
                continue;
            }
            $cmnid_index = explode('_', $cmnid)[0];
            $item = $infomap[$cmnid];
            if ($item['u_type'] == 1) {
                $uids[] = $item['uid'];
            } elseif ($item['u_type'] == 2) {
                $pids[] = $item['uid'];
            }
            $item['last_replays'] = array();
            if (!isset($reply_list[$cmnid]) || empty($reply_list[$cmnid])) {
                $list[] = $item;
                continue;
            }            
            $replyids = explode(',', $reply_list[$cmnid]);
            foreach ($replyids as $replyid) {
                $replyid = $cmnid_index . '_' . $replyid;
                if (!isset($infomap[$replyid])) {
                    continue;
                }
                $item['last_replays'][] = $infomap[$replyid];
                if ($infomap[$replyid]['u_type'] == 1) {
                    $uids[] = $infomap[$replyid]['uid'];
                } elseif ($infomap[$replyid]['u_type'] == 2) {
                    $pids[] = $infomap[$replyid]['uid'];
                }
            }
            $list[] = $item;
        }
        //获取 用户 理财师  二级评论 的信息
        $users_map = !empty($uids) ? User::model()->getUserInfoById(array_unique($uids)) : array();
        // $planners_map = !empty($pids) ? Planner::model()->getPlannerById(array_unique($pids)) : array();
        // 用以下新方法获取同时获取理财师评级信息
        $planners_map = !empty($pids) ? Planner::model()->getPlannerByIdsNew(array_unique($pids), 35) : array();
        foreach ($list as &$item) {
            $tbindex = $item['crc32_id'] % NewComment::COMMENT_TABLE_NUMS;
            //是否屏蔽内容，我的说说和管理员登陆无需处理，其他情况判断是否购买
            $is_sub = self::getIsSub($item, $cur_uid);
            $is_charge = self::isCharge($item);
            //一级评论是否能够回复点赞
            $item['able_parise'] = 0;
            if ($is_sub || $item['uid'] == $cur_uid || !$is_charge || $item['cmn_type'] == NewComment::CMN_TYPE_TOPIC || $item['cmn_type'] == NewComment::CMN_TYPE_SYSTEM) {
                $item['able_parise'] = 1;
            }            
            $item['is_praise'] = empty($cur_uid) ? 0 : $praiselist[$tbindex . '_' . $item['cmn_id']];
            self::handleCommentInfo($item, $users_map, $planners_map, (!$is_sub && $item['is_anonymous'] == 1), $is_realname,$cur_uid);
            if (!empty($item['last_replays'])) {
                foreach ($item['last_replays'] as &$replyitem) {                                     
                    self::handleCommentInfo($replyitem, $users_map, $planners_map, (!$is_sub && $replyitem['is_anonymous'] == 1), $is_realname,$cur_uid);
                    $replyitem['is_praise'] = empty($cur_uid) ? 0 : $praiselist[$tbindex . '_' . $replyitem['cmn_id']];
                    self::touchOldColumn($replyitem);
                }
            }
            self::touchOldColumn($item);
        }
        $result['data'] = $list;
        return $apiResult->setSuccess($result);
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
    public static function getDiscussionInfo($discussion_type, $discussion_id, $has_planner = false, $uid = 0,$is_sub_plan=false) {
        $result = array();
        if (NewComment::DISCUSSION_TYPE_PLAN == $discussion_type) {
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
        } else if (NewComment::DISCUSSION_TYPE_VIEW == $discussion_type) {
            $view_map = View::model()->getViewById($discussion_id);
            if (!empty($view_map) && isset($view_map[$discussion_id])) {
                $view_click = View::model()->getViewClick($discussion_id);

                $view['id'] = $view_map[$discussion_id]['id'];
                $view['title'] = $view_map[$discussion_id]['title'];
                $view['view_num'] = $view_map[$discussion_id]['view_num'];
                $view['p_time'] = $view_map[$discussion_id]['p_time'];
                $view['click'] = isset($view_click[$discussion_id]) ? (int)$view_click[$discussion_id] : 0;
                $view['p_uid'] = $view_map[$discussion_id]['p_uid'];
                $result = $view;
            }
        } else if (NewComment::DISCUSSION_TYPE_PACKAGE == $discussion_type) {
            //id 图片 名称 关注数  观点数  理财师信息：id 名称 头像 公司 职位
            $package_map = Package::model()->getPackagesById($discussion_id);
            if (!empty($package_map) && isset($package_map[$discussion_id])) {
                $package['id'] = $package_map[$discussion_id]['id'];
                $package['pkg_id'] = $package_map[$discussion_id]['pkg_id'];
                $package['p_uid'] = $package_map[$discussion_id]['p_uid'];
                $package['title'] = $package_map[$discussion_id]['title'];
                $package['image'] = $package_map[$discussion_id]['image'];
                $package['view_num'] = $package_map[$discussion_id]['view_num'];
                $package['sub_num'] = $package_map[$discussion_id]['sub_num'];
                $package['collect_num'] = $package_map[$discussion_id]['collect_num'];
                $result = $package;
            }
        } else if (NewComment::DISCUSSION_TYPE_ASK == $discussion_type) {
            //id 内容 状态 理财师信息：id 名称 头像 公司 职位
            $question_map = Question::model()->getQuestionById($discussion_id);
            if (!empty($question_map) && isset($question_map[$discussion_id])) {
                $question['id'] = $question_map[$discussion_id]['id'];
                $question['q_id'] = $question_map[$discussion_id]['q_id'];
                $question['p_uid'] = $question_map[$discussion_id]['p_uid'];
                $question['content'] = $question_map[$discussion_id]['content'];
                $question['status'] = $question_map[$discussion_id]['status'];
                $question['price'] = $question_map[$discussion_id]['price'];
                $result = $question;
            }
        } else if (NewComment::DISCUSSION_TYPE_PLANNER == $discussion_type) {
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
                $result['card_page']=$planner_ext_map[$discussion_id]['card_page'];
            }else{
                $result['pln_year_rate']="0";
                $result['card_page']="0";
            }
            $view_num = View::model()->getViewNumByPuid($discussion_id);
            $result['view_num']= empty($view_num)?"0":$view_num;

            $planner_ask_map = Planner::model()->getPlannerAskInfoById($discussion_id);
            if (!empty($planner_ask_map) && isset($planner_ask_map[$discussion_id])) {
                $result['q_num'] = $planner_ask_map[$discussion_id]['q_num'];
            }else{
                $result['q_num']="0";
            }
        } else if (NewComment::DISCUSSION_TYPE_TOPIC == $discussion_type) {
            
        } else if (NewComment::DISCUSSION_TYPE_PLAN_TRANS == $discussion_type) {
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
                $transaction['reason']=$tran['reason'];
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
                    $is_subscription = $is_sub_plan?1:0;
                    /*if (!empty($uid)) {
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
                    }*/
                    
                    if(!empty($uid)){
                        $planTranUnlockLists = PlanTranUnlock::model()->getPlanTranUnlockArray($uid,$tran['pln_id']);     
                    }
                    
                    if ($is_subscription == 1 ||  !empty($planTranUnlockLists[$transaction['id']])) {
                        //加密数据
                        if (strtotime($tran['c_time']) + 600 > time()) {
                            $transaction['is_encrypt'] = 1;
                            $tran = array('symbol'=>$transaction['symbol'],'stock_name'=>$transaction['stock_name'],'deal_price'=>$transaction['deal_price'],'deal_amount'=>$transaction['deal_amount']);
                            $transaction['symbol'] = CommonUtils::encrypt3DES($transaction['symbol'], CommonUtils::$key_3des_symbol);
                            $transaction['stock_name'] = CommonUtils::encrypt3DES($transaction['stock_name'], CommonUtils::$key_3des_symbol);
                            $transaction['deal_price'] = CommonUtils::encrypt3DES($transaction['deal_price'], CommonUtils::$key_3des_symbol);
                            $transaction['deal_amount'] = CommonUtils::encrypt3DES($transaction['deal_amount'], CommonUtils::$key_3des_symbol);
                            $transaction['crypt_data'] = CommonUtils::encrypt3DES(base64_encode(json_encode($tran)), CommonUtils::$key_3des_symbol);
                        }
                        $transaction['unlock'] = !empty($planTranUnlockLists[$transaction['id']])?1:0;
                    } else {
                        $transaction['symbol'] = '';
                        $transaction['stock_name'] = '';
                        $transaction['deal_price'] = '';
                        $transaction['deal_amount'] = '';
                        $transaction['unlock'] = 0;
                    }
                }else{
                    $transaction['unlock'] = 0;
                }



                $result = $transaction;
            }
        }

        //添加理财师信息
        if ($has_planner && !empty($result) && isset($result['p_uid'])) {
            $planner_map = Planner::model()->getPlannerByIdsNew($result['p_uid'], 3);
            if (!empty($planner_map) && isset($planner_map[$result['p_uid']])) {
                $planner['p_uid'] = $planner_map[$result['p_uid']]['p_uid'];
                $planner['name'] = $planner_map[$result['p_uid']]['name'];
                $planner['image'] = $planner_map[$result['p_uid']]['image'];
                $planner['company_id'] = $planner_map[$result['p_uid']]['company_id'];
                $planner['company_name'] = $planner_map[$result['p_uid']]['company_name'];
                $planner['position_id'] = $planner_map[$result['p_uid']]['position_id'];
                $planner['position_name'] = $planner_map[$result['p_uid']]['position_name'];
                $result['planner_info'] = $planner;
            }
        }

        //添加理财师的计划年化收益率  日均仓位  定期用户是否观察 购买计划
        if (NewComment::DISCUSSION_TYPE_PLAN == $discussion_type && isset($result['planner_info'])) {
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

        return $result;
    }

    /**
     * 客户端push理财师发布说说的消息 入队列
     * @param type $cmn_data
     * @param type $type
     */
    public static function pushMessageToQueue($cmn_data, $type){
        $push_data['type']= $type;
        $push_data['cmn_id']= $cmn_data['id'];
        $push_data['relation_id']= $cmn_data['relation_id'];
        if($type == 'pkgPlannerComment'){
            $push_data['parent_relation_id'] = $cmn_data['parent_relation_id'];
        }
        $push_data['content'] = CommonUtils::getSubStrNew(CommonUtils::getTextContent($cmn_data['content']),30,'...');
        Message::model()->addMessageToQueue(Message::MESSAGE_QUEUE_COMMENT,$push_data);

    }

    /**
     * 回复通知 入队列
     * @param type $cmn_data
     * @param type $reply_info
     */
    public static function replyMessageToQueue($cmn_data , $reply_info, $type = 'replayComment'){
        $push_data['type'] = $type;
        $push_data['cmn_id'] = $cmn_data['id'];
        $push_data['replay_id'] = $cmn_data['reply_id'];
        $push_data['u_type'] = $cmn_data['u_type'];
        $push_data['uid'] = $cmn_data['uid'];
        $push_data['cmn_type'] = $cmn_data['cmn_type'];
        $push_data['relation_id'] = $cmn_data['relation_id'];
        $push_data['parent_relation_id'] = $cmn_data['parent_relation_id'];
        $push_data['content'] = CommonUtils::getSubStrNew(CommonUtils::getTextContent($cmn_data['content']),30,'...');
        $push_data['r_cmn_type']=$reply_info['cmn_type'];
        $push_data['r_cmn_id']=$reply_info['cmn_id'];
        $push_data['r_uid']=$reply_info['uid'];
        $push_data['r_u_type']=$reply_info['u_type'];
        $push_data['r_content']=CommonUtils::getSubStrNew(CommonUtils::getTextContent($reply_info['content']),30,'...');
        Message::model()->addMessageToQueue(Message::MESSAGE_QUEUE_COMMENT,$push_data);

    } 
    
    public static function getRelationLastComment($cmn_type,$relation_id,$child_relation_id){
        $apiResult = new ApiResult();
        $idx = self::getCRC32TbIndex($cmn_type, $relation_id);
        $crc32id = $idx['crc32_id'];
        $tb_index = $idx['tb_index'];

        $order = TRUE; //是否排序
        $has_reply = FALSE; //列表是否包含自己回复的
        if ($u_type == 2 || $u_type == 3) {
            $order = FALSE;
            $has_reply = TRUE;
        }
        //获取id列表
        $condition = self::handleListCondition($cmn_type, $crc32id, $is_display, $uid, $u_type, $has_reply, $child_relation_id);
        $result = NewComment::model()->getCommentPage($tb_index, $condition, $page, $num, $order);
    }

    /**
     * 用户是否对此发表过评论
     *
     */
    public static function getUserCommentNum($cmn_type,$relation_id,$uid){
        $apiResult = new ApiResult();
        $idx = self::getCRC32TbIndex($cmn_type, $relation_id);
        $crc32id = $idx['crc32_id'];
        $tb_index = $idx['tb_index'];

        $order = TRUE; //是否排序
        $u_type = 1;
        $is_display = 1;
        //获取id列表
        $condition = self::handleListCondition($cmn_type, $crc32id, $is_display, $uid, $u_type);
        $result = NewComment::model()->getCommentPage($tb_index, $condition, $page, $num, $order);
        return $result;
    }
    
}

