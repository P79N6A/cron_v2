<?php
/**
 * 话题
 * Date: 2015/10/29
 */

class OperateNotice extends CActiveRecord {

	const PUSH_TYPE_INIT = 0;  //待发送
	const PUSH_TYPE_SEND = 1;  //已发送
	const PUSH_TYPE_CANCEL = 2;//取消发送

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName(){
        return 'lcs_operate_notice';
    }
    

    /**
     * @return mixed
     */
    public function getNoticeByStatus($status = 0){
        $now = date('Y-m-d H:i:s');
        $last = date("Y-m-d H:i:s",strtotime("-5 minute"));
        $sql = "select id,push_type,target_url,target_id, content,phone_type,send_type,send_time,account_type,account_id,push_state,title,image,article,u_type from ".$this->tableName()
            . " where push_state=".$status." and send_time<='".$now."' and (( send_type=1 and send_time>='$last' ) or (send_type=2 and c_time>='$last') )order by send_time asc";
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
    		'push_state' => 1,
    		'u_time' => date('Y-m-d H:i:s')
    	);
    	$rs = $db_w->createCommand()->update($this->tableName(), $data, "id in (" . implode(',', $ids).")" );
    	return $rs;
    }

}
