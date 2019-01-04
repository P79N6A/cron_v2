<?php

/*
 * 1、观点包说说逻辑的变更
 * 2、整体的cmn_id变更，reply_id变更
 */

/**
 * Description of CommentUpgrade
 *
 * @author huanghailin
 */
class CommentUpgrade {
    const TABLE_NUMS = 256;

    private $cmnid_map = array();
    private $newid_map = array();

    //旧字段
    private $_fields = array('cmn_type', 'u_type', 'uid', 'content', 'floor_num', 'is_display', 'is_anonymous', 'source', 'c_time', 'u_time','discussion_type','discussion_id');

    /**
     * 迁移数据主方法
     */
    public function dbUpgrade() {
        $maxsql = "SELECT MAX(cmn_id) as cmn_id FROM lcs_comment";
        $maxinfo = Yii::app()->lcs_r->createCommand($maxsql)->queryAll();
        $maxid = (int) $maxinfo[0]['cmn_id'];
        $minid = 0;
        $flag = TRUE;
        $sql = "SELECT * FROM lcs_comment WHERE cmn_id>:minid AND cmn_id<=:maxid AND status=0 ORDER BY cmn_id ASC LIMIT 2000";
        while ($flag) {
            echo $minid;
            $list = Yii::app()->lcs_r->createCommand($sql)->bindParam(':maxid', $maxid, PDO::PARAM_INT)->bindParam(':minid',$minid,  PDO::PARAM_INT)->queryAll();
            if (!empty($list)) {
                foreach ($list as $item) {
                    $d = array();
                    //新旧数据转换
                    $cmnid = $minid = $item['cmn_id'];                                        
                    $type = $item['cmn_type'];
                    $d['praise_num'] = $item['parise_num'];
                    $d['reply_num'] = $item['replay_num'];
                    $d['reply_id'] = isset($this->cmnid_map[$item['replay_id']]) ? $this->cmnid_map[$item['replay_id']] : 0;
                    if ($type == 2) {
                        $d['relation_id'] = $item['parent_relation_id'];
                        $d['child_relation_id'] = $item['relation_id'];
                    } else {
                        $d['relation_id'] = $item['relation_id'];
                        $d['child_relation_id'] = 0;
                    }
                    $d['root_reply_id'] = $d['reply_id'];
                    foreach ($this->_fields as $field) {
                        $d[$field] = $item[$field];
                    }
                    $crc32_id = CommonUtils::getCRC32($type . '_' . $d['relation_id']);
                    $d['crc32_id'] = $crc32_id;
                    $tb_index = $crc32_id % self::TABLE_NUMS;
                    //插入数据                    
                    $newid = $this->insert($tb_index, $d);
                    $this->cmnid_map[$cmnid] = $newid;
                    $this->newid_map[$cmnid.'_'.$newid] = $tb_index;
                    //点赞数据
                    $this->praiseUpgrade($cmnid, $newid, $tb_index);                                       
                    //楼层数
                    $this->floorUpgrade($type, $item['relation_id'], $cmnid, $newid);
                }
            } else {
                $flag = FALSE;
            }
        }
        //最后回复id 
        $this->lastReplyUpgrade();
    }

    /**
     * 升级楼层缓存
     * @param type $type
     * @param type $relationid
     * @param type $newrelationid
     * @param type $cmnid
     * @param type $newid
     */
    private function floorUpgrade($type, $relationid, $cmnid, $newid) {
        $key = MEM_PRE_KEY . 'cmn_floor_' . $type . '_' . $relationid;
        $newkey = MEM_PRE_KEY . 'cmn_new_floor_' . $type . '_' . $relationid;
        $floor = Yii::app()->redis_r->hget($key, $cmnid);                
        $r_floor = Yii::app()->redis_r->hget($key, 0);                
        Yii::app()->redis_w->hset($newkey, $newid, $floor);
        Yii::app()->redis_w->hset($newkey, 0, $r_floor);
    }

    /**
     * 升级最后回复
     * @param type $cmnid
     * @param type $newid
     * @param type $tb_index
     */
    private function lastReplyUpgrade() {        
        foreach ($this->cmnid_map as $cmnid=>$newid){
            $lastkey = MEM_PRE_KEY . 'cmn_lasted_' . $cmnid;
            $lastlist = Yii::app()->redis_r->getRange($lastkey, 0, -1);
            if (!empty($lastlist)) {
                $key = MEM_PRE_KEY . 'cmn_new_lasted_' . $this->newid_map[$cmnid.'_'.$newid];
                $lastid_arr = array();
                foreach ($lastlist as $lastid) {
                    if(isset($this->cmnid_map[$lastid])){
                        $lastid_arr[] = $this->cmnid_map[$lastid];
                    }                    
                }
                Yii::app()->redis_w->hset($key,$newid,  implode(',', $lastid_arr));
            }
        }
    }
        

    /**
     * 处理点赞数据
     * @param type $cmnid
     * @param type $newcmnid
     * @param type $tb_index
     */
    private function praiseUpgrade($cmnid, $newcmnid, $tb_index) {
        //点赞数据
        $praisekey = MEM_PRE_KEY . 'cmn_parise_' . $cmnid;
        $praiselist = Yii::app()->redis_r->hGetAll($praisekey);

        if (!empty($praiselist)) {
            $newpraisekey = MEM_PRE_KEY . 'cmn_praise_' . $tb_index;
            foreach ($praiselist as $key => $value) {
                $newpraisefield = intval($newcmnid) . '_' . intval($key);
                Yii::app()->redis_r->hset($newpraisekey, $newpraisefield, 1);
            }
        }
    }

    /**
     * 大家说数量
     */
    public function commentNumUpgrade() {
        $index_num = array();
        $sql = "SELECT cmn_type,relation_id,COUNT(*) AS total FROM lcs_comment WHERE cmn_type!=2 AND replay_id=0 AND is_display=1 AND relation_id>0 AND status=0 GROUP BY cmn_type,relation_id";
//        $planner_sql = "SELECT cmn_type,relation_id,SUM(total) AS total FROM ((SELECT cmn_type,relation_id,COUNT(DISTINCT replay_id) AS total FROM lcs_comment WHERE  u_type=2 AND relation_id>0 AND replay_id >0 AND status=0 GROUP BY cmn_type,relation_id) 
//                            UNION
//                        (SELECT cmn_type,relation_id,COUNT(*) AS total FROM lcs_comment WHERE u_type=2 AND relation_id>0 AND replay_id =0 AND status=0 GROUP BY cmn_type,relation_id)) AS a GROUP BY cmn_type,relation_id";
        $planner_sql = "SELECT cmn_type,relation_id,COUNT(*) AS total FROM lcs_comment WHERE cmn_type!=2 AND u_type=2 AND is_display=1 AND relation_id>0 AND status=0 GROUP BY cmn_type,relation_id";
        $viewsql = "SELECT cmn_type,parent_relation_id AS relation_id,COUNT(*) AS total FROM lcs_comment WHERE cmn_type=2 AND replay_id=0 AND is_display=1 AND status=0 GROUP BY cmn_type,parent_relation_id";
//        $planner_viewsql = "SELECT cmn_type,relation_id,COUNT(DISTINCT cmn_id) as total FROM  
//                            ((SELECT cmn_type,parent_relation_id AS relation_id,replay_id AS cmn_id FROM lcs_comment WHERE cmn_type=2 AND is_display=1 AND u_type=2 AND replay_id>0 AND status=0)
//                            UNION 
//                            (SELECT cmn_type,parent_relation_id AS relation_id,cmn_id FROM lcs_comment WHERE  cmn_type=2 AND is_display=1 AND u_type=2 AND replay_id=0 AND status=0)) AS a
//                            GROUP BY cmn_type,relation_id";
        $planner_viewsql = "SELECT cmn_type,parent_relation_id AS relation_id,COUNT(*) AS total FROM lcs_comment WHERE cmn_type=2 AND u_type=2 AND is_display=1 AND status=0 GROUP BY cmn_type,parent_relation_id";
        $foolist_one = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $foolist_two = Yii::app()->lcs_r->createCommand($planner_sql)->queryAll();
        $foolist_three = Yii::app()->lcs_r->createCommand($viewsql)->queryAll();
        $foolist_four = Yii::app()->lcs_r->createCommand($planner_viewsql)->queryAll();
        foreach ($foolist_one as $foo) {
            $crc32_id = CommonUtils::getCRC32($foo['cmn_type'] . '_' . $foo['relation_id']);
            $tb_index = $crc32_id % self::TABLE_NUMS;
            $index_num[$crc32_id] = array(
                'cmn_type' => $foo['cmn_type'],
                'relation_id' => $foo['relation_id'],
                'tb_index' => $tb_index,
                'crc32_id' => $crc32_id,
                'comment_num' => $foo['total']
            );
            if($foo['cmn_type'] == 1){
                $this->updatePlanCommentNum($foo['relation_id'], $foo['total']);
            }elseif($foo['cmn_type'] == 3){
                $this->updateTopicCommentNum($foo['relation_id'], $foo['total']);
            }
        }
        foreach ($foolist_two as $foo) {
            $crc32_id = CommonUtils::getCRC32($foo['cmn_type'] . '_' . $foo['relation_id']);
            $tb_index = $crc32_id % self::TABLE_NUMS;
            if (isset($index_num[$crc32_id])) {
                $index_num[$crc32_id]['planner_comment_num'] = $foo['total'];
            } else {
                $index_num[$crc32_id] = array(
                    'cmn_type' => $foo['cmn_type'],
                    'relation_id' => $foo['relation_id'],
                    'tb_index' => $tb_index,
                    'crc32_id' => $crc32_id,
                    'planner_comment_num' => $foo['total']
                );
            }
        }
        foreach ($foolist_three as $foo) {
            $crc32_id = CommonUtils::getCRC32($foo['cmn_type'] . '_' . $foo['relation_id']);
            $tb_index = $crc32_id % self::TABLE_NUMS;
            $index_num[$crc32_id] = array(
                'cmn_type' => $foo['cmn_type'],
                'relation_id' => $foo['relation_id'],
                'tb_index' => $tb_index,
                'crc32_id' => $crc32_id,
                'comment_num' => $foo['total']
            );
            if($foo['cmn_type'] == 2){
                $this->updateViewCommentNum($foo['relation_id'], $foo['total']);
            }
        }
        foreach ($foolist_four as $foo) {
            $crc32_id = CommonUtils::getCRC32($foo['cmn_type'] . '_' . $foo['relation_id']);
            $tb_index = $crc32_id % self::TABLE_NUMS;
            if (isset($index_num[$crc32_id])) {
                $index_num[$crc32_id]['planner_comment_num'] = $foo['total'];
            } else {
                $index_num[$crc32_id] = array(
                    'cmn_type' => $foo['cmn_type'],
                    'relation_id' => $foo['relation_id'],
                    'tb_index' => $tb_index,
                    'crc32_id' => $crc32_id,
                    'planner_comment_num' => $foo['total']
                );
            }
        }        
        foreach ($index_num as $valus){            
            $valus['c_time'] = date('Y-m-d H:i:s');
            $valus['u_time'] = date('Y-m-d H:i:s');
            Yii::app()->lcs_comment_w->createCommand()->insert('lcs_comment_index_num',$valus);
        }
    }

    /**
     * 数据插入到新表
     * @param type $tb_index
     * @param type $data
     * @return type
     */
    private function insert($tb_index, $data) {
        //插入数据
        $tbname = 'lcs_comment_' . $tb_index;
        Yii::app()->lcs_comment_w->createCommand()->insert($tbname, $data);
        return Yii::app()->lcs_comment_w->getLastInsertID();
    }
    /**
     * 将最近一个月的数据写到master表中
     */
    public function addCommentMaster(){
        $start_time = date('Y-m-d H:i:s',  strtotime('-30 days'));
        $end_time = date('Y-m-d H:i:s');
        for($i = 0;$i < self::TABLE_NUMS; $i++){
            $sql = "SELECT * FROM lcs_comment_".$i." WHERE c_time>='{$start_time}' AND c_time<='{$end_time}'";            
            $list = Yii::app()->lcs_comment_r->createCommand($sql)->queryAll();
            if(empty($list)){
                continue;
            }            
            $keys = array_keys($list[0]);
            $value_str = '';
            foreach ($list as $item){                  
                $value = '';
                foreach ($keys as $key){
                    $value .= ",".Yii::app()->lcs_comment_w->getPdoInstance()->quote($item[$key]);;
                }                       
                $value = trim($value,',');                
                $value_str .= ",({$value})";
            }            
            $value_str = trim($value_str,',');
            $insert_sql = "INSERT INTO lcs_comment_master (".implode(',', $keys).") VALUES ".$value_str;
            Yii::app()->lcs_comment_w->createCommand($insert_sql)->execute();
        }
    }

    /**
     * 处理置顶说说
     */
    public function updateQuality(){
        for($i = 0;$i < self::TABLE_NUMS; $i++){
            $sql = "SELECT * FROM lcs_comment_".$i." WHERE is_display=2";
            $list = Yii::app()->lcs_comment_r->createCommand($sql)->queryAll();
            if(empty($list)){
                continue;
            }
            $del_ids = array();
            foreach ($list as $item){
                $item['index_id'] = $i.'_'.$item['cmn_id'];
                Yii::app()->lcs_comment_w->createCommand()->insert('lcs_comment_quality',$item);
                $del_ids[] = $item['cmn_id'];
            }
            Yii::app()->lcs_comment_w->createCommand()->delete('lcs_comment_'.$i,'cmn_id in ('.  implode(',', $del_ids).')');
        }
    }
    /**
     * 更新热门说说
     */
    public function updateHotComment(){
        $sql = "SELECT * FROM lcs_page_cfg WHERE area_code=6";
        $list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        foreach ($list as $value){
            unset($value['id']);
            $cmnid = $value['relation_id'];
            $new_cmnid = isset($this->cmnid_map[$cmnid]) ? $this->cmnid_map[$cmnid] : 0;
            if(empty($new_cmnid)){
                continue;
            }
            $tb_index = $this->newid_map[$cmnid.'_'.$new_cmnid];
            $value['relation_id'] = $tb_index.'_'.$new_cmnid;
            $value['area_code'] = 14;
            Yii::app()->lcs_w->createCommand()->insert('lcs_page_cfg',$value);
        }
    }
    /**
     * 
     * @param type $pln_id
     * @param type $columns
     * @return type
     */
    public function updatePlanCommentNum($pln_id,$num){
        $columns = array('comment_count'=>$num);
        $condition = "pln_id=".$pln_id;
        return Yii::app()->lcs_w->createCommand()->update('lcs_plan_info',$columns,$condition);
    }
    /**
     * 
     * @param type $v_id
     * @param type $columns
     * @return type
     */
    public function updateViewCommentNum($v_id,$num){
        $columns = array('comment_num'=>$num);
        $condition = "id=".$v_id;
        return Yii::app()->lcs_w->createCommand()->update('lcs_package',$columns,$condition);
    }
    /**
     * 
     * @param type $t_id
     * @param type $columns
     * @return type
     */
    public function updateTopicCommentNum($t_id,$num){
        $columns = array('comment_num'=>$num);
        $condition = "id=".$t_id;
        return Yii::app()->lcs_w->createCommand()->update('lcs_hot_topic',$columns,$condition);
    }
    
    public function updateRootReplyid(){
        for($i = 0;$i < self::TABLE_NUMS; $i++){
            $sql = "UPDATE lcs_comment_".$i." SET root_reply_id=reply_id WHERE reply_id>0 and cmn_type>4";
            Yii::app()->lcs_comment_w->createCommand($sql)->execute();
        }
    }
    
}
