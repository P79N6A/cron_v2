<?php

/**
  服务相关
 */
class Service extends CActiveRecord {

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return TABLE_PREFIX . "service";
	}

	public function tableNameProduct() {
		return TABLE_PREFIX . "service_product";
	}

    public function tableNamePkg(){
		return TABLE_PREFIX . "package_subscription";
    }

    public function tableNameService(){
		return TABLE_PREFIX . "service_buy";
    }

	/**
     * 获取开通vip服务的观点包
	 */
	public function getVipPackage($pkg_ids) {
        $sql = "select service_id,sp_content_id from ".$this->tableNameProduct()." where sp_type=1 and sp_content_id in (".implode(',',$pkg_ids).")";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $res = array();
        if(!empty($data)){
            foreach($data as $item){
                $res[$item['sp_content_id']] = $item['service_id'];
            }
        }
        return $res;
    }

    public function updatePkg(){
        $sql = "update ".$this->tableNamePkg()." set c_time=localtime(),u_time=localtime() where c_time='0000-00-00 00:00:00'";
        Yii::app()->lcs_w->createCommand($sql)->execute();
    }

    public function getPkgSub($time,$page,$num){
        $page = $page<=0?1:$page;
        $skip = ($page-1)*$num;
        $end_time = date("Y-m-d H:i:s",time());
        $sql = "select pkg_id,uid,end_time from ".$this->tableNamePkg()." where u_time>='$time' and end_time>='$end_time' order by id desc limit $skip,$num";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $data;
    }

    /**
     * 更新用户的订阅时间
     */
    public function updateUserServiceSub($uid,$sp_id,$time){
        if($uid =='171429556'){
            var_dump($uid,$sp_id,$time);
        }
        $sql = "select count(*) from ".$this->tableNameService()." where uid='$uid' and service_id='$sp_id'";
        $count = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        if(empty($count)){
            $sql = "insert into ".$this->tableNameService()."(service_id,end_time,uid,c_time,u_time) values('$sp_id','$time','$uid',localtime(),localtime())";
        }else{
            $sql = "update ".$this->tableNameService()." set end_time='$time', u_time=localtime() where uid='$uid' and service_id='$sp_id' and end_time<'$time'";
        }
        Yii::app()->lcs_w->createCommand($sql)->execute();
    }
    /**
     * 获取服务是否开通
     * @param $p_uid
     * @return mixed
     */
    public function getIsService($p_uid,$vip=1,$service_type=1)
    {
        $where = '';
        if ($vip==1){
            $where = ' and service_type=:service_type';
        }
        $res = array();
        if ($p_uid > 0) {
            $sql = "select sp_type,service_id,sp_content_id,service_type,url from ".$this->tableName()." s left join ".$this->tableNameProduct()." sp on s.id=sp.service_id  where s.p_uid=:p_uid and service_status=1 and sp_status=1 and audit_status=2".$where.' order by sp_type desc';
            $cmd = Yii::app()->lcs_r->createCommand($sql);
            $cmd->bindParam(':p_uid',$p_uid,PDO::PARAM_STR);
            $cmd->bindParam(':service_type',$service_type,PDO::PARAM_STR);
            $res = $cmd->queryAll();
        }
        return $res;
    }
}
