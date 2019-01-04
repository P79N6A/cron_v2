<?php

/**
 * 理财师访问日志统计
 */
class AccessLog extends CActiveRecord {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'lcs_access_log';
    }

    public function tableNameHis() {
        return 'lcs_access_log_his';
    }

    public function tableNameStat() {
        return 'lcs_access_stat_log';
    }

    public function tableNameUserIndex() {
        return 'lcs_user_index';
    }

    public function tableNamePlanner() {
        return 'lcs_planner';
    }

    //数据库 读
    private function getCommentDBR(){
        return Yii::app()->lcs_comment_r;
    }

	//数据库 写 
    private function getCommentDBW(){
        return Yii::app()->lcs_comment_w;
    }

    //数据库 读
    private function getDBR(){
        $db_r = Yii::app()->lcs_standby_r;
        ///关闭连接，重新链接
        if(empty($db_r->active)){
            $db_r->active=true;
        }
        return $db_r;
    }

	//数据库 写 
    private function getDBW(){
        return Yii::app()->lcs_w;
    }

    /**
     * 批量存储数据
     * @param type $db_data
     */
    public function saveAccessLogMulti($db_data) {
        try {
            $db_w = $this->getCommentDBW();
            $insert_sql = "insert into " . $this->tableName() . " (acc_time,duration,uid,sina_global,fr,fc_v,sfr,equipment_no,url,s_url,ua,referer,c_time,ip) values";

            foreach ($db_data as $item) {
                $temp = "('" . $item['acc_time'] . "',"
                    . $item['duration'] . ","
                    . $item['uid'] . ","
                    . "'" . $item['sina_global'] . "',"
                    . "'" . $item['fr'] . "',"
                    . "'" . $item['fc_v'] . "',"
                    . "'" . $item['sfr'] . "',"
                    . "'" . $item['equipment_no'] . "',"
                    . "'" . $item['url'] . "',"
                    . "'" . urlencode($item['s_url']) . "',"
                    . "'" . $item['ua'] . "',"
                    . "'" . $item['referer'] . "',"
                    . "'" . $item['c_time'] . "',"
                    . $item['ip']
                    . ")";
                $insert_sql = $insert_sql . $temp . ",";
            }
            $insert_sql = rtrim($insert_sql, ',');
            $res = $db_w->createCommand($insert_sql)->execute();
            return $res;
        } catch (Exception $ex) {
        }
    }

    /**
     * 保存页面访问量,太多了，分批次访问
     * @param string $stat_date 统计时间
     */
    public function savePageView($type,$data,$stat_date){
        $temp = array(); 
        if(count($data)==0){
            $this->SaveAccessStat($type,$data,$stat_date);
            return;
        }
        foreach($data as $key=>$value){
            $temp[$key] = $value;
            if(count($temp)>35){
                $length = strlen(json_encode($temp));
                $left_array = array();
                if($length > 2900){
                    $left_array = array_slice($temp,21,35);
                    $temp = array_slice($temp,20);
                }
                $this->SaveAccessStat($type,$temp,$stat_date);
                $temp = $left_array;
            }
        }
        if( count($temp) > 0){
            $this->SaveAccessStat($type,$temp,$stat_date);
        }
    }

    /**
     * 保存每日统计数据
     * @param type $type 数据类型
     * @param type $data 数据内容
     * @param type $stat_date 统计日期
     */
    public function SaveAccessStat($type,$data,$stat_date) {
        try {
            $db_w = $this->getCommentDBW();
            ///关闭连接，重新链接
            $db_w->active=false;
            $db_w->active=true;
            $db_data = array();
            $db_data['stat_date'] = intval(date("Ymd", strtotime($stat_date)));
            $db_data['stat_item'] = $type;
            $db_data['stat_value'] = json_encode($data);
            $db_data['c_time'] = date("Y-m-d H:i:s", time());
            $res = $this->getCommentDBW()->createCommand()->insert($this->tableNameStat(), $db_data);
            return $res;
        } catch (Exception $ex) {
            var_dump($ex->getMessage());
        }
    }

    /**
     * 获取用户统计数据
     * @param type $time 统计时间,为空则统计所有数据，否则统计某个时间以来的用户信息
     */
    public function getUserStatByTime($time = "") {
        $all_uids = array();
        $db_r = $this->getDBR();
        $result = array();
        $day_end = date("Y-m-d",strtotime("+1 day",strtotime($time)));
        ///某个时间点新注册的用户uid
        for ($i = 0; $i < 10; $i++) {
            $sql = "select uid from lcs_user_" . strval($i) . " where c_time>='" . $time . "' and c_time<='".$day_end."' and status=0";
            $res = $db_r->createCommand($sql)->queryAll();
            if (!empty($res)) {
                $all_uids = array_merge($all_uids, $res);
            }
        }

        $result['total_user'] = count($all_uids);

        ///新进理财师数量
        $sql = "select count(*) from lcs_planner where c_time>='" . $time . "' and c_time<='".$day_end."'";
        $res = $db_r->createCommand($sql)->queryScalar();
        $result['total_planner'] = empty($res) ? 0 : $res;

        ///每日新增用户付费了的,100个一批进行计算
        $temp_uid = array();
        $result['total_pay'] = 0;
        $result['pay_user'] = 0;

        for ($i = 0; $i < count($all_uids); $i++) {
            $temp_uid[] = $all_uids[$i]['uid'];
            if (count($temp_uid) == 100) {
                $sql = "select count(distinct(uid)) from lcs_orders where uid in (" . implode(',', $temp_uid) . ") and status=2";
                $res = $db_r->createCommand($sql)->queryScalar();
                $res = empty($res) ? 0 : $res;
                $result['total_pay'] = $result['total_pay'] + $res;
                $temp_uid = array();
            }
        }
        ///计算剩余用户付费情况
        if (count($temp_uid) > 0) {
            $sql = "select count(distinct(uid)) from lcs_orders where uid in (" . implode(',', $temp_uid) . ") and status=2";
            $res = $db_r->createCommand($sql)->queryScalar();
            $res = empty($res) ? 0 : $res;
            $result['total_pay'] = $result['total_pay'] + $res;
        }

        ///每日付费用户
        $sql = "select count(distinct(uid)) from lcs_orders where c_time>='" . $time. "' and c_time<='".$day_end."' and status=2";
        $all_users = $db_r->createCommand($sql)->queryScalar();
        $result['pay_user'] = empty($all_users)? 0:$all_users;
        return $result;
    }

    /**
     * 获取总的用户统计信息
     */
    public function getUserAllStat() {
        $db_r = $this->getDBR();
        $result = array();

        ///统计所有用户
        $sql = "select count(id) from lcs_user_index ";
        $res = $db_r->createCommand($sql)->queryScalar();
        $res = empty($res) ? 0 : $res;
        $result['total_user'] = $res;

        ///统计微信用户
        $sql = "select count(id) from lcs_user_index where wx_unionid!=''";
        $res = $db_r->createCommand($sql)->queryScalar();
        $res = empty($res) ? 0 : $res;
        $result['total_weixin'] = $res;

        ///统计该机构下的所有理财师
        $sql = "select count(*) from lcs_planner where status=0";
        $res = $db_r->createCommand($sql)->queryScalar();
        $res = empty($res) ? 0 : $res;
        $result['total_planner'] = $res;

        ///统计付费用户
        $temp_uid = array();
        $result['total_pay'] = 0;
        $sql = "select count(distinct(uid)) from lcs_orders where status=2";
        $res = $db_r->createCommand($sql)->queryScalar();
        $res = empty($res) ? 0 : $res;
        $result['total_pay'] = $res;
        
        return $result;
    }

    /**
     * 根据获取用户活动情况
     * @param string $search_date 查询时间
     */
    public function getUserAccessByTime($search_date, $is_his = 0) {
        $today = date("Ymd",strtotime($search_date));
        $count_sina_global = Yii::app()->redis_r->sCard("lcs_sina_global_".$today);
        $count_uid = Yii::app()->redis_r->sCard("lcs_uid_".$today);
        $count_device_no = Yii::app()->redis_r->sCard("lcs_device_no_".$today);
        $result['user_day_access'] = empty($count_sina_global) ? 0 : $count_sina_global;
        $result['user_day_login'] = empty($count_uid) ? 0 : $count_uid;
        $result['user_day_nologin'] = $result['user_day_access'] - $result['user_day_login'];
        $result['user_day_device'] = empty($count_device_no)? 0 : $count_device_no;
        return $result;
    }

    /**
     * 获取所有的url
     *
     */
    public function getAllUrl(){
        $today = date("Ymd",time());
        $all_url = Yii::app()->redis_r->sMembers("lcs_url_".$today);
        if(empty($all_url)){
            $db_r = $this->getCommentDBR();
            $sql = "select stat_item from lcs_access_stat_log where stat_date='20170226' and stat_item like 'page_url_ip%'";
            $res = $db_r->createCommand($sql)->queryAll();

            $all_url = array();
            foreach($res as $item){
                $all_url[] = str_replace('page_url_ip_','',$item['stat_item']);
            }
        }
        return $all_url;
    }

    /*
     * 页面访问量统计
     * @param string $search_date 查询时间
     */
    public function pageView($search_date, $is_his = 0) {
        $db_r = $this->getCommentDBR();
        $result = array();
        $table_name=$this->tableName();
        if($is_his){
            $table_name=$this->tableNameHis();
        }
        $all_url = $this->getAllUrl();
        if (!empty($all_url)) {
            foreach ($all_url as $item) {
                $sql = "select url,count(*) as total,max(duration) as max_time from " . $table_name . " where acc_time='" . $search_date . "' and url='".$item."'";
                $data = $db_r->createCommand($sql)->queryAll();
                $result[$item] = array();
                $result[$item]['pv'] = empty($data)?0:$data[0]['total'];
                $result[$item]['max'] = empty($data)?0:$data[0]['max_time'];
                $result[$item]['min'] = 0;
                $result[$item]['avg'] = 0;
            }
        }
        return $result;
    }

    /**
     * 统计总的页面访问量 
     */
    public function allPageView($search_date, $is_his = 0){
        $db_r = $this->getCommentDBR();
        $table_name = $this->tableName();
        $sql = "select count(*) as total from ".$table_name." where acc_time='".$search_date."'";
        $data = $db_r->createCommand($sql)->queryScalar();
        $result = array();
        $result['total'] = empty($data)?0:$data;
        return $result;
    }

    /**
     * 统计单日页面的相关信息
     */
    public function singlePageView($search_date){
        $db_r = $this->getCommentDBR();
        $result = array();

        $all_url = $this->getAllUrl();
        $table_name = $this->tableName();
        if (!empty($all_url)){
            foreach($all_url as $item){
                $sql = "select url,count(*) as total from ". $table_name ." where acc_time='". $search_date ."' and url='".$item."' ";
                $data = $db_r->createCommand($sql)->queryRow();

                $key = "page_url_".$item;
                $ip_key = "page_url_ip_".$item;
                
                $insert_data = array();
                $insert_data['total'] = isset($data['total'])?$data['total']:0;
                $insert_data['uv'] = 0; 
                $insert_data['sfr'] = array();
                $insert_data['referer'] = array();
                $insert_data['fr'] = array();

                $sql = "select count(distinct(sina_global)) as total from ".$table_name." where acc_time='".$search_date."' and url='".$item."' ";
                $uv_data = $db_r->createCommand($sql)->queryScalar();
                if(!empty($sfr_data)){
                    $insert_data['uv'] = $uv_data;
                }else{
                    $insert_data['uv'] = 0;
                }

                $sql = "select sfr,count(*) as total from ".$table_name." where acc_time='".$search_date."' and url='".$item."'  group by sfr order by total desc limit 10";
                $sfr_data = $db_r->createCommand($sql)->queryAll();
                if(!empty($sfr_data)){
                    foreach($sfr_data as $temp_sfr){
                        $insert_data['sfr'][$temp_sfr['sfr']] = $temp_sfr['total'];
                    }
                }

                $sql = "select referer,count(*) as total from ".$table_name." where acc_time='".$search_date."' and url='".$item."' and referer!='' group by referer order by total desc limit 10";
                $referer_data = $db_r->createCommand($sql)->queryAll();
                if(!empty($referer_data)){
                    foreach($referer_data as $temp_refer){
                        $insert_data['referer'][$temp_refer['referer']] = $temp_refer['total'];
                    }
                }

                $sql = "select fr,count(*) as total from ".$table_name." where acc_time='".$search_date."' and url='".$item."' and referer!='' group by fr order by total desc limit 8";
                $fr_data = $db_r->createCommand($sql)->queryAll();
                if(!empty($fr_data)){
                    foreach($fr_data as $temp_fr){
                        $insert_data['fr'][$temp_fr['fr']] = $temp_fr['total'];
                    }
                }

                $sql = "select ua,count(*) as total from ".$table_name." where acc_time='".$search_date."' and url='".$item."' group by ua order by total desc limit 10";
                $ua_data = $db_r->createCommand($sql)->queryAll();
                if(!empty($ua_data)){
                    foreach($ua_data as $temp_ua){
                        $insert_data['ua'][$temp_ua['ua']] = $temp_ua['total'];
                    }
                }

                $visit_ip = array();
                $sql = "select ip,count(*) as total from ".$table_name." where acc_time='".$search_date."' and url='".$item."' group by ip order by total desc limit 30";
                $ip_data = $db_r->createCommand($sql)->queryAll();
                if(!empty($ip_data)){
                    foreach($ip_data as $temp_ip){
                        if($temp_ip['ip'] == 0){
                            $visit_ip["empty"] = $temp_ip['total'];
                        }else{
                            $ip_addr = long2ip($temp_ip['ip']);
                            $visit_ip[$ip_addr] = $temp_ip['total'];
                        }
                    }
                }
                $this->SaveAccessStat($key,$insert_data,$search_date);
                $this->saveAccessStat($ip_key,$visit_ip,$search_date);
            }
        }
    }

    /**
     * 用户流失
     */
    public function userLose() {
        try{
            $db_r = $this->getDBR();
            $day_before_5 = date("Y-m-d H:i:s", strtotime("-30 day"));
            $result['user_lose'] = 0;
            for ($i = 0; $i < 10; $i++) {
                $db_r->active=false;
                $db_r->active=true;
                $sql = "select count(*) from lcs_user_" . $i . " where u_time<'" . $day_before_5 . "'";
                $res = $db_r->createCommand($sql)->queryScalar();
                $res = empty($res) ? 0 : $res;
                $result['user_lose'] = $result['user_lose'] + $res;
            }
            return $result;
        }catch(Exception $e){
            var_dump($e->getMessage());
        }
    }

    /**
     * 每日新增用户
     */
    public function dayUserNewAdd($search_date) {
        $db_r = $this->getDBR();
        $result = array();
        $result['new_pay_user'] = 0;
        $result['new_user'] = 0;
        $day_end = date("Y-m-d",strtotime("+1 day",strtotime($search_date)));
        ///每日新增付费用户
        $sql = "select distinct(uid) from lcs_orders where c_time>='" . $search_date . "' and c_time<='".$day_end."' and status=2";
        $all_uids = $db_r->createCommand($sql)->queryAll();
        for ($i = 0; $i < 10; $i++) {
            $temp_uids = array();
            for ($j = 0; $j < count($all_uids); $j++) {
                $temp_uids[] = $all_uids[$j]['uid'];
                if (count($temp_uids) == 100) {
                    $sql = "select count(*) from lcs_user_" . $i . " where uid in (" . implode(',', $temp_uids) . ") and c_time>='" . $search_date . "' and c_time<='".$day_end."'";
                    $res = $db_r->createCommand($sql)->queryScalar();
                    $res = empty($res) ? 0 : $res;
                    $result['new_pay_user'] = $result['new_pay_user'] + $res;
                    $temp_uids = array();
                }
            }
            if (count($temp_uids) > 0) {
                $sql = "select count(*) from lcs_user_" . $i . " where uid in (" . implode(',', $temp_uids) . ") and c_time>='" . $search_date . "' and c_time<='".$day_end."'";
                $res = $db_r->createCommand($sql)->queryScalar();
                $res = empty($res) ? 0 : $res;
                $result['new_pay_user'] = $result['new_pay_user'] + $res;
            }
        }

        ///每日新增用户
        for ($i = 0; $i < 10; $i++) {
            $sql = "select count(*) from lcs_user_" . $i . " where c_time>='" . $search_date . "' and c_time<='".$day_end."'";
            $res = $db_r->createCommand($sql)->queryScalar();
            $res = empty($res) ? 0 : $res;
            $result['new_user'] = $result['new_user'] + $res;
        }

        return $result;
    }

    /**
     * 获取某个机构下当日访问的user_agent信息
     * @param string $search_date 查询日期
     */
    public function getUserAgent($search_date){
        $result = array();
        $today = date("Ymd",strtotime($search_date));
        $all_ua = Yii::app()->redis_r->hGetAll("lcs_ua_".$today);
        foreach($all_ua as $key=>$value){
            if($key == ""){
                $result["empty"] = empty($value)?0:$value;
            }else{
                $result[$key] = empty($value)?0:$value;
            }
        }
        return $result;
    }


    /**
     * 获取所有链接的fr分布
     */
    public function getUrlFr($search_date){
        $today = date("Ymd",strtotime($search_date));
        $all_ua = Yii::app()->redis_r->hGetAll("lcs_fr_".$today);
        foreach($all_ua as $item=>$count){
            if(preg_match('/\W/',$item)){
                continue;
            }else{
                $result[$item] = empty($count)?0:$count;
            }
        }
        return $result;
    }

    /**
     * 获取所有链接的sfr分布
     */
    public function getUrlSfr($search_date){
        $today = date("Ymd",strtotime($search_date));
        $all_ua = Yii::app()->redis_r->hGetAll("lcs_sfr_".$today);
        foreach($all_ua as $item=>$count){
            if(preg_match('/\W/',$item)){
                continue;
            }else{
                $result[$item] = empty($count)?0:$count;
            }
        }
        return $result;
    }

    /**
     * 统计订单相关内容
     */
    public function getOrderStat($search_date){
        try{
            $db_r = $this->getDBR();
            $one_day_after = date("Y-m-d",strtotime("+1 day",strtotime($search_date)));
            $res = array();
            $res['total'] = 0;

            $db_r->active=false;
            $db_r->active=true;
            ///每日成交订单分类
            $sql = "select type,count(*) as total from lcs_orders where c_time>='".$search_date."' and c_time<'".$one_day_after."' and status=2 group by type ";
            $data = $db_r->createCommand($sql)->queryAll();
            if($data){
                $temp = array();
                foreach($data as $item){
                   $temp[$item['type']] = $item['total']; 
                }
                $res['type'] = $temp;
            }

            $db_r->active=false;
            $db_r->active=true;
            ///每日关闭订单分类
            $sql = "select type,count(*) as total from lcs_orders where c_time>='".$search_date."' and c_time<'".$one_day_after."' and status=-1 group by type ";
            $data = $db_r->createCommand($sql)->queryAll();
            if($data){
                $temp = array();
                foreach($data as $item){
                   $temp[$item['type']] = $item['total']; 
                }
                $res['close'] = $temp;
            }

            $db_r->active=false;
            $db_r->active=true;
            ///每日退款订单分类
            $sql = "select type,count(*) as total from lcs_orders where c_time>='".$search_date."' and c_time<'".$one_day_after."' and status=4 group by type ";
            $data = $db_r->createCommand($sql)->queryAll();
            if($data){
                $temp = array();
                foreach($data as $item){
                   $temp[$item['type']] = $item['total']; 
                }
                $res['refund'] = $temp;
            }

            $db_r->active=false;
            $db_r->active=true;
            ///每日不同状态订单数统计
            $sql = "select status,count(*) as total from lcs_orders where c_time>='".$search_date."' and c_time<'".$one_day_after."'group by status";
            $data = $db_r->createCommand($sql)->queryAll();
            if($data){
                $temp = array();
                foreach($data as $item){
                    if($item['status']==2){
                        $res['total'] = $item['total'];
                    }
                   $temp[$item['status']] = $item['total']; 
                }
                $res['status'] = $temp;
            }

            $db_r->active=false;
            $db_r->active=true;
            ///每日成功支付的订单支付渠道统计
            $sql = "select pay_type,count(*) as total from lcs_orders where c_time>='".$search_date."' and c_time<'".$one_day_after."' and status=2 group by pay_type";
            $data = $db_r->createCommand($sql)->queryAll();
            if($data){
                $temp = array();
                foreach($data as $item){
                   $temp[$item['pay_type']] = $item['total']; 
                }
                $res['pay_type'] = $temp;
            }

            $db_r->active=false;
            $db_r->active=true;
            ///每日赚的钱
            $sql = "select sum(price) from lcs_orders where c_time>='".$search_date."' and c_time<'".$one_day_after."' and status=2";
            $data = $db_r->createCommand($sql)->queryScalar();
            $res['got'] = !empty($data)?$data:0;

            $db_r->active=false;
            $db_r->active=true;
            ///每日没赚到的钱
            $sql = "select sum(price) from lcs_orders where c_time>='".$search_date."' and c_time<'".$one_day_after."' and status=4";
            $data = $db_r->createCommand($sql)->queryScalar();
            $res['lose'] = !empty($data)?$data:0;
            return $res;
        }catch(Exception $e){
            var_dump($e->getMessage());
        }

    }

    /**
     * 发布圈子说说的理财师数 和用户数
     * @param $search_date
     * @return mixed
     */
    public function getCircleCommentStat($search_date) {
        $db_r = $this->getCommentDBR();
        $one_day_after = date("Y-m-d",strtotime("+1 day",strtotime($search_date)));
        $res = array();
        //用户数
        $sql = "select count(distinct(uid)) from lcs_comment_master where cmn_type=71 and u_type=1 and c_time>='{$search_date}' and c_time<='{$one_day_after}'";
        $res['user_count'] = $db_r->createCommand($sql)->queryScalar();
        //理财师数
        $sql = "select count(distinct(uid)) from lcs_comment_master where cmn_type=71 and u_type=2 and c_time>='{$search_date}' and c_time<='{$one_day_after}'";
        $res['planner_count'] = $db_r->createCommand($sql)->queryScalar();

        return $res;
    }

    /**
     * 圈子说说数统计
     * @param $search_date
     * @return array
     */
    public function getCircleCommentCountStat($search_date) {
        $db_r = $this->getCommentDBR();
        $one_day_after = date("Y-m-d",strtotime("+1 day",strtotime($search_date)));
        $res = array();
        //用户发布的圈子说说
        $sql = "select count(*)  from lcs_comment_master where cmn_type=71 and u_type=1 and c_time>='{$search_date}' and c_time<='{$one_day_after}'";
        $res['user_comment_count'] = $db_r->createCommand($sql)->queryScalar();
        //理财师发布的圈子说说
        $sql = "select count(*)  from lcs_comment_master where cmn_type=71 and u_type=2 and c_time>='{$search_date}' and c_time<='{$one_day_after}'";
        $res['planner_comment_count'] = $db_r->createCommand($sql)->queryScalar();
        //圈子说说总数
        $sql = "select count(*)  from lcs_comment_master where cmn_type=71 and c_time>='{$search_date}' and c_time<='{$one_day_after}'";
        $res['total_comment_count'] = $db_r->createCommand($sql)->queryScalar();

        return $res;
    }

    /**
     * 新加入圈子的用户和理财师
     * @param $search_date
     * @return array
     */
    public function getCircleNewUserCountStat($search_date) {
        $db_r = $this->getDBR();
        $one_day_after = date("Y-m-d",strtotime("+1 day",strtotime($search_date)));
        $res = array();
        //新入圈子的用户
        $sql = "select count(*) from lcs_circle_user where u_type=1 and c_time>='{$search_date}' and c_time<='{$one_day_after}'";
        $res['new_circle_user_count'] = $db_r->createCommand($sql)->queryScalar();
        //新入圈子的理财师
        $sql = "select count(*) from lcs_circle_user where u_type=2 and c_time>='{$search_date}' and c_time<='{$one_day_after}'";
        $res['new_circle_planner_count'] = $db_r->createCommand($sql)->queryScalar();

        return $res;
    }

    /**
     * 每天活跃的圈子数(分别有理财师和用户发说说)
     * @param $search_date
     * @return array
     */
    public function getCircleActiveCountStat($search_date) {
        $db_r = $this->getCommentDBR();
        $one_day_after = date("Y-m-d",strtotime("+1 day",strtotime($search_date)));
        $res = array();
        $sql = "select count(distinct(relation_id)) from lcs_comment_master where cmn_type=71 and c_time>='{$search_date}' and c_time<='{$one_day_after}'";
        $res['active_circle_count'] = $db_r->createCommand($sql)->queryScalar();

        return $res;
    }

    /**
     * 将用户访问日志转移到历史表中,为防止数据库一下子生成太大的日志文件，分批次转移数据
     */
    public function moveLogToHis(){
        try{
            $db_w = $this->getCommentDBW();
            $last_day = date("Y-m-d",strtotime("-3 day"));
            $sql = "select count(*) from ". $this->tableName()." where acc_time<='".$last_day."'";
            $count = $db_w->createCommand($sql)->queryScalar();
            $max_id_sql = "select max(id) from ". $this->tableName()." where acc_time<='".$last_day."'";
            $max_id = $db_w->createCommand($max_id_sql)->queryScalar();
            $i = 0;
            while($i<$count+1999){
                $sql = "delete from ". $this->tableName() ." where id<$max_id limit 2000";
                $res = $db_w->createCommand($sql)->execute(); 
                $i = $i + 2000;
                sleep(1);
            }
        }catch(Exception $ex){
            var_dump($ex->getMessage());
            return True;
        }
    }

    /**
     * 统计单个圈子的每日信息
     */
    public function statSingleCircle($search_date){
        try{
            $next_day = date("Y-m-d",strtotime("+1 day",strtotime($search_date)));
            $db_r = $this->getDBR();
            $circle = Circle::model()->getCircleInfoByPage("",1,1);
            $circle_total = $circle['total'];
            ///每次处理500个圈子的数据
            $row = 500;
            $pages = ceil($circle_total/$row + 1);

            for($i=1;$i<=$pages;$i++){
                $circles = Circle::model()->getCircleInfoByPage("",$i,$row);
                if(count($circles['data'])>0){
                    $all_circle_id = array();
                    $all_circle_info = array();
                    foreach($circles['data'] as $single_circle){
                        $all_circle_id[] = $single_circle['id'];
                        $all_circle_info[$single_circle['id']] = $single_circle;
                    }

                    $result = array();
                    $db_comment = $this->getCommentDBR();
                    $table_suffix = 0;
                    for($table_suffix=0;$table_suffix<=255;$table_suffix++){
                        $sql = "select count(*) as total,u_type,relation_id from lcs_comment_{$table_suffix} where cmn_type=71 and relation_id in (".implode(',',$all_circle_id).") and c_time>='{$search_date}' and c_time<='{$next_day}' group by relation_id,u_type;";
                        $data = $db_comment->createCommand($sql)->queryAll();
                        if(!empty($data)){
                            foreach($data as $t){
                                if(!isset($result[$t['relation_id']])){
                                    $result[$t['relation_id']]['user_total'] = 0;
                                    $result[$t['relation_id']]['planner_total'] = 0;
                                }
                                ///普通用户发布说说
                                if($t['u_type']==1){
                                    $result[$t['relation_id']]['user_total'] += $t['total'];
                                }else if($t['u_type']==2){
                                ///理财师发布说说
                                    $result[$t['relation_id']]['planner_total'] += $t['total'];
                                }
                            }
                        }
                    }
                    if(!empty($result)){
                        foreach($result as $circle_id=>$res){
                            ///总发言用户数
                            $sql = "select count(distinct(uid)) as total from lcs_comment_master where cmn_type=71 and relation_id={$circle_id} and c_time>='{$search_date}' and c_time<='{$next_day}';";
                            $res['total_su'] = $db_comment->createCommand($sql)->queryScalar();

                            ///用户发布说说数
                            $res['user'] = $res['user_total'];
                            ///理财师发布说说数
                            $res['planner'] = $res['planner_total'];
                            ///总发言数
                            $res['total'] = $res['user_total']+$res['planner_total'];
                            ///圈子标题
                            $res['title'] = $all_circle_info[$circle_id]['title'];
                            ///圈子id
                            $res['id'] = $circle_id;
                            ///圈子新增人数
                            $sql = "select count(*) as total from lcs_circle_user where u_type=1 and circle_id={$res['id']} and c_time>='{$search_date}' and c_time<='{$next_day}';";
                            $res['add'] = $db_r->createCommand($sql)->queryScalar();
                            ///当前圈子总人数
                            $sql = "select count(*) as total from lcs_circle_user where u_type=1 and circle_id={$res['id']} ";
                            $res['total_u'] = $db_r->createCommand($sql)->queryScalar();

                            ///查询该circle id的pv和uv
                            $sql = "select count(*) as pv,count(distinct(sina_global)) as uv from lcs_access_log where url='web-circle' and acc_time='{$search_date}' and s_url like '%{$circle_id}%';";
                            $data = $db_comment->createCommand($sql)->queryRow();
                            ///圈子pv
                            $res['pv'] = 0;
                            ///圈子uv
                            $res['uv'] = 0;
                            if(!empty($data)){
                                $res['pv'] = $data['pv'];
                                $res['uv'] = $data['uv'];
                            }
                        
                            if( $res['total']==0 && $res['add']==0 && $res['total_u']==0 && $res['pv']==0 && $res['uv']==0 ){
                                continue;
                            }
                            ///查询该圈子的礼物统计数量
                            $sql = "select count(*) as total,amount from lcs_orders where type=80 and relation_id=$circle_id and status=2 and c_time>='$search_date' group by amount";
                            $data = $db_r->createCommand($sql)->queryAll();
                            $res['gift'] = array();
                            if(!empty($data)){
                                foreach($data as $item){
                                    $res['gift'][$item['amount']] = $item['total'];
                                }
                            }
                            $key = "single_circle_".$res['id'];
                            $this->SaveAccessStat($key,$res,$search_date);
                        }
                    }
                }
            }
        }catch(Exception $ex){
            var_dump($ex->getMessage());
        }
    }

    ///统计圈子礼物
    public function statCircleGift($day_start){
        try{
            $db_r = $this->getDBR();
            $sql = "select count(*) as total,amount from lcs_orders where type=80  and status=2  and c_time>='$day_start'group by amount";
            $data = $db_r->createCommand($sql)->queryAll();
            $result = array();
            if(!empty($data)){
                foreach($data as $item){
                    $result[$item['amount']] = $item['total'];
                }
            }
            return $result;
        }catch(Exception $ex){
            var_dump($ex->getMessage());
        }
    }

}
