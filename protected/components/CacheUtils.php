<?php
class CacheUtils {
	/**
	 * 清除缓存
	 * @param unknown $keys
	 */
	public static function removeCache($keys){
		$_cache = Yii::app()->cache;
		$keys = (array)$keys;
        foreach ($keys as $key){
            $res = $_cache->delete($key);
        }
        Common::model()->saveLog("删除缓存:".json_encode($keys),"info","deleteCache");
		self::delOtherCache($keys);
        self::_remove($keys);
	}
	
	public static $other_server = null;
	
	/**
	 * 删除全国各地机房的mc缓存
	 *
	 * @param unknown_type $key
	 */
	public static function  delOtherCache($keys){

        $hosts = Yii::app()->redis_w->get(MEM_PRE_KEY."redis_host");

        if(empty($hosts)){
            Common::model()->saveLog('清理缓存失败:缓存服务器 host is null'.',server ip 172.16.15.54 server->'.$hosts,CLogger::LEVEL_ERROR, 'web.CacheUtils.delOtherCache');
            return;
        }
        self::$other_server = new Memcached();
        $hosts_arr = json_decode($hosts,true); 
        if(count($hosts_arr)>0){
            foreach ($hosts_arr as $val){
                if(!empty($val)){
                    $tmp = explode(':',$val);
                    if(is_array($tmp) && !empty($tmp[0])  && !empty($tmp[1])  ){
                        self::$other_server->addServer($tmp[0],$tmp[1]);
                    }
                }
            }
        
            $keys = (array)$keys;
            $res_arr = array();
            foreach ($keys as $key){
                if(!empty($key)){
                    $res = self::$other_server->delete($key);
                }
            }
            return ;
        }
	}

    /** 
	 * 发布观点后需要删除的缓存信息
	 * @param integer $p_uid
	 * @param integer $ind_id
	 */
    public static function removeCacheForPublishView($p_uid, $ind_id, $quote_url){
		//web端首页用的缓存
		$removeKeys = array(
				//MEM_PRE_KEY.'v_list_0_0_1_15', //首页最新观点第一页
				//MEM_PRE_KEY.'v_list_0_0_2_15', //首页最新观点第二页
				MEM_PRE_KEY.'v_list_'.$ind_id.'_0_1_15', //首页行业观点第一页
				MEM_PRE_KEY.'v_list_'.$ind_id.'_0_2_15', //首页行业观点第二页
				MEM_PRE_KEY.'v_list_0_'.$p_uid.'_1_15', //首页行业观点第一页
				MEM_PRE_KEY.'v_list_0_'.$p_uid.'_2_15', //首页行业观点第二页
				);

		//删除应用url的缓存
		if(!empty($quote_url)){
			$md5_url = md5(strtolower(trim($quote_url)));
			$removeKeys[] = MEM_PRE_KEY.$md5_url.'_1_15';
			$removeKeys[] = MEM_PRE_KEY.$md5_url.'_2_15';
		}

		self::_remove($removeKeys);
	}
	
	
	/**
	 * 理财师管理后台-理财师设置能力圈后重置其个人信息
	 * Date:2014-8-4
	 * Author:yangmao
	 * $p_uid:理财师id
	 */
	public static function removeCacheForUpdatePlannerAnswer($p_uid){
	
		$removeKeys = array(
				MEM_PRE_KEY.'ask_p_'.$p_uid, //理财师缓存
				#MEM_PRE_KEY.'ask_p_ability_'.$p_uid, //用户的观点包列表第二页
	
		);
	
	
		self::_remove($removeKeys);
	}
	
	/**
	 * 理财师管理后台-问题部分缓存清空
	 * Date:2014-8-5
	 * Author:yangmao
	 * $q_id:问题id
	 * $add_id:追问id
	 */
	public static function removeCacheForUpdateAnswer($q_id='',$add_id=''){
	
		$removeKeys = array();
		if(!empty($q_id))
		{
			$removeKeys[] = MEM_PRE_KEY.'q_'.$q_id; //问题详情
		}
		if(!empty($add_id))
		{
			$removeKeys[] = MEM_PRE_KEY.'q_add_'.$add_id; //问题详情
		}
	
	
		self::_remove($removeKeys);
	}
	
	public static function removeCacheForUpdateView($v_id){
		//缓存key
		$removeKeys = array(
				MEM_PRE_KEY.'v_'.$v_id, //观点缓存
		);
		
		self::_remove($removeKeys);
	}

    /**
	 * 从缓存中删除相应的key
	 * @param string or array $keys
	 */
	private static function _remove($keys){
		//$_cache = Yii::app()->cache;
        $mc_a = new Memcached();
		$mc_b = new Memcached();
		$conf_a = array(
		    array('10.71.48.45', 7817),
			array('10.71.48.46', 7817)
		);
		$conf_b = array(
		    array('10.73.48.64', 7817),
			array('10.69.16.107', 7817)
		);

		if(is_array($keys)){
			$mc_a->addServers($conf_a);
			foreach ($keys as $key){
				$rs = $mc_a->delete($key);
				echo 'a:delete cache '. $key . ':'. $rs ."\n";
				var_dump($rs);
			}
			$mc_b->addServers($conf_b);
			foreach ($keys as $key){
				$rs = $mc_b->delete($key);
				echo 'b:delete cache '. $key . ':'. $rs ."\n";
				var_dump($rs);
			}
		}else{
			$mc_a->addServers($conf_a);
			$mc_a->delete($keys);
            $mc_b->addServers($conf_b);
			$mc_b->delete($keys);
		}
	}
    
    /**
     * 删除单条说说的缓存
     * @param type $cmn_ids
     */
    public static function delNewComment($cmn_ids) {
        $curl = Yii::app()->curl;
        $params = array(
            'cmn_id' => implode(',',(array)$cmn_ids)
        );
        $result = $curl->post(LCS_WEB_INNER_URL .'/cacheApi/NewComment', $params);
        if($result) {
            $result = json_decode($result, true);
            if($result['code'] != 0) {
                return false;
            }
            return true;
        }
    }

    /**
     * 删除用户订阅的计划id
     * @param $uid
     * @return bool
     */
    public static function delUserSubscriptionIds($uid) {
        $curl = Yii::app()->curl;
        $params = array(
            'uid' => $uid
        );
        $result = $curl->post(LCS_WEB_INNER_URL .'/cacheApi/delUserSub', $params);
        if ($result) {
            $result = json_decode($result, true);
            if ($result['code'] != 0) {
                return false;
            }
            return true;
        }

        return false;
    }
    
}
