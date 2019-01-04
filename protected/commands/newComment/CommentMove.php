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
class CommentMove {
    const TABLE_NUMS = 256;

    private $cmnid_map = array();    

    public $cmnid_map_key = 'lcs_cmnid_map';
    public $newid_map_key = 'lcs_newid_map';
    public $not_finish_lasted = 'lcs_not_finish_lasted';
    //旧字段
    private $_fields = array('cmn_type', 'u_type', 'uid', 'content', 'floor_num', 'is_display', 'is_anonymous', 'source', 'c_time', 'u_time','discussion_type','discussion_id');

    public function __construct() {
        ;
    }

    public function planMove($minid = 1,$maxid = 2){        
//        $maxid = 1870582; //计划说说到2015年末最大的id
//        $minid = 0;
        $sql = "SELECT * FROM lcs_comment WHERE cmn_id<=:maxid AND cmn_id>:minid AND cmn_type='1' AND status=0 ORDER BY cmn_id ASC LIMIT 2000";        
        $flag = 1;
        while ($flag){
            $list = Yii::app()->lcs_r->createCommand($sql)->bindParam(':maxid', $maxid, PDO::PARAM_INT)->bindParam(':minid',$minid,  PDO::PARAM_INT)->queryAll();
            if(!empty($list) && sizeof($list) > 0){                
                foreach ($list as $item){
                    $d = array();
                    //新旧数据转换
                    $cmnid = $item['cmn_id'];  
                    $minid = $cmnid;
                    $type = $item['cmn_type'];
                    $d['praise_num'] = $item['parise_num'];
                    $d['reply_num'] = $item['replay_num'];
                    //获取回复id对应的新id
                    $new_reply_id = 0;
                    if(!empty($item['replay_id'])){
                        $new_reply_id = Yii::app()->redis_w->hget($this->cmnid_map_key, $item['replay_id']);
                        if(empty($new_reply_id)){
                            echo "找不到旧id：".$cmnid."\n";
                            continue;
                        }                        
                    }
                    
                    $d['reply_id'] = !empty($new_reply_id) ? $new_reply_id : 0;                   
                    $d['relation_id'] = $item['relation_id'];
                    $d['child_relation_id'] = 0;                    
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
                    echo "旧id：".$cmnid." 新id：".$newid." 所在表：".$tb_index."\n";
                    Yii::app()->redis_w->hset($this->cmnid_map_key, $cmnid, $newid);
                    Yii::app()->redis_w->hset($this->newid_map_key, $cmnid.'_'.$newid, $tb_index);
                    
                    
                    //点赞数据
                    $this->praiseUpgrade($cmnid, $newid, $tb_index);                                       
                    //楼层数
                    $this->floorUpgrade($type, $item['relation_id'], $cmnid, $newid);
                }
            }else {
                $flag = 0;
            }
        }
        //最后回复id 
        $this->lastReplyUpgrade();
    }
    public function otherMove($minid,$maxid){
//        $cmn_type = 1;
//        $maxid = 1870582; //计划说说到2015年末最大的id
        $minid = 0;
        $sql = "SELECT * FROM lcs_comment WHERE cmn_id<=:maxid AND cmn_id>:minid AND cmn_type not in (1,2) AND status=0 ORDER BY cmn_id ASC LIMIT 2000";        
        $flag = 1;
        while ($flag){
            $list = Yii::app()->lcs_r->createCommand($sql)->bindParam(':maxid', $maxid, PDO::PARAM_INT)->bindParam(':minid',$minid,  PDO::PARAM_INT)->queryAll();
            if(!empty($list) && sizeof($list) > 0){                
                foreach ($list as $item){
                    $d = array();
                    //新旧数据转换
                    $cmnid = $item['cmn_id'];  
                    $minid = $cmnid;
                    $type = $item['cmn_type'];
                    $d['praise_num'] = $item['parise_num'];
                    $d['reply_num'] = $item['replay_num'];
                    //获取回复id对应的新id
                    $new_reply_id = 0;
                    if(!empty($item['replay_id'])){
                        $new_reply_id = Yii::app()->redis_w->hget($this->cmnid_map_key, $item['replay_id']);
                        if(empty($new_reply_id)){
                            echo "找不到旧id：".$cmnid."\n";
                            continue;
                        }                        
                    }
                    $d['reply_id'] = !empty($new_reply_id) ? $new_reply_id : 0;                   
                    $d['relation_id'] = $item['relation_id'];
                    $d['child_relation_id'] = 0;                    
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
                    Yii::app()->redis_w->hset($this->cmnid_map_key, $cmnid, $newid);
                    Yii::app()->redis_w->hset($this->newid_map_key, $cmnid.'_'.$newid, $tb_index);
                    
                    echo "旧id：".$cmnid." 新id：".$newid." 所在表：".$tb_index."\n";
                    //点赞数据
                    $this->praiseUpgrade($cmnid, $newid, $tb_index);                                       
                    //楼层数
                    $this->floorUpgrade($type, $item['relation_id'], $cmnid, $newid);
                }
            }else {
                $flag = 0;
            }
        }
        //最后回复id 
        $this->lastReplyUpgrade();
    }

    /**
     * 迁移数据主方法
     */
    public function viewMove($minid,$maxid) {        
//        $maxid = 1870582;
//        $minid = 0;
        $flag = TRUE;
        $sql = "SELECT * FROM lcs_comment WHERE cmn_id>:minid AND cmn_id<=:maxid AND cmn_type=2 AND status=0 ORDER BY cmn_id ASC LIMIT 2000";
        while ($flag) {            
            $list = Yii::app()->lcs_r->createCommand($sql)->bindParam(':maxid', $maxid, PDO::PARAM_INT)->bindParam(':minid',$minid,  PDO::PARAM_INT)->queryAll();
            if (!empty($list)) {
                foreach ($list as $item) {
                    $d = array();
                    //新旧数据转换
                    $cmnid = $minid = $item['cmn_id'];                                        
                    $type = $item['cmn_type'];
                    $d['praise_num'] = $item['parise_num'];
                    $d['reply_num'] = $item['replay_num'];
                    $new_reply_id = Yii::app()->redis_w->hget($this->cmnid_map_key, $item['replay_id']);
                    $d['reply_id'] = !empty($new_reply_id) ? $new_reply_id : 0; 
                    
                    $d['relation_id'] = $item['parent_relation_id'];
                    $d['child_relation_id'] = $item['relation_id'];                    
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
                    Yii::app()->redis_w->hset($this->cmnid_map_key, $cmnid, $newid);
                    Yii::app()->redis_w->hset($this->newid_map_key, $cmnid.'_'.$newid, $tb_index);
                    echo "旧id：".$cmnid." 新id：".$newid." 所在表：".$tb_index."\n";
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
        $floor = Yii::app()->redis_w->hget($key, $cmnid);                
        $r_floor = Yii::app()->redis_w->hget($key, 0);                
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
        //将未处理好的拿出来重新处理
        $not_finish_list = Yii::app()->redis_w->getRange($this->not_finish_lasted, 0, -1);
        if(!empty($not_finish_list)){
            foreach ($not_finish_list as $key){
                $foo = explode('_', $key);
                if(sizeof($foo) == 2){
                    $this->cmnid_map[$foo[0]] = $foo[1];
                }                
            }
            Yii::app()->redis_w->delete($this->not_finish_lasted);
        }
        foreach ($this->cmnid_map as $cmnid=>$newid){
            $lastkey = MEM_PRE_KEY . 'cmn_lasted_' . $cmnid;
            $lastlist = Yii::app()->redis_w->getRange($lastkey, 0, -1);
            if (!empty($lastlist)) {
                $tb_index = Yii::app()->redis_w->hget($this->newid_map_key, $cmnid.'_'.$newid);
                $key = MEM_PRE_KEY . 'cmn_new_lasted_' . $tb_index;
                $lastid_arr = array();
                foreach ($lastlist as $lastid) {
                    $lid = Yii::app()->redis_w->hget($this->cmnid_map_key, $lastid);
                    if(!empty($lid)){
                        $lastid_arr[] = $lid;
                    }else{
                        Yii::app()->redis_w->push($this->not_finish_lasted,$cmnid.'_'.$newid);
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
        $praiselist = Yii::app()->redis_w->hGetAll($praisekey);

        if (!empty($praiselist)) {
            $newpraisekey = MEM_PRE_KEY . 'cmn_praise_' . $tb_index;
            foreach ($praiselist as $key => $value) {
                $newpraisefield = intval($newcmnid) . '_' . intval($key);
                Yii::app()->redis_w->hset($newpraisekey, $newpraisefield, 1);
            }
        }
    }

    /**
     * 大家说数量
     */
    public function commentNumUpgrade() {
        $index_num = array();
        $sql = "SELECT cmn_type,relation_id,COUNT(*) AS total FROM lcs_comment WHERE cmn_type!=2 AND replay_id=0 AND is_display=1 AND relation_id>0 AND status=0 GROUP BY cmn_type,relation_id";
        $planner_sql = "SELECT cmn_type,relation_id,SUM(total) AS total FROM ((SELECT cmn_type,relation_id,COUNT(DISTINCT replay_id) AS total FROM lcs_comment WHERE  u_type=2 AND relation_id>0 AND replay_id >0 AND status=0 GROUP BY cmn_type,relation_id) 
                            UNION
                        (SELECT cmn_type,relation_id,COUNT(*) AS total FROM lcs_comment WHERE u_type=2 AND relation_id>0 AND replay_id =0 AND status=0 GROUP BY cmn_type,relation_id)) AS a GROUP BY cmn_type,relation_id";
        //$planner_sql = "SELECT cmn_type,relation_id,COUNT(*) AS total FROM lcs_comment WHERE cmn_type!=2 AND u_type=2 AND is_display=1 AND relation_id>0 AND status=0 GROUP BY cmn_type,relation_id";
        $viewsql = "SELECT cmn_type,parent_relation_id AS relation_id,COUNT(*) AS total FROM lcs_comment WHERE cmn_type=2 AND replay_id=0 AND is_display=1 AND status=0 GROUP BY cmn_type,parent_relation_id";
        $planner_viewsql = "SELECT cmn_type,relation_id,COUNT(DISTINCT cmn_id) as total FROM  
                            ((SELECT cmn_type,parent_relation_id AS relation_id,replay_id AS cmn_id FROM lcs_comment WHERE cmn_type=2 AND is_display=1 AND u_type=2 AND replay_id>0 AND status=0)
                            UNION 
                            (SELECT cmn_type,parent_relation_id AS relation_id,cmn_id FROM lcs_comment WHERE  cmn_type=2 AND is_display=1 AND u_type=2 AND replay_id=0 AND status=0)) AS a
                            GROUP BY cmn_type,relation_id";
        //$planner_viewsql = "SELECT cmn_type,parent_relation_id AS relation_id,COUNT(*) AS total FROM lcs_comment WHERE cmn_type=2 AND u_type=2 AND is_display=1 AND status=0 GROUP BY cmn_type,parent_relation_id";
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
//            if($foo['cmn_type'] == 1){
//                $this->updatePlanCommentNum($foo['relation_id'], $foo['total']);
//            }elseif($foo['cmn_type'] == 3){
//                $this->updateTopicCommentNum($foo['relation_id'], $foo['total']);
//            }
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
//            if($foo['cmn_type'] == 2){
//                $this->updateViewCommentNum($foo['relation_id'], $foo['total']);
//            }
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
            $id = Yii::app()->lcs_comment_w->createCommand()->insert('lcs_comment_index_num',$valus);
            echo $id."\n";
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
        for($i = 0;$i < self::TABLE_NUMS; $i++){
            $count_sql = "SELECT count(*) as total FROM lcs_comment_".$i." WHERE cmn_type IN (1,2,3,4) AND c_time>'2015-12-31'";   
            $total = Yii::app()->lcs_comment_r->createCommand($count_sql)->queryScalar();
            echo "表:lcs_comment_".$i."  总条数：".$total."\n";
            $pagesize = 1000;
            $page = floor(($total + $pagesize - 1) / $pagesize);
            for ($p=0;$p<$page;$p++){
                $offset = $p*$pagesize;
                $sql = "SELECT * FROM lcs_comment_{$i} WHERE cmn_type IN (1,2,3,4) AND c_time>'2015-12-31' ORDER BY cmn_id ASC LIMIT ".$offset.','.$pagesize;
                echo $sql."\n";
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
     * 更新热门说说、
     */
    public function updateHotComment(){
        $sql = "SELECT * FROM lcs_page_cfg WHERE area_code=6";
        $list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        foreach ($list as $value){
            unset($value['id']);
            $cmnid = $value['relation_id'];
            $new_cmnid = Yii::app()->redis_w->hget($this->cmnid_map_key, $cmnid);
            $tb_index = Yii::app()->redis_w->hget($this->newid_map_key, $cmnid.'_'.$new_cmnid);                        
            if(empty($new_cmnid) || empty($tb_index)){
                continue;
            }            
            $value['relation_id'] = $tb_index.'_'.$new_cmnid;
            $value['area_code'] = 14;
            Yii::app()->lcs_w->createCommand()->insert('lcs_page_cfg',$value);
        }
        $sql = "SELECT * FROM lcs_page_cfg WHERE area_code=12";
        $list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        foreach ($list as $value){
            unset($value['id']);
            $cmnid = $value['relation_id'];
            $new_cmnid = Yii::app()->redis_w->hget($this->cmnid_map_key, $cmnid);
            $tb_index = Yii::app()->redis_w->hget($this->newid_map_key, $cmnid.'_'.$new_cmnid);                        
            if(empty($new_cmnid) || empty($tb_index)){
                continue;
            }            
            $value['relation_id'] = $tb_index.'_'.$new_cmnid;
            $value['area_code'] = 24;
            Yii::app()->lcs_w->createCommand()->insert('lcs_page_cfg',$value);
        }
    }
    /**
     * 名片
     */
    public function updateXinComment($run = 0){
        $sql = "SELECT s_uid,id,card_comment,card_comment_back FROM lcs_planner_ext where card_comment<>'' or card_comment_back<>''";
        $list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $content = '';
        foreach ($list as $item){
            $content .= "UPDATE lcs_planner_ext SET card_comment='{$item['card_comment']}',card_comment_back='{$item['card_comment_back']}' WHERE id='{$item['id']}';\n";
        }        
        $filename = 'lcs_planner_ext.txt';
        $path = CommonUtils::createPath(DATA_PATH . DIRECTORY_SEPARATOR, 'commentback');        
        if (!$handle = fopen($path .DIRECTORY_SEPARATOR. $filename, 'w')) {
            echo '不能打开文件';
            return;
        }
        if (fwrite($handle, $content) === FALSE) {
            echo '不能写入文件';
            return;
        }
        $new_sql = '';
        foreach ($list as $item){
            $card_comment = $item['card_comment'];
            $card_comment_back = $item['card_comment_back'];
            $newcard_arr = array();
            $back_arr = array();
            if(!empty($card_comment)){
                $card_arr = explode(',', $card_comment);                
                foreach ($card_arr as $cmnid){
                    $new_cmnid = Yii::app()->redis_w->hget($this->cmnid_map_key, $cmnid);
                    $tb_index = Yii::app()->redis_w->hget($this->newid_map_key, $cmnid.'_'.$new_cmnid); 
                    if(empty($new_cmnid)){
                        echo "旧评论id:$cmnid 新id：$new_cmnid 所在表: $tb_index\n";
                        continue;
                    }
                    $newcard_arr[] = $new_cmnid.'_'.$tb_index;
                }
            }
            if(!empty($card_comment_back)){
                $card_arr = explode(',', $card_comment_back);                
                foreach ($card_arr as $cmnid){
                    $new_cmnid = Yii::app()->redis_w->hget($this->cmnid_map_key, $cmnid);
                    $tb_index = Yii::app()->redis_w->hget($this->newid_map_key, $cmnid.'_'.$new_cmnid);  
                    if(empty($new_cmnid)){
                        echo "旧评论id:$cmnid 新id：$new_cmnid 所在表: $tb_index\n";
                        continue;
                    }
                    $back_arr[] = $new_cmnid.'_'.$tb_index;
                }
            }
            if(empty($newcard_arr) && empty($back_arr)){
                echo "异常理财师名片 id：{$item['id']}\n";
                continue;
            }
            $sql = "UPDATE lcs_planner_ext SET card_comment='".  implode(',', $newcard_arr)."',card_comment_back='".  implode(',', $back_arr)."' WHERE id=".$item['id'];
            echo $sql."\n";   
            $new_sql .= "微博id：".$item['s_uid'].'   '.$sql.";\n";
            if($run == 1){
                Yii::app()->lcs_w->createCommand($sql)->execute();
            }
            
        }
        $filename = 'lcs_planner_ext_new.txt';
        $path = CommonUtils::createPath(DATA_PATH . DIRECTORY_SEPARATOR, 'commentback');        
        if (!$handle = fopen($path .DIRECTORY_SEPARATOR. $filename, 'w')) {
            echo '不能打开文件';
            return;
        }
        if (fwrite($handle, $new_sql) === FALSE) {
            echo '不能写入文件';
            return;
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
            $sql = "UPDATE lcs_comment_".$i." SET root_reply_id=reply_id WHERE reply_id>0 and cmn_type in (1,2,3,4)";
            echo $sql."\n";
            Yii::app()->lcs_comment_w->createCommand($sql)->execute();
        }
        $sql = "UPDATE lcs_comment_master SET root_reply_id=reply_id WHERE reply_id>0 and cmn_type in (1,2,3,4)";
        echo $sql."\n";
        Yii::app()->lcs_comment_w->createCommand($sql)->execute();
    }
    
    public function deleteComment(){
        for($i=0;$i<256;$i++){
            $sql = "delete from lcs_comment_".$i." where cmn_type=1";
            echo $sql."\n";
            Yii::app()->lcs_comment_w->createCommand($sql)->execute();
        }
//        $index_sql = "delete from lcs_comment_index_num where cmn_type in (1,2,3,4)";        
//        $quality_sql = "delete from lcs_comment_quality where cmn_type in (1,2,3,4)";        
//        $master_sql = "delete from lcs_comment_master where cmn_type in (1,2,3,4)";
//        Yii::app()->lcs_comment_w->createCommand($index_sql)->execute();
//        Yii::app()->lcs_comment_w->createCommand($quality_sql)->execute();
//        Yii::app()->lcs_comment_w->createCommand($master_sql)->execute();
    }
    
    public function fixReplyid(){        
//        $sql = "SELECT DISTINCT parent_relation_id FROM lcs_comment where cmn_type=2 and parent_relation_id>0";
//        $list = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
//        foreach ($list as $id){            
//            $rsql = "SELECT cmn_id,replay_id FROM lcs_comment WHERE parent_relation_id='{$id}' AND cmn_type=2 AND status=0 AND replay_id>0";
//            $pkg_list = Yii::app()->lcs_r->createCommand($rsql)->queryAll();
//            if(empty($pkg_list)){
//                continue;
//            }
//            
//            foreach ($pkg_list as $v){
//                $cmnid = $v['cmn_id'];                
//                $replay_id = $v['replay_id'];
//                $new_cmnid = Yii::app()->redis_w->hget($this->cmnid_map_key, $cmnid);                        
//                $new_reply_id = Yii::app()->redis_w->hget($this->cmnid_map_key, $replay_id);
//                $tb_index = Yii::app()->redis_w->hget($this->newid_map_key, $cmnid.'_'.$new_cmnid);
//                if(empty($new_reply_id)){
//                    $u_sql = "DELETE FROM lcs_comment_{$tb_index} WHERE cmn_id='{$new_cmnid}';";
//                }else{
//                    $u_sql = "UPDATE lcs_comment_{$tb_index} SET reply_id='{$new_reply_id}' WHERE cmn_id='{$new_cmnid}';";                    
//                }       
//                echo $u_sql."\n";
//                Yii::app()->lcs_comment_w->createCommand($u_sql)->execute();
//            }
//            
//        }        
        $sql = "SELECT DISTINCT relation_id FROM lcs_comment where cmn_type=1 and relation_id>0 AND cmn_id<=1254547";
        $list = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        foreach ($list as $id){            
            $rsql = "SELECT cmn_id,replay_id FROM lcs_comment WHERE relation_id='{$id}' AND cmn_type=1 AND status=0 AND replay_id>0 AND cmn_id<=1254547";
            $pkg_list = Yii::app()->lcs_r->createCommand($rsql)->queryAll();
            if(empty($pkg_list)){
                continue;
            }
            
            foreach ($pkg_list as $v){
                $cmnid = $v['cmn_id'];  
                if($cmnid > 1254547){
                    continue;
                }
                $replay_id = $v['replay_id'];
                $new_cmnid = Yii::app()->redis_w->hget($this->cmnid_map_key, $cmnid);                        
                $new_reply_id = Yii::app()->redis_w->hget($this->cmnid_map_key, $replay_id);
                $tb_index = Yii::app()->redis_w->hget($this->newid_map_key, $cmnid.'_'.$new_cmnid);
                if(empty($new_reply_id)){
                    $u_sql = "DELETE FROM lcs_comment_{$tb_index} WHERE cmn_id='{$new_cmnid}';";
                }else{
                    $u_sql = "UPDATE lcs_comment_{$tb_index} SET reply_id='{$new_reply_id}' WHERE cmn_id='{$new_cmnid}';";                    
                }       
                echo $u_sql."\n";
                //Yii::app()->lcs_comment_w->createCommand($u_sql)->execute();
            }
            
        }
    }
    
    public function finxCommentNum($r = 0){
        $sql = "SELECT * FROM lcs_comment_index_num WHERE cmn_type=1";
        $plan_sql = "SELECT pln_id,comment_count FROM lcs_plan_info";
        $package_sql = "SELECT id,comment_num FROM lcs_package";
        $plan_list = Yii::app()->lcs_r->createCommand($plan_sql)->queryAll();
        $package_list = Yii::app()->lcs_r->createCommand($package_sql)->queryAll();
        $comment_list = Yii::app()->lcs_comment_r->createCommand($sql)->queryAll();
        $plan_map = array();
        $package_map = array();
        foreach ($plan_list as $p){
            $plan_map[$p['pln_id']] = $p['comment_count'];
        }
        foreach ($package_list as $p){
            $package_map[$p['id']] = $p['comment_num'];
        }
        foreach ($comment_list as $c){
            $type = $c['cmn_type'];
            $comment_num = $c['comment_num'];
            $relation_id = $c['relation_id'];
            if($type == 1){
                if(isset($plan_map[$relation_id]) && $comment_num != $plan_map[$relation_id]){
                    echo "计划id：".$relation_id." plan_info数：".$plan_map[$relation_id]." index_num:".$comment_num."\n";
                    $u_sql = "UPDATE lcs_plan_info SET comment_count='{$comment_num}' WHERE pln_id=".$relation_id;
                    echo $u_sql."\n";
                }
            }
            if(!empty($u_sql) && $r == 1){
                Yii::app()->lcs_w->createCommand($u_sql)->execute();
            }
        }
    }
    
}
