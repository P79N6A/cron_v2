<?php

/**
 * 理财师评价
 * User: zwg
 * Date: 2015/5/18
 * Time: 18:06
 */
class Group extends CActiveRecord {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'lcs_partner_group';
    }

    //数据库 读
    private function getDBR() {
        return Yii::app()->lcs_r;
    }

    //数据库 写
    private function getDBW() {
        return Yii::app()->lcs_w;
    }

    /**
     * 根据理财师编号获取群组列表
     * @param int $relation_id 相关id
     */
    public function getGroupListByRelationId($relation_id, $type) {
        try {
            $cmd = "select id,partner_id,relation_id,p_uid,type from " . $this->tableName() . " where relation_id=".$relation_id." and type=".$type." and status>=0";
            $res = $this->getDBR()->createCommand($cmd)->queryAll();
            return $res;
        } catch (Exception $ex) {
            return null;
        }
    }

}
