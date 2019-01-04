<?php
/**
 * 圈子
 *
 * add by zhihao6 2016/12/27
 */

class Circle extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public static $notice_type = [
        "1" => "normal",
        "2" => "live",
        "3" => "liveroom",
        "4" => "video",
    ];

    private $default_select_fields = "id,type,relation_id,p_uid,title,summary,image,user_num,comment_num,status,c_time,is_push";
    private $last_alive_second = 60; // 默认1分钟，测试1天
        
    public function tableName()
    {
        return 'lcs_circle';
    }
    public function tableNameCircleUser()
    {
        return 'lcs_circle_user';
    }
    public function tableNameCircleNotice()
    {
        return 'lcs_circle_notice';
    }
    public function tableNameCircleSub()
    {
        return 'lcs_circle_subscription';
    }
    public function tableNameFollow()
    {
        return TABLE_PREFIX .'circle_follow';
    }

    /**
     * 添加圈子用户
     */
    public function addCircleUser($data){
        $db_w = Yii::app()->lcs_w;
        $command = $db_w->createCommand();
        return $command->insert($this->tableNameCircleUser(), $data);
    }

    /**
     * 删除圈子用户
     * @param   int $circle_id圈子id
     * @param   int $uid用户uid
     */
    public function deleteCircleUser($circle_id,$uid){
        if(!empty($circle_id) && !empty($uid)){
            $db_w = Yii::app()->lcs_w;
            $sql = "delete from ".$this->tableNameCircleUser()." where circle_id='$circle_id' and uid='$uid'";
            $command = $db_w->createCommand($sql)->execute();
        }
    }

    /**
     * 更新圈子用户信息
     * @param   int $circle_id圈子id
     * @param   int $uid用户id
     * @param   array   $data需要更新的数据
     */
    public function updateCircleUser($circle_id,$uid,$data){
        $db_w = Yii::app()->lcs_w;
        $sql = "update ".$this->tableNameCircleUser()." set ";
        if(count($data)>0 && $circle_id!=0 && $uid!=0){
            foreach($data as $key=>$val){
                $sql = $sql." $key='$val',";
            }
            $sql = rtrim($sql,',');
            $sql = $sql. " where circle_id='$circle_id' and uid='$uid'";
        }
        return $db_w->createCommand($sql)->execute();
    }

    /**
     * 获取指定圈子id的圈子信息map
     * @param  array $circle_ids 圈子id列表
     * @return array             圈子信息map
     */
    public function getCircleInfoMapByCircleids($circle_ids)
    {
        if(empty($circle_ids)){
            return null;
        } else {
            $circle_ids = (array) $circle_ids;
        }

        $db_w = Yii::app()->lcs_r;
        $sql = "select {$this->default_select_fields} 
                from {$this->tableName()} 
                where id in (" . implode(',', $circle_ids) . ")";
        $command = $db_w->createCommand($sql);
        $res = $command->queryAll();
        if (empty($res)) {
            return null;
        }

        $circle_info_map = [];
        foreach ($res as $row) {
            $circle_info_map[$row['id']] = $row;
        }
        return $circle_info_map;
    }
    
    public function getCircleOnlineUser($circle_id, $u_type=0)
    {
        $last_online_time = date("Y-m-d H:i:s", time()-$this->last_alive_second);

        $sql = "select uid
                from {$this->tableNameCircleUser()}
                where u_type=:u_type and circle_id=:circle_id and is_online=1 and u_time>:u_time";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':u_type', $u_type, PDO::PARAM_INT);
        $cmd->bindParam(':circle_id', $circle_id, PDO::PARAM_INT);
        $cmd->bindParam(':u_time', $last_online_time, PDO::PARAM_STR);
        $res = $cmd->queryColumn();
        if (empty($res)) {
            return [];
        } else {
            return $res;
        }
    }

    public function getCircleUser($circle_id, $u_type=0)
    {
        $sql = "select uid
                from {$this->tableNameCircleUser()}
                where u_type=:u_type and circle_id=:circle_id";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':u_type', $u_type, PDO::PARAM_INT);
        $cmd->bindParam(':circle_id', $circle_id, PDO::PARAM_INT);
        $res = $cmd->queryColumn();
        if (empty($res)) {
            return [];
        } else {
            return $res;
        }
    }

    public function getCircleNotice($notice_id)
    {
        $sql = "select id,circle_id,u_type,uid,type,title,notice,status,c_time
                from {$this->tableNameCircleNotice()}
                where id=:id";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':id', $notice_id, PDO::PARAM_INT);
        $res = $cmd->queryRow();
        if (empty($res)) {
            return null;
        } else {
            return $res;
        }
    }

    public function getCircleUnpushLiveNotice()
    {
        $sql = "select id,circle_id,u_type,uid,type,title,notice,c_time
                from {$this->tableNameCircleNotice()}
                where type=2 and status=1";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $res = $cmd->queryAll();
        if (empty($res)) {
            return [];
        } else {
            return $res;
        }
    }
    
    public function updateCircleNotice($columns, $conditions='', $params=array()){
        return Yii::app()->lcs_w->createCommand()->update($this->tableNameCircleNotice(), $columns, $conditions, $params);
    }

    /**
     * 获取圈子信息,分页
     */
    public function getCircleInfoByPage($where = "" ,$page = 1, $row = 10){
        $count_sql = "select count(*) from {$this->tableName()}";
        if(!empty($where)){
            $count_sql = $count_sql . " where " . $where;
        }
        $cmd = Yii::app()->lcs_r->createCommand($count_sql);
        $count = $cmd->queryScalar();

        $sql = "select id,type,relation_id,p_uid,title,expire_cid,renew_cid from {$this->tableName()} ";
        if(!empty($where)){
            $sql = $sql . " where " . $where;
        }
        $skip = ($page -1 ) * $row;
        $sql = $sql . " limit {$skip},{$row}";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $res = $cmd->queryAll();
        return array("total"=>$count,"data"=>$res);
    }

    /**
     * 根据理财师id与类型获取圈子id
     * @param   array   $p_uids理财师id数组
     * @param   int $type类型
     */
    public function getCircleIdByPuid($p_uids,$type){
        $result = array();
        if(count($p_uids)>0){
            $sql = "select p_uid,id,relation_id from ".$this->tableName()." where p_uid in (".implode(',',$p_uids).") and type='$type' and status=0";
            $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            if($data){
                foreach($data as $item){
                    $result[$item['p_uid']] = $item['id'];
                }
            }
        }
        return $result;
    }

    /**
     * 更新付费圈子的订阅情况
     */
    public function updatePayCircleSubscription($circle_id,$uid,$end_time){
        $sub_info = Yii::app()->lcs_r->createCommand("select id from lcs_circle_subscription where c_id='$circle_id' and uid='$uid' ")->queryAll();
        $db_w = Yii::app()->lcs_w;
        if($sub_info){
            $now = date("Y-m-d H:i:s");
            $sql = "update ".$this->tableNameCircleSub()." set u_time='$now', end_time='$end_time' where c_id='$circle_id' and uid='$uid'";
            return $db_w->createCommand($sql)->execute();
        }else{
            $data = array();
            $data['c_id'] = $circle_id;
            $data['uid'] = $uid;
            $data['end_time'] = $end_time;
            $data['c_time'] = date("Y-m-d H:i:s");
            $data['u_time'] = date("Y-m-d H:i:s");
            $command = $db_w->createCommand();
            return $command->insert($this->tableNameCircleSub(), $data);
        }
    }

    /**
     * 获取付费圈子用户
     */
    public function getPayCircleSubUser($circle_id)
    {
        $sql = "select uid
                from {$this->tableNameCircleSub()}
                where c_id=:circle_id and end_time>:end_time";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $end_time = date("Y-m-d H:i:s");
        $cmd->bindParam(':circle_id', $circle_id, PDO::PARAM_INT);
        $cmd->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        $res = $cmd->queryColumn();
        if (empty($res)) {
            return [];
        } else {
            return $res;
        }
    }
   
    /**
     * 创建圈子
     * @param   $p_uid理财师id
     * @param   $type类型
     */
    public function createCircle($p_uid,$type){
        $sql = "insert into ".$this->tableName()." (type,p_uid,title,image,c_time,u_time,u_type) values(:type,:p_uid,:title,:image,:c_time,:u_time,:u_type)";
        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $now = date("Y-m-d H:i:s");
        $u_type = 2;
        $planner_list = Planner::model()->getPlannerById(array($p_uid));
        if(isset($planner_list[$p_uid])){
            $planner_info = $planner_list[$p_uid];
            if($type=="6001"){
                $title = $planner_info['name']."的私密圈子";
            }
            $cmd->bindParam(':type', $type, PDO::PARAM_STR);
            $cmd->bindParam(':p_uid', $p_uid, PDO::PARAM_STR);
            $cmd->bindParam(':title', $title, PDO::PARAM_STR);
            $cmd->bindParam(':image', $planner_info['image'], PDO::PARAM_STR);
            $cmd->bindParam(':c_time', $now, PDO::PARAM_STR);
            $cmd->bindParam(':u_time', $now, PDO::PARAM_STR);
            $cmd->bindParam(':u_type', $u_type, PDO::PARAM_STR);
            $cmd->execute();
            $circle_id = Yii::app()->lcs_w->getLastInsertID($this->tableName());

            $data = array();
            $data['u_type'] = 2;
            $data['uid'] = $p_uid;
            $data['circle_id'] = $circle_id;
            $data['c_time'] = date("Y-m-d H:i:s");
            $data['u_time'] = date("Y-m-d H:i:s");
            $data['end_time'] = date("Y-m-d H:i:s",strtotime("+10 year"));
            $data['service_status'] = 1;
            $this->addCircleUser($data);
            return $circle_id;
        }

    }

    /**
     * 获取在start_time和end_time时间段内结束的用户信息
     * @param $c_id 圈子ID
     * @param $start_time 开始时间
     * @param $end_time 结束时间
     * @return array
     */
    public function getEndUser($c_id,$start_time,$end_time){
        $sql = "select uid
                from {$this->tableNameCircleSub()}
                where c_id=:circle_id and end_time>=:start_time and end_time<:end_time";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':circle_id', $c_id, PDO::PARAM_INT);
        $cmd->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        $cmd->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        $res = $cmd->queryColumn();
        if (empty($res)) {
            return [];
        } else {
            return $res;
        }
    }

    /**
     * 获取用户圈子订阅信息
     * @param $cid 圈子ID
     * @param $uid 用户UID
     * @return array
     */
    public function getCircleUserInfo($cid,$uid){
        $sql = "select id,end_time from ".$this->tableNameCircleUser()." where u_type=1 and circle_id=:circle_id and uid=:uid";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':circle_id',$cid,PDO::PARAM_INT);
        $cmd->bindParam(':uid',$uid,PDO::PARAM_STR);
        $res = $cmd->queryRow();
        if(empty($res)){
            return [];
        }else{
            return $res;
        }
    }

    /**
     * 获取subscription记录信息
     * @param $cid
     * @param $uid
     * @return array
     */
    public function getCircleSubInfo($cid,$uid){
        $sql = "select id,end_time,u_time from ".$this->tableNameCircleSub()." where c_id=:circle_id and uid=:uid";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':circle_id',$cid,PDO::PARAM_INT);
        $cmd->bindParam(':uid',$uid,PDO::PARAM_STR);
        $res = $cmd->queryRow();
        if(empty($res)){
            return [];
        }else{
            return $res;
        }
    }

    /**
     * 清理subscription变更直播前的订阅记录
     * @param $cid
     * @param $uid
     * @return int
     */
    public function deleteCircleSub($cid,$uid){
        $sub_info = Yii::app()->lcs_r->createCommand("select id from lcs_circle_subscription where c_id='$cid' and uid='$uid' ")->queryAll();
        $db_w = Yii::app()->lcs_w;
        if($sub_info){
            $sql = "delete from ".$this->tableNameCircleSub()." where c_id='$cid' and uid='$uid'";
            return $db_w->createCommand($sql)->execute();
        }else{
            return 1;
        }
    }
        /**
     * 获取单条圈子信息
     * @param  array $where_arr 过滤字段列表
     * @param  array  $order_arr 排序规则
     * @return array            圈子信息
     */
    public function getCircleInfoNew($where_arr, $order_arr=array())
    {
        $fields = $this->default_select_fields;
        $where = $this->buildAndWhere($where_arr);
        if (!empty($order_arr)) {
            $order = ' order by ' . implode(',', $order_arr);
        } else {
            $order = '';
        }

        $sql = "select {$fields}".
                " from {$this->tableName()} ".
                " {$where} ".
                " {$order} ";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryRow();
        if (empty($res)) {
            return null;
        } else {
            return $res;
        }
    }

        /**
     * 根据圈子id获取理财师圈子视频直播信息
     */
    public function getVideoCircleAll($circle_ids,$is_cache=true){
        if($is_cache){
            $redis_key = MEM_PRE_KEY . "c_v_ids_" . implode(',',$circle_ids);
            $result = Yii::app()->redis_r->get($redis_key);
            $result = json_decode($result,true);
        }else{
            $result = "";
        }
        if(!$result){
            $result = array();
            $sql = "select circle_id,identify,media_url,video_code,img_url,status,c_time,u_time from ".$this->tableNameVideo()." where circle_id in (".implode(',',$circle_ids).")";
            $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            if(!$data){
                return $result;
            }
            foreach ($data as $k=>$v){
                $result[$v['circle_id']] = $v;
            }
            Yii::app()->redis_w->setex($redis_key, 60*30, json_encode($result));
        }
        return $result;
    }


    /**
     * 更新理财师圈子视频直播状态
     */
    public function updateVideoCircle($circle_id,$status){
            $sql_data = [];
            $redis_key = MEM_PRE_KEY . "c_v_id_" . intval($circle_id);

            $sql = "update ".$this->tableNameVideo()." set `status`=".$status." where circle_id=".$circle_id;
            array_push($sql_data, $sql);
            Yii::app()->licaishi_w->createCommand($sql)->execute();
            //更新redis
            $sql1 = "select circle_id,identify,media_url,video_code,img_url,status,c_time,u_time from ".$this->tableNameVideo()." where circle_id=".$circle_id;
            array_push($sql_data, $sql1);
            $data = Yii::app()->licaishi_w->createCommand($sql1)->queryRow();

            // Yii::app()->redis_w->setex($redis_key, 60*30, json_encode($data));
            $response = Yii::app()->redis_w->delete($redis_key);
            Common::model()->saveLog('删除圈子视频直播缓存:res->'.json_encode($data)."redis返回状态".json_encode($response)."sql:".json_encode($sql_data),'info', 'web.Circle.updateVideoCircle');
            return $data;
    }
   	
   /**
     * 批量获取理财师圈子视频直播信息
     */
    public function getVideoCircles($circle_ids){
	$result = array();
	$circle_ids = (array)$circle_ids;
	$redis_keys = array();
	foreach($circle_ids as $circle_id){
		$redis_keys[] = MEM_PRE_KEY . "c_v_id_" . intval($circle_id); 
	}
	$res = Yii::app()->redis_r->mget($redis_keys);
	foreach($res as $k=>$v){
		if(!$v){
			$result[$circle_ids[$k]] = $this->getVideoCircle($circle_ids[$k]);
		}else{
			$result[$circle_ids[$k]] = json_decode($v,true);
		}
	}
        return $result;
    }

    ///获取圈子所有用户uid
    public function getCircleUids($circle_id){
        $sql = 'select uid,u_type from lcs_circle_user WHERE circle_id=:circle_id;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':circle_id', $circle_id, PDO::PARAM_INT);
        $data = $cmd->queryAll();
        return $data;
    }

    //获取视频直播
    public function getCircleVideo(){
        $sql = "select circle_id,u_type,uid,title,notice from lcs_circle_notice where type=4";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $data;
    }
    //更新视频直播状态
    public function updateVideoNoticeCircle($id,$notice,$title){
        $sql = "update lcs_circle_notice set `notice`='{$notice}',`title`='{$title}' where id={$id}";
        Yii::app()->licaishi_w->createCommand($sql)->execute();
    }
    //圈子铁粉操作
    public function optionCircleFollow($circle_id,$uid,$type){
        $sql = "select id,circle_id,type,uid from ".$this->tableNameFollow()." where circle_id='{$circle_id}' and type='{$type}'";
        $follow_data = Yii::app()->lcs_r->createCommand($sql)->queryRow();
        if($uid == 0){
            $conditions = 'id = :id';
            $params = array(':id'=>$follow_data['id']);
            Yii::app()->licaishi_w->createCommand()->delete($this->tableNameFollow(),$conditions,$params);
        }


        // if(empty($follow_data) && $uid!=0){
            //删除type记录
        $sql = "select id from ".$this->tableNameFollow()." where circle_id='{$circle_id}' and uid={$uid}";
        $follow_user = Yii::app()->lcs_r->createCommand($sql)->queryRow();
        if(!empty($follow_user)){
            $conditions = 'id = :id';
            $params = array(':id'=>$follow_user['id']);
            Yii::app()->licaishi_w->createCommand()->delete($this->tableNameFollow(),$conditions,$params);
        }

        $conditions = 'id = :id';
        $params = array(':id'=>$follow_data['id']);
        Yii::app()->licaishi_w->createCommand()->delete($this->tableNameFollow(),$conditions,$params);

        $follow = array(
            "circle_id"=>$circle_id,
            "uid"=>$uid,
            "type"=>$type,
            "c_time"=>date("Y-m-d H:i:s",time()),
            "u_time"=>date("Y-m-d H:i:s",time()),
        );
        Yii::app()->licaishi_w->createCommand()->insert($this->tableNameFollow(),$follow);
        // }else{
        //     $db_w = Yii::app()->licaishi_w;
        //     $columns = array(
        //         'uid'=>$uid,
        //     );
        //     $conditions = 'id = :id';
        //     $params = array(':id'=>$follow_data['id']);
        //     $db_w->createCommand()->delete($this->tableNameFollow(),$conditions,$params);
            
        //     $db_w->createCommand()->update($this->tableNameFollow(),$columns,$conditions,$params);
            
        // }
        $this->setCircleFollowCache($circle_id);
        return true;
    }
    //设置圈子粉丝缓存
    public function optionCircleFollowCache($circle_id){
        $redis_key = MEM_PRE_KEY."circle_follow_cache".$circle_id;
        $follow = json_decode(Yii::app()->redis_r->get($redis_key),true);
        if(empty($follow)){
            return $this->setCircleFollowCache($circle_id);
        }
        return $follow;
    }
    public function setCircleFollowCache($circle_id){
        $redis_key = MEM_PRE_KEY."circle_follow_cache".$circle_id;
        $sql = "select id,uid,circle_id,type from ".$this->tableNameFollow()." where circle_id='{$circle_id}'";
        $tempData = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $follow = array();
        foreach ($tempData as $key => $value) {
            if($value['uid'] == 0){
                continue;
            }
            $follow[$value['uid']] = $value;
            $user_info = User::model()->getUserInfoByUid($value['uid']);
            $follow[$value['uid']]['name'] = $user_info['name'];
            $follow[$value['uid']]['image'] = $user_info['image'];
        }
        Yii::app()->redis_w->setex($redis_key,"3600",json_encode($follow));
        return $follow;
    }
    /**
     * 获取圈子信息
     * @param  array $where_arr 过滤字段列表
     * @param  array  $order_arr 排序规则
     * @return array            圈子信息
     */
    public function getAllCircle($where_arr, $order_arr=array())
    {
        $fields = $this->default_select_fields;
        $where = $this->buildAndWhere($where_arr);
        if (!empty($order_arr)) {
            $order = ' order by ' . implode(',', $order_arr);
        } else {
            $order = '';
        }

        $sql = "select {$fields}".
                " from {$this->tableName()} ".
                " {$where} ".
                " {$order} ";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if (!empty($res)) {
            return $res;
        } else {
            return null;
        }
    }

        /**
     * 获取圈子信息
     * @param unknown $circle_id
     */
    public function getCircleInfo($circle_id, $useCache = 0) {
		if(empty($circle_id)){
            return null;
        } else {
            $res = $this->getCircleInfoMapByCircleids($circle_id, $useCache);
			$circle_info = isset($res[$circle_id]) ? $res[$circle_id] : null;
            if($circle_info){
                return $circle_info;
            }
            return null;
        }
    }

        /**
     * 更新圈子表增长字段
     * @param  int  $circle_id 圈子id
     * @param  string  $column    数据库表待增长字段
     * @param  int $val       增长值
     * @return boolean             true
     */
    public function updateCircleInc($circle_id, $column, $val=1)
    {
        $val = intval($val);
        $sql = "update {$this->tableName()} set {$column}={$column}+{$val} where id={$circle_id};";
        $res = Yii::app()->licaishi_w->createCommand($sql)->execute();

        // 清除缓存
        // $cache_key = CacheKeyHelper::buildKey(10001, ["circle_id" => $circle_id]);
        // CacheKeyHelper::cleanKeyCache($cache_key);

        return true;
    }

}
