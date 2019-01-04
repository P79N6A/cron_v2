<?php
/**
 * 观点包基本信息数据库访问类
 * User: zwg
 * Date: 2015/5/18
 * Time: 18:06
 */

class Package extends CActiveRecord {


    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName(){
        return TABLE_PREFIX .'package';
    }

    public function tableNameSubscription(){
        return TABLE_PREFIX .'package_subscription';
    }

    //观点包订阅表
    public function tableNameSub(){
        return TABLE_PREFIX .'subscription';
    }

    public function tableNameCollect(){
        return TABLE_PREFIX . 'collect';
    }
    
    public function tableNameSubscriptionHistory(){
        return TABLE_PREFIX . 'package_subscription_history';
    }

    /**
     * 根据理财师id获取指定收费类型的观点包id集合
     * @param  int  $p_uid        理财师id
     * @param  integer $pkg_fee_type 收费类型 1收费 -1免费 0所有
     * @return array                观点包id集合
     */
    public function getPackageIdsByPuid($p_uid, $pkg_fee_type=0) {
        $where = "p_uid={$p_uid} and status=0";
        if ($pkg_fee_type == 1) {
            $where .= " and subscription_price>0";
        } elseif ($pkg_fee_type == -1) {
            $where .= " and subscription_price=0";
        } else {
        }

        $sql = "select id from {$this->tableName()} where {$where}";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        if (!empty($res)) {
            return $res;
        } else {
            return [];
        }
    }

    /**
     * 获取观点包详情
     * @param $ids
     * @param null $fields
     * @return mixed
     */
    public function getPackageInfoByIds($ids, $fields=null){
        $select='id';
        if(!empty($fields)){
            $select = is_array($fields)?implode(',',$fields): $fields;
        }
        $ids = (array)$ids;
        $sql = 'select '.$select.' from '.$this->tableName().' where id in ('.implode(',',$ids).');';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        return $cmd->queryAll();
    }


    /**
     * 获取观点包的订阅信息
     * @param $start_time
     * @param $end_time
     * @return mixed
     */
    public function getPackageSubscriptionInfo($start_time, $end_time){
        $cdn = '';
        if(!empty($start_time)){
            $cdn .= ' AND end_time>=:start_time';
        }
        if(!empty($end_time)){
            $cdn .= ' AND end_time<:end_time';
        }
        $sql = 'SELECT pkg_id, uid, end_time FROM '.$this->tableNameSubscription().' WHERE 1=1 '.$cdn.';';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        if(!empty($start_time)){
            $cmd->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        }
        if(!empty($end_time)){
            $cmd->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        }
        return $cmd->queryAll();
    }


    /**
     * 根据观点包ID获取观点包详情
     * @param array $ids
     */
    public function getPackagesById($ids,$use_cache=true){

        $ids = (array)$ids;
        if(empty($ids)){
            return array();
        }
        $ids = array_unique($ids);
        $return = array();

        //缓存没取到去数据库取
        if(sizeof($ids) > 0){

            $sql = "SELECT id,id as pkg_id,p_uid,title,summary,image,tags,view_num,sub_num,collect_num,v_id,c_time,u_time,charge_time,subscription_price FROM ".$this->tableName().
                " WHERE id in(";
            foreach ($ids as $val){
                $sql .= intval($val).',';
            }

            $sql = substr($sql,0,-1).') AND status=0';
            $cmd =  Yii::app()->lcs_r->createCommand($sql);
            $packages = $cmd->queryAll();

            if(is_array($packages) && sizeof($packages)>0){
                foreach ($packages as $vals){
                    $return[$vals['id']] = $vals;
                }
            }
        }

        return $return;
    }

    public function getPackageRecommandList($day=30,$limit=20){
        $day = intval($day) > 0 ? intval($day) : 30;
        $limit = intval($limit) > 0 ? intval($limit) : 20;

        $_date = new DateTime();
        $_date->sub(new DateInterval("P".$day."D"));
        $date = $_date->format("Y-m-d");

        $sql = "SELECT `pkg_id`,COUNT(`id`) AS `num` FROM ".$this->tableNameSub()." WHERE `c_time`>='".$date."' GROUP BY `pkg_id` ORDER BY `num` DESC,`id` LIMIT ".$limit;
        $result = Yii::app()->lcs_r->createCommand($sql)->queryAll();

        return $result;
    }

    /**
     * 获取指定结束日期的订阅列表
     * @param $date
     * @return mixed
     */
    public function getPackageSubUserList($btime,$etime){
        return Yii::app()->lcs_r->createCommand("select pkg_id,uid from ".$this->tableNameSubscription()." where end_time>='$btime' and end_time<='$etime'")->queryAll();
    }

    /**
     * 获取所有观点包
     * @return mixed
     */
    public function getAllPackages()
    {
        $sql = "SELECT id as pkg_id,c_time FROM " . $this->tableName();
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }

    /**
     * 5天没发收费观点的理财师的id
     */
    public function getNoViewPuid() {
        $now = date('Y-m-d H:i:s');
        $start_time = date('Y-m-d H:i:s', strtotime("-6 days"));
        $end_time = date('Y-m-d H:i:s', strtotime("-5 days"));

        $sql = "select p_uid from ". $this->tableName()
            ." where subscription_price>0 and charge_time<='".$now ."'
    	     		  and (view_time>='". $start_time ."' and view_time<='".$end_time ."')";

        $db_r = Yii::app()->lcs_r;
        return $db_r->createCommand($sql)->queryColumn();
    }

    public function getNoViewUpdatedPuids($days)
    {
        $now = date('Y-m-d H:i:s');
        $today_timestamp = strtotime('today');
        $days_before = date('Y-m-d', strtotime("-{$days} days", $today_timestamp));
        $days_one_more_before = date('Y-m-d', strtotime("-".($days +1)." days", $today_timestamp));
        $sql = "SELECT p_uid FROM " . $this->tableName() . " WHERE id in ( SELECT pkg_id FROM " . $this->tableNameSubscription()
            ." WHERE end_time>'{$now}' ) AND "
            . "((charge_time<='{$days_before}' AND view_time<='{$days_before}' AND view_time>'{$days_one_more_before}')"
            . " OR (view_time='0000-00-00 00:00:00' AND charge_time<='{$days_before}' AND charge_time>'{$days_one_more_before}'))";
        $result = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        return $result;
    }

    /**
     *获取关注观点包的用户uid
     */
    public function getCollectUid($pkg_id){
        $pkg_id = intval($pkg_id);
        return Yii::app()->lcs_r->createCommand("select uid from ".$this->tableNameCollect()." where type=4 and relation_id=$pkg_id")->queryColumn();
    }

    /**
     *获取购买观点包的用户uid
     */
    public function getSubscriptionUid($pkg_id){
        $pkg_id = intval($pkg_id);
        return Yii::app()->lcs_r->createCommand("select uid from ".$this->tableNameSubscription()." where pkg_id=$pkg_id and end_time>='".date("Y-m-d H:i:s")."'")->queryColumn();
    }

    //添加收藏
    public function saveUserCollect($uid,$pkg_id,$type=4)
    {
        $db_w = Yii::app()->lcs_w;
        $now_time = date('Y-m-d H:i:s');
        //$data = array('uid'=>$uid,'type'=>$type,'relation_id'=>$pkg_id,'c_time'=>$now_time,'u_time'=>$now_time);
        $sql = "insert into ".$this->tableNameCollect()." (uid, type, relation_id, c_time, u_time) values "
             ." (".$uid.", ".$type.", ".$pkg_id.", '".$now_time  ."','".$now_time ."'  ) ON DUPLICATE KEY UPDATE u_time='".$now_time."'";

        try {
            $rs_insert = $db_w->createCommand($sql)->execute();
            if($type == 4 && $rs_insert == 1){//观点包收藏数据+1
                $this->updateNum($pkg_id,'collect_num');
            }
        }catch(Exception $e){
            $rs_insert = false;
        }
        return $rs_insert;
    }

    /**
     * 更新观点包计数
     * @param $v_id
     * @param string $oper
     * @return mixed
     */
    public function updateNum($pkg_id,$field="sub_num",$oper="add"){
        $pkg_id = intval($pkg_id);
        $field = !empty($field) ? $field : "sub_num";

        $set_str = $oper=="add" ? "$field=$field+1" : "$field=$field-1";
        //$set_str .= ",u_time='".date("Y-m-d H:i:s")."'";
        return Yii::app()->lcs_w->createCommand("update ".$this->tableName()." set $set_str where id=$pkg_id")->execute();
    }

    /**
     * 获取理财师的所有观点包的说说数量
     *
     * @param number $p_uid
     */
    public function getAllCommentNumByPuid($p_uid) {
    	$p_uid = intval($p_uid);
    	$sql = "select sum(comment_num)  from ". $this->tableName() ." where p_uid='$p_uid'";
    	return Yii::app()->lcs_r->createCommand($sql)->queryScalar();
    }

	/**
     * 查询指定日期的服务人数
     * @param string  $stat_time
     * @param integer $pkg_id
     */
    public function getSubNum($pkg_id,$stat_date){
        $sql = "SELECT COUNT(uid) AS num FROM ". $this->tableNameSubscriptionHistory() ." WHERE pkg_id=:pkg_id and STATUS=1 AND end_time>:end_time and start_time<:start_time";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $s_time = $stat_date . '23:59:59';
        $e_time = $stat_date . '00:00:00';
        $cmd->bindParam(':pkg_id', $pkg_id, PDO::PARAM_INT);
        $cmd->bindParam(':start_time', $s_time, PDO::PARAM_STR);
        $cmd->bindParam(':end_time', $e_time, PDO::PARAM_STR);
        return $cmd->queryScalar();
    }

    /**
     * 获取截止日期所有开启收费观点包的id 
     * @param $end_date 
     */
    public function getChargePkgIds($end_date = ''){
        if($end_date == ''){
            $end_date = date('Y-m-d');
        }
        $sql = "select id from ".$this->tableName()." where `status`=0 and subscription_price>0 and charge_time < :end_date";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $end_date = $end_date." 00:00:00";
        $cmd->bindParam(':end_date', $end_date,PDO::PARAM_STR);      
        return $cmd->queryColumn();
    }

    /**
     * 更新数量
     * @param $pln_id
     * @param string $field
     * @param string $oper
     * @param int $num
     * @return mixed
     */
    public function updateNumber($pkg_id, $field = 'sub_num', $oper = "add", $num = 1) {
        $pkg_id = intval($pkg_id);
        $num = intval($num);
        $sql = "update " . $this->tableName() . " set $field=" . ($oper == 'add' ? "$field+$num" : "$field-$num") . " where id=$pkg_id";
        return Yii::app()->lcs_w->createCommand($sql)->execute();
    }

    /***
     * 获取收费观点包列表
     */
    public function getDepthPkgList($p,$num){
        $offset = ($p - 1)*$num;
        $sql = "select id,title,p_uid from ".$this->tableName()." where status=0 and subscription_price>0 order by id desc limit ".$offset.",".$num;
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }
    /**
     *获取购买观点包的用户uid
     */
    public function getSubscriptionUids($pkg_ids){
        $pkg_ids=implode(',',$pkg_ids);
        $sql="select uid from ".$this->tableNameSubscription()." where pkg_id in(".$pkg_ids.") and end_time>='".date("Y-m-d H:i:s")."' group by uid";
        return Yii::app()->lcs_r->createCommand($sql)->queryColumn();
    }
    /**
     * 更新购买表
     */
    public  function updatePkgSub($pkg_id,$uid,$data){
        if(empty($pkg_id)|| empty($uid) || empty($data)){
            return false;
        }
        $db_w =Yii::app()->lcs_w ;
        $result=$db_w->createCommand()->update($this->tableNameSubscription(), $data, 'pkg_id=:pkg_id and uid=:uid ', array(':pkg_id'=>$pkg_id,':uid'=>$uid));
        return $result>=0?true:false;
    }
    /**
     * 删除
     */
    public  function deleteCollect($relation_id,$uid,$type=4){
        if(empty($relation_id)|| empty($uid)){
            return false;
        }
        $db_w =Yii::app()->lcs_w ;
        $sql = "delete from ".$this->tableNameCollect()." where relation_id='$relation_id' and uid='$uid' and type=$type";
        $command = $db_w->createCommand($sql)->execute();
        return $command;
    }
    /**
     *获取购买观点包的用户uid
     */
    public function getCollect($relation_ids,$type=4){
        $relation_ids=implode(',',$relation_ids);
        $sql="select uid from ".$this->tableNameCollect()." where type=4 and relation_id in(".$relation_ids.") group by uid";
        return Yii::app()->lcs_r->createCommand($sql)->queryColumn();
    }
    /**
     * 获取用户创建的所有观点包
     * @param $p_uid
     * $param $status 为空返回状态为0的观点包
     */
    public function getPackageByPlanner($p_uid, $status = array()) {
        $status = (array) $status;
        $cdn = '';
        if (empty($status)) {
            $cdn = 'and status=0';
        } else {
            $cdn = 'and status in (' . implode(',', $status) . ')';
        }

        $sql = "select id,p_uid,title,summary,image,view_num,sub_num,collect_num,status,c_time,u_time,is_sale_stopt, subscription_price,comment_num
        	    from  " . $this->tableName() . " where p_uid=:p_uid " . $cdn . " order by view_time desc ";

        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(":p_uid", $p_uid, PDO::PARAM_INT);
        return $cmd->queryAll();
    }
}

