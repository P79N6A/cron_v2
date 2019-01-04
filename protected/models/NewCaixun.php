<?php
/**
 * 新财讯对应的新闻表
 */

class NewCaixun extends CActiveRecord {

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName(){
        return TABLE_PREFIX.'new_caixun';
    }
    
    public function tableNewSymbol(){
        return TABLE_PREFIX.'new_symbol_middle';
    }

    private $select_fields = "id,n_id,appType,author,freeContent,keyWord,mobileSummary,mobileTitle,otherStockCodes,partCodes,publishChannel,serialNo,sourceCode,stockCodes,summary,title,titleImage,showTime,updateTime,subjectIds,subjectTitle,c_time,u_time,type";

    /**
     * 根据财讯主键id获取新闻内容
     */
    public function getNewByOldId($oid,$fields=""){
        if(empty($fields)){
            $fields = $this->select_fields;
        }
        $sql = "select $fields from ".$this->tableName()." where n_id='$oid'";
        $data = Yii::app()->lcs_standby_r->createCommand($sql)->queryRow();
        if(!empty($data)){
            return $data['id'];
        }else{
            return false;
        }
    }

    /**
     * 新增新闻
     * @param   array   $new 新闻内容
     */
    public function addNew($new){
        try{
            $unset_item = array("id");
            foreach($unset_item as $item){
                if(isset($new[$item])){
                    unset($new[$item]);
                }
            }

            $new['u_time'] = $new['c_time'] = date("Y-m-d H:i:s",time());

            $sql = "insert into ".$this->tableName()." (n_id,appType,author,freeContent,keyWord,mobileSummary,mobileTitle,otherStockCodes,partCodes,payContent,publishChannel,serialNo,sourceCode,stockCodes,summary,title,titleImage,showTime,updateTime,c_time,u_time,status,subjectIds,subjectTitle,type) values (:n_id,:appType,:author,:freeContent,:keyWord,:mobileSummary,:mobileTitle,:otherStockCodes,:partCodes,:payContent,:publishChannel,:serialNo,:sourceCode,:stockCodes,:summary,:title,:titleImage,:showTime,:updateTime,:c_time,:u_time,:status,:subjectIds,:subjectTitle,:type)";
            
	    //var_dump(Yii::app()->lcs_w);die;
	    $cmd = Yii::app()->lcs_w->createCommand($sql);
            $cmd->bindParam(":n_id", $new['n_id'], PDO::PARAM_STR);
            $cmd->bindParam(":appType", $new['appType'], PDO::PARAM_STR);
            $cmd->bindParam(":author", $new['author'], PDO::PARAM_STR);
            $cmd->bindParam(":freeContent", $new['freeContent'], PDO::PARAM_STR);
            $cmd->bindParam(":keyWord", $new['keyWord'], PDO::PARAM_STR);
            $cmd->bindParam(":mobileSummary", $new['mobileSummary'], PDO::PARAM_STR);
            $cmd->bindParam(":mobileTitle", $new['mobileTitle'], PDO::PARAM_STR);
            $cmd->bindParam(":otherStockCodes", $new['otherStockCodes'], PDO::PARAM_STR);
            $cmd->bindParam(":partCodes", json_encode($new['partCodes']), PDO::PARAM_STR);
            $cmd->bindParam(":payContent", $new['payContent'], PDO::PARAM_STR);
            $cmd->bindParam(":publishChannel", $new['publishChannel'], PDO::PARAM_STR);
            $cmd->bindParam(":serialNo", $new['serialNo'], PDO::PARAM_STR);
            $cmd->bindParam(":sourceCode", $new['sourceCode'], PDO::PARAM_STR);
            $cmd->bindParam(":stockCodes", json_encode($new['stockCodes']), PDO::PARAM_STR);
            $cmd->bindParam(":summary", $new['summary'], PDO::PARAM_STR);
            $cmd->bindParam(":title", $new['title'], PDO::PARAM_STR);
            $cmd->bindParam(":titleImage", $new['titleImage'], PDO::PARAM_STR);
            $cmd->bindParam(":updateTime", date('Y-m-d H:i:s', substr($new['updateTime'],0,10)), PDO::PARAM_STR);
            $cmd->bindParam(":showTime", date('Y-m-d H:i:s', substr($new['showTime'],0,10)), PDO::PARAM_STR);
            $cmd->bindParam(":c_time", $new['c_time'], PDO::PARAM_STR);
            $cmd->bindParam(":u_time", $new['u_time'], PDO::PARAM_STR);
            $cmd->bindParam(":status", $new['status'], PDO::PARAM_STR);
            $cmd->bindParam(":subjectIds", json_encode($new['subjectIds']), PDO::PARAM_STR);
            $cmd->bindParam(":subjectTitle", $new['subjectTitle'], PDO::PARAM_STR);
            $cmd->bindParam(":type", $new['type'], PDO::PARAM_STR);
	     
            $cmd->execute();
	    $new_id = Yii::App()->lcs_w->getLastInsertID();
            return $new_id;
        }catch(Exception $e){
            var_dump($e->getMessage());
            exit;
            Common::model()->saveLog("新增新闻失败:".$e->getMessage(),"error","caixun_insert_new");
            return false;
        }
    } 

    /**
     * 更新新闻
     * @param   array   $new 新闻内容
     */
    public function updateNew($new){
        try{
                $new['u_time'] = date("Y-m-d H:i:s",time());
                $sql = "update ".$this->tableName()." set appType=:appType,author=:author,freeContent=:freeContent,keyWord=:keyWord,mobileSummary=:mobileSummary,mobileTitle=:mobileTitle,otherStockCodes=:otherStockCodes,partCodes=:partCodes,publishChannel=:publishChannel,serialNo=:serialNo,sourceCode=:sourceCode,stockCodes=:stockCodes,summary=:summary,title=:title,titleImage=:titleImage,showTime=:showTime,updateTime=:updateTime,u_time=:u_time,status=:status,subjectIds=:subjectIds,subjectTitle=:subjectTitle,type=:type where id=:id";
                $cmd = Yii::app()->lcs_w->createCommand($sql);
                $cmd->bindParam(":id", $new['id'], PDO::PARAM_STR);
            	$cmd->bindParam(":appType", $new['appType'], PDO::PARAM_STR);
            	$cmd->bindParam(":author", $new['author'], PDO::PARAM_STR);
            	$cmd->bindParam(":freeContent", $new['freeContent'], PDO::PARAM_STR);
            	$cmd->bindParam(":keyWord", $new['keyWord'], PDO::PARAM_STR);
            	$cmd->bindParam(":mobileSummary", $new['mobileSummary'], PDO::PARAM_STR);
            	$cmd->bindParam(":mobileTitle", $new['mobileTitle'], PDO::PARAM_STR);
            	$cmd->bindParam(":otherStockCodes", $new['otherStockCodes'], PDO::PARAM_STR);
            	$cmd->bindParam(":partCodes", json_encode($new['partCodes']), PDO::PARAM_STR);
            	$cmd->bindParam(":publishChannel", $new['publishChannel'], PDO::PARAM_STR);
            	$cmd->bindParam(":serialNo", $new['serialNo'], PDO::PARAM_STR);
            	$cmd->bindParam(":sourceCode", $new['sourceCode'], PDO::PARAM_STR);
            	$cmd->bindParam(":stockCodes", json_encode($new['stockCodes']), PDO::PARAM_STR);
            	$cmd->bindParam(":summary", $new['summary'], PDO::PARAM_STR);
            	$cmd->bindParam(":title", $new['title'], PDO::PARAM_STR);
            	$cmd->bindParam(":titleImage", $new['titleImage'], PDO::PARAM_STR);
            	$cmd->bindParam(":updateTime", date('Y-m-d H:i:s', substr($new['updateTime'],0,10)), PDO::PARAM_STR);
            	$cmd->bindParam(":showTime", date('Y-m-d H:i:s', substr($new['showTime'],0,10)), PDO::PARAM_STR);
            	$cmd->bindParam(":u_time", $new['u_time'], PDO::PARAM_STR);
            	$cmd->bindParam(":status", $new['status'], PDO::PARAM_STR);
            	$cmd->bindParam(":subjectIds", json_encode($new['subjectIds']), PDO::PARAM_STR);
            	$cmd->bindParam(":subjectTitle", $new['subjectTitle'], PDO::PARAM_STR);
            	$cmd->bindParam(":type", $new['type'], PDO::PARAM_STR);		
			
		$cmd->execute();
                return true;
        }catch(Exception $e){
            var_dump($e->getMessage());
            Common::model()->saveLog("更新新闻失败:".$e->getMessage(),"error","caixun_update_new");
            return false;
        }
    }

  /**
    * 根据新闻id删除相关股票
    */
    public function deleteSymbolByNewId($new_id,$symbol=''){
        
        $sql = "delete from ".$this->tableNewSymbol()." where n_id='$new_id' and type='1' and symbol_type='stock_cn' ";
        if(!empty($symbol)){
            $sql = $sql." and symbol='$symbol'";
        }
	
        $data = Yii::app()->lcs_w->createCommand($sql)->execute();
        return $data;
    }
    
    public function addSymbolNewRelation($values){
        if(empty($values)){
            return FALSE;
        }        
        $sql = 'INSERT INTO '.$this->tableNewSymbol().' (n_id,symbol_type,symbol,status,showTime,u_time,c_time) VALUES '.$values;                
        return Yii::app()->lcs_w->createCommand($sql)->execute();
    }
}
