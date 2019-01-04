<?php

class PlanTransactions extends CActiveRecord{
	
	public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    
    //交易记录
    public function tableName(){
        return TABLE_PREFIX .'plan_transactions';
    }
    
    //数据库 读
    private function getDBR(){
        return Yii::app()->lcs_r;
    }

    //数据库 写
    private function getDBW(){
        return Yii::app()->lcs_w;
    }
    
    /**
     * 获取计划的交易列表
     *
     */
    public function getTransList($pln_id){
    	$sql = "select symbol,type,profit,deal_price,deal_amount,transaction_cost,c_time from ".$this->tableName()." where pln_id=$pln_id";
    	$res =  $this->getDBR()->createCommand($sql)->queryAll();
    	return $res;
    }
    
    public function getPlanTransactionsOfEndDate($pln_id,$fields,$e_date='',$num=10){
        $pln_id = intval($pln_id);

        $sql = "select $fields from lcs_plan_transactions where pln_id=$pln_id";
        if(!empty($e_date)){
            $sql .= " and c_time<'".date("Y-m-d",strtotime($e_date))." 00:00:00"."'";
        }

        $sql .= " order by c_time desc limit 0, {$num}";

        return $this->getDBR()->createCommand($sql)->queryAll();
    }

    /**
     * get id from lcs_plan_transactions by c_time between a time range.
     */
    public function getPlanTransIdsByTime($s_time, $e_time = '') {
        $db_r = $this->getDBR();
        if($e_time == '') {
            $sql = "select id from ". $this->tableName() ." where c_time>='". $s_time ."' ";
        }else{
            $sql = "select id from ". $this->tableName() ." where c_time>='". $s_time ."' and c_time<='". $e_time ."' ";
        }
        $cmd =  $db_r->createCommand($sql);
        $res = $cmd->queryColumn();
        if (!empty($res)) {
            return $res;
        } else {
            return [];
        }
    }
    
    public function getPlanTransactionsByPlnID($pln_id,$fields,$b_date='',$e_date='',$order="desc"){
        $pln_id = intval($pln_id);

        $sql = "select $fields from lcs_plan_transactions where pln_id=$pln_id";

        if(!empty($b_date)){
            $sql .= " and c_time>'".date("Y-m-d",strtotime($b_date))." 00:00:00"."'";
        }

        if(!empty($e_date)){
            $sql .= " and c_time<'".date("Y-m-d",strtotime($e_date))." 23:59:59"."'";
        }

        $sql .= " order by c_time ".$order;

        return $this->getDBR()->createCommand($sql)->queryAll();
    }
    /**
     * 
     * @param type $ids
     * @return type
     */
    public function getTransListByIds($ids){
        $sql = "select pln_id,symbol,type,profit,deal_price,deal_amount,transaction_cost,c_time,hold_avg_cost from ".$this->tableName()." where pln_id in (".  implode(',', $ids).")";
    	$res =  $this->getDBR()->createCommand($sql)->queryAll();
    	return $res;
    }

    /**
     * 
     * @param type $ids
     * @return type
     */
    public function getTransListByTransIds($ids){
        $ids = (array) $ids;
        if (empty($ids))
            return [];

        $sql = "select id,pln_id,symbol,type,profit,deal_price,deal_amount,transaction_cost,c_time,hold_avg_cost from ".$this->tableName()." where id in (".  implode(',', $ids).")";
        $res =  $this->getDBR()->createCommand($sql)->queryAll();
        if(!empty($res)) {
            return $res;
        } else {
            return [];
        }
    }
}
