<?php
/**
 * 新财讯对应的观点或者新闻表
 */

class ViewCaixun extends CActiveRecord {


    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableRelation(){
        return 'lcs_view_theme_relation';
    }

    public function tableTheme(){
        return 'lcs_view_theme';
    }

    public function tableName(){
        return 'lcs_view_caixun';
    }
    
    public function tableNamePageCfg(){
        return 'lcs_page_cfg';
    }


    private $select_fields = "old_vid,appType,author,authorId,fee,freeContent,old_id,keyWord,mobileSummary,mobileTitle,otherStockCodes,partCodes,payContent,payFee,publishChannel,serialNo,setting,sourceCode,stockCodes,summary,title,titleImage,updateTime,subjectIds,c_time,u_time";

    /**
     * 根据财讯主键id获取观点内容
     */
    public function getViewByOldId($oid,$fields=""){
        if(empty($fields)){
            $fields = $this->select_fields;
        }
        $sql = "select $fields from ".$this->tableName()." where old_id='$oid'";
        $data = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        if(!empty($data)){
            return $data;
        }else{
            return false;
        }
    }

    /**
     * 新增观点
     * @param   array   $view 观点内容
     */
    public function addView($view){
        try{
            $unset_item = array("id");
            foreach($unset_item as $item){
                if(isset($view[$item])){
                    unset($view[$item]);
                }
            }

            $view['u_time'] = $view['c_time'] = date("Y-m-d H:i:s",time());

            $sql = "insert into ".$this->tableName()." (old_vid,appType,author,authorId,fee,freeContent,old_id,keyWord,mobileSummary,mobileTitle,otherStockCodes,partCodes,payContent,payFee,publishChannel,serialNo,setting,sourceCode,stockCodes,summary,title,titleImage,updateTime,c_time,u_time,status,subjectIds,remark) values(:old_vid,:appType,:author,:authorId,:fee,:freeContent,:old_id,:keyWord,:mobileSummary,:mobileTitle,:otherStockCodes,:partCodes,:payContent,:payFee,:publishChannel,:serialNo,:setting,:sourceCode,:stockCodes,:summary,:title,:titleImage,:updateTime,:c_time,:u_time,:status,:subjectIds,:remark)";
            $cmd = Yii::app()->lcs_w->createCommand($sql);
            $cmd->bindParam(":old_vid", $view['old_vid'], PDO::PARAM_STR);
            $cmd->bindParam(":appType", $view['appType'], PDO::PARAM_STR);
            $cmd->bindParam(":author", $view['author'], PDO::PARAM_STR);
            $cmd->bindParam(":authorId", $view['authorId'], PDO::PARAM_STR);
            $cmd->bindParam(":fee", $view['fee'], PDO::PARAM_STR);
            $cmd->bindParam(":freeContent", $view['freeContent'], PDO::PARAM_STR);
            $cmd->bindParam(":old_id", $view['old_id'], PDO::PARAM_STR);
            $cmd->bindParam(":keyWord", $view['keyWord'], PDO::PARAM_STR);
            $cmd->bindParam(":mobileSummary", $view['mobileSummary'], PDO::PARAM_STR);
            $cmd->bindParam(":mobileTitle", $view['mobileTitle'], PDO::PARAM_STR);
            $cmd->bindParam(":otherStockCodes", $view['otherStockCodes'], PDO::PARAM_STR);
            $cmd->bindParam(":partCodes", json_encode($view['partCodes']), PDO::PARAM_STR);
            $cmd->bindParam(":payContent", $view['payContent'], PDO::PARAM_STR);
            $cmd->bindParam(":payFee", $view['payFee'], PDO::PARAM_STR);
            $cmd->bindParam(":publishChannel", $view['publishChannel'], PDO::PARAM_STR);
            $cmd->bindParam(":serialNo", $view['serialNo'], PDO::PARAM_STR);
            $cmd->bindParam(":setting", $view['setting'], PDO::PARAM_STR);
            $cmd->bindParam(":sourceCode", $view['sourceCode'], PDO::PARAM_STR);
            $cmd->bindParam(":stockCodes", json_encode($view['stockCodes']), PDO::PARAM_STR);
            $cmd->bindParam(":summary", $view['summary'], PDO::PARAM_STR);
            $cmd->bindParam(":title", $view['title'], PDO::PARAM_STR);
            $cmd->bindParam(":titleImage", $view['titleImage'], PDO::PARAM_STR);
            $cmd->bindParam(":updateTime", $view['updateTime'], PDO::PARAM_STR);
            $cmd->bindParam(":c_time", $view['c_time'], PDO::PARAM_STR);
            $cmd->bindParam(":u_time", $view['u_time'], PDO::PARAM_STR);
            $cmd->bindParam(":status", $view['status'], PDO::PARAM_STR);
            $cmd->bindParam(":subjectIds", json_encode($view['subjectIds']), PDO::PARAM_STR);
            $cmd->bindParam(":remark", $view['remark'], PDO::PARAM_STR);
            $res = $cmd->execute();
            return true;
        }catch(Exception $e){
            var_dump($e->getMessage());
            exit;
            Common::model()->saveLog("新增观点失败:".$e->getMessage(),"error","caixun_insert_view");
            return false;
        }
    } 

    /**
     * 更新观点
     * @param   array   $view 观点内容
     */
    public function updateView($view){
        try{
                $view['u_time'] = date("Y-m-d H:i:s",time());
                $sql = "update ".$this->tableName()." set appType=:appType,author=:author,authorId=:authorId,fee=:fee,freeContent=:freeContent,keyWord=:keyWord,mobileSummary=:mobileSummary,mobileTitle=:mobileTitle,otherStockCodes=:otherStockCodes,partCodes=:partCodes,payContent=:payContent,payFee=:payFee,publishChannel=:publishChannel,serialNo=:serialNo,setting=:setting,sourceCode=:sourceCode,stockCodes=:stockCodes,summary=:summary,title=:title,titleImage=:titleImage,updateTime=:updateTime,u_time=:u_time,status=:status,subjectIds=:subjectIds,remark=:remark where old_id=:old_id";
                $cmd = Yii::app()->lcs_w->createCommand($sql);
                $cmd->bindParam(":old_id", $view['old_id'], PDO::PARAM_STR);
                $cmd->bindParam(":appType", $view['appType'], PDO::PARAM_STR);
                $cmd->bindParam(":author", $view['author'], PDO::PARAM_STR);
                $cmd->bindParam(":authorId", $view['authorId'], PDO::PARAM_STR);
                $cmd->bindParam(":fee", $view['fee'], PDO::PARAM_STR);
                $cmd->bindParam(":freeContent", $view['freeContent'], PDO::PARAM_STR);
                $cmd->bindParam(":keyWord", $view['keyWord'], PDO::PARAM_STR);
                $cmd->bindParam(":mobileSummary", $view['mobileSummary'], PDO::PARAM_STR);
                $cmd->bindParam(":mobileTitle", $view['mobileTitle'], PDO::PARAM_STR);
                $cmd->bindParam(":otherStockCodes", $view['otherStockCodes'], PDO::PARAM_STR);
                $cmd->bindParam(":partCodes", json_encode($view['partCodes']), PDO::PARAM_STR);
                $cmd->bindParam(":payContent", $view['payContent'], PDO::PARAM_STR);
                $cmd->bindParam(":payFee", $view['payFee'], PDO::PARAM_STR);
                $cmd->bindParam(":publishChannel", $view['publishChannel'], PDO::PARAM_STR);
                $cmd->bindParam(":serialNo", $view['serialNo'], PDO::PARAM_STR);
                $cmd->bindParam(":setting", $view['setting'], PDO::PARAM_STR);
                $cmd->bindParam(":sourceCode", $view['sourceCode'], PDO::PARAM_STR);
                $cmd->bindParam(":stockCodes", json_encode($view['stockCodes']), PDO::PARAM_STR);
                $cmd->bindParam(":summary", $view['summary'], PDO::PARAM_STR);
                $cmd->bindParam(":title", $view['title'], PDO::PARAM_STR);
                $cmd->bindParam(":titleImage", $view['titleImage'], PDO::PARAM_STR);
                $cmd->bindParam(":updateTime", $view['updateTime'], PDO::PARAM_STR);
                $cmd->bindParam(":u_time", $view['u_time'], PDO::PARAM_STR);
                $cmd->bindParam(":status", $view['status'], PDO::PARAM_STR);
                $cmd->bindParam(":subjectIds", json_encode($view['subjectIds']), PDO::PARAM_STR);
                $cmd->bindParam(":remark", $view['remark'], PDO::PARAM_STR);
                $cmd->execute();
                return true;
        }catch(Exception $e){
            var_dump($e->getMessage());
            Common::model()->saveLog("更新观点失败:".$e->getMessage(),"error","caixun_update_view");
            return false;
        }
    }

    /**
    * 新增保存专题数据
    */
    public function addTheme($theme_info){
        try{
            $sql = "insert into ".$this->tableTheme()." (zhuanti_id,image,introduction,lastTime,name,c_time,u_time) values(:zhuanti_id,:image,:introduction,:lastTime,:name,:c_time,:u_time)";
            $cmd = Yii::app()->lcs_w->createCommand($sql);
	    $current = date("Y-m-d H:i:s",time());
            $cmd->bindParam(":zhuanti_id", $theme_info['id'], PDO::PARAM_STR);
            $cmd->bindParam(":image", $theme_info['image'], PDO::PARAM_STR);
            $cmd->bindParam(":introduction", $theme_info['introduction'], PDO::PARAM_STR);
            $cmd->bindParam(":lastTime", $current, PDO::PARAM_STR);
            $cmd->bindParam(":name", $theme_info['name'], PDO::PARAM_STR);
            $cmd->bindParam(":c_time", $current, PDO::PARAM_STR);
            $cmd->bindParam(":u_time", $current, PDO::PARAM_STR);
            $cmd->execute();
        }catch(Exception $e){
            var_dump($e->getMessage());
            Common::model()->saveLog("新增保存观点专题失败:".$e->getMessage(),"error","caixun_add_view_theme");
            return false;
        }
    }

    /**
    * 修改保存专题数据
    */
    public function saveTheme($theme_info){
        try{
            $sql = "update ".$this->tableTheme()." set image=:image,introduction=:introduction,lastTime=:lastTime,name=:name,c_time=:c_time,u_time=:u_time where zhuanti_id=:zhuanti_id";
            $cmd = Yii::app()->lcs_w->createCommand($sql);
	    $current = date("Y-m-d H:i:s",time());
            $cmd->bindParam(":zhuanti_id", $theme_info['id'], PDO::PARAM_INT);
            $cmd->bindParam(":image", $theme_info['image'], PDO::PARAM_STR);
            $cmd->bindParam(":introduction", $theme_info['introduction'], PDO::PARAM_STR);
            $cmd->bindParam(":lastTime", $current, PDO::PARAM_STR);
            $cmd->bindParam(":name", $theme_info['name'], PDO::PARAM_STR);
            $cmd->bindParam(":c_time", $current, PDO::PARAM_STR);
            $cmd->bindParam(":u_time", $current, PDO::PARAM_STR);
            $cmd->execute();
        }catch(Exception $e){
	    var_dump($e->getMessage());
            Common::model()->saveLog("修改保存观点专题失败:".$e->getMessage(),"error","caixun_update_view_theme");
            return false;
        }
    }

    /**
    * 根据id获取专题内容
    */
    public function getThemeById($id){
        try{
            $sql = "select id,image,introduction,lastTime,name from ".$this->tableTheme()." where zhuanti_id='$id'";
            $data = Yii::app()->lcs_standby_r->createCommand($sql)->queryRow();
            return $data;
        }catch(Exception $e){
            return false;
        }
    }

    /**
    * 更新观点和专题相关内容
    * 
    *
    */
    public function updateViewThemeRelation($old_id,$old_v_id,$subjectids){
        try{
                ///删除旧的观点专题相关信息
                $sql = "delete from ".$this->tableRelation()." where caixun_id='".$old_id."'";
                Yii::app()->lcs_w->createCommand($sql)->execute();
                $sql = "delete from ".$this->tableRelation()." where view_id='".$old_v_id."'";
                Yii::app()->lcs_w->createCommand($sql)->execute();

                $now = date('Y-m-d H:i:s',time());
                foreach($subjectids as $theme_id){
                    ///添加新的观点专题相关信息
                    $sql = "insert into ".$this->tableRelation()." (caixun_id,view_id,theme_id,c_time,u_time) values(:caixun_id,:view_id,:theme_id,:c_time,:u_time)";
                    $cmd = Yii::app()->lcs_w->createCommand($sql);
                    $cmd->bindParam(":caixun_id", $old_id, PDO::PARAM_STR);
                    $cmd->bindParam(":view_id", $old_v_id, PDO::PARAM_STR);
                    $cmd->bindParam(":theme_id", $theme_id, PDO::PARAM_STR);
                    $cmd->bindParam(":c_time", $now, PDO::PARAM_STR);
                    $cmd->bindParam(":u_time", $now, PDO::PARAM_STR);
                    $cmd->execute();
                }
        }catch(Exception $e){
            var_dump($e->getMessage());
            Common::model()->saveLog("修改观点所属专题关系失败:".$e->getMessage(),"error","caixun_update_view_theme_relation");
            return false;
        }
    }

   /**
    * 新增运营后台的设置lcs_page_cfg
    * 
    *
    */
    public function addPageCfgInfo ($r_id,$view,$tag = 0){
        try{
		$now_time = date('Y-m-d H:i:s', time());
                $view['u_time'] = $now_time;
                $view['area_code'] = 16;
                $view['type'] = 'dapan';
                $check_page_cfg = $this->checkPageCfgInfo($r_id, $view['area_code']);
		if(!$tag && !$check_page_cfg){
		    return true;
		}
                //var_dump($check_page_cfg);die; 
                if($check_page_cfg){
                    $sql = "update ".$this->tableNamePageCfg()." set title=:title,c_time=:c_time,u_time=:u_time,sequence=:sequence,status=:status where area_code=:area_code and type=:type and relation_id=:relation_id;";    
                    
                    $cmd = Yii::app()->lcs_w->createCommand($sql);
                    $cmd->bindParam(":title", $view['title'], PDO::PARAM_STR);
                    $cmd->bindParam(":c_time", $view['c_time'], PDO::PARAM_STR);
                    $cmd->bindParam(":u_time", $view['u_time'], PDO::PARAM_STR);
                    $cmd->bindParam(":sequence", $view['sequence'], PDO::PARAM_STR);
                    $cmd->bindParam(":area_code", $view['area_code'], PDO::PARAM_STR);
                    $cmd->bindParam(":status", $view['status'], PDO::PARAM_INT);
                    $cmd->bindParam(":type", $view['type'], PDO::PARAM_STR);
                    $cmd->bindParam(":relation_id", $r_id, PDO::PARAM_INT);
                    
                    $cmd->execute();
                 } else {
                    $sql = "insert into ".$this->tableNamePageCfg()." (area_code,type,title,relation_id,sequence,status,c_time,u_time) values (:area_code,:type,:title,:relation_id,:sequence,:status,:c_time,:u_time); ";
                    
                    $cmd = Yii::app()->lcs_w->createCommand($sql);
                    $cmd->bindParam(":title", $view['title'], PDO::PARAM_STR);
                    $cmd->bindParam(":c_time", $view['c_time'], PDO::PARAM_STR);
                    $cmd->bindParam(":u_time", $view['u_time'], PDO::PARAM_STR);
                    $cmd->bindParam(":area_code", $view['area_code'], PDO::PARAM_INT);
                    $cmd->bindParam(":status", $view['status'], PDO::PARAM_INT);
                    $cmd->bindParam(":sequence", $view['sequence'], PDO::PARAM_INT);
                    $cmd->bindParam(":type", $view['type'], PDO::PARAM_STR);
                    $cmd->bindParam(":relation_id", $r_id, PDO::PARAM_INT);
                    //var_dump($cmd);die;
                    $cmd->execute();
                 }
                
        }catch(Exception $e){
            var_dump($e->getMessage());
            Common::model()->saveLog("新增观点运营后台置顶失败:".$e->getMessage(),"error","caixun_update_page_cfg_info");
            return false;
        }
    }

    /**
    * 查询运营后台的设置lcs_page_cfg
    * 
    *
    */
    public function checkPageCfgInfo ($r_id, $area_code){
        try{
                $type = 'dapan';
                $sql = "select id from ".$this->tableNamePageCfg()." where area_code=:area_code and type=:type and relation_id=:relation_id;";
                $cmd = Yii::app()->lcs_w->createCommand($sql);
                $cmd->bindParam(":area_code", $area_code, PDO::PARAM_STR);
                $cmd->bindParam(":type", $type, PDO::PARAM_STR);
                $cmd->bindParam(":relation_id", $r_id, PDO::PARAM_INT);
 
                $data = $cmd->queryRow();
                if(!$data){
                    return false;
                }
                return true;
        }catch(Exception $e){
            var_dump($e->getMessage());
            Common::model()->saveLog("新增观点运营后台置顶失败:".$e->getMessage(),"error","caixun_update_page_cfg_info");
            return false;
        }
    }

}
