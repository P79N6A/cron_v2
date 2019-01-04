<?php
/**
 * 收藏数据库访问类
 * User: zwg
 * Date: 2015/5/18
 * Time: 18:06
 */

class Collect extends CActiveRecord {


    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName(){
        return 'lcs_collect';
    }

    public function getDistinctRelationIdByType($type,$start_time, $end_time){
        $cdn = '';
        if(!empty($start_time)){
            $cdn .= ' AND c_time>=:start_time';
        }
        if(!empty($end_time)){
            $cdn .= ' AND c_time<:end_time';
        }
        $sql = 'select DISTINCT(relation_id)  from '.$this->tableName().' where type=:type '.$cdn.' ;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':type', $type, PDO::PARAM_INT);
        if(!empty($start_time)){
            $cmd->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        }
        if(!empty($end_time)){
            $cmd->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        }
        return $cmd->queryAll();
    }

    /**
     * 获取订阅详情
     * @param $ids
     * @param null $fields
     * @return mixed
     */
    public function getCollectCountOfUidByType($type, $relation_ids, $start_time, $end_time){
        $cdn = '';
        if(!empty($relation_ids)){
            $relation_ids = (array)$relation_ids;
            $cdn .= ' AND relation_id in ('.implode(',',$relation_ids).')';
        }
        if(!empty($start_time)){
            $cdn .= ' AND c_time>=:start_time';
        }
        if(!empty($end_time)){
            $cdn .= ' AND c_time<:end_time';
        }
        $sql = 'select count(DISTINCT(uid)) from '.$this->tableName().' where type=:type '.$cdn.' ;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':type', $type, PDO::PARAM_INT);
        if(!empty($start_time)){
            $cmd->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        }
        if(!empty($end_time)){
            $cmd->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        }
        return $cmd->queryScalar();
    }


    /**
     * 获取订阅详情
     * @param $ids
     * @param null $fields
     * @return mixed
     */
    public function getCollectByType($type, $relation_ids, $start_time, $end_time){
        $cdn = '';
        if(!empty($relation_ids)){
            $relation_ids = (array)$relation_ids;
            $cdn .= ' AND relation_id in ('.implode(',',$relation_ids).')';
        }
        if(!empty($start_time)){
            $cdn .= ' AND c_time>=:start_time';
        }
        if(!empty($end_time)){
            $cdn .= ' AND c_time<:end_time';
        }
        $sql = 'select uid,type,relation_id from '.$this->tableName().' where type=:type '.$cdn.' ;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':type', $type, PDO::PARAM_INT);
        if(!empty($start_time)){
            $cmd->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        }
        if(!empty($end_time)){
            $cmd->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        }
        return $cmd->queryAll();
    }

    /**
     * 取消收藏
     * @param $ids
     * @param $uid
     * @param int $type
     * @return mixed
     */
    public function delUserCollect($ids, $uid, $type=3) {
        $ids = (array)$ids;
        $sql = "delete from ".$this->tableName()." where uid=$uid and relation_id in(".implode(",", $ids).")  and type=$type";
        return  Yii::app()->lcs_w->createCommand($sql)->execute();
    }
}