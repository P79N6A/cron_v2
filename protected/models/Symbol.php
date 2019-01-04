<?php

/**
 * Description of Symbol
 * @datetime 2015-11-5  13:52:01
 * @author hailin3
 */
class Symbol extends CActiveRecord {
        
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
    
    public function tableName(){
        return 'lcs_symbol';
    }

    public function tableAskTags(){
        return 'lcs_ask_tags';
    }

    public function tableRelation(){
        return TABLE_PREFIX.'symbol_relation';
    }
    
    public function delSymbol($type){
        if(empty($type)){
            return FALSE;
        }
        $condition = "type='{$type}'";
        $sql = 'DELETE FROM '.$this->tableName().' WHERE '.$condition;
        return Yii::app()->lcs_w->createCommand($sql)->execute();        
    }
    
    public function addSymbolRelation($values){
        if(empty($values)){
            return FALSE;
        }        
        $sql = 'INSERT INTO '.$this->tableRelation().' (type,symbol_type,symbol,r_id,u_time,c_time) VALUES '.$values;                
        return Yii::app()->lcs_w->createCommand($sql)->execute();
    }

    public function addSymbol($values){
        if(empty($values)){
            return FALSE;
        }        
        $sql = 'INSERT INTO '.$this->tableName().' (type,code,symbol,name,pinyin,search_content) VALUES '.$values;                
        return Yii::app()->lcs_w->createCommand($sql)->execute();
    }
    
    public function getAskTagsList($type){
        $sql = "SELECT id,symbol FROM {$this->tableAskTags()} WHERE type='$type'";        
        $res = Yii::app()->lcs_standby_r->CreateCommand($sql)->queryAll();
        return $res;
    }
    
    public function insertAskTags($values){
        if(empty($values)){
            return FALSE;
        }
        $sql = "insert into {$this->tableAskTags()} (type,code,symbol,name,pinyin,c_time,u_time) values ".$values;
        return Yii::app()->lcs_w->createCommand($sql)->execute(); 
    }
    
    public function updateAskTags($condition,$data = array()){
        if(empty($condition) || empty($data)){
            return FALSE;
        }
        return Yii::app()->lcs_w->createCommand()->update($this->tableAskTags(), $data,$condition);
    }

    public function getSymbolList($type='') {
        $db_r = Yii::app()->lcs_r;
        
        $where ='';
        if ($type){
        	$where = " where type='$type'";
        }
        $sql = "select type, code, symbol, name, pinyin, c_time from ".$this->tableName()." $where";
        $result = Yii::app()->lcs_r->CreateCommand($sql)->queryAll();
        return $result;
    }
    
    /**
     * 根据symbol获取信息
     * @param $type
     * @param $symbol
     * @return array
     */
    public function getTagsBySymbol($type,$symbol){
        $type = (array)$type;
        $symbol = (array)$symbol;

        $res = array();
        $where_type = '';
        $where_symbol = '';
        if(sizeof($type) > 0){
            foreach ($type as $val){
                $where_type .= Yii::app()->lcs_r->getPdoInstance()->quote($val).",";
            }
            $where_type = substr($where_type,0,-1);
            if(sizeof($symbol) > 0){
                foreach ($symbol as $val ){
                    $where_symbol .= Yii::app()->lcs_r->getPdoInstance()->quote($val).",";
                }
                $where_symbol = substr($where_symbol,0,-1);
            }
            $sql = "select id,symbol,name from ".$this->tableName()." where type in($where_type) and symbol in ($where_symbol)";

            $cmd = Yii::app()->lcs_r->CreateCommand($sql);
            $r =$cmd->queryAll();

            if(!empty($r)){
                array_walk($r,function($v) use (&$res){
                    $res[$v['symbol']] = $v;
                });
            }
        }

        return $res;
    }

    /**
     * 根据相关ｉｄ获取股票代码
     *  @param  string $type  "view 观点,ask　问答";
     *  @param  int $relation_id
     */
    public function getSymbolByRelationId($type,$relation_id,$symbol_type="stock_cn"){
        if($type=="view"){
            $type = 1;
        }elseif($type=="ask"){
            $type = 2;
        }elseif($type=="new"){
	    $type = 3;
	}else{
            return false;
        }
        $sql = "select symbol from ".$this->tableRelation()." where symbol_type='".$symbol_type."' and type='$type' and r_id='".$relation_id."'";
        $data = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        return $data;
    }

    /**
    * 根据类型和相关代码删除相关股票
    */
    public function deleteSymbolByTypeId($type,$relation_id,$symbol='',$symbol_type="stock_cn"){
        if($type=="view"){
            $type = 1;
        }elseif($type=="ask"){
            $type = 2;
        }elseif($type=="new"){
	    $type = 3;
	}else{
            return false;
        }
        
        $sql = "delete from ".$this->tableRelation()." where symbol_type='".$symbol_type."' and type='$type' and r_id='".$relation_id."' ";
        if(!empty($symbol)){
            $sql = $sql." and symbol='$symbol'";
        }
	
        $data = Yii::app()->lcs_w->createCommand($sql)->execute();
        return $data;
    }
}
