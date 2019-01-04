<?php

/*
 * 理财师评论表
 * Author:weiguang3
 * date:2015-01-10
 * */

class CommentNew extends CActiveRecord {

    const CMN_TYPE_PLAN = 1;
    const CMN_TYPE_VIEW = 2;
    const CMN_TYPE_TOPIC = 3;
    const CMN_TYPE_SYSTEM = 4;
    const U_TYPE_USER = 1;
    const U_TYPE_PLANNER = 2;
    const U_TYPE_ADMIN = 3;
    //说说讨论类型：0未知 1计划 2观点 3观点包 4问答 5理财师 6话题
    const DISCUSSION_TYPE_PLAN = 1;
    const DISCUSSION_TYPE_VIEW = 2;
    const DISCUSSION_TYPE_PACKAGE = 3;
    const DISCUSSION_TYPE_ASK = 4;
    const DISCUSSION_TYPE_PLANNER = 5;
    const DISCUSSION_TYPE_TOPIC = 6;
    const DISCUSSION_TYPE_PLAN_TRANS = 7;  //计划交易
    const DISCUSSION_TYPE_REWARD = 8;  //打赏礼物
    const DISCUSSION_TYPE_COURSE = 9;  //课程
    const DISCUSSION_TYPE_SILK = 10;  //锦囊    
    const DISCUSSION_TYPE_SILK_ARTICLE = 11;  //锦囊文章
    const DISCUSSION_TYPE_DYNAMIC = 12; //动态

    public static $discussion_field = array(
        1 => 'discussion_plan',
        2 => 'discussion_view',
        3 => 'discussion_package',
        4 => 'discussion_ask',
        5 => 'discussion_planner',
        6 => 'discussion_topic',
        7 => 'discussion_plan_trans',
        8 => 'discussion_reward',
        9 => 'discussion_course',
        10 => 'discussion_silk',
        11 => 'discussion_silk_article',
        12 => 'discussion_dynamic',

    );

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    // 缓存开关
    private $isUseCache = TRUE;

    //理财师
    public function tableName() {
        return 'lcs_comment';
    }

    /**
     * 插入新记录
     * @param unknown $data
     */
    public function saveComment($data) {
        $db_w = Yii::app()->licaishi_w;
        $res = $db_w->createCommand()->insert($this->tableName(), $data);
        if ($res == 1) {
            return $db_w->getLastInsertID();
        } else {
            return $res;
        }
    }

    /**
     * 修改记录
     * @param unknown $columns
     * @param string $conditions
     * @param unknown $params
     */
    public function updateComment($id, $columns) {
        $res = Yii::app()->licaishi_w->createCommand()->update($this->tableName(), $columns, "cmn_id=:id", array(':id' => $id));
        if ($res) {
            CacheUtils::delCommentById($id);
        }
        return $res;
    }

    /**
     * 修改记录根据关联ID
     * @param unknown $columns
     * @param string $conditions
     * @param unknown $params
     */
    public function updateCommentByReplayId($replay_id, $columns) {
        $cmd = Yii::app()->licaishi_w->createCommand();
        $res = $cmd->update($this->tableName(), $columns, "replay_id=:replay_id", array(':replay_id' => $replay_id));
        return $res;
    }

    /**
     * 修改自增的字段
     * @param unknown $id
     * @param unknown $key
     * @param number $val
     * @return unknown
     */
    public function updateCommentInc($id, $key, $val = 1) {
        //$key_quote = Yii::app()->licaishi_w->getPdoInstance()->quote($key);
        $sql = 'update ' . $this->tableName() . ' set ' . $key . '=' . $key . '+' . intval($val) . ' where cmn_id=' . intval($id) . ';';
        $command = Yii::app()->licaishi_w->CreateCommand($sql);
        $res = $command->execute();
        if ($res) {
            CacheUtils::delCommentById($id);
        }
        return $res;
    }

    /**
     * 根据评论ID获取评论详情
     * @param unknown $ids
     */
    public function getCommentByIds($ids = array()) {
        $ids = !is_array($ids) ? (array) $ids : $ids;
        $ids = array_unique($ids);
        if (empty($ids)) {
            return array();
        }
        $return = array();
        //从缓存获取数据
        $mult_key = array();
        foreach ($ids as $val) {
            $mult_key[] = MEM_PRE_KEY . "cmn_" . intval($val);
        }

        $result = Yii::app()->cache->mget($mult_key);
        $leave_key = array();
        foreach ($result as $key => $val) {
            $v_key = str_replace(MEM_PRE_KEY . "cmn_", '', $key);
            //TODO 缓存禁用
            //$val = false;
            if ($val !== false) {
                $return["$v_key"] = $val;
            } else {
                $leave_key[] = intval($v_key);
            }
        }
        //缓存没取到去数据库取
        if (sizeof($leave_key) > 0) {
            $sql = "SELECT cmn_id, cmn_id AS id, cmn_type, relation_id, u_type,uid, content, parise_num, replay_num, floor_num, 
            		replay_id, is_display, is_anonymous, status, c_time, u_time, parent_relation_id,discussion_type,discussion_id "
                . " FROM " . $this->tableName()
                . " WHERE cmn_id IN(" . implode(',', $leave_key) . ") and status=0";
            $cmd = Yii::app()->lcs_r->createCommand($sql);
            $comments = $cmd->queryAll();

            if (is_array($comments) && sizeof($comments) > 0) {
                foreach ($comments as $vals) {
                    $return[$vals['id']] = $vals;
                    Yii::app()->cache->set(MEM_PRE_KEY . "cmn_" . $vals['id'], $vals, 36000);
                }
            }
        }
        return $return;
    }

    /**
     * 获取用户发布的评论(包括回复的评论)的分页数据
     * @param unknown $cmn_type  评论类型
     * @param unknown $relation_id   相关ID
     * @param number $u_type 用户类型
     * @param number $uid  用户ID
     * @param number $page
     * @param number $num
     */
    public function getMyCommentPage($cmn_type, $relation_id, $u_type, $uid, $page = 1, $num = 15, $has_info = true, $parent_relation_id = 0) {
        $db_r = Yii::app()->lcs_r;
        $offset = CommonUtils::fomatPageParam($page, $num);

        $cdn = ' and u_type=' . intval($u_type);
        if (!empty($uid)) {
            $cdn .= ' and uid=' . intval($uid);
        }
        //add by weiguang3 20160406 理财师说 只能看购买用户的
        if($u_type==2){
            $cdn .= ' and is_display=1';
        }
        if ($cmn_type == self::CMN_TYPE_PLAN) {
            $cdn .= ' and relation_id=' . intval($relation_id);
        } elseif ($cmn_type == self::CMN_TYPE_VIEW) {
            if ($relation_id == 0) {
                $cdn .= ' and parent_relation_id=' . intval($parent_relation_id);
            } else {
                $cdn .= ' and relation_id=' . intval($relation_id);
            }
        }


        $sql = 'select cmn_id, cmn_id as id, replay_id from ' . $this->tableName()
            . ' where cmn_type=:cmn_type ' . $cdn . ' and status=0;';
        $cmd = $db_r->createCommand($sql);
        $cmd->bindParam(':cmn_type', $cmn_type, PDO::PARAM_INT);
        //$cmd->bindParam(':relation_id',$relation_id,PDO::PARAM_INT);
        $data = $cmd->queryAll();
        $sort_page = CommonUtils::getPage(array(), $page, $num, 0);
        //获取详情
        if (!empty($data)) {
            $ids = array();
            foreach ($data as $row) {
                if (!empty($row['replay_id'])) {
                    $ids[] = $row['replay_id'];
                } else {
                    $ids[] = $row['id'];
                }
            }
            $ids = array_unique($ids);
            rsort($ids);
            $sort_page = CommonUtils::arrayPage($ids, $num, $page);

            if ($has_info) {
                $data = array();
                $comment_map = $this->getCommentByIds($sort_page['data']);
                foreach ($sort_page['data'] as $id) {
                    if (isset($comment_map[$id])) {
                        $data[] = $comment_map[$id];
                    }
                }
                $sort_page['data'] = $data;
            }
        }

        return $sort_page;
    }

    /**
     * TODO 增加运营设置的说说
     * 获取热门说说 最多10条
     * 72小时内 运营设置和评论数和赞数最多
     * @param int $seconds 秒  默认259200 72小时
     * @param int $page
     * @param int $num
     */
    public function getHotCommentList($seconds = 259200, $page = 1, $num = 10, $type) {
        //先获取理财小妹推荐说说
        $result = PageCfg::model()->getHotComment($page, $num);
        $ids = array();

        foreach ($result['data'] as $row) {
            if (!empty($row) && sizeof($row['relation_id']) > 0) {
                $ids[] = $row['relation_id'];
            }
        }
        /*
          if($count<10){
          //72小时内
          $db_r = Yii::app()->licaishi_r;
          $_c_time = date('Y-m-d H:i:s',time()-$seconds);
          $sql_total = 'select count(cmn_id) as total from '.$this->tableName()
          .' where status=0 and replay_id=0 and is_display>0 and is_anonymous =0 and c_time>=:c_time'
          .' and cmn_type IN ('.implode(',',$type).');';
          $cmd_count = $db_r->createCommand($sql_total);
          $cmd_count->bindParam(':c_time',$_c_time,PDO::PARAM_STR);
          $total  = $cmd_count->queryScalar();

          $sql_hotcomment='select cmn_id,cmn_id as id FROM '.$this->tableName()
          .' where status=0 and replay_id=0 and is_display>0 and is_anonymous =0 and c_time>=:c_time'
          .' and cmn_type IN ('.implode(',',$type).')'
          .' order by (parise_num+replay_num) desc limit :offset,:limit;';
          $cmd = $db_r->createCommand($sql_hotcomment);
          $_offset =1;
          $cmd->bindParam(':offset',$_offset,PDO::PARAM_INT);
          $cmd->bindParam(':c_time',$_c_time,PDO::PARAM_STR);
          $cmd->bindParam(':limit',$num,PDO::PARAM_INT);
          $data = $cmd->queryAll();
          $result = CommonUtils::getPage($data,$page,$num,$total);
          if(!empty($result['data'])) {
          foreach ($result['data'] as $row){
          if($count<=10){
          if(in_array($row['id'],$ids)){
          }else{
          $ids[] = $row['id'];
          $count++;
          }
          }
          }
          }
          } */
        $data = array();
        $hotcomment_map = $this->getCommentByIds($ids);
        foreach ($ids as $row) {
            if (isset($hotcomment_map[$row])) {
                $data[] = $hotcomment_map[$row];
            }
        }
        $result['data'] = $data;
        return $result;
    }

    /**
     * 最新的说说
     * @param int $page
     * @param int $num
     */
    public function getLastCommentList($page = 1, $num = 15, $type) {
        $db_r = Yii::app()->lcs_r;
        $offset = CommonUtils::fomatPageParam($page, $num);
        $sql_total = 'select count(cmn_id) as total from ' . $this->tableName() . ' where replay_id=0 and status=0 and is_display>0 and is_anonymous =0'
            . ' and cmn_type IN (' . implode(',', $type) . ');';
        $cmd_count = $db_r->createCommand($sql_total);
        $total = $cmd_count->queryScalar();

        $sql_hotcomment = 'select cmn_id,cmn_id as id FROM ' . $this->tableName()
            . ' where status=0 and replay_id=0 and is_display>0 and is_anonymous =0'
            . ' and cmn_type IN (' . implode(',', $type) . ')'
            . ' order by c_time desc limit :offset,:limit;';
        $cmd = $db_r->createCommand($sql_hotcomment);
        $cmd->bindParam(':offset', $offset, PDO::PARAM_INT);
        $cmd->bindParam(':limit', $num, PDO::PARAM_INT);
        ;
        $data = $cmd->queryAll();

        $result = CommonUtils::getPage($data, $page, $num, $total);
        //获取详情
        if (!empty($result['data'])) {
            $ids = array();
            foreach ($result['data'] as $row) {
                $ids[] = $row['id'];
            }
            $data = array();
            $hotcomment_map = $this->getCommentByIds($ids);
            foreach ($result['data'] as $row) {
                if (isset($hotcomment_map[$row['id']])) {
                    $data[] = $hotcomment_map[$row['id']];
                }
            }
            $result['data'] = $data;
        }
        return $result;
    }

    public function getTopicCommentsById($topicId, $number) {
        $db_r = Yii::app()->lcs_r;
        $sql_total = 'select count(cmn_id) from ' . $this->tableName() . ' where replay_id=0 and status=0 and is_display>0 and is_anonymous =0 and cmn_type=3'
            . ' and relation_id = :relation_id;';
        $cmd_count = $db_r->createCommand($sql_total);
        $cmd_count->bindParam(':relation_id', $topicId, PDO::PARAM_INT);
        $total = $cmd_count->queryScalar();

        $data = array();
        $_first = 0;
        $sql = 'select cmn_id as id from ' . $this->tableName() . ' where replay_id=0 and status=0 and is_display>0 and is_anonymous =0 and cmn_type=3'
            . ' and relation_id = :relation_id'
            . ' order by c_time desc limit :first,:number;';
        $command = $db_r->createCommand($sql);
        $command->bindParam(':relation_id', $topicId, PDO::PARAM_INT);
        $command->bindParam(':first', $_first, PDO::PARAM_INT);
        $command->bindParam(':number', $number, PDO::PARAM_INT);
        $data = $command->queryAll();
        $_first +=1;
        $result = CommonUtils::getPage($data, $_first, $number, $total);
        return $result;
    }

    /**
     * 获取一级评论的分页数据
     * @param unknown $cmn_type
     * @param unknown $relation_id
     * @param number $page
     * @param number $num
     */
    public function getCommentPage($cmn_type, $relation_id, $u_type = null, $uid = null, $is_display = 1, $page = 1, $num = 15, $parent_relation_id = 0) {
        $db_r = Yii::app()->lcs_r;
        $offset = CommonUtils::fomatPageParam($page, $num);

        $cdn = '';
        if (!empty($u_type) && !empty($uid)) {
            $cdn = ' and u_type=' . intval($u_type) . ' and uid=' . intval($uid);
        } else if (!is_null($is_display) && ($is_display == 1 || $is_display == 0)) {
            $cdn = 'and is_display=' . $is_display;
        }

        //add by weiguang3 20150817 观点和观点包的说说 比较特殊单独处理，其他的说说处理一样
        if ($cmn_type == self::CMN_TYPE_VIEW) {
            if ($relation_id == 0) {
                $cdn .= ' and parent_relation_id=' . (int) $parent_relation_id;  //对应观点包的id
            } else {
                $cdn .= ' and relation_id=' . (int) $relation_id;  //对应观点的id
            }
        } else {
            $cdn .= ' and relation_id=' . (int) $relation_id;  //对应计划的id
        }

        $sql_total = 'select count(cmn_type) as total from ' . $this->tableName()
            . ' where cmn_type=:cmn_type  ' . $cdn . ' and replay_id=0 and status=0;';
        //error_log($sql_total);
        //计算总页数
        $cmd_count = $db_r->createCommand($sql_total);
        $cmd_count->bindParam(':cmn_type', $cmn_type, PDO::PARAM_INT);
        //$cmd_count->bindParam(':relation_id',$relation_id,PDO::PARAM_INT);
        $total = $cmd_count->queryScalar();
        //error_log($cmn_type);error_log($cmd_count->getText());
        $data = null;
        if ($offset < $total) {
            $sql = 'select cmn_id, cmn_id as id from ' . $this->tableName()
                . ' where cmn_type=:cmn_type ' . $cdn . ' and replay_id=0 and status=0 
			     		order by u_time desc,floor_num desc limit :offset, :limit;';
            $cmd = $db_r->createCommand($sql);
            $cmd->bindParam(':cmn_type', $cmn_type, PDO::PARAM_INT);
            //$cmd->bindParam(':relation_id',$relation_id,PDO::PARAM_INT);
            $cmd->bindParam(':offset', $offset, PDO::PARAM_INT);
            $cmd->bindParam(':limit', $num, PDO::PARAM_INT);
            $data = $cmd->queryAll();
        }
        $result = CommonUtils::getPage($data, $page, $num, $total);

        //获取详情
        if (!empty($result['data'])) {
            $ids = array();
            foreach ($result['data'] as $row) {
                $ids[] = $row['id'];
            }
            $data = array();
            $comment_map = $this->getCommentByIds($ids);
            foreach ($result['data'] as $row) {
                if (isset($comment_map[$row['id']])) {
                    $data[] = $comment_map[$row['id']];
                }
            }
            $result['data'] = $data;
        }

        return $result;
    }

    /**
     * 获取二级评论的分页数据
     * @param unknown $replay_id
     * @param number $page
     * @param number $num
     */
    public function getCommentPageByReplayId($replay_id, $page = 1, $num = 15) {
        $db_r = Yii::app()->lcs_r;
        $offset = CommonUtils::fomatPageParam($page, $num);

        $sql_total = 'select count(replay_id) as total from ' . $this->tableName() . ' where replay_id=:replay_id and status=0;';
        //计算总页数
        $cmd_count = $db_r->createCommand($sql_total);
        $cmd_count->bindParam(':replay_id', $replay_id, PDO::PARAM_INT);
        $total = $cmd_count->queryScalar();

        $data = null;
        if ($offset < $total) {
            $sql = 'select cmn_id, cmn_id as id from ' . $this->tableName()
                . ' where replay_id=:replay_id and status=0 order by u_time desc,floor_num desc limit :offset, :limit;';
            $cmd = $db_r->createCommand($sql);
            $cmd->bindParam(':replay_id', $replay_id, PDO::PARAM_INT);
            $cmd->bindParam(':offset', $offset, PDO::PARAM_INT);
            $cmd->bindParam(':limit', $num, PDO::PARAM_INT);
            $data = $cmd->queryAll();
        }
        $result = CommonUtils::getPage($data, $page, $num, $total);

        //获取详情
        if (!empty($result['data']) && sizeof($result['data']) > 0) {
            $ids = array();
            foreach ($result['data'] as $row) {
                $ids[] = $row['id'];
            }
            $data = array();
            $comment_map = $this->getCommentByIds($ids);
            foreach ($result['data'] as $row) {
                if (isset($comment_map[$row['id']])) {
                    $data[] = $comment_map[$row['id']];
                }
            }
            $result['data'] = $data;
        } else {
            $result['data'] = array();
        }

        return $result;
    }

    /**
     * 根据评论ID获取评论的楼层数量    如果$cmn_type和$relation_id不为空记录一级评论的楼层     如果$replay_id不为空计算二级评论的楼层
     * @param unknown $cmn_id
     * @param unknown $cmn_type
     * @param unknown $relation_id
     * @param unknown $replay_id
     */
    public function getCommentFloorNum($cmn_id, $cmn_type, $relation_id, $replay_id) {
        $db_r = Yii::app()->licaishi_w;
        $floor_num = 0;
        if (!empty($cmn_id) && !empty($replay_id)) {
            $sql_total = 'select count(replay_id) as total from ' . $this->tableName() . ' where replay_id=:replay_id and cmn_id<=:cmn_id;';
            //计算总页数
            $cmd_count = $db_r->createCommand($sql_total);
            $cmd_count->bindParam(':replay_id', $replay_id, PDO::PARAM_INT);
            $cmd_count->bindParam(':cmn_id', $cmn_id, PDO::PARAM_INT);
            $floor_num = $cmd_count->queryScalar();
        } else if (!empty($cmn_id) && !empty($cmn_type) && !empty($relation_id)) {
            $sql_total = 'select count(cmn_type) as total from ' . $this->tableName()
                . ' where cmn_type=:cmn_type and relation_id=:relation_id and is_display=1 and replay_id=0 and cmn_id<=:cmn_id;';
            //计算总页数
            $cmd_count = $db_r->createCommand($sql_total);
            $cmd_count->bindParam(':cmn_type', $cmn_type, PDO::PARAM_INT);
            $cmd_count->bindParam(':relation_id', $relation_id, PDO::PARAM_INT);
            $cmd_count->bindParam(':cmn_id', $cmn_id, PDO::PARAM_INT);
            $floor_num = $cmd_count->queryScalar();
        }

        return $floor_num;
    }

    /**
     * 获取评论数量
     * @param unknown_type $pln_id
     * @param unknown_type $p_uid
     */
    public function getCommentNum($cmn_type, $relation_id, $u_type, $uid, $time = '', $parent_relation_id = 0, $is_display = null) {
        $cdn = 'cmn_type=' . (int) $cmn_type;
        if ($cmn_type == self::CMN_TYPE_PLAN) {
            $cdn .= ' and relation_id=' . (int) $relation_id;
        } elseif ($cmn_type == self::CMN_TYPE_VIEW) {
            if ($relation_id == 0) {
                $cdn .= ' and parent_relation_id=' . (int) $parent_relation_id;  //对应观点包的id
            } else {
                $cdn .= ' and relation_id=' . (int) $relation_id;  //对应观点的id
            }
        }

        $cdn .=!empty($u_type) ? ' and u_type=' . intval($u_type) : '';
        $cdn .=!empty($uid) ? ' and uid=' . intval($uid) : '';
        $cdn .=!empty($time) ? ' and c_time>' . "'" . $time . "'" : '';
        $cdn .=!is_null($is_display) ? ' and is_display!=0' : '';

        $sql = "select count(cmn_type) from " . $this->tableName()
            . " where " . $cdn . " and replay_id=0 and status=0 ";

        return Yii::app()->lcs_r->createCommand($sql)->queryScalar();
    }

    /**
     * 获取评论数量 - 可以传数组获取
     * @param unknown_type $pln_id
     * @param unknown_type $p_uid
     */
    public function getCommentNumAll($cmn_type, $relation_id, $u_type, $uid, $parent_relation_id, $is_display = null) {
        $cdn = 'cmn_type=' . (int) $cmn_type;
        $group_by = 'parent_relation_id';
        if ($cmn_type == self::CMN_TYPE_PLAN) {
            $cdn .= ' and relation_id=' . (int) $relation_id;
        } elseif ($cmn_type == self::CMN_TYPE_VIEW) {
            if ($relation_id == 0) {                     //代表查观点包
                if (is_array($parent_relation_id)) {
                    if (!empty($parent_relation_id)) {
                        $str = implode(",", $parent_relation_id);
                        $cdn .= ' and parent_relation_id in (' . $str . ')';
                        ;  //对应观点包的ids
                    }
                } else {
                    $cdn .= ' and parent_relation_id=' . (int) $parent_relation_id;  //对应观点包的id
                }
            } else {    //代表查观点
                $group_by = 'relation_id';
                $cdn .= ' and relation_id=' . (int) $relation_id;  //对应观点的id
            }
        }
        $cdn .=!empty($u_type) ? ' and u_type=' . intval($u_type) : '';
        $cdn .=!empty($uid) ? ' and uid=' . intval($uid) : '';
        $cdn .=!is_null($is_display) ? ' and is_display!=0' : '';
        $sql = "select parent_relation_id,count(cmn_type) as comment_num from " . $this->tableName()
            . " where " . $cdn . " and replay_id=0 and status=0 group by " . $group_by;

        //update by zwg 20150721 sql语句太耗时，取消执行
        //$pkg_comment_nums_temp = Yii::app()->licaishi_r->createCommand($sql)->queryAll();
        $pkg_comment_nums = array();
        //foreach ($pkg_comment_nums_temp as $val){
        //	$pkg_comment_nums[$val['parent_relation_id']] = $val['comment_num'];
        //}
        return $pkg_comment_nums;
    }

    /**
     * 获取最新的子评论ids
     * @param unknown $replay_id  父评论ID
     * @param unknown $num
     */
    public function getLastedCommentIds($replay_id, $num) {
        $db_r = Yii::app()->lcs_r;
        $sql = 'select cmn_id, cmn_id as id from ' . $this->tableName()
            . ' where replay_id=:replay_id and status=0 order by u_time desc,floor_num desc limit 0, :limit;';
        $cmd = $db_r->createCommand($sql);
        $cmd->bindParam(':replay_id', $replay_id, PDO::PARAM_INT);
        $cmd->bindParam(':limit', $num, PDO::PARAM_INT);
        return $cmd->queryAll();
    }

    /**
     * 获取置顶的评论ids
     * @param unknown $cmn_type
     * @param unknown $relation_id
     */
    public function getTopComments($cmn_type, $relation_id, $parent_relation_id = 0) {
        $db_r = Yii::app()->lcs_r;
        $where = 'cmn_type=:cmn_type and relation_id=:relation_id ';
        if ($parent_relation_id > 0) {
            $where .= ' and parent_relation_id=' . (int) $parent_relation_id;
        }
        $sql = 'select cmn_id, cmn_id as id from ' . $this->tableName()
            . ' where ' . $where . ' and is_display=2 and status=0 order by u_time desc;';
        $cmd = $db_r->createCommand($sql);
        $cmd->bindParam(':cmn_type', $cmn_type, PDO::PARAM_INT);
        $cmd->bindParam(':relation_id', $relation_id, PDO::PARAM_INT);
        $res_data = $cmd->queryAll();

        $result = array();
        //获取详情
        if (!empty($res_data)) {
            $ids = array();
            foreach ($res_data as $row) {
                $ids[] = $row['id'];
            }
            $comment_map = $this->getCommentByIds($ids);
            foreach ($res_data as $row) {
                if (isset($comment_map[$row['id']])) {
                    $result[] = $comment_map[$row['id']];
                }
            }
        }

        return $result;
    }

    /**
     * 根据观点包的类型和相关id获取观点的id
     * @param type $type
     * @param type $id
     */
    public function getCmnIdByCmnType($type, $id,$length=0) {
        $cdn="";
        if($length>0){
            $cdn=" limit 0,".$length;
        }
        if ($type == 0) {
            $sql = "select cmn_id from " . $this->tableName() . " where parent_relation_id=" . $id . " and u_type=1 and cmn_type=2 and replay_id=0 and status=0 and is_display=1 order by c_time desc ".$cdn;
        } else if ($type >= 1) {
            $sql = "select cmn_id from " . $this->tableName() . " where relation_id=" . $id . " and u_type=1 and cmn_type=" . $type." and  replay_id=0 and status=0 and is_display=1 order by c_time desc ".$cdn;
        }
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $data = $cmd->queryAll();
        return $data;
    }

}
