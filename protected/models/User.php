<?php

/**
 * 获取用户相关信息
 *
 */
class User extends CActiveRecord {

    ///替换头像中的http为https,ios无法显示http协议的头像
    public static $https_host = array("thirdwx.qlogo.cn","www.sinaimg.cn");

    public $ranking_level_info = [
        "0"  => ["level" => 0, "name" => "草根", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/01481168660.jpg", "pay_min" => 0, "pay_max" => 0], // 默认值，不用处理
        "1"  => ["level" => 1, "name" => "温饱", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/11481168811.jpg", "pay_min" => 0, "pay_max" => 100],
        "2"  => ["level" => 2, "name" => "小康", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/21481168853.jpg", "pay_min" => 100, "pay_max" => 500],
        "3"  => ["level" => 3, "name" => "小资", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/31481168873.jpg", "pay_min" => 500, "pay_max" => 2000],
        "4"  => ["level" => 4, "name" => "小富", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/41481168895.jpg", "pay_min" => 2000, "pay_max" => 5000],
        "5"  => ["level" => 5, "name" => "地主", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/51481168918.jpg", "pay_min" => 5000, "pay_max" => 10000],
        "6"  => ["level" => 6, "name" => "土豪", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/61481168942.jpg", "pay_min" => 10000, "pay_max" => 20000],
        "7"  => ["level" => 7, "name" => "富豪", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/71481168958.jpg", "pay_min" => 20000, "pay_max" => 50000],
        "8"  => ["level" => 8, "name" => "大富豪", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/81481168983.jpg", "pay_min" => 50000, "pay_max" => 100000],
        "9"  => ["level" => 9, "name" => "首富", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/91481169008.jpg", "pay_min" => 100000, "pay_max" => 2000000],
        "10" => ["level" => 10, "name" => "财神爷", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/A1481169229.jpg", "pay_min" => 2000000, "pay_max" => -1],
    ];


	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}


	public function userIndexTable() {
		return TABLE_PREFIX.'user_index';
	}

	public function tableNameChannel(){
	    return TABLE_PREFIX.'user_channel';
    }
    public function tableNameEvaluate(){
        return TABLE_PREFIX . 'user_evaluate';
    }



	/**
     * 根据用户id 获取对应的用户表.
     * @param source: lcs_uid(default),w_uid, s_uid
     */
	public function tableNameByUid($uid) {
		if($uid) {
			return TABLE_PREFIX.'user_'. substr($uid, -1);
		}
		return false;
	}
	/**
     * get uid by s_uid or w_uid 
     * @param string key: 'w_uid' or 's_uid'
     * @param int val:
     * @return int uid.
     */
	public function getUidBySuid($s_uid) {
		$cmd =  Yii::app()->lcs_r->createCommand();
		$uid = $cmd->select('id')->from($this->userIndexTable())->where('s_uid='.$s_uid)->queryScalar();
		return $uid ? $uid : false;
	}

    /**
     * 根据s_uid查用户的uid.
     * @param array $uids
     * @return array
     */
    public function getUidBySuids($s_uids) {
        $return = array();
        $s_uids = (array)$s_uids;
        if(empty($s_uids)) {
            return $return;
        }
        $sql = "select id, s_uid from lcs_user_index where s_uid in (". implode(',', $s_uids) .")";
        $u_list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if($u_list) {
            foreach ($u_list as $vals) {
                $return[$vals['s_uid']] = $vals['id'];
            }
        }
        return $return;
    }

	/**
	 * 根据uid查用户的s_uid.
	 * @param array $uids
	 * @return array
	 */
	public function getSuidByUids($uids) {
		$return = array();
		$uids = (array)$uids;
		if(empty($uids)) {
			return false;
		}
		$sql = "select id, s_uid from lcs_user_index where id in (". implode(',', $uids) .")";
		$u_list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		if($u_list) {
			foreach ($u_list as $vals) {
				$return[$vals['id']] = $vals['s_uid'];
			}
		}
		return $return;
	}

	//根据uid获取用户信息
	public function getUserInfoByUid($uid) {
		$sql = "select uid,phone as w_uid,phone,s_uid,name,image,ranking_lv,name as wb_name,image as wb_image,wx_name,wx_image,ind_id,client_token,is_first_login,pact,u_time,r_time from ".$this->tableNameByUid($uid)." where uid=$uid AND status=0";
        //$sql = "select uid,phone as w_uid,phone,s_uid,name,image,name as wb_name,image as wb_image,ind_id,is_first_login,pact,u_time,r_time from ".$this->tableNameByUid($uid)." where uid=$uid AND status=0";
		$user_info = Yii::app()->lcs_r->createCommand($sql)->queryRow();
		if(empty($user_info)){
			$user_info = Yii::app()->lcs_r->createCommand($sql)->queryRow();
		}
		if ($user_info['w_uid']) {
            if (empty($user_info['name'])) {
                $user_info['name'] = $user_info['wx_name'];
            }
            if (empty($user_info['image'])) {
                $user_info['image'] = $user_info['wx_image'];
            }
            $user_info['name'] = "L{$user_info['ranking_lv']}{$this->ranking_level_info[$user_info['ranking_lv']]['name']}_{$user_info['name']}";
            $user_info['image'] = !empty($this->ranking_level_info[$user_info['ranking_lv']]['image']) ? $this->ranking_level_info[$user_info['ranking_lv']]['image'] : $user_info['image'];

			$user_info['phone'] = $user_info['w_uid'] = CommonUtils::decodePhoneNumber($user_info['w_uid']);
		}
		return $user_info;
	}

    /**
     * 获取用户的来源
     * @param $uids
     * @return array|null
     */
	public function getUserSource($uids){

        $return = array();
        $uids = (array)$uids;
        if(empty($uids)) {
            return null;
        }
        $sql = "select uid, channel, channel_id, channel_uid from ".$this->tableNameChannel()." where uid in (". implode(',', $uids) .")";
        $u_list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if($u_list) {
            foreach ($u_list as $vals) {
                $return[$vals['uid']] = $vals;
            }
        }
        return $return;

    }
    //根据uid获取用户昵称
    public function getUserNameByUid($uids){
        if(empty($uids)){
            return false;
        }
        $uids=(array)$uids;
        $sql = "select id,name from ".$this->userIndexTable()." where id in(".implode(',',$uids).')';
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }
    //根据uid获取用户信息
    public function getUserInfo($uid) {
        if(empty($uid)){
            return false;
        }
        $sql = "select uid,s_uid,w_uid,phone,name,name_u_time,gender,image,wb_name,wb_image,wx_unionid,wx_open_uid,wx_public_uid,wx_name,wx_image,source,ind_id,client_token,is_first_login,pact,status,ranking_lv,c_time,client_time,u_time,r_time,cert_id from ".$this->tableNameByUid($uid)." where uid=$uid AND status=0";
        $user_info = Yii::app()->lcs_standby_r->createCommand($sql)->queryRow();
        return $user_info;
    }

    public function getUsersAll($i=0,$page=1,$num=1000){
        $db_r = Yii::app()->lcs_standby_r;
        $sql_count = 'SELECT count(id)  FROM lcs_user_'.$i.' where status=0';
        $cmd_count = $db_r->createCommand($sql_count);
        $total = $cmd_count->queryScalar();
        $page = $page < 1 ? 1 : $page;
        $num = $num < 1 ? 1000 : $num;
        $offset = ($page - 1) * $num;
        $pages=ceil($total / $num);
        $sql = "select uid,s_uid,w_uid,phone,name,name_u_time,gender,image,wb_name,wb_image,wx_unionid,wx_open_uid,wx_public_uid,wx_name,wx_image,source,ind_id,client_token,is_first_login,pact,status,ranking_lv,c_time,client_time,u_time,r_time,cert_id from lcs_user_".$i." where status=0 order by c_time desc  limit :offset, :limit";
        $cmd= $db_r->createCommand($sql);
        $cmd->bindParam(':offset', $offset, PDO::PARAM_INT);
        $cmd->bindParam(':limit', $num, PDO::PARAM_INT);
        $data = $cmd->queryAll();
        $data['data']=$data;
        $data['pages']=$pages;
        return $data;

    }
    //获取用户认证信息
    public function getCertInfo($phone,$status=0){
        $db_r = Yii::app()->lcs_standby_r;
        if(empty($phone)){
            return false;
        }
        $sql_data = 'select id,real_name,phone,id_number,result,status,level,u_time,c_time from '.$this->tableNameEvaluate()." where  phone=".$phone." and status=".$status;
        $cmd = $db_r->createCommand($sql_data);
        $data = $cmd->queryRow();
        return $data;
    }

    /**
     * 获取用户信息
     * @param   array   $uids用户uid
     */
    public function getUserPhone($uids){
        $res = array();
        if(count($uids)>0){
            $sql = "select id,s_uid,phone,name from ".$this->userIndexTable()." where id in (".implode(',',$uids).")";
            $data = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
            if($data){
                foreach($data as $item){
                    $res[$item['id']] = $item;
                }
            }
        }
        return $res;
    }

    /**
     * 根据某一个手机号获取uid __im系统
     * @param type $phone
     * @return type
     */
    public function getPhoneUidIm($phone)
    {
        if(empty($phone)){
            return [];
        }
        $sql = "select id from ".$this->userIndexTable() . " where phone in (".implode(",", $phone).");";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $result = $cmd->queryAll();
        $uids = array();
        foreach ($result as $uid) {
            $uids[] = $uid['id'];
        }
        return $uids;
    }
    	/**
	 * 根据用户名获取用户昵称和头像
	 *
	 * @param unknown_type $uid
	 */
	public function getUserInfoById($uid)
	{
		$uid = (array) $uid;
		$uid_array = array();
		foreach ($uid as $val) {
			$val = intval($val);
			$last = substr($val, -1);
			$uid_array[$last][] = $val;
		}
		$return = array();

		if (sizeof($uid_array) > 0) {
			$sql = '';
			foreach ($uid_array as $key => $val) {
				$sql .= "select uid,s_uid,name,image,ranking_lv,wx_name,wx_image from " . TABLE_PREFIX . "user_$key where uid in (" . implode(',', $val) . ") union ";
			}
			$sql = substr($sql, 0, -6);
			$rows = Yii::app()->lcs_r->createCommand($sql)->queryAll();

			$channel_user_map = $this->getChannelUserMapByUids($uid);

			if (is_array($rows)) {
				foreach ($rows as $val) {
					if (empty($val['name'])) {
						$val['name'] = $val['wx_name'];
					}
					if (empty($val['image'])) {
						$val['image'] = $val['wx_image'];
					}

					if (!isset($channel_user_map[$val['uid']])) {
						$val['name'] = "L{$val['ranking_lv']}{$this->ranking_level_info[$val['ranking_lv']]['name']}_{$val['name']}";
                        if(empty($val['image'])){
						    $val['image'] = !empty($this->ranking_level_info[$val['ranking_lv']]['image']) ? $this->ranking_level_info[$val['ranking_lv']]['image'] : $val['image'];
                        }
					}
					if(!strstr($val['image'],'https')){
		                foreach(self::$https_host as $pic_host){
		                    if(strpos($val['image'],$pic_host)>0){
		                        $val['image'] = str_replace("http","https",$val['image']);
		                    }
		                }
		            }

					$return["$val[uid]"] = $val;
				}
			}
		}
		return $return;
    }
    	// 渠道用户信息map
	public function getChannelUserMapByUids($uids)
	{
		$uids = (array) $uids;

		$sql = "SELECT uid,channel,channel_id,channel_uid FROM {$this->tableNameChannel()} WHERE uid IN (" . implode(",", $uids) . ")";
		$res = Yii::app()->lcs_r->createCommand($sql)->queryAll();

		$map = [];
		foreach ($res as $row) {
			$map[$row['uid']] = $row;
		}
		return $map;
	}
}
