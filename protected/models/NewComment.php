<?php

/*
 * 理财师新版大家说
 * Author:meixin
 * date:2015-10-14
 * */

class NewComment extends CActiveRecord {

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
    
	const COMMENT_TABLE_NUMS = 256; //大家说分表总数
	const MASTER_SAVE_TIME = '-6 month'; //master 表的保存时间  180天

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function getDB($db_type = 'r') {
		if ($db_type == 'r') {
			if (empty(Yii::app()->lcs_comment_r->active)) {
				Yii::app()->lcs_comment_r->active = false;
				Yii::app()->lcs_comment_r->active = true;
			}
			return Yii::app()->lcs_comment_r;
		} elseif ($db_type == 'w') {
			if (empty(Yii::app()->lcs_comment_w->active)) {
				Yii::app()->lcs_comment_w->active = false;
				Yii::app()->lcs_comment_w->active = true;
			}
			return Yii::app()->lcs_comment_w;
		}
	}

	//索引与总数表
	public function tableIndexNum() {
		return NEW_COMMENT_TABLE_PREFIX . "index_num";
	}

	//全量表
	public function tableCommentMaster() {
		return NEW_COMMENT_TABLE_PREFIX . 'master';
	}

	//普通说表
	public function tableName_prefix() {
		return NEW_COMMENT_TABLE_PREFIX;
	}

	//置顶表
	public function tableCommentQuality() {
		return NEW_COMMENT_TABLE_PREFIX . 'quality';
	}

	//垃圾表
	public function tableCommentTrash() {
		return NEW_COMMENT_TABLE_PREFIX . 'trash';
	}

	//媒体资源表
	public function tableCommentMedia() {
		return NEW_COMMENT_TABLE_PREFIX . 'media';
	}

	public function getTbIndex($cmn_type, $relation_id) {
		$crc32_id = CommonUtils::getCRC32($cmn_type . '_' . $relation_id);
		$tb_index = $crc32_id % self::COMMENT_TABLE_NUMS;
		$res = $this->getCommentTbIndexNum($crc32_id);
		if ($res && sizeof($res) > 0 && $tb_index == $res['tb_index']) {
			return $res;
		} else {
			//不在表中先插进去
			$datas = array(
				'cmn_type' => $cmn_type,
				'relation_id' => $relation_id,
				'crc32_id' => $crc32_id,
				'tb_index' => $tb_index,
				'comment_num' => 0,
				'planner_comment_num' => 0
			);
			$this->insertTbIndexNum($datas);
			return $datas;
		}
	}

	/**
	 * 获取评论数量与表索引
	 * @params unknown $crc32
	 */
	public function getCommentTbIndexNum($crc32_id) {
		$sql = "select cmn_type,relation_id,crc32_id,tb_index,comment_num,planner_comment_num from " . $this->tableIndexNum() . " where crc32_id=" . $crc32_id;
		$result = $this->getDB('r')->createCommand($sql)->queryRow();
		return $result;
	}

	public function insertTbIndexNum($datas = array()) {
		$datas['c_time'] = $datas['u_time'] = date('Y-m-d H:i:s');
		$res = $this->getDB('w')->createCommand()->insert($this->tableIndexNum(), $datas);
		return true;
	}

	public function getCommentInfoFromNormal($tb_index, $cmn_ids) {

		$cmn_ids = (array) $cmn_ids;
		if (empty($cmn_ids)) {
			return array();
		}
		$sql = 'SELECT cmn_id,crc32_id,cmn_type,relation_id,u_type,uid,content,head_ids,praise_num,reply_num,floor_num,' .
			' reply_id,is_display,is_anonymous,is_good,source,child_relation_id,c_time,u_time,root_reply_id ' .
			' FROM ' . $this->tableName_prefix() . $tb_index . ' ' .
			' WHERE cmn_id in (' . implode(',', $cmn_ids) . ')';
		$db_r = $this->getDB('r');
		$comment_list = $db_r->createCommand($sql)->queryAll();
		return $comment_list;
	}

	/**
	 * 从普通表里删除
	 * @param type $tb_index
	 * @param type $cmn_id
	 */
	public function delCommentFromNormal($tb_index, $cmn_id = array()) {
		$cmn_id = is_array($cmn_id) ? $cmn_id : (array) $cmn_id;
		$where = " cmn_id in (" . implode(',', $cmn_id) . ")";
		$sql = "delete from " . $this->tableName_prefix() . $tb_index . " where " . $where;
		$res = $this->getDB('w')->createCommand($sql)->execute();
		return $res;
	}

	/**
	 * 从普通表到置顶或垃圾表里
	 * @param type $data
	 * @param type $tb_type  1 置顶表 ， 2 垃圾表
	 */
	public function insertCommentToSpecial($data, $tb_type = 1) {
		$data['u_time'] = date('Y-m-d H:i:s');
		$tb_name = ($tb_type == 1) ? $this->tableCommentQuality() : $this->tableCommentTrash();
		$res = $this->getDB('w')->createCommand()->insert($tb_name, $data);
		if ($res == 1) {
			return $this->getDB('w')->getLastInsertID();
		} else {
			return $res;
		}
	}

	/**
	 * 从置顶表或垃圾表里删除
	 * @param type $data
	 * @param type $tb_type 1 置顶表 2 垃圾表
	 */
	public function delCommentFromSpecial($index_id, $tb_type = 1) {

		$tb_name = ($tb_type == 1) ? $this->tableCommentQuality() : $this->tableCommentTrash();
		$sql = "delete from " . $tb_name . " where index_id =:index_id";
		$cmn = $this->getDB('w')->createCommand($sql);
		$cmn->bindParam(':index_id', $index_id, PDO::PARAM_STR);
		$res = $cmn->execute();
		if ($res > 0 && 1 == $tb_type) {
			CacheUtils::delNewComment($index_id);
		}
		return $res;
	}

	/**
	 * 从定期全量表里删除
	 * @param type $crc32_id
	 * @param type $cmn_id
	 * @return type
	 */
	public function delCommentFromMaster($crc32_id, $cmn_id) {
		$sql = "delete from " . $this->tableCommentMaster() . " where crc32_id=:crc32_id and cmn_id =:cmn_id";
		$cmn = $this->getDB('w')->createCommand($sql);
		$cmn->bindParam(':crc32_id', $crc32_id, PDO::PARAM_STR);
		$cmn->bindParam(':cmn_id', $cmn_id, PDO::PARAM_INT);
		$res = $cmn->execute();
		return $res;
	}

	/**
	 * 更新说说数量
	 * @param type $crc32_id
	 * @param type $opt  add del
	 * @param type $is_planner
	 * @param type $num
	 */
	public function updatetbIndexNum($crc32_id, $opt = 'add', $num = 1, $p_num = 0) {
		$set_str = '';
		$u_time = date('Y-m-d H:i:s');
		if ($p_num > 0) {
			$set_str .= " , planner_comment_num=" . (($opt == 'add') ? ("planner_comment_num+" . $p_num) : ("planner_comment_num-" . $p_num) );
		}
		$sql = "update " . $this->tableIndexNum() . " set comment_num =" . (($opt == 'add') ? ("comment_num+" . $num) : ("comment_num-" . $num) ) .
			$set_str . ", u_time='" . $u_time . "' where crc32_id=" . $crc32_id;
		$res = $this->getDB('w')->createCommand($sql)->execute();
	}

	/**
	 * 获取说说数量
	 * @param $crc32_id
	 * @param $s_time
	 * @return int
	 */
	public function getCommentNumOfMaster($crc32_id, $s_time) {
		try {
			$sql = 'select count(cmn_id) as total from ' . $this->tableCommentMaster() . ' where crc32_id=:crc32_id and root_reply_id =0 and c_time >=:s_time;';
			$cmd = $this->getDB('r')->createCommand($sql);
			$cmd->bindParam(':crc32_id', $crc32_id, PDO::PARAM_STR);
			$cmd->bindParam(':s_time', $s_time, PDO::PARAM_STR);
			$total = $cmd->queryRow();
			return isset($total['total']) ? $total['total'] : 0;
		} catch (Exception $e) {

		}
		return 0;
	}

	//更新分表， 更新master表的回复数量

	/**
	 * 更新分表增长的字段
	 * @param type $tb_index
	 * @param type $cmn_id
	 * @param type $column
	 * @param type $val
	 * @return type
	 */
	public function updateCommentInc($tb_index, $cmn_id, $column, $val = 1) {
		$sql = "update " . $this->tableName_prefix() . $tb_index . " set " . $column . "=" . $column . "+" . intval($val) . " where cmn_id=" . intval($cmn_id) . ";";
		$res = $this->getDB('w')->createCommand($sql)->execute();
		if ($res) {
			CacheUtils::delNewComment($tb_index . '_' . $cmn_id);
		}
		return $res;
	}

	/**
	 * 更新主表增长的字段
	 * @param type $tb_index
	 * @param type $cmn_id
	 * @param type $column
	 * @param type $val
	 * @return type
	 */
	public function updateCommentMasterInc($crc32_id, $cmn_id, $column, $val = 1) {
		$where = "crc32_id=" . $crc32_id . " and cmn_id=" . intval($cmn_id) . ";";
		$sql = "update " . $this->tableCommentMaster() . " set " . $column . "=" . $column . "+" . intval($val) . " where " . $where;
		$res = $this->getDB('w')->createCommand($sql)->execute();
		return $res;
	}

	/**
	 * 依据 crc32_id 和 cmn_id 获取说说媒体资源列表
	 * @param  array $crc32_cmn_ids key=crc32id, value=cmn_ids
	 * @return array                媒体资源信息
	 */
	public function getMediaByCrc32Cmnids($crc32_cmn_ids) {
		if (empty($crc32_cmn_ids)) {
			return array();
		}

		$media_arr = array();

		$where = "";
		foreach ($crc32_cmn_ids as $crc32_id => $cmn_ids) {
			$where .= " (crc32_id={$crc32_id} AND cmn_id IN (" . implode(',', $cmn_ids) . ")) OR";
		}
		$where = substr($where, 0, -2);

		$sql = "SELECT id AS media_id,crc32_id,cmn_id,type,url,summary,c_time,duration FROM " . $this->tableCommentMedia() . " WHERE 1 AND {$where}";
		$rows = $this->getDB('r')->createCommand($sql)->queryAll();
		if (!empty($rows)) {
			foreach ($rows as $row) {
				$media_arr[$row['crc32_id']][$row['cmn_id']][] = $row;
			}
		}

		return $media_arr;
	}

	/**
	 * 删除主表记录
	 */
	public function deleteMasterRecord() {
		$last_3month = date("Y-m-d H:i:s", strtotime("-3 month"));
		$sql = "delete from " . $this->tableCommentMaster() . " where c_time<='$last_3month' order by id limit 1000";
		$this->getDB("w")->createCommand($sql)->execute();
	}

	/**
	 * getTableIndex
	 * @param type $crc32_id
	 * @return type
	 */
	public function getTableIndex($crc32_id) {
		return intval($crc32_id) % self::COMMENT_TABLE_NUMS;
	}
	

    /**
     * 插入新记录
     * @param type $tb_index
     * @param type $data
     * @return type
     */
	public function saveComment($tb_index,$data){
		$res = $this->getDB('w')->createCommand()->insert($this->tableName_prefix().$tb_index, $data);
		if($res==1){
			return $this->getDB('w')->getLastInsertID();
		}else{
			return $res;
		}
	}

    /**
     * 插入全量表
     * @param type $tb_index
     * @param type $data
     * @return type
     */
	public function saveCommentMaster($data){
		$res = $this->getDB('w')->createCommand()->insert($this->tableCommentMaster(), $data);
		if($res==1){
			return $this->getDB('w')->getLastInsertID();
		}else{
			return false;
		}
	}

    /**
     * 插入媒体资源表
     * @param  array $data the data
     * @return int       >0 sussess 0 false
     */
    public function saveCommentMedia($data) {
        $res = $this->getDB('w')->createCommand()->insert($this->tableCommentMedia(), $data);
        if($res==1){
            return $this->getDB('w')->getLastInsertID();
        }else{
            return 0;
        }
    }
    
    /**
     * 更新置顶表增长的字段
     * @param type $q_id
     * @param type $tb_index
     * @param type $cmn_id
     * @param type $column
     * @param type $val
     * @return type
     */
    public function updateCommentQualityInc($q_id , $tb_index, $cmn_id , $column , $val=1){
        $sql = "update ".$this->tableCommentQuality(). " set ".$column."=".$column."+".intval($val)." where id=".intval($q_id).";";
        $res =$this->getDB('w')->createCommand($sql)->execute();
        if($res){
            CacheUtils::delNewCommentById($tb_index.'_'.$cmn_id);
        }
        return $res;
            
    }
    public function updateCommentQualityIncByCrc32($crc32_id, $cmn_id, $column , $val=1){
        $where =  "crc32_id=".$crc32_id." and cmn_id=".intval($cmn_id).";";
        $sql = "update ".$this->tableCommentQuality(). " set ".$column."=".$column."+".intval($val)." where " .$where;
        $res =$this->getDB('w')->createCommand($sql)->execute();
        return $res;
            
    }
    
	
	/**
	 * 修改记录
	 * @param unknown $tb_index
	 * @param unknown $cmn_id
	 * @param unknown $columns
	 */
	public function updateComment($tb_index , $cmn_id , $columns){
        $tb_name = $this->tableName_prefix().$tb_index;
        $res = $this->getDB('w')->createCommand()->update($tb_name, $columns, 'cmn_id=:cmn_id',array(':cmn_id'=>$cmn_id));
        if($res){
            CacheUtils::delNewCommentById($tb_index.'_'.$cmn_id);
        }        
        return $res;
    }
    
    	
	/**
	 * 修改全量表记录
	 * @param unknown $tb_index
	 * @param unknown $cmn_id
	 * @param unknown $columns
	 */
	public function updateCommentMaster($crc32_id, $cmn_id, $columns){
        $tb_name = $this->tableCommentMaster();
        $res = $this->getDB('w')->createCommand()->update($tb_name, $columns, 'crc32_id=:crc32_id and cmn_id=:cmn_id', array(':crc32_id'=>$crc32_id,':cmn_id'=>$cmn_id));
        return $res;
    }  
    
    /**
	 * 修改置顶表记录
     * @param type $q_id
     * @param type $tb_index
     * @param type $cmn_id
     * @param type $columns
     * @return type
     */
	public function updateCommentQuality($q_id, $tb_index, $cmn_id, $columns){
        $tb_name = $this->tableCommentQuality();
        $res = $this->getDB('w')->createCommand()->update($tb_name, $columns, 'id=:id', array(':id'=>$q_id));
        if($res){
            CacheUtils::delNewCommentById($tb_index.'_'.$cmn_id);
        }  
        return $res;
    }
    public function updateCommentQualityByCrc32($crc32_id, $cmn_id, $columns){
        $tb_name = $this->tableCommentQuality();
        $res = $this->getDB('w')->createCommand()->update($tb_name, $columns, 'crc32_id=:crc32_id and cmn_id=:cmn_id', array(':crc32_id'=>$crc32_id,':cmn_id'=>$cmn_id));
        return $res;
    } 
	
	/**
	 * 修改记录根据关联ID
	 * @param unknown $columns
	 * @param string $conditions
	 * @param unknown $params
	 */
	public function updateCommentByReplyId($reply_id, $columns){
		$res = $this->getDB('w')->createCommand()->update($this->tableName(),$columns,"reply_id=:reply_id",array(':reply_id'=>$reply_id));
		return $res;
	}
	
	
	/**
     * 根据评论ID获取评论详情
     * @param type $ids
     * @param type $tb_index
     * @return type
     */
	public function getCommentByIds($ids=array(),$tb_index, $cached=true){ 
        $ids = !is_array($ids) ? (array) $ids : $ids;
        $ids = array_unique($ids);
        if (empty($ids)) {
            return array();
        }
        $mc_pre_key = MEM_PRE_KEY . "cmn_" .$tb_index.'_';
        $return = array();
        //从缓存获取数据
        $mult_key = array();
        foreach ($ids as $val) {
            $mult_key[] = $mc_pre_key. intval($val);
        }

        $result = Yii::app()->cache->mget($mult_key);

        $leave_key = array();
        $quality_ids = array();
        foreach ($result as $key => $val) {
            $v_key = str_replace($mc_pre_key, '', $key);
            if ($cached && !empty($val) && $val !== false) {
                $return["$v_key"] = $val;
            } else {
                $leave_key[] = intval($v_key);
            }
        }
        //缓存没取到去数据库取
        if (sizeof($leave_key) > 0) {
            $sql = "SELECT cmn_id,crc32_id,cmn_type,relation_id,u_type,uid,content,match_search,head_ids,praise_num,reply_num,floor_num, 
                        reply_id,root_reply_id,is_display,is_anonymous,is_good,source,child_relation_id,c_time,u_time,discussion_type,discussion_id,up_down "
                 . " FROM " . $this->tableName_prefix().$tb_index
                 . " WHERE cmn_id IN(".implode(',', $leave_key).")";               
            $cmd = Yii::app()->licaishi_comment_r->createCommand($sql);
            $comments = $cmd->queryAll();
            $cmn_arr = array();
            if (is_array($comments) && sizeof($comments) > 0) {
                foreach ($comments as $vals) {
                    $vals['is_top'] = 0;
                    $cmn_arr[] = intval($vals['cmn_id']);                                                                  
                    $return[$vals['cmn_id']] = $vals;                    
                    Yii::app()->cache->set($mc_pre_key. $vals['cmn_id'], $vals, 36000);
                }
            }
            $quality_ids = array_diff($leave_key, $cmn_arr);
        }
        //评论表没取到去置顶表里取
        if(sizeof($quality_ids) > 0){    
            foreach($quality_ids as $k=>$v){
                $quality_ids[$k] = $tb_index.'_'.$v;
            }
            $sql = "SELECT id, index_id,cmn_id,crc32_id,cmn_type,relation_id,u_type,uid,content,match_search,head_ids,praise_num,reply_num,floor_num,"
                    . "reply_id,root_reply_id,is_display,is_anonymous,is_good,source,child_relation_id,c_time,u_time,discussion_type,discussion_id,up_down  "
                    . " FROM ".$this->tableCommentQuality()." WHERE index_id IN ('".  implode("','", $quality_ids)."')";
            $cmd = Yii::app()->licaishi_comment_r->createCommand($sql);
            $quality_list = $cmd->queryAll();
            if (is_array($quality_list) && sizeof($quality_list) > 0) {
                foreach ($quality_list as $vals) { 
                    $vals['is_top'] = 1;  //置顶标记                    
                    $return[$vals['cmn_id']] = $vals;
                    Yii::app()->cache->set($mc_pre_key. $vals['cmn_id'], $vals, 36000);
                }
            }
        }
        return $return;				
	}
	
    /**
     *  说说的回复列表，上下拉分页
     * @param type $tb_index
     * @param type $replyid
     * @param type $num
     * @param type $maxid
     * @param type $page
     * @return type
     */
    public function getCommentReplyList($tb_index,$replyid,$num = 1,$maxid = 0,$action = 1){
        $db_r = Yii::app()->licaishi_comment_r;        
        $num = ($num <= 100) ? $num : 100;                
        if(!empty($maxid)){
            $cdn .= ($action == 1) ? ' AND cmn_id<'.$maxid : ' AND cmn_id>'.$maxid;
        }
        $limit = ' LIMIT '.$num;   
        $order = ' ORDER BY cmn_id DESC ';                
        $sql = 'SELECT cmn_id FROM '.$this->tableName_prefix().$tb_index.' WHERE reply_id=:'
                . ' '.$cdn.$order.$limit;        
        $cmd = $db_r->createCommand($sql);
        $cmd->bindParam(':replyid',$replyid,PDO::PARAM_INT);        
        $list = $cmd->queryAll();        
        return $list;
    }
    /**
     * 获取说说的回复列表，常规分页
     * @param type $tb_index
     * @param type $replyid
     * @param type $page
     * @param type $num
     * @return type
     */
    public function getCommentByReplyId($tb_index,$replyid,$page = 0,$num = 10){
        $db_r = Yii::app()->licaishi_comment_r;        
        $num = ($num <= 100) ? $num : 100;
        $offset = CommonUtils::fomatPageParam($page, $num);
        $limit = ' LIMIT '.$offset.','.$num;   
        $order = ' ORDER BY u_time DESC,floor_num DESC ';                
        $sql = 'SELECT cmn_id FROM '.$this->tableName_prefix().$tb_index.' WHERE reply_id=:replyid '.$order.$limit; 
        $sqlcount = 'SELECT COUNT(cmn_id) AS total FROM '.$this->tableName_prefix().$tb_index.' WHERE reply_id=:replyid '; 
        
        //计算总页数
		$cmd_count = $db_r->createCommand($sqlcount);
		$cmd_count->bindParam(':replyid',$replyid,PDO::PARAM_INT);
		$total = $cmd_count->queryScalar();
        
        $data = null;
		if ($offset < $total) {
			$cmd = $db_r->createCommand($sql);
			$cmd->bindParam(':replyid',$replyid,PDO::PARAM_INT);
			$data = $cmd->queryAll();
		}
		$result = CommonUtils::getPage($data, $page, $num, $total);     
        return $result;
    }

    /**
     * 根据 reply_id 获取二级说信息 
     * @param type $tb_index
     * @param type $reply_id
     * @return type
     */
    public function getAllCommentByReplyId($tb_index, $reply_id) {       
                        
        $sql ="SELECT cmn_id,crc32_id,cmn_type,relation_id,u_type,uid,content,head_ids,praise_num,reply_num,floor_num, 
                        reply_id,is_display,is_anonymous,is_good,source,child_relation_id,c_time,u_time "
                 . " FROM " . $this->tableName_prefix().$tb_index
                 . " WHERE reply_id=:reply_id "; 
        $cmn = $this->getDB('r')->createCommand($sql);
        $cmn->bindParam(':reply_id', $reply_id, PDO::PARAM_INT); 
        $result = $cmn->queryAll();
        return $result;
    }

    /**
     * 根据 root_reply_id 获取二级说信息 
     * @param type $tb_index
     * @param type $root_reply_id
     * @return type
     */
    public function getAllCommentByRootReplyId($tb_index, $root_reply_id) {       
                        
        $sql ="SELECT cmn_id,crc32_id,cmn_type,relation_id,u_type,uid,content,head_ids,praise_num,reply_num,floor_num, 
                        reply_id,root_reply_id,is_display,is_anonymous,is_good,source,child_relation_id,c_time,u_time "
                 . " FROM " . $this->tableName_prefix().$tb_index
                 . " WHERE root_reply_id=:root_reply_id "; 
        $cmn = $this->getDB('r')->createCommand($sql);
        $cmn->bindParam(':root_reply_id', $root_reply_id, PDO::PARAM_INT); 
        $result = $cmn->queryAll();
        return $result;
    }
    
    /**
     * 个股行情页说说列表
     * @param type $tb_index
     * @param type $crc32id
     * @param type $type
     * @param type $num
     * @param type $maxid 
     * @param type $action
     * @return type $array
     */
    public function getCommentList($tb_index,$crc32id,$type = 0,$total = 1,$maxid = 0,$action = 1){
        $db_r = Yii::app()->licaishi_comment_r;
        $num = ($total <= 100) ? $total : 100;
        $cdn = ' crc32_id='.$crc32id;        
        if(!empty($maxid)){
            $cdn .= ($action == 2) ? ' AND cmn_id<'.$maxid : ' AND cmn_id>'.$maxid;
        }
        $order = ' ORDER BY cmn_id DESC ';
        $limit = ' LIMIT '.$num;    
        if(!empty($type)){
            $cdn .= ' AND u_type= '.$type;
        }
        $sql = 'SELECT cmn_id,head_ids FROM '.$this->tableName_prefix().$tb_index.' WHERE '.$cdn.$order.$limit;            
        $list = $db_r->createCommand($sql)->queryAll();
        return $list;
    }

    /**
     * 获取吧啦吧啦聚合说说列表
     */
    public function getBalaCommentList($fields_arr, $where_arr, $order_arr = array(), $page = 1, $page_num = 20, $table_type = 1, $where_string = '') {
        if ($table_type == 1) {
            $tableName = $this->tableCommentMaster();
        } else {
            $tableName = $this->tableCommentQuality();
        }

        if (empty($fields_arr)) {
            $fields_arr = array('cmn_type','relation_id','cmn_id');
        }
        $fields = implode(',', $fields_arr);
        $where = ' where 1 ';
        if (!empty($where_arr)) {
            foreach ($where_arr as $f => $v) {
                $tmp_f = explode(' ', $f);
                if (isset($tmp_f[1]) && $tmp_f[1] == 'or') {
                    $where .= " and ".implode(" or ", $v)." ";
                } elseif (isset($tmp_f[1])) {
                    $where .= " and {$tmp_f[0]} {$tmp_f[1]} " . ( is_numeric($v) ? $v : "'{$v}'");
				} else {
                    $where .= " and {$f} =  " . ( is_numeric($v) ? $v : "'{$v}'");
				}
            }
        }

		if ($where_string != '') {
			$where .= $where_string;
		}

		$sqlcount = "select count(cmn_id) as total ".
                    " from {$tableName} ".
                    " {$where};";
		$total = Yii::app()->licaishi_comment_r->createCommand($sqlcount)->queryScalar();
        $page_num = $page_num > 0 ? $page_num : $total;
        $offset = CommonUtils::fomatPageParam($page, $page_num);
        if (!empty($order_arr)) {
            $order = ' order by '.implode(',', $order_arr);
        } else {
            $order = '';
        }

        $list = null;
        if ($offset < $total) {
            $sql = "select {$fields}".
                    " from {$tableName} ".
                    " {$where} ".
                    " {$order} ".
                    " limit {$offset},{$page_num}";
			$list = Yii::app()->licaishi_comment_r->createCommand($sql)->queryAll();
        }

        return CommonUtils::getPage($list,$page,$page_num,$total);
    }


    /**
     * 获取圈子的最后一条说说
     * @param   array   $circle_ids圈子id数组
     *
     */
    public function getLastCommentByRedis($circle_ids){
        $redis_keys = array();
        $key_map = array();
        $result = array();

        if(count($circle_ids)>0){
            foreach($circle_ids as $id){
                $redis_keys[] = MEM_PRE_KEY."new_circle_last_comment_".$id;
                $key_map[] = $id;
            }

            $data = Yii::app()->redis_r->mget($redis_keys);
            foreach($data as $k=>$v){
                if($v){
                    $result[$key_map[$k]] = json_decode($v,true);
                }else{
                    $result[$key_map[$k]] = null;
                }
            }
        }
        return $result;
    }

    /**
     * 获取最后一条说说
     * @param $fields_array
     * @param $where_arr
     * @param array $order_arr
     * @param int $table_type
     * @return mixed
     */
    public function getLastComment($fields_array,$where_arr,$order_arr=array(),$table_type=1,$limit=1){
        if ($table_type == 1) {
            $tableName = $this->tableCommentMaster();
        } else {
            $tableName = $this->tableCommentQuality();
        }
        if (empty($fields_array)) {
            $fields_array = array('cmn_type','relation_id','cmn_id');
        }
        $fields = implode(',', $fields_array);
        $where = ' where 1 ';
        if (!empty($where_arr)) {
            foreach ($where_arr as $f => $v) {
                $tmp_f = explode(' ', $f);
                if (isset($tmp_f[1]) && $tmp_f[1] == 'or') {
                    $where .= " and ".implode(" or ", $v)." ";
                } elseif (isset($tmp_f[1])) {
                    $where .= " and {$tmp_f[0]} {$tmp_f[1]} '{$v}' ";
                } else {
                    $where .= " and {$f} = '{$v}' ";
                }
            }
        }

        if (!empty($order_arr)) {
            $order = ' order by '.implode(',', $order_arr);
        } else {
            $order = '';
        }

        $sql = "select {$fields}".
            " from {$tableName} ".
            " {$where} ".
            " {$order} ".
            " limit ".$limit;
        $data = Yii::app()->licaishi_comment_r->createCommand($sql)->queryAll();

        return CommonUtils::getPage($data,1,$limit,$limit);
    }

    /**
     * 获取的说说的总数
     * @param $where_arr
     * @param int $table_type
     * @return mixed
     */
    public function getCommentTotalNumber($where_arr,$table_type=1){
        if ($table_type == 1) {
            $tableName = $this->tableCommentMaster();
        } else {
            $tableName = $this->tableCommentQuality();
        }

        $where = ' where 1 ';
        if (!empty($where_arr)) {
            foreach ($where_arr as $f => $v) {
                $tmp_f = explode(' ', $f);
                if (isset($tmp_f[1]) && $tmp_f[1] == 'or') {
                    $where .= " and ".implode(" or ", $v)." ";
                } elseif (isset($tmp_f[1])) {
                    $where .= " and {$tmp_f[0]} {$tmp_f[1]} '{$v}' ";
                } else {
                    $where .= " and {$f} = '{$v}' ";
                }
            }
        }

        $sqlcount = "select count(cmn_id) as total ".
            " from {$tableName} ".
            " {$where};";
        $total = Yii::app()->licaishi_comment_r->createCommand($sqlcount)->queryScalar();

        return $total;
    }


    /**
     * 获取说说数量
     * @param type $crc32id
     * @return type
     */
    public function getCommentTotal($crc32id){
        $db_r = Yii::app()->licaishi_comment_r;
        $sql = 'SELECT comment_num,planner_comment_num FROM '.$this->tableIndexNum().' WHERE crc32_id=:crc32id LIMIT 1';
        $data = $db_r->createCommand($sql)->bindParam(':crc32id',$crc32id,  PDO::PARAM_INT)->queryAll();
        return $data;
    }
	
    /**
     * 获取置顶说说
     * @param type $crc32id
     * @param type $replyid
     * @return type
     */
    public function getQualityList($crc32id = 0,$replyid = 0,$u_type = 0){
        $db_r = Yii::app()->licaishi_comment_r;
        $cdn = ' crc32_id= '.$crc32id;
        if(!empty($replyid)){
            $cdn .= ' AND reply_id='.$replyid;
        }
        if(!empty($u_type)){
            $cdn .= ' AND u_type='.$u_type;
        }
        $order = ' ORDER BY id DESC ';
        $sql = 'SELECT cmn_id FROM '.$this->tableCommentQuality().' WHERE '.$cdn.$order;
        $qualitylist = $db_r->createCommand($sql)->queryColumn();
        return $qualitylist;
    }
        
    /**
     * 获取用户是否点赞
     * 
     * @param type $uid
     * @param type $cmn_id
     * @param type $tb_index
     * @return type
     */
    public function getUserPraise($tb_index,$cmn_id,$uid){
        $redis_key = MEM_PRE_KEY . 'cmn_praise_' . $tb_index;
        $praise = Yii::app()->redis_r->hget($redis_key,$cmn_id.'_'.$uid);
        $is_praise = !empty($praise) ? 1 : 0;
        return $is_praise;
    }            
    /**
     * 批量获取点赞数据
     * @param type $tb_index
     * @param type $ids
     * @param type $uid
     * @return type
     */
    public function getMutiUserPraise($tb_index,$ids,$uid){
        if(empty($ids) || empty($uid)){
			return array();
		}		
        $result = array();
		$ids = (array)$ids;
        $key = MEM_PRE_KEY.'cmn_praise_'.$tb_index;
        $fields = array();
        foreach ($ids as $cmnid){
            $fields[] = intval($cmnid).'_'.intval($uid);            
        }
        $praiselist = Yii::app()->redis_r->hmget($key,$fields);    
        if(!empty($praiselist)){
            foreach ($praiselist as $k=>$v){
                $cmnid = explode('_', $k)[0];
                $result[$cmnid] = !empty($v) ? 1 : 0;
            }
        }
        return $result;
    }

        /**
     * 获取说说列表 常规分页
     * @param type $tb_index
     * @param type $condition
     * @param type $page
     * @param type $num
     * @param type $is_order
     * @param type $is_count
     * @return type
     */
    public function getCommentPage($tb_index,$condition,$page,$num,$is_order = TRUE,$is_count = FALSE){
        $db = Yii::app()->licaishi_comment_r;
        $offset = CommonUtils::fomatPageParam($page, $num);
        $sql = 'SELECT cmn_id FROM '.$this->tableName_prefix().$tb_index.' WHERE '.  implode(' AND ', $condition);
        $sqlcount = 'SELECT count(cmn_id) AS total FROM '.$this->tableName_prefix().$tb_index.' WHERE '.  implode(' AND ', $condition);
        //$order = ' ORDER BY u_time DESC,floor_num DESC ';
        $order = ' ORDER BY cmn_id DESC';
        $limit = ' LIMIT '.$offset.','.$num;
        if($is_order){
            $sql .= $order;
        }
        $sql .= $limit;                
        $total = $db->createCommand($sqlcount)->queryScalar();
        $list = NULL;
        if(!$is_count && $offset < $total){            
            $list = $db->createCommand($sql)->queryColumn();
        }                            
        $result = CommonUtils::getPage($list,$page,$num,$total);
        return $result;
    }
    /**
     * 说说列表 理财师说
     * @param type $tb_index
     * @param type $condition
     * @param type $page
     * @param type $num
     * @return type
     */
    public function getMyCommentPage($tb_index,$condition,$page,$num){        
        $db = Yii::app()->licaishi_comment_r;        
        $sql = 'SELECT cmn_id,reply_id FROM '.$this->tableName_prefix().$tb_index.' WHERE '.  implode(' AND ', $condition);                             
        $data = $db->createCommand($sql)->queryAll();                
        $sort_page = CommonUtils::arrayPage(array(), $num,$page);
        //获取详情
        if (!empty($data)) {
            $ids = array();
            foreach ($data as $row) {
                if (!empty($row['reply_id'])) {
                    $ids[] = $row['reply_id'];
                } else {
                    $ids[] = $row['cmn_id'];
                }
            }
            $ids = array_unique($ids);
            rsort($ids);
            $sort_page = CommonUtils::arrayPage($ids, $num, $page);            
        }
        return $sort_page;
    }


    /**
     * 支持旧版说按某个字段来更新数量
     * @param type $tb_index
     * @param type $cmn_id
     * @param type $column
     * @return type
     */
    public function updatetbIndexNumByColumn($crc32_id, $columns = array()){
        $set_val = "";
        foreach($columns as $col=>$val){
            $set_val .= $col .'='. $col ."+" . intval($val).",";
        }
        $set_val = substr($set_val, 0 , -1);
        $sql = "update ".$this->tableIndexNum(). " set ". $set_val ." where crc32_id=".$crc32_id.";";

        $res =$this->getDB('w')->createCommand($sql)->execute();
        return $res;
            
    }
    
    /**
     * 批量插入到 垃圾表 或 置顶表
     * @param type $sql
     * @param type $tb_type 1 置顶表 ， 2 垃圾表
     * @return boolean
     */
    public function insertBatchCommentToSpecial($sql , $tb_type=1){
        $res = $this->getDB('w')->createCommand($sql)->execute();
        if($res) {
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 获取说说最后两条回复
     * @param type $tb_index
     * @param type $cmn_id
     * @return type
     */
    public function getCommentLastReplys($tb_index,$cmn_id,$start = 0,$end = 1){
        $cache_pre = MEM_PRE_KEY.'cmn_lasted_'.$tb_index.'_';
        //从redis中获取最新的两个二级评论ID
        $redis_key = $cache_pre.$cmn_id;        
        $sub_cmn_lasted_ids = Yii::app()->redis_r->lRange($redis_key,$start,$end);
        return $sub_cmn_lasted_ids;
    }
    
    /**
     * 获取一级说的最后两条回复id 
     * @param type $tb_index
     * @param type $reply_id
     * @param type $num
     * @return type
     */
    public function getCommentLastReplyIds($tb_index , $reply_id , $num){
        $db_r = $this->getDB();
		$sql ='select cmn_id from '.$this->tableName_prefix().$tb_index
		     .' where reply_id=:reply_id order by u_time desc,floor_num desc limit 0, :limit;';
		$cmd = $db_r->createCommand($sql);
		$cmd->bindParam(':reply_id',$reply_id,PDO::PARAM_INT);
		$cmd->bindParam(':limit', $num, PDO::PARAM_INT);
		return $cmd->queryAll();
    }

    /**
     * 获取一级说的最后两条回复id 
     * @param type $tb_index
     * @param type $root_reply_id
     * @param type $num
     * @return type
     */
    public function getCommentLastRootReplyIds($tb_index , $root_reply_id , $num){
        $db_r = $this->getDB();
        $sql ='select cmn_id from '.$this->tableName_prefix().$tb_index
             .' where root_reply_id=:root_reply_id order by u_time desc,floor_num desc limit 0, :limit;';
        $cmd = $db_r->createCommand($sql);
        $cmd->bindParam(':root_reply_id',$root_reply_id,PDO::PARAM_INT);
        $cmd->bindParam(':limit', $num, PDO::PARAM_INT);
        return $cmd->queryAll();
    }
    
    /**
     * 
     * @param type $tb_index
     * @param type $cmn_ids
     * @return array
     */
    public function getMutiCommentLastReplys($tb_index,$cmn_ids){
        $last_reply_list = array();
        $cmn_ids = !is_array($cmn_ids) ? (array) $cmn_ids : $cmn_ids;
        if(empty($cmn_ids)){
            return $last_reply_list;
        }
        $cache_field = MEM_PRE_KEY.'cmn_new_lasted_'.$tb_index;
        $last_reply_list = Yii::app()->redis_r->hmget($cache_field,$cmn_ids);
        return $last_reply_list;
    }
    /**
     * 获取最热说说
     * @param type $type
     * @param type $page
     * @param type $num
     * @return type
     */
    public function getHotCommentList($page=1,$num=10){
        //先获取理财小妹推荐说说
        $result = PageCfg::model()->getHotComment($page,$num);
        $ids = array();
        foreach($result['data'] as $row){
            if(!empty($row) && sizeof($row['relation_id']) >0 && sizeof($row['tb_index'] > 0)){
                $ids[] = $row['tb_index'].'_'.$row['relation_id'];
            }
        }        
        $data = array();
        $hotcomment_map = $this->getCommentByIndexIds($ids);
        foreach($ids as $row){
            if(isset($hotcomment_map[$row])){
                $data[] = $hotcomment_map[$row];
            }
        }
        $result['data'] = $data;        
        return $result;
    }
    /**
     * 根据表索引和评论id获取详情
     * @param type $ids
     * @return int
     */
    public function getCommentByIndexIds($ids){
        $ids = (array)$ids;        
        $mc_pre_key = MEM_PRE_KEY . "cmn_";
        $return = array();
        //从缓存获取数据
        $mult_key = array();
        foreach ($ids as $val) {
            $mult_key[] = $mc_pre_key.$val;
        }        
        $result = Yii::app()->cache->mget($mult_key);
        $leave_key = array();
        $quality_ids = array();
        foreach ($result as $key => $val) {
            $v_key = str_replace($mc_pre_key, '', $key);            
            if ($val !== false) {
                $return["$v_key"] = $val;
            } else {
                $leave_key[] = $v_key;
            }
        }
        $columns = 'cmn_id,crc32_id,cmn_type,relation_id,u_type,uid,content,match_search,head_ids,praise_num,reply_num,floor_num, 
                        reply_id,is_display,is_anonymous,is_good,source,child_relation_id,c_time,u_time,discussion_type,discussion_id ';
        //缓存没取到去数据库取
        if (sizeof($leave_key) > 0) {
            $sql_map = array();            
            foreach ($leave_key as &$item){
                $foo = explode('_', $item);
                $sql_map[] = '(SELECT '.$columns.' FROM '.$this->tableName_prefix().$foo[0].' WHERE cmn_id='.$foo[1].')';
            }
            $sql = implode(' UNION ', $sql_map);              
            $cmd = Yii::app()->licaishi_comment_r->createCommand($sql);              
            $comments = $cmd->queryAll();
            $cmn_arr = array();
            if (is_array($comments) && sizeof($comments) > 0) {
                foreach ($comments as $vals) {
                    $vals['is_top'] = 0;
                    $tbindex = $vals['crc32_id'] % NewComment::COMMENT_TABLE_NUMS;
                    $cmn_arr[] = $tbindex.'_'.$vals['cmn_id'];                                                                  
                    $return[$tbindex.'_'.$vals['cmn_id']] = $vals;                    
                    Yii::app()->cache->set($mc_pre_key. $vals['cmn_id'], $vals, 36000);
                }
            }
            $quality_ids = array_diff($leave_key, $cmn_arr);
        }
        //评论表没取到去置顶表里取
        if(sizeof($quality_ids) > 0){    
            $sql = "SELECT index_id,cmn_id,crc32_id,cmn_type,relation_id,u_type,uid,content,match_search,head_ids,praise_num,reply_num,floor_num,"
                    . "reply_id,is_display,is_anonymous,is_good,source,child_relation_id,c_time,u_time,discussion_type,discussion_id  "
                    . " FROM ".$this->tableCommentQuality()." WHERE index_id IN ('".  implode("','", $quality_ids)."')";
            $cmd = Yii::app()->licaishi_comment_r->createCommand($sql);
            $quality_list = $cmd->queryAll();
            if (is_array($quality_list) && sizeof($quality_list) > 0) {
                foreach ($quality_list as $vals) { 
                    $vals['is_top'] = 1;  //置顶标记                    
                    $return[$vals['index_id']] = $vals;
                    Yii::app()->cache->set($mc_pre_key. $vals['cmn_id'], $vals, 36000);
                }
            }
        }
        return $return;	
    }
    /**
     * 获取最新说说
     * @param type $page
     * @param type $num
     * @param type $type
     * @return type
     */
    public function getLastCommentList($page=1,$num=15,$type){
        $db_r = Yii::app()->licaishi_comment_r;
        $offset = CommonUtils::fomatPageParam($page, $num);
        $sql_total = 'SELECT COUNT(cmn_id) AS TOTAL FROM '.$this->tableCommentMaster().' WHERE reply_id=0 AND is_display>0 AND is_anonymous =0'
                    .' AND cmn_type IN ('.implode(',',$type).');';
        $cmd_count = $db_r->createCommand($sql_total);
        $total  = $cmd_count->queryScalar();

        $sql_hotcomment = 'SELECT cmn_id,crc32_id FROM '.$this->tableCommentMaster()
           .' WHERE  reply_id=0 AND is_display>0 AND is_anonymous =0'
            .' AND cmn_type IN ('.implode(',',$type).')'
           .' ORDER BY c_time DESC LIMIT :offset,:limit;';        
        $cmd = $db_r->createCommand($sql_hotcomment);
        $cmd->bindParam(':offset',$offset,PDO::PARAM_INT);
        $cmd->bindParam(':limit',$num,PDO::PARAM_INT);;
        $data = $cmd->queryAll();
        $result = CommonUtils::getPage($data,$page,$num,$total);
        return $result;
    }


    /**
     * 获取balabla的数量，默认获取所有balabla
     * @param int $trade_type 1 A股， 2 金银油， 3 美股
     */
    public function getBalaCommentCount($trade_type)
    {
        // 8888 A股， 8889 金银油, 8890 美股
        $status = array(
            1 => 8888,
            2 => 8889,
            3 => 8890
        );
        $db_r = Yii::app()->licaishi_comment_r;
        $sql = "select count(*) from ".$this->tableCommentMaster()." where cmn_type=51 and relation_id=:relation_id";
        $cmd = $db_r->createCommand($sql);
        $cmd->bindParam(":relation_id", $status[$trade_type]);
        $total = $cmd->queryScalar();
        return $total;
    }

    /**
     * @description 查找该表是否被使用（是否存在）
     * @param $tb_index 表索引
     *
     * @return mixed
     */
    public function isTableUsed($tb_index)
    {
        $sql = "select count(*) from ".$this->tableIndexNum()." where tb_index=:tb_index";
        $cmd = Yii::app()->licaishi_comment_r->createCommand($sql);
        $cmd->bindParam(':tb_index', $tb_index, PDO::PARAM_INT);
        $res = $cmd->queryScalar();
        return $res;
    }
    
    
    /**
     * 根据ID获取Master总表中的评论详情
     * @param type $ids
     * @return type
     */
	public function getCommentMasterByIds($ids=array()){ 
        $ids = !is_array($ids) ? (array) $ids : $ids;
        $ids = array_unique($ids);
        if (empty($ids)) {
            return array();
        }
        $sql = "SELECT id,cmn_id,crc32_id,cmn_type,relation_id,u_type,uid,content,match_search,head_ids,praise_num,reply_num,floor_num, 
                        reply_id,root_reply_id,is_display,is_anonymous,is_good,source,child_relation_id,c_time,u_time,discussion_type,discussion_id,up_down "
                . " FROM " . $this->tableCommentMaster()
                . " WHERE id IN(" . implode(',', $ids) . ")";
        $cmd = Yii::app()->licaishi_comment_r->createCommand($sql);
        $cmn_arr = $cmd->queryAll();
        $comments = array();
        if (is_array($cmn_arr) && sizeof($cmn_arr) > 0) {
            foreach ($cmn_arr as $vals) {
                $comments[$vals['id']] = $vals;
            }
        }
        return $comments;				
	}
    
    public function getCircleCommentList($num=6,$u_type,$uid=0){
              
        $db_r = Yii::app()->licaishi_comment_r;
        $now_time = time();
        $c_time = date('Y-m-d H:i:s',$now_time-60*60*24*7);
        $where = " where c_time >='".$c_time."' and cmn_type=71 and u_type={$u_type}";
        if($uid){
            $where.= " and uid = $uid";
        }
        $sql = "select relation_id,content,c_time from {$this->tableCommentMaster()} {$where} order by c_time desc limit {$num}";
        $cmd = $db_r->createCommand($sql);
        return $cmd->queryAll();
    }
    /**
    *   获取大赛计划热门说说
    */
    public function getMatchPlanHotComment($pln_ids,$limit){
        $db_r = Yii::app()->licaishi_comment_r;        
        if(empty($pln_ids)){
            return false;
        }
        $where  = " where cmn_type=1 and relation_id in (".implode(',',$pln_ids).")";
        $sql = "select relation_id,content,c_time from {$this->tableCommentQuality()} {$where}  order by c_time desc limit {$limit}";        
        $cmd = $db_r->createCommand($sql);
        return $cmd->queryAll();
    }

    /**
     * 设置用户对某个圈子已读数量
     * @param   int $circle_id圈子id
     * @param   int $uid用户id
     */
    public function updateUserCircleReadCommentNumber($circle_id,$uid){
        if(!empty($circle_id) && !empty($uid)){
            $redis_key = MEM_PRE_KEY."circle_comment_index_".$circle_id;
            $number = Yii::app()->redis_r->get($redis_key);
            $redis_key = MEM_PRE_KEY."user_circle_comment_index_".$uid."_".$circle_id;
            Yii::app()->redis_w->set($redis_key,$number);
        }
    }

    /**
     * 获取圈子当前的评论发言数量,圈子每次发言，数量都会向上自增1
     * @param   array $circle_id圈子id数组
     */
    public function getCircleCurrentCommentNumber($circle_ids){
        $result = array();
        if(count($circle_ids)>0){
            $redis_keys = array();
            $key_map = array();
            foreach($circle_ids as $circle_id){
                $redis_keys[] = MEM_PRE_KEY."circle_comment_index_".$circle_id;
                $key_map[] = $circle_id;
            }
            $data = Yii::app()->redis_r->mget($redis_keys);
            foreach($data as $k=>$v){
                if($v){
                    $result[$key_map[$k]] = $v;
                }else{
                    $result[$key_map[$k]] = 0;
                }
            }
        }
        return $result;
    }

    /**
     * 获取用户对某个圈子的评论发言阅读数量，每次调用circleCommentList的时候都会和当前的圈子数量保持一致
     * @param   int $uid用户id
     * @param   array $circle_id圈子id数组
     */
    public function getUserCircleReadCommentNumber($uid,$circle_ids){
        $result = array();
        if(count($circle_ids)>0){
            $redis_keys = array();
            $key_map = array();
            foreach($circle_ids as $circle_id){
                $redis_keys[] = MEM_PRE_KEY."user_circle_comment_index_".$uid."_".$circle_id;
                $key_map[] = $circle_id;
            }
            $data = Yii::app()->redis_r->mget($redis_keys);
            foreach($data as $k=>$v){
                if($v){
                    $result[$key_map[$k]] = $v;
                }else{
                    $result[$key_map[$k]] = 0;
                }
            }
        }
        return $result;
    }

    /**
     * 将圈子最近的一条发言保存到redis中
     */
    public function saveLastCommentData($data){
        $cmn_data = array();
        $cmn_data['cmn_type'] = $data['cmn_type'];
        $cmn_data['relation_id'] = $data['relation_id'];
        $cmn_data['cmn_id'] = $data['cmn_id'];
        $cmn_data['u_type'] = $data['u_type'];
        $cmn_data['uid'] = $data['uid'];
        $cmn_data['content'] = $data['content'];
        $cmn_data['reply_id'] = $data['reply_id'];
        $cmn_data['root_reply_id'] = $data['root_reply_id'];
        $cmn_data['c_time'] = $data['c_time'];
        $cmn_data['discussion_type'] = $data['discussion_type'];
        $cmn_data['discussion_id'] = $data['discussion_id'];
        $redis_key = MEM_PRE_KEY."new_circle_last_comment_".$data['relation_id'];
        Yii::app()->redis_w->set($redis_key,json_encode(array($cmn_data)));

        $redis_key = MEM_PRE_KEY."circle_comment_index_".$data['relation_id'];
        ///当前评论所在位置加1
        Yii::app()->redis_w->incrBy($redis_key);
    }

	/**
	 *
	 * @param type $cmn_type
	 * @param type $circleId
	 * @param type $cmn_id
	 * @param type $field
	 * @return type
	 */
	public function getBalaCommentInfo($cmn_type, $circleId, $cmn_id, $field = '*') {
		$db_r = Yii::app()->licaishi_comment_w;
		$tbIndexArr = NewCommentService::getCRC32TbIndex($cmn_type, $circleId);
		$tb_index = $tbIndexArr['tb_index'];
		$sql = 'select ' . $field . ' from ' . NEW_COMMENT_TABLE_PREFIX . $tb_index . ' where cmn_id=' . intval($cmn_id) . ' limit 1';
		return $db_r->createCommand($sql)->queryRow();
	}

    /**
     * 获取圈子聊天信息
     */
    public function getBalaCommentInfoByDiscussion_id($cmn_type, $circleId, $uid, $discussion_id, $discussion_type, $field = '*') {
        try {
            $db_r = Yii::app()->licaishi_comment_w;
            $tbIndexArr = NewCommentService::getCRC32TbIndex($cmn_type, $circleId);
            $tb_index = $tbIndexArr['tb_index'];
            $sql = 'select ' . $field . ' from ' . NEW_COMMENT_TABLE_PREFIX . $tb_index . ' where uid=' . intval($uid) . ' and u_type=2 and discussion_type='.$discussion_type.' and discussion_id='.$discussion_id.' limit 1';
            return $db_r->createCommand($sql)->queryRow();
            
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

}
