<?php

class PlanAssess extends CActiveRecord{
	
    
    //计划表
    public function tableName(){
        return TABLE_PREFIX .'plan_assess';
    }
    
    //数据库 读
    private function getDBR(){
        return Yii::app()->lcs_r;

    }

    //数据库 写
    private function getDBW(){

        return Yii::app()->lcs_w;

    }
    
    public static function model($className = __CLASS__) {
		return parent::model($className);
	}
    
    /**
     * 获取评估指标
     *
     */
    public function getAssessInfo($pln_id){
    	$pln_id = intval($pln_id);
    	$sql = "select pln_id,profit_num,loss_num,max_profit,max_loss,max_profit_num,max_loss_num,avg_profit,avg_loss,total_trans_value,buy_num,hold_days,hold_total_weight,max_weight,min_weight,sell_total_profit,
    				total_cost from ".$this->tableName()." where pln_id=$pln_id";
    	$res =  $this->getDBR()->createCommand($sql)->queryRow();
    	
    	if(empty($res)){
    		$sql = "insert into ".$this->tableName()." set pln_id=$pln_id,c_time=now(),u_time=now()";
    		$this->getDBW()->createCommand($sql)->execute();
    		$res = array('pln_id'=>$pln_id,'profit_num'=>0,'loss_num'=>0,'max_profit_num'=>0,'max_loss_num'=>0,'avg_profit'=>0,'avg_loss'=>0,'total_trans_value'=>0,
    					'max_profit'=>0,'max_loss'=>0,'buy_num'=>0,'hold_days'=>0,'hold_total_weight'=>0,'max_weight'=>0,'min_weight'=>0,'sell_total_profit'=>0,'total_cost'=>0);
    	}
    	return $res;
    }
    
    /**
     * 更新计划的指标数据
     *
     * @param unknown_type $data
     * @param unknown_type $pln_id
     * @return unknown
     */
    public function updateAssess($data,$pln_id){
    	
    	return $this->getDBW()->createCommand()->update($this->tableName(),$data,"pln_id=$pln_id");
    }
    
    /**
     * 获取计划评估信息
     * @param array $pln_ids
     * @return array
     *
     */
    public function getAssessInfos($pln_ids=array()){
        $pln_ids = (array)$pln_ids;
        $result = array();
        if(!empty($pln_ids)){
            $sql = "select pln_id,max_back,profit_num,loss_num,max_profit_num,max_loss_num,max_profit,max_loss,avg_profit,avg_loss,total_trans_value,buy_num,hold_days,hold_total_weight,max_weight,min_weight,sell_total_profit,total_cost,u_time,c_time from ".$this->tableName()." where pln_id in (".implode(',',$pln_ids).")";
            $plans = Yii::app()->lcs_r->createCommand($sql)->queryAll();

            if(!empty($plans)){
                foreach($plans as $key=>$val){
                    $result[$val['pln_id']] = $val;
                }
            }
        }
        return $result;
    }
}