<?php
/**
 * 用戶权限操作数据对象
 * @author 李寧
 * @date 2018/01/22
 */ class UserAuth extends CActiveRecord{

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 用戶權限表
     * @return string
     */
    public function tableName()
    {
        return TABLE_PREFIX.'user_auth';
    }
    /**
     * 用户语言权限表
     */
    public function tableNameAudio(){
        return TABLE_PREFIX.'audio_subscription';
    }
    /**
	 * 獲取用戶權限列表
     */
    public function getAuthList($name)
    {
        $db_r = Yii::app()->lcs_r;
		$cmd = $db_r->createCommand('SELECT id,phone,auth,weixin,name,status,c_time,u_time FROM '.$this->tableName().' where name=:name and status = 0;');
		$cmd->bindParam(':name',$name, PDO::PARAM_INT);
		return $cmd->queryAll();
    }
    /**
     * 獲取用戶權限列表(手机号)
     */
    public function getAuthByPhone($phone)
    {
        $db_r = Yii::app()->lcs_r;
        $cmd = $db_r->createCommand('SELECT id,phone,auth,weixin,name,status,c_time,u_time FROM '.$this->tableName().' where phone=:phone and status = 0;');
        $cmd->bindParam(':phone',$phone, PDO::PARAM_INT);
        return $cmd->queryRow();
    }
    /**
     * 添加用戶權限
     */
    public function addUserAuth($data){
    	$db_w = Yii::app()->licaishi_w;
        if(!empty($data)){
		  return $db_w->createCommand()->insert($this->tableName(), $data);
        }else{
          return 0;
        }
    }
    /**
     * 修改用戶權限
     */
    public function updateUserAuth(){
    	Yii::app()->licaishi_w->createCommand()->update($this->userIndexTable($uid), $user_info, 'id=' . $uid);
    }
    /**
     * 获取用户权限
     */
    public function getAllUserAuthPhone(){
        $sql = "SELECT id,phone,auth,u_time,`name` FROM ".$this->tableName()." where status = 0;";
        // echo $sql;
        // die();
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $temp = [];
        foreach($data as $key=>$value){
            $auth = json_decode($value['auth'],true);
            if(empty($auth)){
                unset($data[$key]);
            }
            foreach($auth as $k=>$v){
                $count = count($auth);
                $temp[] = $v['type'];
                if(($count==$k-1) && !in_array(4,$temp)){
                    unset($data[$key]);
                }
                //判断权限时间
                $ctime = 2592000 * $v['zhen'];
                $start_time = strtotime($value['u_time']);
                //echo $start_time+$ctime."==";
                //echo time()."\n";
                if($v['type']==4 &&($start_time + $ctime) < time()){
                    //echo 123;
                    unset($data[$key]);
                }
                if($v['type'] != 4){
                    unset($data[$key]);
                }
                $temp = [];
            }
        }
        foreach ($data as $key => $value) {
            $temp[] = CommonUtils::encodePhoneNumber($value['phone']);
        }
        return $temp;
    }
    public function getPlannerUis($p_uid){
        $sql = "select uid from ".$this->tableNameAudio()." where end_time>now() and p_uid={$p_uid}";
        $uids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        return $uids;
    }
}
