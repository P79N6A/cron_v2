<?php
/**
 * Created by PhpStorm.
 * User: jichao3
 * Date: 15-2-2
 * Time: 下午1:55
 */

class PackageSubscription extends CActiveRecord {

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    //观点包订阅表
    public function tableName(){
        return TABLE_PREFIX .'package_subscription';
    }

    //观点包订阅历史表
    public function tableNameHistory(){
        return TABLE_PREFIX .'package_subscription_history';
    }

    //观点包表
    public function tableNamePackage(){
        return TABLE_PREFIX .'package';
    }

    /**
     * 保存观点包订阅信息
     * @param array $data
     */
    public function savePackageSub($data){
        $res = Yii::app()->licaishi_w->createCommand()->insert($this->tableName(), $data);
        if($res==1){
            return Yii::app()->licaishi_w->getLastInsertID();
        }else{
            return $res;
        }
    }

    /**
     * 修改观点包订阅信息
     * @param array $columns
     * @param string $conditions
     * @param array $params
     */
    public function updatePackageSub($columns, $conditions='', $params=array()){
        return Yii::app()->licaishi_w->createCommand()->update($this->tableName(),$columns,$conditions,$params);

    }

    /**
     * 保存观点包订阅历史信息
     * @param array $data
     */
    public function savePackageSubHis($data){
        $res = Yii::app()->licaishi_w->createCommand()->insert($this->tableNameHistory(), $data);
        if($res==1){
            return Yii::app()->licaishi_w->getLastInsertID();
        }else{
            return $res;
        }
    }

    /**
     * 获取订阅信息
     * @param $uid 用户id
     * @param $pkg_id 观点包ID（数组）
     * @param $check_endTime //是否检查结束时间
     * @return mixed
     */
    public function getPackageSubscriptionInfo($uid, $pkg_id = array(), $check_endTime = false) {
		$uid = intval($uid);
        $pkg_id = (array) $pkg_id;
		$now = time();

        if(!empty($pkg_id)){
            foreach($pkg_id as $k=>$v){
                if(empty($v)){
                    unset($pkg_id[$k]);
                }
            }
        }

        if(empty($pkg_id)){
            return array();
        }

		$db_r =  Yii::app()->lcs_r;
        $sql = "select id,uid,pkg_id,c_time,u_time,end_time from ".$this->tableName()." where uid=$uid and pkg_id in (".implode(",",$pkg_id).")";

        $subs = $db_r->createCommand($sql)->queryAll();
        $data = array();
        if(!empty($subs)){
            foreach ($subs as $val) {
				//检查结束时间
				if ($check_endTime == TRUE && strtotime($val['end_time']) < $now) {
					continue; 
				}

				$data[$val['pkg_id']] = $val;
            }
        }
        return $data;
    }

	public function SaveSubscription($uid, $pkg_id, $order_no, $amount) {
		$uid = intval($uid);
        $pkg_id = intval($pkg_id);
        $amount = intval($amount)>0 ? intval($amount) : 1;
        $counter = 0;
        
        $sub_info_history = $this->getSubByOrderNo($order_no);
        if($sub_info_history) {
            return $counter;
        }

        $sub_info = $this->getPackageSubscriptionInfo($uid,$pkg_id);
        $sub_info = isset($sub_info[$pkg_id]) ? $sub_info[$pkg_id] : array();
        $start_time = date("Y-m-d H:i:s");
        $end_time = date("Y-m-d H:i:s",time()+($amount*30*86400));
        //$is_new_sub = 0; //是否新增订阅用户
        if(empty($sub_info)){
            $counter = Yii::app()->licaishi_w->createCommand()->insert($this->tableName(),array(
                "pkg_id" => $pkg_id,
                "uid" => $uid,
                "c_time" => date("Y-m-d H:i:s"),
                "u_time" => date("Y-m-d H:i:s"),
                "end_time" => $end_time
            ));
            //$is_new_sub = 1;
        }else{
            if(strtotime($sub_info['end_time']) > time()){
                $start_time = $sub_info['end_time'];
                $end_time = date("Y-m-d H:i:s",strtotime($sub_info['end_time'])+($amount*30*86400));
            }

            $counter = Yii::app()->licaishi_w->createCommand()->update($this->tableName(),array(
                "end_time" => $end_time,
                "u_time" => date("Y-m-d H:i:s")
            ),"uid=$uid and pkg_id=$pkg_id");
        }

        if($counter){
            //观点包订阅数 +1
            //($is_new_sub==1) && Package::model()->updateNum($pkg_id);
            Package::model()->updateNum($pkg_id);
            //取消观注
            if(Collect::model()->delUserCollect($pkg_id,$uid,4)){
                //关注数-1
                Package::model()->updateNum($pkg_id,"collect_num","");
            }

            //添加订阅历史
            $history = array(
                "pkg_id" => $pkg_id,
                "uid" => $uid,
                "order_no" => $order_no,
                "amount" => $amount,
                "start_time" => $start_time,
                "end_time" => $end_time,
                "status" => 1,
                "c_time" => date("Y-m-d H:i:s"),
                "u_time" => date("Y-m-d H:i:s")
            );
            Yii::app()->licaishi_w->createCommand()->insert($this->tableNameHistory(),$history);

            //add by weiguang3 20150831 订阅观点成功后给用户的通知
            //发送通知  type, pkg_id, uid, start_time,end_time
            $msg_data['type']='buyPackage';
            $msg_data['pkg_id']=$pkg_id;
            $msg_data['uid']=$uid;
            $msg_data['start_time']=$start_time;
            $msg_data['end_time']=$end_time;
            //update by weiguang3 使用新通知系统
            //Yii::app()->redis_w->rPush("lcs_common_message",json_encode($msg_data));
            Message::model()->addMessageToQueue(Message::MESSAGE_QUEUE_COMMENT,$msg_data);
        }
        return $counter;
    }
    /**
     * 取订阅历史表的数据
     * @param int $order_no
     * @return array
     */
    public function getSubByOrderNo($order_no) {
    	$db_w = Yii::app()->licaishi_w;  //为解决数据同步问题，此处访问主库
    	$sql = "select id,pkg_id,uid,order_no,amount,start_time,end_time,status,c_time from ".$this->tableNameHistory()
    	     ." where order_no=:order_no ";
    	$cmd = $db_w->createCommand($sql);
    	$cmd->bindParam(":order_no", $order_no, PDO::PARAM_INT);
    	$sub_info = $cmd->queryRow();
    	return $sub_info;
    }

    
    /**
     * 获取我的订阅列表
     * 
     * @param unknown_type $uid
     */
    public function getSubscriptionList($uid)
    {
    	$now = date('Y-m-d H:i:s');
    	$uid = intval($uid);
    	$sql = "select pkg_id from ".$this->tableName()." where uid=$uid and end_time>'$now'  order by id desc";
    	$data = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
    	if(!empty($data)){
    		$sql = "select id as pkg_id from lcs_package where id in (".implode(',',$data).") and status <0";
    		$pkg_id = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
    		if(!empty($pkg_id)){
    			$data = array_diff($data,$pkg_id);
    		}
    	}
    	return $data;
    	
    }
    
   /**
     * 获取我的某个理财师订阅列表
     * 
     * @param unknown_type $uid
     * @param unknown_type $p_uid
     */
    public function getPlannerSubscriptionList($uid,$p_uid)
    {
    	$now = date('Y-m-d H:i:s');
    	$uid = intval($uid);
    	$p_uid = intval($p_uid);
    	$sql = "select a.pkg_id from ".$this->tableName()." a,". $this->tableNamePackage() ." b where a.uid=$uid and a.end_time>'$now' and a.pkg_id=b.id and b.p_uid=$p_uid and b.status=0 order by a.id desc";
    	$data = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
	if(!$data){
		return array();
	}
	return $data;
    	
    }
    //6385751728
    
    //是否购买过观点包
    public function isSubscription($uid,$pkg_ids){
    	$uid = intval($uid);
    	$pkg_ids = (array)$pkg_ids;
    	if(!empty($pkg_ids)){
    		foreach ($pkg_ids as &$ids){
    			$ids = intval($ids);
    		}
    	}else {
    		return array();
    	}
    	$now = date('Y-m-d H:i:s');
    	$sql = "select pkg_id from ".$this->tableName()." where uid=$uid and pkg_id in (".implode(',',$pkg_ids).") and  end_time>'$now'";
    	return Yii::app()->lcs_r->createCommand($sql)->queryColumn();
    }



    public function refundSubscription($uid,$pkg_id,$order_no,$amount){
        $uid = intval($uid);
        $pkg_id = intval($pkg_id);
        $amount = intval($amount)>0 ? intval($amount) : 1;
        $counter = 0;

        $sub_info = $this->getPackageSubscriptionInfo($uid,$pkg_id);
        $sub_info = isset($sub_info[$pkg_id]) ? $sub_info[$pkg_id] : array();
        if(!empty($sub_info)){
            $end_time = date("Y-m-d H:i:s",strtotime($sub_info['end_time'])-($amount*30*86400));

            $counter = Yii::app()->licaishi_w->createCommand()->update($this->tableName(),array(
                "end_time" => $end_time,
                "u_time" => date("Y-m-d H:i:s")
            ),"uid=$uid and pkg_id=$pkg_id");
        }

        if($counter){
            //观点包订阅数 -1
            Package::model()->updateNum($pkg_id,"sub_num","");

            //更新订阅历史
            Yii::app()->licaishi_w->createCommand()->update($this->tableNameHistory(),array(
                "status" => -1,
                "u_time" => date("Y-m-d H:i:s")
            ),"order_no='$order_no'");
        }

        return $counter;
    }
    
    /**
     * 获取用户购买的观点包
     */
    public function getUserPackages($uid, $page = 1, $num = 15, $max_time = null, $since_time = null)
    {
        $start = CommonUtils::fomatPageParam($page,$num);
        $sql = "SELECT * FROM " . $this->tableName() . " WHERE uid={$uid} AND end_time>=' " . date('Y-m-d H:i:s') . "'";
        $count_sql = "SELECT COUNT(*) FROM " . $this->tableName() . " WHERE uid={$uid} AND end_time>='" . date('Y-m-d H:i:s') . "'";
        if ($max_time) {
            $sql .= " AND u_time<='{$max_time}'";
            $count_sql .= " AND u_time<='{$max_time}'";
        } elseif ($since_time) {
            $sql .= " AND u_time<'{$since_time}'";
            $count_sql .= " AND u_time<'{$since_time}'";
        }
        $count = Yii::app()->lcs_r->createCommand($count_sql)->queryScalar();
        $sql .= " ORDER BY u_time DESC LIMIT {$start}, {$num}";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $result = CommonUtils::getPage($data, $page, $num, $count);
        return $result;
    }

    /**
     * 获取用户购买的全部观点包
     */
    public function getUserPackagesAll($uid,$max_time = null, $since_time = null)
    {

        $sql = "SELECT * FROM " . $this->tableName() . " WHERE uid={$uid}";
        if ($max_time) {
            $sql .= " AND u_time<='{$max_time}'";

        } elseif ($since_time) {
            $sql .= " AND u_time<'{$since_time}'";
        }
        $sql .= " ORDER BY u_time DESC";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $data;
    }

    /**
     * 获取用户我的服务购买的某个理财师的观点包
     */
    public function getUserPlannerPackages($uid, $p_uid)
    {
    	$now = date('Y-m-d H:i:s');
        $sql = "SELECT a.* FROM " . $this->tableName() . " a,". $this->tableNamePackage() ." b WHERE a.uid={$uid} AND a.end_time>='$now' and a.pkg_id=b.id and b.p_uid=$p_uid and b.status=0";
        $sql .= " ORDER BY a.u_time DESC";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if(!$data){
		return array();
	}
	return $data;
    }

    /**
     * 获取用户我的服务购买的某个理财师的观点包
     */
    public function getPlannerPackages($pkg_id)
    {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT uid,end_time from ". $this->tableName() ."  where pkg_id=$pkg_id and  end_time>='$now'";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if(!$data){
            return array();
        }
        return $data;
    }

} 
