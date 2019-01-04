
<?php

/**
 * 理财师访问日志统计，基于ElasticStatic查询数据
 */
class ESstat extends CActiveRecord {

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
     * 获取每日系统新增用户的统计数据
     *
     */
    public function getUserStatByTime($time = ""){
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
        $index = "logstash-".date("Y.m.d",strtotime($search_date));
        $result['user_day_access'] = $this->getEsCount($index,"sina_global.raw");
        $result['user_day_login'] = $this->getEsCount($index,"uid");
        $result['user_day_nologin'] = $result['user_day_access'] - $result['user_day_login'];
        $result['user_day_device'] = $this->getEsCount($index,"deviceid.raw");
        return $result;
    }

    /**
     * 获取所有的url
     *
     */
    public function getAllUrl(){
        $index = "logstash-".date("Y.m.d",strtotime("-1 day",time()));
        $page_views = $this->getDistinctField($index,"action.raw");
        $all_page = array();
        if(!empty($page_views)){
            foreach($page_views as $item){
                $all_page[] = $item['key'];
            }
        }
        return $all_page;
    }

    /**
     * 页面访问量统计
     *
     */
    public function pageView(){
        $index = "logstash-".date("Y.m.d",strtotime("-1 day",time()));
        $page_views = $this->getDistinctField($index,"action.raw");
        $all_page_view = array();
        if(!empty($page_views)){
            foreach($page_views as $item){
                $all_page_view[$item['key']] = array();
                $all_page_view[$item['key']]['pv'] = $item['doc_count'];
                $all_page_view[$item['key']]['max'] = 0;
                $all_page_view[$item['key']]['min'] = 0;
                $all_page_view[$item['key']]['avg'] = 0;
            }
        }
        return $all_page_view;
    }

    /**
     * 统计总的页面访问量 
     */
    public function allPageView($search_date){
        $index = "logstash-".date("Y.m.d",strtotime($search_date));
        $url = ES_URL."/$index/logs/_count";
        $res = $this->getEs($url);
        $result = array();
        if(isset($res['count'])){
            $result['total'] = $res['count'];
        }else{
            $result['total'] = 0;
        }
        return $result;
    }

    /**
     * 统计单日页面的相关信息
     */
    public function singlePageView($search_date){
        $index = "logstash-".date("Y.m.d",strtotime($search_date));
        $url = ES_URL."/$index/logs/_count";
        $result = array();

        $all_url = $this->getAllUrl();
        if (!empty($all_url)){
            foreach($all_url as $item){
                $dsl = array(
                    "query"=>array(
                        "term"=>array(
                            "action.raw"=>"$item"
                        )
                    )
                );
                $data = $this->postEs($url,$dsl);

                $key = "page_url_".$item;
                $ip_key = "page_url_ip_".$item;
                
                $insert_data = array();
                $insert_data['total'] = isset($data['count'])?$data['count']:0;
                $insert_data['uv'] = 0; 
                $insert_data['sfr'] = array();
                $insert_data['referer'] = array();
                $insert_data['fr'] = array();

                $uv_count = $this->getEsCount($index,"sina_global.raw",array("term"=>array("action.raw"=>$item)));
                if(!empty($uv_count)){
                    $insert_data['uv'] = $uv_count;
                }else{
                    $insert_data['uv'] = 0;
                }

                $sfr_array = $this->getDistinctField($index,"sfr.raw",array("term"=>array("action.raw"=>$item)),10);
                if(!empty($sfr_array)){
                    $insert_data['sfr'] = $this->convertToKV($sfr_array);
                }

                $referer_array = $this->getDistinctField($index,"referer.raw",array("term"=>array("action.raw"=>$item)),10);
                if(!empty($referer_array)){
                    $insert_data['referer'] = $this->convertToKV($referer_array);
                }

                $fr_array = $this->getDistinctField($index,"fr.raw",array("term"=>array("action.raw"=>$item)),10);
                if(!empty($fr_array)){
                    $insert_data['fr'] = $this->convertToKV($fr_array);
                }

                $ua_array = $this->getDistinctField($index,"ua.raw",array("term"=>array("action.raw"=>$item)),2000);
                if(!empty($ua_array)){
                    $insert_data['ua'] = $this->convertToKV($ua_array);
                    $insert_data['ua'] = $this->formatUserAgent($insert_data['ua']);
                }

                $visit_ip = array();
                $visit_array = $this->getDistinctField($index,"ip.raw",array("term"=>array("action.raw"=>$item)),30);
                if(!empty($visit_array)){
                    $visit_ip = $this->convertToKV($visit_array);
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
        $index = "logstash-".date("Y.m.d",strtotime($search_date));
        $ua_array = $this->getDistinctField($index,"ua.raw",array("match_all"=>array()),2000);
        if(!empty($ua_array)){
            $result = $this->convertToKV($ua_array);
            $result = $this->formatUserAgent($result);
        }
        return $result;
    }

    /**
     * 获取所有链接的fr分布
     */
    public function getUrlFr($search_date){
        $result = array();
        $index = "logstash-".date("Y.m.d",strtotime($search_date));
        $fr_array = $this->getDistinctField($index,"fr.raw",array("match_all"=>array(),100));
        if(!empty($fr_array)){
            $insert_data['fr'] = $this->convertToKV($fr_array);
        }
        return $result;
    }

    /**
     * 获取所有链接的sfr分布
     */
    public function getUrlSfr($search_date){
        $result = array();
        $index = "logstash-".date("Y.m.d",strtotime($search_date));
        $sfr_array = $this->getDistinctField($index,"sfr.raw",array("match_all"=>array()),100);
        if(!empty($sfr_array)){
            $insert_data['sfr'] = $this->convertToKV($sfr_array);
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
            $index = "logstash-".date("Y.m.d",strtotime($search_date));

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
                            $dsl = array(
                                "query"=>array(
                                    "match_phrase"=>array(
                                        "url"=>"circle/$circle_id"
                                        )
                                    ),
                                    "aggs"=>array(
                                        "count"=>array(
                                            "cardinality"=>array(
                                                "field"=>"uid.raw"
                                            )
                                        )
                                    )
                                );
                            $url = ES_URL."/$index/logs/_search?size=0";
                            ///$result = $this->postEs($url,$dsl);
                            ///圈子pv
                            $res['pv'] = 0;
                            ///圈子uv
                            $res['uv'] = 0;
                            /*if(isset($result['hits'])){
                                $res['pv'] = $result['hits']['total'];
                            }

                            if(isset($result['aggregations'])){
                                $res['uv'] = $result['aggregations']['count']['value'];
                            }*/
                        
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

    /**
     * select distinct(uid) fromr lcs_user_index 类似语句
     *
     */
    private function getDistinctField($index,$field,$query = array("match_all"=>array()),$size = 10000000){
        $dsl = array(
            "query"=>$query,
            "aggs"=>array(
                "all"=>array(
                    "terms"=>array(
                        "field"=>"$field",
                        "size"=>$size #设置较大值，返回所有数据
                        )
                )
            )
        );
        $this->setFieldData($index,"$field");
        $url = ES_URL."/$index/logs/_search?size=0";
        $res = $this->postEs($url,$dsl);
        if(isset($res['aggregations'])){
            $value = $res['aggregations']['all']['buckets'];
            return $res['aggregations']['all']['buckets'];
        }else{
            return 0;
        }
    }

    /**
     * _count方法统计某个查询返回的具体命中数
     *
     */
    private function getQueryCount($index,$query = array("match_all"=>array())){
        $dsl = array(
            "query"=>$query
        );
        $url = ES_URL."/$index/logs/_count";
        $res = $this->postEs($url,$dsl);
        if(isset($res['count'])){
            $value = $res['count'];
            return $value;
        }else{
            return 0;
        }
    }

    /**
     * ES查询语句执行
     */
    private function esQuery($index,$query,$size=1){
        $dsl = array(
            "size"=>$size,
            "query"=>$query
        );
        $url = ES_URL."/$index/logs/_search";
        $res = $this->postEs($url,$dsl);
        if(isset($res['hits'])){
            $value = $res['hits']['hits'];
            return $value;
        }else{
            return 0;
        }
    }    
    /**
     * 根据字段聚合统计数量 ,比如统计有多少个不同的uid，多少个不同的url或者action之类
     *
     */
    private function getEsCount($index,$field,$query = array("match_all"=>array())){
        $dsl = array(
            "query"=>$query,
            "aggs"=>array(
                "count"=>array(
                    "cardinality"=>array(
                        "field"=>"$field"
                        )
                )
            )
        );
        $this->setFieldData($index,"$field");
        $url = ES_URL."/$index/logs/_search?size=0";
        $res = $this->postEs($url,$dsl);
        if(isset($res['aggregations'])){
            $value = $res['aggregations']['count']['value'];
            return $value;
        }else{
			var_dump($dsl);
			var_dump($res);
            return 0;
        }
    }

    /*
     * 设置ES mapping中的fielddata属性，以便可以进行聚类查询
     *
     */
    private function setFieldData($index,$field,$type="text"){
        $url = ES_URL."/$index/logs/_mapping";
        $param = array(
            "properties"=>array(
                "$field"=>array(
                    "type"=>"$type",
                    "norms"=>false,
                    "fielddata"=>true
                )
            )
        );
        $res = $this->postEs($url,$param);
    }

    /**
     *  ES get方法
     *
     */
    private function getEs($url){
        $index = 0;
        for($index=0;$index<=3;$index++){
            $curl = Yii::app()->curl;
            $curl->setTimeOut(59);
            $curl->setHeaders(array("Content-Type"=>"application/json; charset=UTF-8"));
            $res = $curl->get($url);
            $res = json_decode($res,true);
            if(!empty($res)){
                return $res;
            }
            sleep(10);
        }
        Common::model()->saveLog("GET查询ES错误,url:".$url,"error","stat");
        return false;
    }

    /**
     *  ES post方法
     *
     */
    private function postEs($url,$param){
        $index = 0;
        for($index=0;$index<=3;$index++){
            $curl = Yii::app()->curl;
            #$curl->setTimeOut(59);
            $curl->setHeaders(array("Content-Type"=>"application/json; charset=UTF-8"));
            $res = $curl->post($url,json_encode($param,JSON_FORCE_OBJECT));
            $res = json_decode($res,true);
            if(!empty($res)){
                return $res;
            }
            sleep(10);
        }
        Common::model()->saveLog("POST查询ES错误,url:".$url.",data:".json_encode($param),"error","stat");
        return false;
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
     * 统计每日理财师发布观点数和圈子粉丝数
     *
     */
    public function statPlannerInfo($start_day){
        try{
            $next_day = date("Y-m-d",strtotime("+1 day",strtotime($start_day)));
            $db_r = $this->getDBR();
            $result = array();
            $sql = "select count(*) from lcs_planner";
            $count = $db_r->createCommand($sql)->queryScalar();

            //获取观点数
            $month_begin = date("Y-m-01",strtotime($start_day));
			$month_end = date("Y-m-01",strtotime("+1 month",strtotime($start_day)));
            var_dump($month_begin);
            var_dump($month_end);

            $sql = "select p_uid,count(*) as total from lcs_view where p_time>='".$month_begin."' and p_time<'".$month_end."' group by p_uid";
            $view = $db_r->createCommand($sql)->queryAll();

            //获取圈子用户数
            $sql = "select circle.p_uid as s_uid,user.total as total from lcs_circle as circle left join (select circle_id,count(*) as total from lcs_circle_user group by circle_id) user on circle.id=user.circle_id where circle_id is not null";
            $circle_user = $db_r->createCommand($sql)->queryAll();


            //处理一个理财师有多个圈子的情况
            $sql = "select p_uid,total from (select p_uid,count(*) as total from lcs_circle group by p_uid) planner_circle where planner_circle.total >1;";
            $planner_circle = $db_r->createCommand($sql)->queryAll();

            //观点包关注人数统计
            $sql = "select p_uid,sum(collect_num) as total from lcs_package where status=0 group by p_uid";
            $planner_attention = $db_r->createCommand($sql)->queryAll();

			//理财师计划收入统计
			$sql = "select p_uid,sum(price) as total from lcs_orders where status=2 and type in (21,22) and c_time>='".$month_begin."' and c_time<'".$month_end."' group by p_uid";
			$plan_income = $db_r->createCommand($sql)->queryAll();

			//理财师问答收入统计
			$sql = "select p_uid,sum(price) as total from lcs_orders where status=2 and type in (11,12) and c_time>='".$month_begin."' and c_time<'".$month_end."' group by p_uid";
			$ask_income = $db_r->createCommand($sql)->queryAll();

			//理财师观点收入统计
			$sql = "select p_uid,sum(price) as total from lcs_orders where status=2 and type in (31,32) and c_time>='".$month_begin."' and c_time<'".$month_end."' group by p_uid";
			$view_income = $db_r->createCommand($sql)->queryAll();

            for($i=0;$i<$count;$i=$i+1000){
                $sql = "select s_uid,name,phone from lcs_planner where status=0 limit $i,1000";
                $planner = $db_r->createCommand($sql)->queryAll();
                if(!empty($planner)){
                    foreach($planner as $item){
                        $result[$item['s_uid']]['name'] = $item['name'];
                        $result[$item['s_uid']]['phone'] = $item['phone'];
                        $result[$item['s_uid']]['view'] = 0;
                        $result[$item['s_uid']]['attention'] = 0;
                        $result[$item['s_uid']]['circle_user'] = 0;
                        $result[$item['s_uid']]['plan_income'] = 0;
                        $result[$item['s_uid']]['view_income'] = 0;
                        $result[$item['s_uid']]['ask_income'] = 0;
                    }
                }

            }

            ///当月发送观点数
            if(!empty($view)){
                foreach($view as $item){
                    if(isset($result[$item['p_uid']])){
                        $result[$item['p_uid']]['view'] = $item['total'];
                    }
                }
            }

            ///圈子粉丝数
            if(!empty($circle_user)){
                foreach($circle_user as $item){
                    if(isset($result[$item['s_uid']])){
                        $result[$item['s_uid']]['circle_user'] = $item['total'];
                    }
                }
            }

            ///理财师多个圈子统计粉丝数
            if(!empty($planner_circle)){
                foreach($planner_circle as $item){
                    if(isset($result[$item['p_uid']])){
                        $sql = "select id from lcs_circle where p_uid='".$item['p_uid']."'";
                        $circle_ids = $db_r->createCommand($sql)->queryAll();
                        $ids = array();
                        foreach($circle_ids as $id){
                            $ids[] = $id['id'];
                        }
                        $sql = "select count(distinct(uid)) from lcs_circle_user where circle_id in (".implode(',',$ids).");";
                        $user_count = $db_r->createCommand($sql)->queryScalar();
                        $result[$item['p_uid']]['circle_user'] = $user_count;
                    }
                }
            }

            ///观点包关注人数统计
            if(!empty($planner_attention)){
                foreach($planner_attention as $item){
                    if(isset($result[$item['p_uid']])){
                        $result[$item['p_uid']]['attention'] = $item['total'];
                    }
                }
            }

            ///计划收入统计
            if(!empty($plan_income)){
                foreach($plan_income as $item){
                    if(isset($result[$item['p_uid']])){
                        $result[$item['p_uid']]['plan_income'] = $item['total'];
                    }
                }
            }

            ///观点收入统计
            if(!empty($view_income)){
                foreach($view_income as $item){
                    if(isset($result[$item['p_uid']])){
                        $result[$item['p_uid']]['view_income'] = $item['total'];
                    }
                }
            }
            ///问答收入统计
            if(!empty($ask_income)){
                foreach($ask_income as $item){
                    if(isset($result[$item['p_uid']])){
                        $result[$item['p_uid']]['ask_income'] = $item['total'];
                    }
                }
            }

            return $result;
        }catch(Exception $e){
            var_dump($e->getMessage());
            return false;
        }
    }

    /**
     * 获取lcs_client_caidao*客户端访问的所有用户的uid
     */
    public function getVisitUser($index,$client){
        if($client == "ios"){
            $dsl = '{"size":0,"query":{"term":{"url":"lcs_client_caidao_ios"}},"aggs":{"all_uid":{"terms":{"field":"uid.raw","size":10000000}}}}';
        }else{
            $dsl = '{"size":0,"query":{"term":{"url":"lcs_client_caidao_android"}},"aggs":{"all_uid":{"terms":{"field":"uid.raw","size":10000000}}}}';
        }
            $url = ES_URL."/$index/_search?size=0";
            $res = null;
            for($index=0;$index<=3;$index++){
                $curl = Yii::app()->curl;
                $curl->setTimeOut(59);
                $curl->setHeaders(array("Content-Type"=>"application/json; charset=UTF-8"));
                $res = $curl->post($url,$dsl);
                $res = json_decode($res,true);
                if(!empty($res)){
                    break;
                }
                sleep(10);
            }
            
            $all_uid = array();
            if(!empty($res) && isset($res['aggregations'])){
                $res = $res['aggregations']['all_uid']['buckets'];
                foreach($res as $item){
                    if($empty($item['key']) && $item['key']!=0 && $item['key']!=''){
                        $all_uid[] = $item['key'];
                    }
                }
                return $all_uid;
            }
            return $all_uid;
    }


    /**
     * 根据用户查找相应的自选股数据
     */
    public function getStockData($index,$day){
        $android_uid = $this->getVisitUser($index,"ios");
        $ios_uid = $this->getVisitUser($index,"android");
        $all_uid = array_merge($android_uid,$ios_uid);

        $next_day = date("Y-m-d",strtotime("+1 day",$day));
        $temp = array();
        $res = array("visit_stock"=>0,  ///访问自选股的用户数
            "visit_stock_detail"=>0,    ///访问自选股详情的用户数
            "has_stock"=>0);            ///拥有自选股的用户数
        foreach($all_uid as $item){
            $temp[] = $item;
            if(count($temp)>=50){
                $sql = "select count(distinct(uid)) from lcs_visit_log where utype=1 and uid in (".implode(',',$temp).") and r_id='' and c_time>='$day' and c_time<='$next_day')";
                $res['visit_stock'] = $res['visit_stock'] + Yii::app()->lcs_standby_r->createCommand($sql)->queryScalar();

                $sql = "select count(distinct(uid)) from lcs_visit_log where utype=1 and uid in (".implode(',',$temp).") and r_id!='' and c_time>='$day' and c_time<='$next_day')";
                $res['visit_stock_detail'] = $res['visit_stock_detail'] + Yii::app()->lcs_standby_r->createCommand($sql)->queryScalar();

                $sql = "select count(distinct(uid)) from lcs_user_stock_group where uid in (".implode(',',$temp).")";
                $res['has_stock'] = $res['has_stock'] + Yii::app()->lcs_standby_r->createCommand($sql)->queryScalar();
                $temp = array();
            }
        }

        $sql = "select count(distinct(uid)) from lcs_visit_log where utype=1 and uid in (".implode(',',$temp).") and r_id='' and c_time>='$day' and c_time<='$next_day')";
        $res['visit_stock'] = $res['visit_stock'] + Yii::app()->lcs_standby_r->createCommand($sql)->queryScalar();

        $sql = "select count(distinct(uid)) from lcs_visit_log where utype=1 and uid in (".implode(',',$temp).") and r_id!='' and c_time>='$day' and c_time<='$next_day')";
        $res['visit_stock_detail'] = $res['visit_stock_detail'] + Yii::app()->lcs_standby_r->createCommand($sql)->queryScalar();

        $sql = "select count(distinct(uid)) from lcs_user_stock_group where uid in (".implode(',',$temp).")";
        $res['has_stock'] = $res['has_stock'] + Yii::app()->lcs_standby_r->createCommand($sql)->queryScalar();

        return $res;
    }

	/**
	* 登录用户获取ua
	*
	*/
	public function getUserByUa($index,$ua,$item){
		$dsl = '{"query":{"bool":{"must":[{"match":{"ua":"'.$ua.'"}},{"range":{"uid":{"gte":10}}}]}},"aggs":{"users":{"cardinality":{"field":"'.$item.'"}}}}';
        $url = ES_URL."/$index/_search?size=0";

        $res = null;
        for($index=0;$index<=3;$index++){
            $curl = Yii::app()->curl;
            $curl->setTimeOut(59);
            $curl->setHeaders(array("Content-Type"=>"application/json; charset=UTF-8"));
            $res = $curl->post($url,$dsl);
            $res = json_decode($res,true);
            if(!empty($res)){
                break;
            }
            sleep(10);
        }

		if(!empty($res) && isset($res['aggregations'])){
			$res = $res['aggregations']['users']['value'];
			return $res;
		}
		return 0;
	}

	/**
	*
	* 访客用户获取ua
	*/
	public function getVisitorByUa($index,$ua){
		$dsl = '{"query":{"match":{"ua":"'.$ua.'"}},"aggs":{"users":{"cardinality":{"field":"sina_global.raw"}}}}';
        $url = ES_URL."/$index/_search?size=0";

        $res = null;
        for($index=0;$index<=3;$index++){
            $curl = Yii::app()->curl;
            $curl->setTimeOut(59);
            $curl->setHeaders(array("Content-Type"=>"application/json; charset=UTF-8"));
            $res = $curl->post($url,$dsl);
            $res = json_decode($res,true);
            if(!empty($res)){
                break;
            }
            sleep(10);
        }

		if(!empty($res) && isset($res['aggregations'])){
			$res = $res['aggregations']['users']['value'];
			return $res;
		}
		return 0;
	}

	/**
	* 获取客户端来源的uid，没有sina_global但是有uid的用户
	*/
	public function getIndivdualUid($index){
		$dsl = '{"query":{"bool":{"must":[{"range":{"uid":{"gte":1}}}]}},"aggs":{"users":{"cardinality":{"field":"sina_global.raw"}}}}';
        $url = ES_URL."/$index/_search?size=0";

        $res = null;
        for($index=0;$index<=3;$index++){
            $curl = Yii::app()->curl;
            $curl->setTimeOut(59);
            $curl->setHeaders(array("Content-Type"=>"application/json; charset=UTF-8"));
            $res = $curl->post($url,$dsl);
            $res = json_decode($res,true);
            if(!empty($res)){
                break;
            }
            sleep(10);
        }

		if(!empty($res) && isset($res['aggregations'])){
			$res = $res['aggregations']['users']['value'];
			return $res;
		}
		return 0;
	}

	/**
	* 获取注册用户的fr分布
	*/
	public function getUserFr($index){
        $result = array("pc"=>0,"app"=>0);

        $user_windows = $this->getUserByUa($index,"windows","uid");
        if($user_windows==0){
            $user_windows = $this->getUserByUa($index,"windows","uid.raw");
        }

        $user_darwin = $this->getUserByUa($index,"darwin","uid");
        if($user_darwin==0){
            $user_darwin = $this->getUserByUa($index,"darwin","uid.raw");
        }

        $user_weixin = $this->getUserByUa($index,"micromessenger","uid");
        if($user_weixin==0){
		    $user_weixin = $this->getUserByUa($index,"micromessenger","uid.raw");
        }

        $user_iphone = $this->getUserByUa($index,"iphone",'uid');
        if($user_iphone==0){
            $user_iphone = $this->getUserByUa($index,"iphone",'uid.raw');
        }

        $user_android = $this->getUserByUa($index,"android",'uid');
        if($user_android){
            $user_android = $this->getUserByUa($index,"android","uid.raw");
        }

        $user_ipad = $this->getUserByUa($index,"ipad",'uid');
        if($user_ipad){
            $user_ipad = $this->getUserByUa($index,"ipad",'uid.raw');
        }

        $user_ipod = $this->getUserByUa($index,"ipod",'uid');
        if($user_ipod){
            $user_ipod = $this->getUserByUa($index,"ipod",'uid.raw');
        }
		$result['pc'] = $user_windows + $user_darwin;
		$result['app'] = $user_weixin + $user_iphone + $user_android + $user_ipad + $user_ipod;
		return $result;
	}

	/**
	* 获取登录用户的fr分布
	*/
	public function getLoginUserFr($index){
		$result = array("pc"=>0,"app"=>0);
		$user_windows = $this->getVisitorByUa($index,"windows");
		$user_darwin = $this->getVisitorByUa($index,"darwin");
		$user_weixin = $this->getVisitorByUa($index,"micromessenger");
		$user_iphone = $this->getVisitorByUa($index,"iphone");
		$user_android = $this->getVisitorByUa($index,"android");
		$user_ipad = $this->getVisitorByUa($index,"ipad");
		$user_ipod = $this->getVisitorByUa($index,"ipod");
		$result['pc'] = $user_windows + $user_darwin;
		$result['app'] = $user_weixin + $user_iphone + $user_android + $user_ipad + $user_ipod;
		return $result;
		$result = array("pc"=>0,"app"=>0);
		$dsl = array("aggs"=>array(
								"agg"=>array(
									"terms"=>array(
										"field"=>"ua.raw",
										"size"=>1500
									),
									"aggs"=>array(
										"users"=>array(
											"cardinality"=>array(
												"field"=>"sina_global.raw"
											)
										)
									)
								)
							));
        $url = ES_URL."/$index/logs/_search";
        $res = $this->postEs($url,$dsl);
		if(!empty($res) && isset($res['aggregations'])){
			$res = $res['aggregations']['agg']['buckets'];
			foreach($res as $item){
				$ua = $item['key'];
				$val = $item['users']['value'];
				if($val!=0){
					$ua = $this->userAgent($ua);
					if($ua == "pc"){
						$result['pc'] =  $result['pc'] + $val;
					}else{
						$result['app'] =  $result['app'] + $val;
					}
				}
			}
		}
		return $result;
	}

    /**
     * 根据用户的uid查找该用户的来源,pc/app端
     *
     */
    public function getUserFrom($index,$uid){
        $result = $this->esQuery($index,array("term"=>array("uid"=>array("value"=>$uid))));
		if(isset($result[0]['_source'])){
			$ua = $result[0]['_source']['ua'];
			return $this->userAgent($ua);
		}
		return "pc";
    }
    /**
     * 统计每日产品纬度的数据
     * 用户相关、观点、问答、计划等数据
     */
    public function statProductInfo($start_day){
        try{
            $index = "logstash-".date("Y.m.d",strtotime($start_day));
            $end_day = date("Y-m-d",strtotime('+ 1 day',strtotime($start_day)));
            $db_r = $this->getDBR();
            $result = array();
			var_dump("1");
            $result['new_user'] = 0;
            $result['new_user_app'] = 0;
            $result['new_user_pc'] = 0;
			$result['user_access'] = 0;
			$result['user_access_app'] = 0;      #独立访客中来自app的
			$result['user_access_pc'] = 0;       #独立访客中来自pc的
			$result['user_login'] = 0;			 #已注册用户数
			$result['user_app'] = 0;    		 #已注册app
			$result['user_pc'] = 0;  		 #已注册pc
            $result['live_1_day'] = 0;
            $result['live_7_day'] = 0;
			///每日访问用户的fr分布
			$login_fr = $this->getLoginUserFr($index);
			$result['user_access_app'] = $login_fr['app'];
			var_dump("2");
			$result['user_access_pc'] = $login_fr['pc'];
			///每日登录用户的fr分布
			$user_fr = $this->getUserFr($index);
            $result['user_app'] = $user_fr['app'];
            $result['user_pc'] = $user_fr['pc'];

			var_dump("3");
            ///统计每日新增用户数
            for($i=0;$i<10;$i++){
                $cmd = "select uid,s_uid from lcs_user_$i where c_time>='".$start_day."' and c_time<'".$end_day."'";
                $res = $db_r->createCommand($cmd)->queryAll();
                foreach($res as $item){
                    $ua = $this->getUserFrom($index,$item['uid']);
					if($ua == "pc"){
						$result['new_user_pc'] = $result['new_user_pc'] + 1;
					}else{
						$result['new_user_app'] = $result['new_user_app'] + 1;
					}
                }
                $result['new_user'] = $result['new_user'] + count($res);
            }
			var_dump("4");
            $result['user_access'] = $this->getEsCount($index,"sina_global.raw");
			var_dump("5");
            $result['user_login'] = $this->getEsCount($index,"uid");
			if($result['user_login'] == 0){
            	$result['user_login'] = $this->getEsCount($index,"uid.raw");
			}
			var_dump("6");

			$indivdual_user = $this->getIndivdualUid($index);
			var_dump("7");
			if($indivdual_user>0){
				$user_login_from_client = $result['user_login'] - $indivdual_user;
				$result['user_access'] = $result['user_access'] + $user_login_from_client;
				$result['user_access_app'] = $result['user_access_app'] + $user_login_from_client;
			}
			
            #统计自选股相关数据
            $stock_result = $this->getStockData($index,$start_day);
            $result['visit_stock'] = $stock_result['visit_stock'];
            $result['visit_stock_detail'] = $stock_result['visit_stock_detail'];
            $result['has_stock'] = $stock_result['has_stock'];

            #统计留存用户，次日留存
			$day_1_start = date("Y-m-d",strtotime("-1 day",strtotime($start_day)));
			$day_1_end = date("Y-m-d",strtotime($start_day));
			$end_day = date("Y-m-d",strtotime("+1 day",strtotime($start_day)));
			for($i=0;$i<10;$i++){
				$cmd = "select count(*) from lcs_user_$i where c_time>='".$day_1_start."' and c_time<'".$day_1_end."' and u_time>='".$start_day."' and u_time<'".$end_day."'";
                $res = $db_r->createCommand($cmd)->queryScalar();
                $result['live_1_day'] = $result['live_1_day'] + $res;
			}

            #统计留存用户，7日留存
			$day_7_start = date("Y-m-d",strtotime("-7 day",strtotime($start_day)));
			$day_7_end = date("Y-m-d",strtotime("-6 day",strtotime($start_day)));
			$end_day = date("Y-m-d",strtotime("+1 day",strtotime($start_day)));
			for($i=0;$i<10;$i++){
				$cmd = "select count(*) from lcs_user_$i where c_time>='".$day_7_start."' and c_time<'".$day_7_end."' and u_time>='".$start_day."' and u_time<'".$end_day."'";
                $res = $db_r->createCommand($cmd)->queryScalar();
                $result['live_7_day'] = $result['live_7_day'] + $res;
			}

			#观点相关
			$result['view_p'] = 0;			 #发布观点的理财师数
			$result['view'] = 0;			 #发布观点数
			$result['view_v'] = 0;			 #观点页访问数
			$result['view_c'] = 0;			 #观点页访问人数
			$result['view_buy'] = 0;		 #观点购买数
			$result['view_buy_money'] = 0;	 #观点购买金额
            $end_day = date("Y-m-d",strtotime('+ 1 day',strtotime($start_day)));

			$cmd = "select count(*) from lcs_view where c_time>='".$start_day."' and c_time<'".$end_day."'";
			$db_r = $this->getDBR();
			$result['view'] = $db_r->createCommand($cmd)->queryScalar();

			$cmd = "select count(distinct(p_uid)) from lcs_view where c_time>='".$start_day."' and c_time<'".$end_day."'";
			$db_r = $this->getDBR();
			$result['view_p'] = $db_r->createCommand($cmd)->queryScalar();

			$result['view_v'] = $this->getQueryCount($index,array("match"=>array("action"=>"viewInfo")));
			$result['view_c'] = $this->getEsCount($index,"sina_global.raw",array("match"=>array("action"=>"viewInfo")));
		
			$cmd = "select count(*) from lcs_orders where status=2 and type in (31,32) and c_time>='".$start_day."' and c_time<'".$end_day."'"; 
			$db_r = $this->getDBR();
			$result['view_buy'] = $db_r->createCommand($cmd)->queryScalar();
			
			$cmd = "select sum(price) from lcs_orders where status=2 and type in (31,32) and c_time>='".$start_day."' and c_time<'".$end_day."'";
			$db_r = $this->getDBR();
			$result['view_buy_money'] = $db_r->createCommand($cmd)->queryScalar();


			#问答相关
			$result['ask_u'] = 0;			#提问的用户数量
			$result['ask'] = 0;				#发布的问题数量
			$result['ask_p'] = 0;			#回答问题的理财师数量
			$result['ask_c'] = 0;			#回答的问题数
			$result['ask_avg'] = 0;			#平均回答时长
			$result['ask_ans'] = 0;			#5分钟内回答率
			$result['ask_buy'] = 0;			#问答的购买数量
			$result['ask_buy_money'] = 0;	#问答的购买金额

			$cmd = "select count(distinct(uid)) from lcs_ask_question where c_time>='".$start_day."' and c_time<'".$end_day."'";
			$db_r = $this->getDBR();
			$result['ask_u'] = $db_r->createCommand($cmd)->queryScalar();

			$cmd = "select count(*) from lcs_ask_question where c_time>='".$start_day."' and c_time<'".$end_day."'";
			$db_r = $this->getDBR();
			$result['ask'] = $db_r->createCommand($cmd)->queryScalar();

			$cmd = "select count(distinct(p_uid)) from lcs_ask_question where c_time>='".$start_day."' and c_time<'".$end_day."' and status>=3";
			$db_r = $this->getDBR();
			$result['ask_p'] = $db_r->createCommand($cmd)->queryScalar();

			$cmd = "select count(*) from lcs_ask_question where c_time>='".$start_day."' and c_time<'".$end_day."' and status>=3";
			$db_r = $this->getDBR();
			$result['ask_c'] = $db_r->createCommand($cmd)->queryScalar();

			$cmd = "select avg(minuts) from (select ceil((unix_timestamp(answer_time)-unix_timestamp(c_time))/60) as minuts from lcs_ask_question where c_time>='".$start_day."' and c_time<'".$end_day."' and status=3 and answer_time!='') new_ask_question;";
			$db_r = $this->getDBR();
			$result['ask_avg'] = $db_r->createCommand($cmd)->queryScalar();

			$cmd = "select sum(if(minuts>5,1,0)) as big,sum(if(minuts<5,1,0)) as small from (select ceil((unix_timestamp(answer_time)-unix_timestamp(c_time))/60) as minuts from lcs_ask_question where c_time>='".$start_day."' and c_time<'".$end_day."' and status=3 and answer_time!='') new_ask_question";
			$db_r = $this->getDBR();
			$temp_result = $db_r->createCommand($cmd)->queryRow();
			if(isset($temp_result['big'])){
				$total = $temp_result['big'] + $temp_result['small'];
				if($total != 0){
					$result['ask_ans'] =  $temp_result['small'] / $total;
				}
			}

			$cmd = "select count(*) from lcs_orders where status=2 and type in (11,12) and c_time>='".$start_day."' and c_time<'".$end_day."'";
			$db_r = $this->getDBR();
			$result['ask_buy'] = $db_r->createCommand($cmd)->queryScalar();
			
			$cmd = "select sum(price) from lcs_orders where status=2 and type in (11,12) and c_time>='".$start_day."' and c_time<'".$end_day."'";
			$db_r = $this->getDBR();
			$result['ask_buy_money'] = $db_r->createCommand($cmd)->queryScalar();

			#计划相关
			$result['plan_p'] = 0;          #创建计划的理财师数量
			$result['plan'] = 0;            #创建计划的数量
			$result['plan_buy'] = 0;		#计划的购买数量
			$result['plan_buy_money'] = 0;	#计划的购买金额

			$cmd = "select count(*) from lcs_plan_info where c_time>='".$start_day."' and c_time<'".$end_day."' and status>=1";
			$db_r = $this->getDBR();
			$result['plan'] = $db_r->createCommand($cmd)->queryScalar();

			$cmd = "select count(distinct(p_uid)) from lcs_plan_info where c_time>='".$start_day."' and c_time<'".$end_day."' and status>=1";
			$db_r = $this->getDBR();
			$result['plan_p'] = $db_r->createCommand($cmd)->queryScalar();

			$cmd = "select count(*) from lcs_orders where status=2 and type in (21,22) and c_time>='".$start_day."' and c_time<'".$end_day."'";
			$db_r = $this->getDBR();
			$result['plan_buy'] = $db_r->createCommand($cmd)->queryScalar();
			
			$cmd = "select sum(price) from lcs_orders where status=2 and type in (21,22) and c_time>='".$start_day."' and c_time<'".$end_day."'";
			$db_r = $this->getDBR();
			$result['plan_buy_money'] = $db_r->createCommand($cmd)->queryScalar();
			$str = "";	
            foreach($result as $k=>$v){
                if(empty($v)){
                    $str = $str."0 ";
                }else{
                    $str = $str.$v." ";
                }
			}
			return $start_day." ".$str."\n";
        }catch (Exception $ex){
            var_dump($ex->getMessage());
        }
    }

    /**
     * 格式转化
     *
     */
    public function convertToKV($data){
        $result = array();
        foreach($data as $item){
            $result[$item['key']] = $item['doc_count'];
        }
        return $result;
    }

    /**
     * 格式化userAgent并输出统计结果
     *
     */
    public function formatUserAgent($data){
        $result = array();
        foreach($data as $key=>$value){
            $agent = $this->userAgent($key);
            if(!isset($result[$agent])){
                $result[$agent] = 0;
            }else{
                $result[$agent] = $result[$agent] + $value;
            }
        }
        return $result;
    }

    /**
     * 处理各种ua情况
     * @param string $useragent user_agent值
     */
    public function userAgent($useragent) {
        // 微信
        $is_weixin = strripos($useragent, 'micromessenger');
        if ($is_weixin) {
            return 'weixin';
        }
        // iphone
        $is_iphone = strripos($useragent, 'iphone');
        if ($is_iphone) {
            return 'iphone';
        }
        // android
        $is_android = strripos($useragent, 'android');
        if ($is_android) {
            return 'android';
        }
        // ipad
        $is_ipad = strripos($useragent, 'ipad');
        if ($is_ipad) {
            return 'ipad';
        }
        // ipod
        $is_ipod = strripos($useragent, 'ipod');
        if ($is_ipod) {
            return 'ipod';
        }
        // pc电脑
        $is_pc = strripos($useragent, 'windows nt');
        if ($is_pc) {
            return 'pc';
        }
        // mac电脑
        $is_pc = strripos($useragent, 'mac');
        if ($is_pc) {
            return 'pc';
        }
        // mac电脑
        $is_pc = strripos($useragent, 'darwin');
        if ($is_pc) {
            return 'pc';
        }
        // mac电脑
        $is_pc = strripos($useragent, 'linux');
        if ($is_pc) {
            return 'pc';
        }
        return 'other';
    }

    /**
     * 将数据存放到es中
     * @param json   $data
     */
    public function saveEs($data){
        try{
            $url = ES_URL."/_bulk";
            $cmd = "curl -s -XPOST '".$url."' --data-binary @".$data." >  /tmp/eslog.txt ";
            exec($cmd);
            return true;
        }catch(Exception $e){
            return false;
        }
    }

}
