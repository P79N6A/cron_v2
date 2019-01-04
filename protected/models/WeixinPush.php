<?php
/**
 * 微信模板推送
 * Date: 2017/10/09
 */

class WeixinPush extends CActiveRecord {

	const PUSH_TYPE_INIT = 0;  //待发送
	const PUSH_TYPE_SEND = 1;  //已发送

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 获取模板消息
     */  
    public static function getTemplate(){
        $template = array('85ovFXY9OE0yXO5PQQd-CJ2FJ3SchIrCQZi6Y_FuaxM'=>'24','6XlSC7VTZExyQxt9Tc_GOcT-721nP090AaEgPFbAsDI'=>'23','r2oZrfIHYHYSihCXWap_E7Tnm50ByysxzuVWggABQ6k'=>'22','Image'=>'21');
        return $template;
    }

    public function tableName(){
        return 'lcs_wx_push';
    }
    

    /**
     * @return mixed
     */
    public function getPushByStatus($status = 0){
        $now = date('Y-m-d H:i:s');
        $sql = "select id,title,content,template_id,operate_uid,send_time,send_type,account_uid,account_type,is_push,push_type,wechat,c_time,u_time from ".$this->tableName()
            . " where is_push=".$status." and send_time<='".$now."' order by send_time asc";
        $cmd = Yii::app()->lcs_r->createCommand($sql);

        return $cmd->queryAll();
    }
    /**
     * 设为已发送
     * @param unknown $id
     * @return boolean|unknown
     */
    public function setPushState($ids) {
    	if (empty($ids)) {
    		return false;
    	}
    	$db_w = Yii::app()->lcs_w;
    	$data = array(
    		'is_push' => 1,
    		'u_time' => date('Y-m-d H:i:s')
    	);
    	$rs = $db_w->createCommand()->update($this->tableName(), $data, "id in (" . implode(',', $ids).")" );
    	return $rs;
    }

}
