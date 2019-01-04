<?php
/**
 * 道具模型
 * @author yougang1
 * @date 2016-11-28
 */
class Prop extends CActiveRecord{
    
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
        
    public function tableName(){
        return 'lcs_prop';
    }
    
    /**
     * 查询存在的平台体验卡计划ID
     * @param unknown $use_channel
     * @param number $status
     */
    public function getProp($use_channel,$status=0){
        $db_r = Yii::app()->lcs_r;
        $sql = "select relation_id from {$this->tableName()} where use_channel = :use_channel and statuss = :status"; 
        $command = $db_r->createCommand($sql);
        $command->bindParam(':use_channel',$use_channel,PDO::PARAM_INT);
        $command->bindParam(':status',$status,PDO::PARAM_INT);
        $pln_ids = $command->queryColumn();
        return $pln_ids;
    }
    
    
}