<?php

/**
 * 短信消息日志
 * @author lixiang23 <lixiang23@staff.sina.com.cn>
 * @copyright (c) 20161107
 */
class Sms extends CActiveRecord {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return TABLE_PREFIX.'sms_log';
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
     * 新增短信日志
     */
    public function saveSmsLog($data) {
        $sql = "insert into " . $this->tableName() . " (mobiles,channel,content,send_time,status,c_time,u_time) values"
                . "(:mobiles,:channel,:content,:send_time,:status,:c_time,:u_time)";
        
        if(empty($data['send_time'])){
            $data['send_time']="1990-03-01 00:00:00";
        }
 
        $cmd = $this->getDBW()->createCommand($sql);
        $current_time=date("Y-m-d H:i:s",time());
        
        $cmd->bindParam(':mobiles', $data['mobiles'], PDO::PARAM_STR);
        $cmd->bindParam(':channel', $data['channel'], PDO::PARAM_INT);
        $cmd->bindParam(':content', $data['content'], PDO::PARAM_STR);
        $cmd->bindParam(':send_time', $data['send_time'], PDO::PARAM_STR);
        $cmd->bindParam(':status', $data['status'], PDO::PARAM_INT);
        $cmd->bindParam(':c_time', $data['c_time'], PDO::PARAM_STR);
        $cmd->bindParam(':u_time', $current_time, PDO::PARAM_STR);
        $res = $cmd->execute();
        if ($res) {
            $last_inser_id = $this->getDBW()->getLastInsertID();
            return $last_inser_id;
        }
        return $res;
    }

    /**
     * 更新短信日志
     */
    public function updateSmsLog($id, $data) {
        $data['u_time'] = date('Y-m-d H:i:s');
        return $this->getDBW()->createCommand()->update($this->tableName(), $data, 'id=:id', array(':id' => $id));
    }
    
    /**
     * 获取延迟需要发送的短信
     */
    public function getDelaySms(){
        $sql="select id as sms_log_id,mobiles,channel,content,send_time,status,times from ".$this->tableName()." where send_time<'".date("Y-m-d H:i:s",time())."' and status=-2";
        $res=$this->getDBR()->createCommand($sql)->queryAll();
        return $res;
    }

}
