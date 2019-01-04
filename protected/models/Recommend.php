<?php
/**
 * 收藏数据库访问类
 * User: zwg
 * Date: 2015/5/18
 * Time: 18:06
 */

class Recommend extends CActiveRecord {


    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName(){
        return 'lcs_recommend';
    }


    /**
     * 根据推荐类型获取推荐ID
     * @param $type  1 理财师 2观点 3观点包 4理财计划
     * @param null $ind_id
     * @param int $limit
     * @return mixed
     */
    public function getRecommendByType($type, $ind_id=null, $limit=10){
        $cdn = '';
        if(!empty($ind_id)){
            $cdn = ' and ind_id=:ind_id';
        }
        $sql = "select ind_id,rcmd_id,weight from ".$this->tableName()
            . " where status=0 and type=:type '.$cdn.' order by weight desc limit 0,:limit;";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':type', $type, PDO::PARAM_INT);
        $cmd->bindParam(':limit', $limit, PDO::PARAM_INT);
        if(!empty($ind_id)) {
            $cmd->bindParam(':ind_id', $ind_id, PDO::PARAM_INT);
        }

        return $cmd->queryAll();
    }


}