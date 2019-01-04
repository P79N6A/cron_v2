<?php
/**
 * 用户关闭提醒.
 * User: bianjichao
 * Date: 15/5/11
 * Time: 下午1:30
 */

class MessageUserClose extends CActiveRecord {

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    //提醒用户关闭表
    public function tableName(){
        return TABLE_PREFIX .'message_user_close';
    }

    public function getCloseMessageUsers($uids,$u_type,$close_type,$client_type){
        $uids = (array)$uids;
        $u_type = intval($u_type);
        $close_type = intval($close_type);
        $client_type = intval($client_type);

        return Yii::app()->lcs_r->createCommand("select uid from ".$this->tableName()." where uid in (".implode(",",$uids).") and u_type=$u_type and close_type=$close_type and client_type=$client_type")->queryColumn();
    }

    public function filterCloseUids($uids,$u_type,$close_type,$client_type){
        $uids_close = array();
        $uids = (array)$uids;
        if(!empty($uids)){
            $uids_close = $this->getCloseMessageUsers($uids,$u_type,$close_type,$client_type);
        }

        if(!empty($uids_close)){
            $uids = array_diff($uids,$uids_close);
        }

        return array_values($uids);
    }




}