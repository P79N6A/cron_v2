<?php
/**
 * 获取理财师的潜在用户
 * Wiki: http://wiki.intra.sina.com.cn/pages/viewpage.action?pageId=98436975
 * Author: shixi_shifeng
 * Date: 2016-05-03
 */
class PotentialUser{
    
    const CRON_NO = 1111;
    //用户登录天数
    const USER_LOGIN_DAY = 30;
    //新用户的数据天数
    const NEW_USER_DAY   = 7;
    //默认获取90天数据
    const DEFAUTL_DAY    = 90;

    private $_user_login_day;
    private $_new_user_day;
    private $_default_day;

    public function __construct() {
    	    
    	$this->_user_login_day = date('Y-m-d', time() - self::USER_LOGIN_DAY * 86400);
    	$this->_new_user_day   = date('Y-m-d', time() - self::NEW_USER_DAY   * 86400);
    	$this->_default_day    = date('Y-m-d', time() - self::DEFAUTL_DAY    * 86400);
    }
    /**
     * 插入更新潜在用户数据
     */
    public function insertOrUpdatePotentialData() {
    	try {
    		
    		//从lcs_planner 表中获取 最近3个月登录的理财师
    		$p_uids = $this->_getFieldsByCdn('s_uid', 'lcs_planner', 'u_time>"'.$this->_default_day.'"');
    		//标识
			$count = 0;
			$sql = '';
			$date = date('Y-m-d H:i:s');
    		//开始迭代p_uids
    		foreach ($p_uids as $p_uid) {
    			
    			//关注理财师用户 最近3个月
				$collect_planner_uids = $this->_getFieldsByCdn('uid', 'lcs_attention', 'p_uid='.$p_uid.' and c_time>"'.$this->_default_day.'"');
				//7日内关注理财师新用户
				$new_collect_planner = $this->_getFieldsByCdn('uid', 'lcs_attention', 'p_uid='.$p_uid.' and c_time>"'.$this->_new_user_day.'"');
				
				//免费提问用户 最近3个月
				$ask_free_uids = $this->_getFieldsByCdn('uid','lcs_ask_question', 'p_uid='.$p_uid.' and status>0 and is_price=0 and c_time>"'.$this->_default_day.'"');
				//7日内免费提问新用户
				$new_ask_free = $this->_getFieldsByCdn('uid','lcs_ask_question', 'p_uid='.$p_uid.' and status>0 and is_price=0 and c_time>"'.$this->_new_user_day.'"');
				
				//获取该理财师的 最新的 3个观察包 (一般只有两个)
				$package_ids = $this->_getFieldsByCdn('id', 'lcs_package', 'p_uid='.$p_uid.' and status=0 order by u_time DESC limit 3');
				$collect_package_uids = array();	//关注用户
				$new_collect_package  = array();	//新用户
				
				foreach ($package_ids as $package_id) {
					//获取关注观点包用户 最近3个月数据
					$temp[$package_id] = $this->_getFieldsByCdn('uid', 'lcs_collect', 'type=4 and relation_id='.$package_id.' and c_time>"'.$this->_default_day.'"');
					$collect_package_uids  = array_merge_recursive($collect_package_uids, $temp[$package_id]);
					//7日内关注观点包用户
					$temp1[$package_id] = $this->_getFieldsByCdn('uid', 'lcs_collect', 'type=4 and relation_id='.$package_id.' and c_time>"'.$this->_new_user_day.'"');
					$new_collect_package  = array_merge_recursive($new_collect_package, $temp1[$package_id]);	
				}
				
				//获取该理财师 最新的 3个计划
				$plan_ids = $this->_getFieldsByCdn('pln_id', 'lcs_plan_info', 'p_uid='.$p_uid.' and status>1 order by u_time DESC limit 3');
				$collect_plan_uids = array();	//关注用户
				$new_collect_plan  = array();	//新用户
				
				foreach ($plan_ids as $plan_id) {
					//获取观察计划的用户 最近3个月数据
					$temp3[$plan_id] = $this->_getFieldsByCdn('uid', 'lcs_collect', 'type=3 and relation_id='.$plan_id.' and c_time>"'.$this->_default_day.'"');
					$collect_plan_uids = array_merge_recursive($collect_plan_uids, $temp3[$plan_id]);
					//获取7日内观察计划的新用户
					$temp4[$plan_id] = $this->_getFieldsByCdn('uid', 'lcs_collect', 'type=3 and relation_id='.$plan_id.' and c_time>"'.$this->_new_user_day.'"');
					$new_collect_plan = array_merge_recursive($new_collect_plan, $temp4[$plan_id]);
				}
				
				//获取潜在用户
				$pre_potential_customer = array_unique(array_merge_recursive($collect_planner_uids, $ask_free_uids, $collect_package_uids, $collect_plan_uids));
				if (empty($pre_potential_customer)) {
					continue;
				}
				
				//生成新用户列表
				$new_users = array_unique(array_merge($new_collect_planner,$new_ask_free, $new_collect_package, $new_collect_plan));
				
				//获取最近一个月登陆的用户
				$login_day_users = $this->_getLoginDayUser($pre_potential_customer);
				if ( empty($login_day_users) ) {
					continue;
				}

				//用户是否已经存在于lcs_planner_customer表中
				$exist_users = $this->_getFieldsByCdn('uid', 'lcs_planner_customer', 'c_type=1 and p_uid='.$p_uid);

				//更新用户组，插入用户组
				$update_users = array();
				$insert_users = array();

				foreach ($login_day_users as $uid) {
					if ( in_array($uid, $exist_users) ) {
						$update_users[] = $uid;
					} else {
						$insert_users[] = $uid;
					}
				}
				
				//更新用户日期
				if (!empty($update_users)) {
					$this->_updateData($p_uid, $update_users, $date);
				}
				//插入用户
				if (!empty($insert_users)) {
					foreach ($insert_users as $uid) {

						$sql .='('.$p_uid.','.$uid.',1,';
						//是否是新用户
						in_array($uid, $new_users) ? $t_new=1 : $t_new=0;
						$sql .= $t_new.',';
						//是否关注理财师
						in_array($uid, $collect_planner_uids) ? $t_follower=1 : $t_follower=0;
						$sql .= $t_follower.',0,0,0,0,"'.$date.'","'.$date.'"),';
						$count ++;
					}
				}
				
				//每大于100,就插入一次
				if ($count > 100) {
					$this->_insertData('lcs_planner_customer',$sql);
					$count = 0;
					$sql = '';
				}
    		}
    		
    		//最后的再插入
    		if ($count > 0) {
				$this->_insertData('lcs_planner_customer',$sql);
			}
    	} catch(Exception $e) {
    		//print_r($e->getMessage());
    		throw LcsException::errorHandlerOfException($e);
    	}
    }
    /**
     * 获取指定表里的指定信息
     * @param  string $fields 要获取的字段
     * @param  string $table 使用到的表
     * @param  string $cdn 查询的where条件 
     * @return array
     */
    private function _getFieldsByCdn($fields, $table, $cdn) {
		$db_r = Yii::app()->lcs_r;
		$data = array();
		$sql = 'select '.$fields.' from '.$table.' where '.$cdn;
		
		$res = $db_r->createCommand($sql)->queryAll();
		foreach($res as $v) {
			$data[] = $v[$fields];
		}
		return $data;
    }
	/**
	 * 插入操作
	 * @param str $table 表名
	 * @param str sql 要插入的数据
	 */
	private function _insertData($table, $sql) {
		$db_w = Yii::app()->lcs_w;
		$sql = rtrim($sql,',');
		$total_sql = 'insert into '.$table.' (p_uid,uid,c_type,t_new,t_follower,t_view,t_plan,attion,level,c_time,u_time) values '.$sql;
		$db_w->createCommand($total_sql)->execute();
	}
	/**
	 * 更新数据
	 * @param int $p_uid 理财师p_uid
	 * @param int $uid 用户uid
	 * @param str $date 当前脚本执行时间
	 */
	private function _updateData($p_uid, $uids, $date) {
		return Yii::app()->lcs_w->createCommand()->update('lcs_planner_customer',array('u_time'=>$date),'c_type=1 and p_uid=:p_uid and uid in ('.implode(',',$uids).')',array(':p_uid'=>$p_uid));
	}
	/**
	 * 获取30天内登录的用户
	 * @param array $pre_potential_customer 潜在用户
	 * @return array 30天登录用户数
	 */
	private function _getLoginDayUser($pre_potential_customer) {
		$db_r = Yii::app()->lcs_r;
		$customer_group = array();
		$login_day_users = array();
		foreach ($pre_potential_customer as $uid) {
			switch ( intval(substr($uid, -1)) ) {
				case 0:
					$customer_group[0][] = $uid;
					break;
				case 1:
					$customer_group[1][] = $uid;
					break;
				case 2:
					$customer_group[2][] = $uid;
					break;
				case 3:
					$customer_group[3][] = $uid;
					break;
				case 4:
					$customer_group[4][] = $uid;
					break;
				case 5:
					$customer_group[5][] = $uid;
					break;
				case 6:
					$customer_group[6][] = $uid;
					break;
				case 7:
					$customer_group[7][] = $uid;
					break;
				case 8:
					$customer_group[8][] = $uid;
					break;
				case 9:
					$customer_group[9][] = $uid;
					break;
			}
		}
		#循环分组用户
		foreach ($customer_group as $k=> $uids) {
			
			$sql = 'select uid from lcs_user_'.$k.' where u_time>"'.$this->_user_login_day.'" and status=0 and uid in ('.implode(',', $uids).')';
			$res = $db_r->createCommand($sql)->queryAll();
			foreach ($res as $v) {
				$login_day_users[] = $v['uid'];
			}
		}
		return $login_day_users;
	}
}