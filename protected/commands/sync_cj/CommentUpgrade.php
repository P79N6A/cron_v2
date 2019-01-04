<?php

/*
 * 1、
 */

/**
 * Description of CommentUpgrade
 *
 * @author huanghailin
 */
class CommentUpgrade {

    //旧字段
    private $_fields = array('cmn_type', 'u_type', 'uid', 'content', 'floor_num', 'is_display', 'is_anonymous', 'source', 'c_time', 'u_time');

    /**
     * 迁移数据主方法
     */
    public function dbUpgrade() {
        $maxsql = "SELECT MAX(cmn_id) as cmn_id FROM lcs_comment";
        $maxinfo = Yii::app()->lcs_r->createCommand($maxsql)->queryAll();
        $maxid = (int) $maxinfo[0]['cmn_id'] + 1;
        $flag = TRUE;
        $sql = "SELECT * FROM lcs_comment WHERE cmn_id<:maxid ORDER BY cmn_id DESC LIMIT 2000";
        while ($flag) {
            echo $maxid;
            $list = Yii::app()->lcs_r->createCommand($sql)->bindParam(':maxid', $maxid, PDO::PARAM_INT)->queryAll();
            if (!empty($list)) {
                foreach ($list as $item) {
                    $d = array();
                    //新旧数据转换
                    $maxid = $item['cmn_id'];
                    $type = $item['cmn_type'];
                    $d['praise_num'] = $item['parise_num'];
                    $d['reply_num'] = $item['replay_num'];
                    $d['reply_id'] = $item['replay_id'];
                    if ($type == 2) {
                        $d['relation_id'] = $item['parent_relation_id'];
                        $d['child_relation_id'] = $item['relation_id'];
                    } else {
                        $d['relation_id'] = $item['relation_id'];
                        $d['child_relation_id'] = 0;
                    }
                    foreach ($this->_fields as $field) {
                        $d[$field] = $item[$field];
                    }
                    $crc32_id = CommonUtils::getCRC32($type . '_' . $d['relation_id']);
                    $d['crc32_id'] = $crc32_id;
                    $tb_index = $crc32_id % 100;
                    //插入数据                    
                    $newid = $this->insert($tb_index, $d);
                    //点赞数据
                    $this->praiseUpgrade($item['cmn_id'], $newid, $tb_index);
                    //最后回复id
                    $this->lastReplyUpgrade($item['cmn_id'], $newid, $tb_index);
                    //楼层数
                    $this->floorUpgrade($type, $item['relation_id'], $d['relation_id'], $item['cmn_id'], $newid);
                }
            } else {
                $flag = FALSE;
            }
        }
    }

    /**
     * 升级楼层缓存
     * @param type $type
     * @param type $relationid
     * @param type $newrelationid
     * @param type $cmnid
     * @param type $newid
     */
    private function floorUpgrade($type, $relationid, $newrelationid, $cmnid, $newid) {
        $key = MEM_PRE_KEY . 'cmn_floor_' . $type . '_' . $relationid;
        $floor = Yii::app()->redis_r->hget($key, $cmnid);
        $relation_floor = Yii::app()->redis_r->hget($key, 0);
        $redis_key_floor = MEM_PRE_KEY . 'cmn_floor_' . $type . '_' . $newrelationid;
        Yii::app()->redis_w->hset($redis_key_floor, 0, $relation_floor);
        Yii::app()->redis_w->hset($redis_key_floor, $newid, $floor);
    }

    /**
     * 升级最后回复
     * @param type $cmnid
     * @param type $newid
     * @param type $tb_index
     */
    private function lastReplyUpgrade($cmnid, $newid, $tb_index) {
        $lastkey = MEM_PRE_KEY . 'cmn_lasted_' . $cmnid;
        $lastlist = Yii::app()->redis_r->getRange($lastkey, 0, -1);
        if (!empty($lastlist)) {
            $key = MEM_PRE_KEY . 'cmn_lasted_' . $tb_index . '_' . $newid;
            foreach ($lastlist as $lastid) {
                Yii::app()->redis_w->lPush($key, intval($lastid));
                Yii::app()->redis_w->trimlist($key, 0, 1);
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
        $sql = "SELECT cmn_type,relation_id,COUNT(*) AS total FROM lcs_comment WHERE cmn_type!=2 AND replay_id=0 AND is_display=1 AND relation_id>0 GROUP BY cmn_type,relation_id";
        $planner_sql = "SELECT cmn_type,relation_id,COUNT(*) AS total FROM lcs_comment WHERE cmn_type!=2 AND replay_id=0 AND is_display=1 AND u_type=2 AND relation_id>0 GROUP BY cmn_type,relation_id";
        $viewsql = "SELECT cmn_type,parent_relation_id AS relation_id,COUNT(*) AS total FROM lcs_comment WHERE cmn_type=2 AND replay_id=0 AND is_display=1 GROUP BY cmn_type,parent_relation_id";
        $planner_viewsql = "SELECT cmn_type,parent_relation_id AS relation_id,COUNT(*) AS total FROM lcs_comment WHERE cmn_type=2 AND replay_id=0 AND is_display=1 AND u_type=2 GROUP BY cmn_type,parent_relation_id";
        $foolist_one = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $foolist_two = Yii::app()->lcs_r->createCommand($planner_sql)->queryAll();
        $foolist_three = Yii::app()->lcs_r->createCommand($viewsql)->queryAll();
        $foolist_four = Yii::app()->lcs_r->createCommand($planner_viewsql)->queryAll();
        foreach ($foolist_one as $foo) {
            $crc32_id = CommonUtils::getCRC32($foo['cmn_type'] . '_' . $foo['relation_id']);
            $tb_index = $crc32_id % 100;
            $index_num[$crc32_id] = array(
                'cmn_type' => $foo['cmn_type'],
                'relation_id' => $foo['relation_id'],
                'tb_index' => $tb_index,
                'crc32_id' => $crc32_id,
                'comment_num' => $foo['total']
            );
        }
        foreach ($foolist_two as $foo) {
            $crc32_id = CommonUtils::getCRC32($foo['cmn_type'] . '_' . $foo['relation_id']);
            $tb_index = $crc32_id % 100;
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
            $tb_index = $crc32_id % 100;
            $index_num[$crc32_id] = array(
                'cmn_type' => $foo['cmn_type'],
                'relation_id' => $foo['relation_id'],
                'tb_index' => $tb_index,
                'crc32_id' => $crc32_id,
                'comment_num' => $foo['total']
            );
        }
        foreach ($foolist_four as $foo) {
            $crc32_id = CommonUtils::getCRC32($foo['cmn_type'] . '_' . $foo['relation_id']);
            $tb_index = $crc32_id % 100;
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
        $newid = Yii::app()->lcs_comment_w->createCommand()->insert($tbname, $data);
        return $newid;
    }
}
