<?php
/**
 * 公告的数据表操作类
 */
class Common extends CActiveRecord {
    
    const REMOVE_MAX_LIMIT_TIME = 50000;
    const INDEX_NAME = 'savelog';
    const INDEX_USER_NAME = 'data-user';
    const TYPE_USER_NAME = 'doc';
    const INDEX_LOG_NAME = 'access';
    const INDEX_ADMIN2_LOG = 'access_admin2';
    public $url;
    public function __construct(){
        if(defined('ENV') && ENV == 'dev'){
            $this->url='http://192.168.48.224:9200/';
        }else{
            if(time()%2==0){
                try{
                    $url = "http://47.104.254.17:9200/";
                    $res = Yii::app()->curl->setTimeOut(1)->get($url);
                    if(!empty($res)){
                        $this->url = $url;
                        return;
                    }
                }catch(Exception $e){
                }

                try{
                    $url = "http://47.104.129.89:9200/";
                    $res = Yii::app()->curl->setTimeOut(1)->get($url);
                    if(!empty($res)){
                        $this->url = $url;
                    }
                }catch(Exception $e){
                }
            }else{
                try{
                    $url = "http://47.104.129.89:9200/";
                    $res = Yii::app()->curl->setTimeOut(1)->get($url);
                    if(!empty($res)){
                        $this->url = $url;
                    }
                }catch(Exception $e){
                }

                try{
                    $url = "http://47.104.254.17:9200/";
                    $res = Yii::app()->curl->setTimeOut(1)->get($url);
                    if(!empty($res)){
                        $this->url = $url;
                        return;
                    }
                }catch(Exception $e){
                }

            }
        }
    }
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
   
    //分类表
    public function tableNameIndustry(){
    	return TABLE_PREFIX .'industry';
    }
    
    //公司表
    public function tableNameCompany(){
    	return TABLE_PREFIX .'company';
    }
    
    //职位表
    public function tableNamePosition(){
    	return TABLE_PREFIX .'position';
    }
    
    // 资格类型
    public function tableNameCertification(){
    	return TABLE_PREFIX .'certification';
    }
    
    //产品代码表
    public function tableNameSymbol(){
    	return TABLE_PREFIX .'symbol';
    }
    ##日志表
    public function tableNameLog(){
    	return 'lcs_log';
    }

    public function tableNamePageCfg(){
        return 'lcs_page_cfg';
    }
        
    /**
     * 获取交易日天数
     * @param type $start_date
     * @param type $end_date
     * @return type
     */
    public function getMarketDays($start_date,$end_date=''){
        if(strtotime($end_date) <=0){
            $end_date = date("Y-m-d");
        }
        $sql = "select count(cal_id) from lcs_calendar where cal_date>='".$start_date."' AND  cal_date<='".$end_date."'";
        return Yii::app()->lcs_r->createCommand($sql)->queryscalar();
    }

    /**
     * 判断该天是不是交易日
     * @param type $day
     */
    public static function getMarketDayAsArray($start,$end){
        $sql="select cal_date from lcs_calendar where cal_date>='".$start."' and cal_date<='".$end."'";
        $data=Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $result=Array();
        foreach($data as $item){
            $result[$item['cal_date']]=1;
        }
        return $result;
    }
    
    /**
     * 获取页面配置信息
     * @param $area_codes
     * @return mixed
     */
    public function getPageCfgByAreaCodes($area_codes){
        $cdn = '';
        if(!empty($area_codes)){
            if(is_array($area_codes)){
                $cdn = ' area_code in ('.implode(',',$area_codes).') and ';
            }else{
                $cdn = ' area_code = '.intval($area_codes).' and ';
            }
        }
        $sql = 'select id, area_code, type, tag, title,summary, img, url, relation_id,android,ios,sequence,  status, staff_uid, c_time, u_time from lcs_page_cfg where '.$cdn.' status=0 order by sequence asc, u_time desc;';

        $db_r = Yii::app()->lcs_r;
        $cmd = $db_r->createCommand($sql);
        return $cmd->queryAll();
    }

    /**
     * 获取职位
     * @param unknown $id
     */
    public function getPositionById($id){
    	$db_r = Yii::app()->lcs_r;
    	$sql = 'select * from lcs_position where id=:id;';
    	$cmd = $db_r->createCommand($sql);
    	$cmd->bindParam(':id',$id,PDO::PARAM_INT);
    	return $cmd->queryRow();
    }
    
    /**
     * 获取认证类型
     * @param unknown $id
     */
    public function getCertificationById($id){
    	$db_r = Yii::app()->lcs_r;
    	$sql = 'select * from lcs_certification where id=:id;';
		$cmd = $db_r->createCommand($sql);
		$cmd->bindParam(':id',$id,PDO::PARAM_INT);
		return $cmd->queryRow();
    }

    public function getRegionById($id){
    	$db_r = Yii::app()->lcs_r;
    	$sql = 'select * from lcs_region where id=:id;';
	    $cmd = $db_r->createCommand($sql);
	    $cmd->bindParam(':id',$id,PDO::PARAM_INT);
	    return $cmd->queryRow();
    }
    
    
    public function getTagsByIds($ids){
    	$db_r = Yii::app()->lcs_r;
    	$sql = 'select * from lcs_tags where id in ('.implode(',', $ids).');';
    	$cmd = $db_r->createCommand($sql);
    	return $cmd->queryAll();
    }
   
	/**
	 * 保存日志
	 * @param string $message
	 * @param string $categroy
	 * @param string $level
	 * @return int
	 */
	public function saveLog($message,$level='',$category=''){
        $category = empty($category) ? '': $category;
        $level = empty($level) ? '': $level;
        $logtime = time();
        $message = date("Y-m-d H:i:s")." ".$message;
        $data=array('category'=>$category,'message'=>$message,'level'=>$level,'logtime'=>$logtime);
        Yii::app()->redis_w->rpush('lcs_all_online_log',json_encode($data));
    }

    
    /**
     * 获取行业id和标签的关系
     *
     */
    public function getIndTagTypeMap(){
    
    	$mc_key = MEM_PRE_KEY . "ind_tag_map";
    	$res = Yii::app()->cache->get($mc_key);
    
    	if($res === false){
    		$sql = "select ind_id,tag_type from lcs_ind_tagtype";
    		$cmd = Yii::app()->lcs_standby_r->CreateCommand($sql);
    		$result = $cmd->queryAll();
    		if(is_array($result) && sizeof($result)>0){
    			$res = array();
    			foreach ($result as $val){
    				$res[$val['ind_id']][] = $val['tag_type'];
    			}
    			Yii::app()->cache->set($mc_key ,$res,604800);
    		}
    	}
    
    	return $res;
    }
    
    /**
     * 根据行业对应的词类型获取词
     *
     * @param unknown_type $question
     */
    public function getTagsByType($type){
    
    	$type = (array)$type;
    	if(empty($type)){
    		return array();
    	}else{
    		foreach ($type as $key=>$val){
    			$type["$key"] = "'$val'";
    		}
    	}
    
    	$sql = "select id,name from lcs_ask_tags where type in (".implode(',',$type).')';
    	$cmd = Yii::app()->lcs_standby_r->CreateCommand($sql);
    	$res =$cmd->queryAll();
    	return $res;
    }
    
    /**
     * 根据行业对应的词类型获取词
     *
     * @param unknown_type $question
     */
    public function getTagsAll(){
    	$sql = "select id,name from lcs_ask_tags;";
    	$cmd = Yii::app()->lcs_standby_r->CreateCommand($sql);
    	$res =$cmd->queryAll();
    	return $res;
    }

    /**
     * 获取公司信息
     * @param $ids公司id数组
     * @return mixed
     */
    public function getCompany($ids){
        if(empty($ids)){
            return $ids;
        }

        $ids = array_unique(array_filter((array)$ids));
        $key = md5(json_encode($ids));
        $result = Yii::app()->redis_r->get($key);
        if(!empty($result)){
            $result = json_decode($result,true);
            return $result;
        }

        $db_r = Yii::app()->lcs_r;
        $ids = implode(',',$ids);

        $company = $db_r->CreateCommand()
            ->SELECT('id,name,c_type')
            ->FROM($this->tableNameCompany())
            ->WHERE("id IN({$ids})")
            ->queryAll();
        foreach ($company as $vals){
            $result[$vals['id']] = $vals;
        }
		if(!empty($result)){
        	Yii::app()->redis_w->setex($key,300,json_encode($result));
		}else{
			$result = array();
		}
        return $result;

    }
    
    
    /**
     * 删除理财师log日志
     * @param unknown $category
     * @param unknown $end_time
     * @return unknown
     */
    public function clearLog($category,$end_time){
        
        $sql = 'delete from '.$this->tableNameLog().' where category=:category and logtime<:end_time limit :limit;';
        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $limit = self::REMOVE_MAX_LIMIT_TIME;
        $cmd->bindParam(':category',$category,PDO::PARAM_STR);
        $cmd->bindParam(':end_time',$end_time, PDO::PARAM_INT);
        $cmd->bindParam(':limit',$limit,PDO::PARAM_INT);
        $total = 0;        
        $count = 0;
        while($count<4){ //最多执行四次
            $records = $cmd->execute();
            if ($records == 0){
                break;
            }
            //休眠1秒
            sleep(1);
            $total +=$records;
            $count++;
        }      
        return $total;
    }
    
    /**
     * 保存理财师的行为
     * @param int $p_uid 理财师id
     * @param int $type  类型   1观点，２问答，３计划，４交易动态卖出, 5交易动态买入
     * @param int $id    相关id
     */ 
    public function saveAction($p_uid,$type,$id){
        try{
                if(in_array($type,array(1,2,3,4,5)) && !empty($p_uid) && !empty($type) && !empty($id)){
                    $sql = "insert into lcs_planner_active(p_uid,active_type,active_id,publish_time,time_type) values(".$p_uid.",$type,$id,localtime(),1)";
                    Yii::app()->lcs_w->createCommand($sql)->execute();
            return;
                }
            Common::model()->saveLog("not satisfy","info","save_action");
        }catch(Exception $e){
            Common::model()->saveLog($e->getMessage(),"info","save_action");
        }
    }

    public function initActive(){
        /*$sql = "select id,p_time,p_uid from lcs_view";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if(!empty($data)){
            foreach($data as $item){
                try{
                    $sql = "insert into lcs_planner_active(p_uid,active_type,active_id,publish_time,time_type) values(".$item['p_uid'].",1,".$item['id'].",'".$item['p_time']."',1)";
                    Yii::app()->lcs_w->createCommand($sql)->execute();
                }catch(Exception $e){
                }
            }
        }
        $sql = "select q_id,c_time,p_uid from lcs_ask_answer";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if(!empty($data)){
            foreach($data as $item){
                try{
                    $sql = "insert into lcs_planner_active(p_uid,active_type,active_id,publish_time,time_type) values(".$item['p_uid'].",2,".$item['q_id'].",'".$item['c_time']."',1)";
                    Yii::app()->lcs_w->createCommand($sql)->execute();
                }catch(Exception $e){
                }
            }
        }
        $sql = "select pln_id,c_time,p_uid from lcs_plan_info";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if(!empty($data)){
            foreach($data as $item){
                try{
                    $sql = "insert into lcs_planner_active(p_uid,active_type,active_id,publish_time,time_type) values(".$item['p_uid'].",3,".$item['pln_id'].",'".$item['c_time']."',1)";
                    Yii::app()->lcs_w->createCommand($sql)->execute();
                }catch(Exception $e){
                }
            }
        }*/
        $sql = "select id,pln_id,c_time from lcs_plan_transactions where type=1 and c_time>='2017-09-01'";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if(!empty($data)){
            foreach($data as $item){
                try{
                    $planner = "select pln_id,p_uid from lcs_plan_info where pln_id='".$item['pln_id']."'";
                    $p_info = Yii::app()->lcs_r->createCommand($planner)->queryRow();
                    if(!empty($p_info)){
                        $sql = "insert into lcs_planner_active(p_uid,active_type,active_id,publish_time,time_type) values(".$p_info['p_uid'].",5,".$item['pln_id'].",'".$item['c_time']."',1)";
                        Yii::app()->lcs_w->createCommand($sql)->execute();
                    }
                }catch(Exception $e){
                }
            }
        }
    }

    /**
     * 根据id获取相应的数据
     * @param int $id   起始id
     * @param int $num  数量
     */
    public function getLogByPage($id,$num){
        $sql = "select id,level,category,logtime,message from lcs_log where id>='$id' order by id limit $num";
        $cmd = Yii::app()->lcs_standby_r->CreateCommand($sql);
        $res = $cmd->queryAll();
        return $res;
    }

    /**
     * 获取lcs_log中最大的id
     */
    public function getMaxLogId(){
        $sql = "select max(id) from lcs_log";
        $cmd = Yii::app()->lcs_standby_r->CreateCommand($sql);
        $res = $cmd->queryScalar();
        return $res;
    }

    /**
     * 获取热葫芦未执行回调的记录
     *
     */
    public function getUnCallBack(){
        $sql = "select id,callback,sfrom from lcs_client_vrecord where is_callback=0 and status=1 and callback!='' order by id limit 100";
        $data = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        return $data;
    } 

    /**
     * 设置热葫芦回调
     *
     */
    public function setRehuluCallBack($id){
        if(!empty($id)){
            $utime = date('Y-m-d H:i:s');

            $sql = "update lcs_client_vrecord set is_callback=1,u_time='$utime' where id='$id'";
            Yii::app()->lcs_w->createCommand($sql)->execute();
        }
    }

    
    /**
     * 推送(用户uid)
     * @param type $cmd_data
     */
    public function pushGoim($rid,$type,$cmn_data){ 
        $domain = Yii::app()->redis_r->get('goim_logic_http_domain');
        // $domain = "http://47.93.8.145";
        // if(defined('ENV_DEV') && ENV_DEV == 1){      
        //  $domain = "http://goim-logic-local.licaishisina.com.cn";
        // }
        $cmn_data['relation_id'] = strval($cmn_data['relation_id']);
        $cmn_data['is_good'] = intval($cmn_data['is_good']);
        $cmn_data['is_anonymous'] = intval($cmn_data['is_anonymous']);
        //私密回复或者待审核评论
        if($type == 1 && ((isset($cmn_data['is_good']) && $cmn_data['is_good'] != 0) || $cmn_data['is_anonymous'] == 1)){           
            if($cmn_data['is_anonymous'] == 1){
                $uid = $cmn_data['discussion_id'];
            }else{
                $uid = $cmn_data['uid'];
            }
            $args = [
                'uid'=>$uid,
                'rid'=>$rid
            ];
            
            $args = KtktService::buildRequestPara($args);
            $url = sprintf("%s/2/circle/pushOne?uid=%s&rid=%s&sign=%s",$domain,$uid,$rid,$args['sign']);
        }else{
            $args = [
                'type'=>$type,
                'rid'=>$rid
            ];
            $args = KtktService::buildRequestPara($args);           
            $url = sprintf("%s/2/push/room?type=%s&rid=%s&sign=%s",$domain,$type,$rid,$args['sign']);
        }
        try{
            $return = Yii::app()->curl->setTimeOut(10)->post($url, json_encode($cmn_data));
            Common::model()->saveLog(json_encode(array('url'=>$url,'data'=>$cmn_data,'return'=>$return)),"info","cronV2.sendVideoInfoToGoim");
        } catch (Exception $ex) {
            Common::model()->saveLog($ex->getMessage(),"error","cronV2.sendVideoInfoToGoim");
            return $ex->getMessage();
        }
        
    }
    /**
     * 向理财师推送一条信息(理财师p_uid)
     * @param type $cmd_data
     */
    public function pushGoimToPlanner($rid,$type,$cmn_data){
        $domain = Yii::app()->redis_r->get('goim_logic_http_domain');
        $circleInfo = circle::model()->getCircleInfoMapByCircleids($rid);
        // $userInfo['uid'] = User::model()->getUidIndex('s_uid',$circleInfo[$rid]['p_uid']);
        // $domain = "http://47.93.8.145";
        // if(defined('ENV_DEV') && ENV_DEV == 1){      
        //  $domain = "http://goim-logic-local.licaishisina.com.cn";
        // }
        $cmn_data['relation_id'] = strval($cmn_data['relation_id']);
        $cmn_data['is_good'] = intval($cmn_data['is_good']);
        $cmn_data['is_anonymous'] = intval($cmn_data['is_anonymous']);

        if($cmn_data['is_anonymous'] == 1){
            $uid = $cmn_data['discussion_id'];
        }else{
            $uid = $cmn_data['uid'];
        }
        $uid = $circleInfo[$rid]['p_uid'];
        $args = [
            'uid'=>$uid,
            'rid'=>$rid
        ];
        
        $args = KtktService::buildRequestPara($args);
        $url = sprintf("%s/2/circle/pushOne?uid=%s&rid=%s&sign=%s&push_user=is_p",$domain,$uid,$rid,$args['sign']);

        try{
            $return = Yii::app()->curl->setTimeOut(10)->post($url, json_encode($cmn_data));
            Common::model()->saveLog(json_encode(array('url'=>$url,'data'=>$cmn_data,'return'=>$return)),"info","cronV2.sendVideoInfoToGoim_planner");
        } catch (Exception $ex) {
            Common::model()->saveLog($ex->getMessage(),"error","cronV2.sendVideoInfoToGoim_planner");
            return $ex->getMessage();
        }
        
    }
    /**
     * 更新goim中评论的信息，
     * @param type $circle_id 圈子id
     * @param type $cmn_id 评论id
     * @param type $type 
     * @param type $data
     */
    public function updateGoimInfo($circle_id,$cmn_id,$type,$is_good=1,$data=''){
        $args = [
            'circle_id'=>$circle_id,
            'cmn_id'=>$cmn_id,
            'type'=>$type,
            'is_good'=>$is_good
        ];
        $args = KtktService::buildRequestPara($args);
        $sign = $args['sign'];
        $domain = Yii::app()->redis_r->get('goim_logic_http_domain');
        // $domain = "http://47.93.8.145";
        // if(defined('ENV_DEV') && ENV_DEV == 1){      
        //  $domain = "http://goim-logic-local.licaishisina.com.cn";
        // }    
        $url = sprintf("%s/2/circle/updateinfo?circle_id=%s&cmn_id=%s&type=%s&sign=%s&is_good=%s",$domain,$circle_id,$cmn_id,$type,$sign,$is_good);
                try{
            $return = Yii::app()->curl->setTimeOut(10)->post($url, json_encode($data));
            Common::model()->saveLog(json_encode(array('url'=>$url,'data'=>$data,'return'=>$return)),"info","sendVideoInfoToGoim_web");
        } catch (Exception $ex) {
            Common::model()->saveLog($ex->getMessage(),"error","sendVideoInfoToGoim_error_web");
            return $ex->getMessage();
        }
    }
    	/**
	 * 获取职位
	 * @param $ids职位id数组
	 * @return mixed
	 */
	public function getPosition($ids) {
		if (empty($ids)) {
			return $ids;
		}
		$mem_pre_key = MEM_PRE_KEY . "job_";
		$ids = array_unique(array_filter((array) $ids));
		//从缓存获取数据
		$mult_key = array();
		foreach ($ids as $val) {
			$mult_key[] = $mem_pre_key . intval($val);
		}
		$cache = Yii::app()->cache->mget($mult_key);
		$no_cache_id = array();
		foreach ($cache as $key => $val) {
			$v_key = str_replace($mem_pre_key, '', $key);

			if (!empty($val) && $val !== false) {
				$return["$v_key"] = $val;
			} else {
				$no_cache_id[] = $v_key;
			}
		}
		//未缓存的
		if (!empty($no_cache_id)) {
			$db_r = Yii::app()->lcs_r;
			$no_cache_id = implode(',', $no_cache_id);
			$position = $db_r->CreateCommand()
				->SELECT('id,name')
				->FROM($this->tableNamePosition())
				->WHERE("id IN({$no_cache_id})")
				->queryAll();
			foreach ($position as $vals) {
				$return[$vals['id']] = $vals;
				//cache缓存 缓存一周
				Yii::app()->cache->set($mem_pre_key . $vals['id'], $vals, 604800);
			}
		}



		return $return;
	}

    	/**
	 * 根据 ID 获取地区信息
	 * @param mixed $region_id
	 * @return mixed
	 */
	public function getRegion($region_id) {
		if (empty($region_id)) {
			return array();
		}
		if (!is_array($region_id)) {
			$mc_pre_key = MEM_PRE_KEY . "region_p_";
			$result = Yii::app()->cache->get($mc_pre_key . $region_id);
			if ($result !== FALSE) {
				return $result;
			}
			$sql = "select id,name,p_id from lcs_region where p_id='{$region_id}'";
			$result = Yii::app()->lcs_r->CreateCommand($sql)->queryAll();
			Yii::app()->cache->set($mc_pre_key . $region_id, $result, 3600);
			return $result;
		}
		$mc_pre_key = MEM_PRE_KEY . "region_";
		//从缓存获取数据
		$return = array();
		$mult_key = array();
		foreach ($region_id as $val) {
			$mult_key[] = $mc_pre_key . intval($val);
		}
		$result = Yii::app()->cache->mget($mult_key);
		$leave_key = array();
		foreach ($result as $key => $val) {
			$v_key = str_replace($mc_pre_key, '', $key);
			if ($val !== false) {
				$return["$v_key"] = $val;
			} else {
				$leave_key[] = intval($v_key);
			}
		}
		if (sizeof($leave_key) > 0) {
			$sql = 'select id,name,p_id from lcs_region where id in (' . implode(',', $leave_key) . ')';
			$res = Yii::app()->lcs_r->CreateCommand($sql)->queryAll();
			return $res;
			if (is_array($res) && sizeof($res) > 0) {
				foreach ($res as $vals) {
					$return[$vals['id']] = $vals;
					Yii::app()->cache->set($mc_pre_key . $vals['id'], $vals, 600);
				}
			}
		}
		return $return;
    }
    	/**
	 * 根据代码获取产品名称
	 * @param int $ind_id
	 * @param string $code
	 *
	 * 注意：根据code和行业不能唯一获取一只A股股票代码，请使用下面getInfoBySymbol
	 */
	public function getSymbolByCode($ind_id, $code) {
		$result = array();
		$db_r = Yii::app()->lcs_r;

		$mem_pre_key = MEM_PRE_KEY . 'symbol_' . $ind_id . '_' . $code;
		$cache = Yii::app()->cache->get($mem_pre_key);
		if (!empty($cache)) {
			return $cache;
		}

		$type_cdn = '';
		if ($ind_id == 1) {
			$type_cdn = " AND type='stock_cn'";
		} else if ($ind_id == 2) {
			$type_cdn = " AND type in ('fund_open','fund_etf','fund_close','fund_lof')";
		} else if ($ind_id == 3) {
			$type_cdn = " AND type in ('future_inner','future_global')";
		}

		$sql = 'SELECT id, type, code, symbol, name, pinyin, search_content, c_time FROM ' . $this->tableNameSymbol() . " WHERE code=:code" . $type_cdn;
		/* if($type!=null){
		  if(is_string($type)){
		  $sql .=" AND type=:type";
		  }else if(is_array($type)){
		  $sql .=" AND type IN (:type)";

		  }
		  } */

		$cmd = $db_r->CreateCommand($sql);
		$cmd->bindParam(':code', $code, PDO::PARAM_STR);
		/* if($type!=null){
		  if(is_string($type)){
		  $cmd->bindParam(':type', $type, PDO::PARAM_STR);
		  }else if(is_array($type)){
		  $types = "'".implode("','", $type)."'";
		  $cmd->bindParam(':type', $types, PDO::PARAM_STR);
		  }
		  } */

		$symbol = $cmd->queryRow();
		Yii::app()->cache->set($mem_pre_key, $symbol, 86400);

		return $symbol;
	}

	/**
	 * 根据代码获取信息
	 *
	 * @param unknown_type $symbol 带前缀的代码
	 * @param unknown_type $type (A股stock_cn 基金fund,期货future)
	 */
	public function getInfoBySymbol($symbol, $type) {

		$mc_key = MEM_PRE_KEY . 'symbol_' . $type . '_' . $symbol;
		$cache = Yii::app()->cache->get($mc_key);
		if (!empty($cache)) {
			return $cache;
		}
		$where = '';
		if ($type == 'stock_cn') {
			$where = "'stock_cn'";
		} else if ($type == 'fund') {
			$where = "'fund_open','fund_etf','fund_close','fund_lof'";
		} else if ($type == 'future') {
			$where = "'future_inner','future_global'";
		} else {
			return '';
		}

		if ($type == 'fund') {//针对基金不同前缀,有些获取不到前缀的 用code代替，基金code是唯一的
			$symbol = substr($symbol, -6);
			$sql = 'select type,code,symbol,name from ' . $this->tableNameSymbol() . " where code=:symbol";
		} else {
			$sql = 'select type,code,symbol,name from ' . $this->tableNameSymbol() . " where symbol=:symbol";
		}
		$sql .= "  and type in($where)";
		$cmd = Yii::app()->lcs_r->CreateCommand($sql);
		$cmd->bindParam(':symbol', $symbol, PDO::PARAM_STR);
		$symbol = $cmd->queryRow();
		Yii::app()->cache->set($mc_key, $symbol, 86400);

		return $symbol;
	}

	public function getInfoBySym($symbol) {

		$sql = 'SELECT id, type, code, symbol, name, pinyin, search_content, c_time FROM ' . $this->tableNameSymbol() . " WHERE symbol=:symbol";
		$db_r = Yii::app()->lcs_r;
		$cmd = $db_r->CreateCommand($sql);
		$cmd->bindParam(':symbol', $symbol, PDO::PARAM_STR);
		$res = $cmd->queryRow();

		return $res;
	}

	/**
	 * 根据名称获取id
	 * @param string $name
	 * @param number $p_id
	 */
	public function getProvinceByName($name, $p_id = 0) {
		if ($p_id != 0) {
			$sql = "select id,name,p_id from lcs_region where p_id=:p_id and name like :name limit 1";
		} else {
			$sql = "select id,name,p_id from lcs_region where name like :name limit 1";
		}
		$cmd = Yii::app()->lcs_r->CreateCommand($sql);
		if ($p_id != 0) {
			$cmd->bindParam(':p_id', $p_id, PDO::PARAM_INT);
		}
		$name_str = $name . "%";
		$cmd->bindParam(':name', $name_str, PDO::PARAM_STR);
		//echo $cmd->getText();
		$res = $cmd->queryRow();
		return $res;
	}


	/**
	 * 获取营业部id
	 */
	public function getDepartByName($department) {
		if (is_array($department) && !empty($department)) {
			foreach ($department as $dp) {
				$depart .= "\"{$dp}\",";
			}
			$depart = trim($depart, ',');
			$where = "`name` in({$depart})";
		} else if (is_string($department)) {
			$where = "`name`=\"{$department}\"";
		} else {
			return array();
		}
		$db_r = Yii::app()->lcs_r;
		$sql = "SELECT `id`,`name`,`status` FROM " . $this->tableNameDepartment() . " WHERE {$where}";
		return $db_r->createCommand($sql)->queryAll();
	}

	/**
	 * 	是否是投顾大赛参赛人员
	 * @return array   p_uid=>1|0   1是  0否
	 */
	public function isTouguUser($p_uids) {
		$p_uids = (array) $p_uids;
		if (empty($p_uids)) {
			return array();
		}
		$result = array();
		$p_uids_arr = array();
		foreach ($p_uids as $p_uid) {
			$p_uids_arr[] = intval($p_uid);
			$result["$p_uid"] = 0;
		}

		$sql = 'select p_uid, match_id from ' . $this->tableNamePlannerMatch() . ' where p_uid in (' . implode(',', $p_uids_arr) . ');';
		$db_r = Yii::app()->lcs_r;
		$data = $db_r->createCommand($sql)->queryAll();

		if (!empty($data)) {
			foreach ($data as $row) {
				if (array_key_exists($row['p_uid'], $result)) {
					$result["{$row['p_uid']}"] = 1;
				}
			}
		}
		return $result;
	}

	/**
	 * 投顾大赛的所有理财师p_id
	 * @return unknown
	 */
	public function getTouguPuid() {
		$db_r = Yii::app()->lcs_r;
		$sql = 'select p_uid from ' . $this->tableNamePlannerMatch() . ' where status=0';
		$data = $db_r->createCommand($sql)->queryColumn();
		return $data;
	}

	/**
	 * 获取合作者
	 */
	public function getPartner() {
		return $this->partner;
	}

	/**
	 *
	 * @param type $web
	 * @param type $wap
	 * @return type
	 */
	public function savePageAdapt($web, $wap) {
		$db_w = Yii::app()->licaishi_w;
		$cur_date = date(DATE_ISO8601);
		$sql = 'INSERT INTO ' . $this->tableNameJump() . ' (web, wap, c_time, u_time) VALUES(:web, :wap, :c_time, :u_time);';
		$cmd = $db_w->createCommand($sql);
		$cmd->bindParam(':web', $web, PDO::PARAM_STR);
		$cmd->bindParam(':wap', $wap, PDO::PARAM_STR);
		$cmd->bindParam(':c_time', $cur_date, PDO::PARAM_INT);
		$cmd->bindParam(':u_time', $cur_date, PDO::PARAM_INT);
		return $cmd->execute();
	}

	/**
	 * 用户是否关注该理财师
	 * @param   int $uid
	 * @param   int $p_uid
	 */
	public function ifAttentionPlanner($uid, $p_uid) {
		$db_r = Yii::app()->lcs_r;
		$sql = 'select id from ' . $this->tableNameAttention() . ' where p_uid=:p_uid and uid=:uid ';
		$cmd = $db_r->createCommand($sql);
		$cmd->bindParam(':p_uid', $p_uid, PDO::PARAM_INT);
		$cmd->bindParam(':uid', $uid, PDO::PARAM_INT);
		return $cmd->queryAll();
    }
    	/**
	 * 保存用户的访问ｉｐ地址
	 * @param   int $utype 用户类型
	 * @param   int $uid    用户uid
	 * @param   str $op 动作分类
	 * @param   int $r_id   相关id
	 * @return int
	 */
	public function saveVisitIp($utype, $uid, $op, $r_id = 0) {
		try {
			$ip = false;
			if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			}
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$ips = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
				if ($ip) {
					array_unshift($ips, $ip);
					$ip = FALSE;
				}
				for ($i = 0; $i < count($ips); $i++) {
					if (!preg_match('/^(10│172.16│192.168)./', $ips[$i])) {
						$ip = $ips[$i];
						break;
					}
				}
			}
			$ip = $ip ? $ip : $_SERVER['REMOTE_ADDR'];
			$this->saveVisit($utype, $uid, $op, $r_id, $ip);
		} catch (Exception $e) {

		}
	}

	/**
	 * 获取分类信息
	 * @param id 分类
	 */
	public function getIndustry() {
		return array('0' => array('id' => 0, 'name' => '推荐'), '1' => array('id' => 1, 'name' => 'A股'), '3' => array('id' => 3, 'name' => '期货'),
			'2' => array('id' => 2, 'name' => '基金'), '4' => array('id' => 4, 'name' => '金银油'), '6' => array('id' => 6, 'name' => '美股'),
			'7' => array('id' => 7, 'name' => '港股'), '8' => array('id' => 8, 'name' => '保险'), '5' => array('id' => 5, 'name' => '其他理财'),);


		$cache = Yii::app()->cache->get(MEM_PRE_KEY . "ind_all");
		if ($cache === false) {
			$result = Yii::app()->lcs_r->CreateCommand()
				->SELECT('id,name')
				->FROM($this->tableNameIndustry())
				->WHERE($where)
				->queryAll();
			$cache = array();
			foreach ($result as $vals) {
				$cache[$vals['id']] = $vals;
			}
			//cache缓存 缓存一周
			Yii::app()->cache->set(MEM_PRE_KEY . "ind_all", $cache, 604800);
		}
		return $cache;
	}

	/**
	 * 获取验证码
	 *
	 * @param unknown_type $code
	 */
	public function getInvitationCode($code) {
		$sql = "select code,uid from lcs_invitation_code where code=:code";

		$cmd = Yii::app()->licaishi_w->createCommand($sql);
		$cmd->bindParam(":code", $code, PDO::PARAM_STR);
		$res = $cmd->queryRow();
		return $res;
	}

	/**
	 * 获取一个没有分配的计划邀请码
	 *
	 * @param unknown_type $type
	 */
	public function getPlanCode($type) {
		$type = intval($type);
		$sql = "select id,code from lcs_invitation_code where type=$type and status=0";
		return Yii::app()->lcs_r->createCommand($sql)->queryRow();
	}

	public function updateUserPlanCode($uid, $code) {
		$now_time = date('Y-m-d H:i:s');
		$sql = "update lcs_invitation_code set uid=:uid,u_time='$now_time',status=1 where code=:code";
		$cmd = Yii::app()->licaishi_w->createCommand($sql);
		$cmd->bindParam(':uid', $uid, PDO::PARAM_INT);
		$cmd->bindParam(':code', $code, PDO::PARAM_STR);
		return $cmd->execute();
	}

	public function updateInvitationCode($code, $uid) {
		$now_time = date('Y-m-d H:i:s');
		$sql = "update lcs_invitation_code set uid='$uid',u_time='$now_time' where code='$code'";
		$cmd = Yii::app()->licaishi_w->createCommand($sql);
		return $cmd->execute();
	}

	/**
	 * 根据省id获取市
	 *
	 * @param unknown_type $p_id
	 */
	public function getCity($p_id) {
		$p_id = $p_id;
		if (is_array($p_id)) {
			$sql = "select id,name,p_id from lcs_region where p_id in (" . implode(',', $p_id) . ')';
		} else {
			$sql = "select id,name,p_id from lcs_region where p_id='$p_id'";
		}
		$res = Yii::app()->lcs_r->CreateCommand($sql)->queryAll();
		return $res;
	}


	/**
	 * 根据id取资格名称。id为0，则取所有资格名称
	 * @param number $ids
	 * @return array
	 */
	public function getCertification($ids = "") {
		$result = array();
		$db_r = Yii::app()->lcs_r;

		$mem_pre_key = MEM_PRE_KEY . "certs";
		$cache = Yii::app()->cache->get($mem_pre_key);
		if (empty($cache)) {
			$select = $db_r->CreateCommand();
			$select->select('id, name')->from($this->tableNameCertification());
			$result = $select->queryAll();
			$cache = array();
			foreach ($result as $i) {
				$cache[$i['id']] = $i;
			}

			Yii::app()->cache->set($mem_pre_key, $cache, 604800);
		}

		if (!empty($cache)) {
			if (is_array($ids)) {
				foreach ($ids as $id) {
					$result[$id] = $cache[$id];
				}
			} else if (is_integer($ids)) {
				$result[$ids] = $cache[$ids];
			} else {
				$result = $cache;
			}
		}

		return $result;
	}
    
}
