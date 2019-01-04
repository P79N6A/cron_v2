<?php
/**
  * 用户尊享号相关model
  *
  **/
class UserSpecial extends CActiveRecord
{
    public static function model($className = __CLASS__){
        return parent::model($className);
    }

    /**
      * 用户尊享号信息表
      * @return string
      */
    public function tablename(){
        return TABLE_PREFIX . 'user_special_number';
    }

    private $special_info = '';

    /**
      * 生成用户尊享号信息并写入库里
      **/
    public function saveSpecialInfoByUid($uid){
        $special_info = array();//返回
        $sql = "select v_uid from ".$this->tableName()." where uid={$uid}";
        $special_info = Yii::app()->lcs_w->createCommand($sql)->queryRow();
        if(!$special_info){//如果不存在新增
                $special_info['v_uid'] = $this->generateSpecialNumber($uid);//生成直接返回
		return $this->addSpecialInfoByUid($uid,$special_info['v_uid']);
        }else{
		return false;
	}
    }

    public function addSpecialInfoByUid($uid,$v_uid){
	$uid = intval($uid);
	$v_uid = intval($v_uid);
	$now_time = date('Y-m-d H:i:s');
	$data = array('uid'=>$uid, 'v_uid'=>$v_uid, 'c_time'=>$now_time, 'u_time'=>$now_time);
	$db_w = Yii::app()->lcs_w;
	return $db_w->createCommand()->insert($this->tableName(), $data);
    }

    /**
      * 统一生成尊享号的方法
      **/
    public function generateSpecialNumber($uid){
        return intval($uid);//目前是直接用用户uid
    }

}
