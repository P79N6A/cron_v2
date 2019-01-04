<?php


/**
 * 计划的持仓表
 *
 */
class PlanAsset extends CActiveRecord {
	
	
	public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
	//持仓表
    public function tableName() {
        return TABLE_PREFIX.'plan_asset';
    }
    
	
    /**
     * 根据股票获取持仓信息
     *
     * @param unknown_type $symbol
     */
    public function getUserSymbol($pln_id,$amount=0){
    	$amount = intval($amount);
    	$sql = "select pln_id,symbol,amount,avg_cost,hold_avg_cost,buy_avg_cost,available_sell_amount from ".$this->tableName()." where pln_id=:pln_id";
    	if( $amount > 0 ){
    		$sql .= " and amount>0";
    	}
    	$cmd = Yii::app()->lcs_r->createCommand($sql);
		$cmd->bindParam(':pln_id',$pln_id,PDO::PARAM_INT);
    	$row = $cmd->queryAll();
        return $row;
    }
    
    
    /**
     * 更新用户持仓表
     *
     * @param unknown_type $pln_id
     * @param unknown_type $symbol
     * @param unknown_type $hold_avg_cost
     * @param unknown_type $amount
     */
    public function updateAsset($pln_id,$symbol,$hold_avg_cost,$amount,$available_sell_amount){
    	$pln_id = intval($pln_id);
    	$amount = intval($amount);
    	$available_sell_amount = intval($available_sell_amount);
    	$hold_avg_cost = floatval($hold_avg_cost);
    		
    	$sql = "update ".$this->tableName()." set symbol=:symbol,";
    	if($hold_avg_cost != 0){
    		$sql .= "hold_avg_cost=$hold_avg_cost,buy_avg_cost=$hold_avg_cost,";
    	}
    	if($amount > 0){
    		$sql .= "amount=amount+$amount,";
    	}elseif($amount < 0){
    		$amount = 0-$amount;
    		$sql .= "amount=amount-$amount,";
    	}
    	if($amount > 0){
    		$sql .= "buy_total_amount=buy_total_amount+$amount,";
    	}
    	if($available_sell_amount > 0){
    		$sql .= "available_sell_amount=available_sell_amount+$available_sell_amount,";
    	}elseif ($available_sell_amount < 0){
    		$available_sell_amount = 0-$available_sell_amount;
    		$sql .= "available_sell_amount=available_sell_amount-$available_sell_amount,";
    	}
    	$sql = substr($sql,0,-1)." where pln_id=$pln_id and symbol=:symbols";
    	$cmd = Yii::app()->lcs_w->createCommand($sql);
        $cmd->bindParam(':symbol',$symbol,PDO::PARAM_STR);
        $cmd->bindParam(':symbols',$symbol,PDO::PARAM_STR);
        return $cmd->execute();
    	
    	
    }
    
    /**
     * 更新数组
     *
     * @param unknown_type $data
     * @param unknown_type $pln_id
     * @param unknown_type $symbol
     * @return unknown
     */
    public function updateAssetArray($data,$pln_id,$symbol){
    	
    	return Yii::app()->lcs_w->createCommand()->update($this->tableName(),$data,"pln_id=$pln_id and symbol='$symbol'");
    }
    /**
     * 插入一条持仓记录
     *
     * @param unknown_type $asset
     */
    public function addAsset($asset){
    	$res = false;
    	if(is_array($asset) && !empty($asset)){
    		$res = Yii::app()->lcs_w->createCommand()->insert($this->tableName(),$asset);
    	}
    	return  $res;
    }
    
     /**
     * 获取计划的持仓
     *
     * @param unknown_type $pln_id
     * @param unknown_type $amount  1是持有大于0
     */
    public function getPlanAsset($pln_id,$amount=1,$u_time='')
    {
    	$pln_id = intval($pln_id);
    	$amount = intval($amount);
    	
    	$where = '';
    	if($amount > 0){
    		$where = " and amount>0";
    	}
    	
    	if(!empty($u_time)){
    		$where .= " and u_time >'$u_time'";
    	}
    	
    	$sql = "select symbol,amount,buy_avg_cost,hold_avg_cost,avg_cost,buy_total_amount from ".$this->tableName()." where pln_id=$pln_id $where";
    	
    	$res =  Yii::app()->lcs_r->createCommand($sql)->queryAll();
    	
    	return $res;
    }
    
    /**
     * 持仓个股收益
     * @param $pln_id
     * @return mixed
     */
    public function getAssetProfit($pln_ids){        
        if(empty($pln_ids)){
            return FALSE;
        }
        $sql = "select id,pln_id,symbol,profit,profit_weight from ".$this->tableName()." where pln_id in (".  implode(',', $pln_ids).") and amount=0 and buy_total_amount>0 and profit!=0 order by profit DESC";
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }
    /**
     * 获取计划当前持仓列表
     * @$pln_id string
     * @return array
     */
    public function getAssetsByPlnId($pln_id){
        $arr = array();

        $sql = "select id, amount, hold_avg_cost as buy_avg_cost, symbol, hold_avg_cost as avg_cost,hold_avg_cost,available_sell_amount from  ".$this->tableName()."  where pln_id=".intval($pln_id)." and amount>0";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $arr = $cmd->queryAll();

        return $arr;
    }
}
