<?php

/**
 * 用户日志相关.
 * @author lixiang23 <lixiang23@staff.sina.com.cn>
 * Date: 16-11-30
 */
class StatAccessLog
{

    ///定时任务号
    const CRON_NO = 7801;

    private $redis_log_key = "lcs_user_action";

    public function process($type='') {
        $access_type=date('Ym');
        $index_name=Common::INDEX_LOG_NAME.$access_type;
        $end = time() + 60;
        if($type=='org'){
            return;
        }
        while (time()<$end) {
            $count = 0;
            $top_data = array();
            while($count<=100 && time()<$end){
                $pop_data = Yii::app()->redis_w->lPop($this->redis_log_key);
                if (empty($pop_data)) {
                    sleep(2);
                    continue;
                }
                $top_data[] = $pop_data;
                $count = $count + 1;
            }
            $push_data = "";
            $pindex = array("index"=>array("_index"=>$index_name,"_type"=>$access_type,"_id"=>""));
            foreach($top_data as &$item){
                $pindex["index"]['_id'] = date("YmdHis").rand(10000,99999);
                $push_data = $push_data.json_encode($pindex)."\n";

                $temp = json_decode($item,true);
                if(isset($temp['post'])){
                    unset($temp['post']);
                }
                $temp['c_time']=date(DATE_RFC3339,time());
                $push_data = $push_data.json_encode($temp)."\n";
            }
            CommonUtils::esdatabulk($index_name,$access_type,$push_data);
        }
    }

    /**
     * 保存访问admin2的日志
     */
    public function saveAdmin2Log() {
        $access_type=date('Ym');
        $index_name=Common::INDEX_ADMIN2_LOG.$access_type;
        $end = time() + 60;
        while (time()<$end){
            $count = 0;
            $top_data = array();
            while($count<=20 && time()<$end){
                $pop_data = Yii::app()->redis_w->lPop('lcs_admin_v2_user_action');
                if (empty($pop_data)) {
                    sleep(2);
                    continue;
                }
                $top_data[] = $pop_data;
                $count = $count + 1;
            }
            $push_data = "";
            $pindex = array("index"=>array("_index"=>$index_name,"_type"=>$access_type,"_id"=>""));
            foreach($top_data as &$item){
                $pindex["index"]['_id'] = date("YmdHis").rand(10000,99999);
                $push_data = $push_data.json_encode($pindex)."\n";

                $temp = json_decode($item,true);
                if(isset($temp['post'])){
                    unset($temp['post']);
                }
                $temp['c_time']=date(DATE_RFC3339,time());
                $push_data = $push_data.json_encode($temp)."\n";
            }
            CommonUtils::esdatabulk($index_name,$access_type,$push_data);
        }
    }

    //保存错误日志
    public function saveErrorLog(){
        $hash_key = 'lcs_elk_user_info_log_statics';
        $data = Yii::app()->redis_r->hGetAll($hash_key);
        if($data){
            foreach ($data as $k=>$v){
                print_r($v."\n");
            }
            Yii::app()->redis_w->del($hash_key);
        }
    }

    #private $stat_type = array("planner_info");
#private $stat_type = array("user_day_stat", "user_all_stat", "user_day_access", "page_view_day","user_lose", "user_agent","url_fr","url_sfr","single_page_view","all_page_view","orders_today","single_circle","circle_comment_user_day","circle_comment_count_day","circle_new_user_count_day","circle_active_count","circle_gift","product_info");
    private $stat_type = array("orders_today","single_circle","circle_comment_user_day","circle_comment_count_day","circle_new_user_count_day","circle_active_count","circle_gift");

    /**
     * 数据统计处理
     */
    public function StatLog($stat_date) {
        $today = date("Y-m-d H:i:s", time());
        ///常规每日统计数据
        foreach ($this->stat_type as $type) {
            $result = $this->getStatisticData($type,$stat_date);
            if( $type == 'single_page_view' || $type == "single_circle"){
                continue;
            }else if( $type == 'page_view_day'){
                AccessLog::model()->savePageView($type,$result,$stat_date);
            }else{
                AccessLog::model()->SaveAccessStat($type, $result,$stat_date);
            }
        }
    }

	
	public function ProductList(){
		$start_date = "2015-01-01";
		$end_date = "2017-07-06";
		while($start_date<=$end_date){
			$result = $this->getStatisticData("product_info",$start_date);
			$start_date = date("Y-m-d",strtotime("+1 day",strtotime($start_date)));
			echo $result;
		}
	}
    /**
     * 理财师数据统计处理
     */
    public function PlannerInfo($stat_date) {
        $today = date("Y-m-d", strtotime($stat_date));
        ///常规每日统计数据
        $result = $this->getStatisticData("planner_info",$stat_date);
        $table_head = "统计日期\t理财师姓名\t电话\t当月观点数\t观点包关注人数\t圈子粉丝数\t计划收入\t问答收入\t观点收入\r\n";
        $info = $table_head;
        foreach($result as $single){
            $info = $info.$today."\t".$single['name']."\t".$single['phone']."\t".$single['view']."\t".$single['attention']."\t".$single['circle_user']."\t".$single['plan_income']."\t".$single['ask_income']."\t".$single['view_income']."\r\n";
        }
        file_put_contents(DATA_PATH."/planner_".$today.".txt",$info);
        $sendMail = new NewSendMail('理财师观点，粉丝统计数据',$today."理财师统计数据",array('lixiang23@staff.sina.com.cn'),array(DATA_PATH."/planner_".$today.".txt"));
        #$sendMail = new NewSendMail('理财师观点，粉丝统计数据',$today."理财师统计数据",array('lixiang23@staff.sina.com.cn','jianmei.jiang@yintech.cn',"chengyu.wei@yintech.cn"),array(DATA_PATH."/planner_".$today.".txt"));
    }

    /**
     * 数据统计处理
     */
    public function ProductInfo($stat_date) {
        ///常规每日统计数据
        $type = 'product_info';
		$redis_key = MEM_PRE_KEY."product_info";
		$result = $this->getStatisticData($type,$stat_date);
        $product_info = Yii::app()->redis_r->get($redis_key);
        if(!empty($product_info)){
            $product_info = json_decode($product_info,true);
        }else{
            $product_info = array();
            $start_date = date('Y-m-d',strtotime("-1 months",time()));
            for($index=30;$index>=1;$index--){
                $curr_date = date('Y-m-d',strtotime("-$index days",time()));
                $curr_info = $this->getStatisticData($type,$curr_date);
				var_dump($curr_info);
                $product_info[$curr_date] = $curr_info;
            }
        }
        $product_info[$stat_date] = $result;
        Yii::app()->redis_w->set($redis_key,json_encode($product_info));
        $table_head = array("统计日期","新增访客","新增访客-app","新增访客-pc","访客人数","访客人数-app","访客人数-pc","已注册访客总数","已注册访客-app","已注册访客-pc","次日留存","7日留存","观点人数","观点数","点击量","观点页访问人数","观点购买人数","观点购买金额","提问人数","提问数","回答理财师数","回答数","平均相应时间(分钟)","5分钟内比例","问答购买人数","问答购买金额","发表计划人数","发表计划数","计划购买人数","计划购买金额");

        $info = array();
        foreach($product_info as $single){
			$single = trim($single);
            $temp = explode(' ',$single);
            $info[] = $temp;
        }

        $file_name = CommonUtils::outputExcelTable($table_head,$info);
		$sendMail = new NewSendMail('理财师每日产品统计数据',"理财师统计数据，附件下载",array('lixiang29@ggt.sina.com.cn'),array($file_name));
        #$sendMail = new NewSendMail('理财师每日产品统计数据',"理财师统计数据，附件下载",array('lixiang29@ggt.sina.com.cn','lidan.chen@yintech.cn','jianmei.jiang@yintech.cn','chengyu.wei@yintech.cn'),array($file_name));
    }

    /**
     * 导出用户信息
     */    
	public function DumpUser(){
		$db = Yii::app()->lcs_r;
		for($i=0;$i<10;$i++){
			$cmd = "select uid,name,phone,c_time from lcs_user_".$i." where phone!='' and name!=''";
			$temp_res = $db->createCommand($cmd)->queryAll();
			foreach($temp_res as $item){
				$cmd = "select max(c_time) as maxt,min(c_time) as mint,sum(price) as total from lcs_orders where uid='".$item['uid']."' and status=2";
				$order_res = $db->createCommand($cmd)->queryRow();
				$maxt = "空";
				$mint = "空";
				$total = 0;
				$planner_name = "空";
				if(!empty($order_res)){
					if(!empty($order_res['maxt'])){
						$maxt = $order_res['maxt'];
					}
					if(!empty($order_res['mint'])){
						$mint = $order_res['mint'];
					}
					$total = $order_res['total'];
				}
				$cmd = "select lcs_planner.name from (select p_uid from (select p_uid,count(*) as total from lcs_orders where uid='".$item['uid']."' and status=2 and p_uid!=0 group by p_uid) myorders order by total desc limit 1) planner left join lcs_planner on planner.p_uid=lcs_planner.s_uid;";
				$planner = $db->createCommand($cmd)->queryRow();
				if(!empty($planner_name)){
					$planner_name = $planner['name'];
				}
				try{
					if($total>0)
					#CommonUtils::decodePhoneNumber($item['phone']);
					echo $item['name']."&".CommonUtils::decodePhoneNumber($item['phone'])."&".$item['c_time']."&".$maxt."&".$mint."&".$total."&".$planner_name."\n";
				}catch(Exception $e){
					var_dump($item['phone']);
					continue;
				}
			}
		}
	}

    /**
     * 批量格式化数据
     */
    public function processAccessLog($save_data){
        try{
            $result = array();

            foreach($save_data as $data){
                $db_insert_data = array();
                $data = json_decode($data,true);
                ///访问链接必须要有
                if (isset($data['url'])&&!empty($data['url'])) {
                    $db_insert_data['url'] = str_replace("http://licaishi.sina.com.cn/","",$data['url']);
                } else {
                    continue;
                }
                if (isset($data['start_time']) && isset($data['end_time'])) {
                    $db_insert_data['duration'] = intval(($data['end_time'] - $data['start_time'])*1000);
                } else {
                    $db_insert_data['duration'] = 0;
                }

                if (isset($data['ua'])&&!empty($data['ua'])) {
                    $db_insert_data['ua'] = $this->userAgent($data['ua']);
                } else {
                    $db_insert_data['ua'] = "";
                }

                $url_array = explode('?',$db_insert_data['url']);
                if(count($url_array) >= 2){
                    $query_array = explode('&',$url_array[1]);
                    if(count($query_array)>0){
                        foreach($query_array as $query){
                            $temp = explode('=',$query);
                            if( count($temp) >=2){
                                if($temp[0] == 'fr'){
                                    $db_insert_data['fr'] = $temp[1];
                                }else if($temp[0] == 'fc_v'){
                                    $db_insert_data['fc_v'] = $temp[1];
                                }
                            }
                        }
                    }
                }
            
                if(count($url_array) >=2 ){
                    $query_array = explode('&',$url_array[1]);
                    if(count($query_array)>0){
                        foreach($query_array as $query){
                            $temp = explode('=',$query);
                            if( count($temp) >=2){
                                if($temp[0] == 'sfr'){
                                    $db_insert_data['sfr'] = $temp[1];
                                }
                            }
                        }
                    }
                } 

                
                if (!isset($db_insert_data['sfr'])){
                    $db_insert_data['sfr'] = "";
                }

                #保存原始的url
                $db_insert_data['s_url'] = $db_insert_data['url'];

                if (isset($data['action'])){
                    $db_insert_data['url'] = $data['action'];
                }else{
                    $db_insert_data['url'] = explode('?',$db_insert_data['url'])[0];
                }

                ///过滤部分链接
                if(!$this->filterUrl($db_insert_data['url'])){
                    continue;
                }

                if (isset($data['ip'])){
                    $temp_ip = ip2long($data['ip']);
                    if( $temp_ip == false || $temp_ip < 0 ){
                        $db_insert_data['ip'] = 0;
                    }else{
                        $db_insert_data['ip'] = $temp_ip;
                    }
                    if($db_insert_data['ip']==3396086703){
                        continue;
                    }
                }else{
                    $db_insert_data['ip'] = 0;
                }

                if (!isset($db_insert_data['fr'])) {
                    $db_insert_data['fr'] = '';
                }

                if (!isset($db_insert_data['fc_v'])) {
                    $db_insert_data['fc_v'] = '';
                }


                if (isset($data['sina_global'])&&!empty($data['sina_global'])) {
                    $db_insert_data['sina_global'] = $data['sina_global'];
                } else {
                    $db_insert_data['sina_global'] = -1;
                }

                if (isset($data['deviceid'])&&!empty($data['deviceid'])) {
                    $db_insert_data['equipment_no'] = $data['deviceid'];
                } else {
                    $db_insert_data['equipment_no'] = "";
                }

                if (isset($data['uid'])&&!empty($data['uid'])) {
                    $db_insert_data['uid'] = $data['uid'];
                    $uid_list[] = $data['uid'];
                } else {
                    $db_insert_data['uid'] = 0;
                }

                $db_insert_data['referer'] = "";

                $db_insert_data['c_time'] = date("Y-m-d H:i:s", time());
                $db_insert_data['acc_time'] = date("Y-m-d", time());
                $result[] = $db_insert_data;

            }
            return $result;
        }catch(Exception $ex){
            return array();
        }
    }

    /**
     * 过滤部分url
     *
     */
    public function filterUrl($url){
        $filter_url = array("api-circleCommentList","/api/circleCommentList","apic1-circleCommentList");
        if(in_array($url,$filter_url)){
            return false;
        }else{
            return true;
        }
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
        return 'other';
    }

    /**
     * 根据类型获取统计数据
     * @param type $type
     * @param date $day_start
     */
    public function getStatisticData($type, $day_start = null) {
        try {

            $result = 0;
            $day_start = empty($day_start)?date("Y-m-d",strtotime("-1 day")):$day_start;

            switch ($type) {
                ///每日用户统计
                 case "user_day_stat":
                    $result = ESstat::model()->getUserStatByTime($day_start);
                    break;
                ///总的用户统计
                case "user_all_stat":
                    $result = ESstat::model()->getUserAllStat();
                    break;
                ///日活
                case "user_day_access":
                    $result = ESstat::model()->getUserAccessByTime($day_start);
                    break;
                ///每日页面PV
                case "page_view_day":
                    $result = ESstat::model()->pageView($day_start);
                    break;
                ///用户流失统计
                case "user_lose":
                    $result = ESstat::model()->userLose();
                    break;
                ///每日用户新增统计
                case "user_add":
                    $result = ESstat::model()->dayUserNewAdd($day_start);
                    return $result;
                ///访问sfr统计
                case "url_sfr":
                    $result = ESstat::model()->getUrlSfr($day_start);
                    return $result;
                ///访问fr统计
                case "url_fr":
                    $result = ESstat::model()->getUrlFr($day_start);
                    return $result;
                ///用户访问浏览器
                case "user_agent":
                    $result = ESstat::model()->getUserAgent($day_start);
                    return $result;
                ///统计当日的单页访问情况
                case "single_page_view":
                    ESstat::model()->singlePageView($day_start);
                    break;
                ///统计当日的总页访问情况
                case "all_page_view":
                    return ESstat::model()->allPageView($day_start);
                    break;
                ///统计当日的订单情况
                case "orders_today":
                    return ESstat::model()->getOrderStat($day_start);
                    break;
                ///统计当日的单个圈子统计情况
                case "single_circle":
                    return ESstat::model()->statSingleCircle($day_start);
                    break;
                ///发圈子说说用户数
                case "circle_comment_user_day":
                    return ESstat::model()->getCircleCommentStat($day_start);
                    break;
                ///圈子说说数
                case "circle_comment_count_day":
                    return ESstat::model()->getCircleCommentCountStat($day_start);
                ///新增圈子用户、和理财师数
                case "circle_new_user_count_day":
                    return ESstat::model()->getCircleNewUserCountStat($day_start);
                ///每日活跃圈子数
                case "circle_active_count":
                    return ESstat::model()->getCircleActiveCountStat($day_start);
                ///每日圈子礼物统计
                case "circle_gift":
                    return ESstat::model()->statCircleGift($day_start);
                ///统计每日的产品数据
                case "product_info":
                    return ESstat::model()->statProductInfo($day_start);
                default: break;
                case "planner_info":
                    return ESstat::model()->statPlannerInfo($day_start);
                default: break;
            }

            return $result;
        } catch (Exception $ex) {
            var_dump($ex->getMessage());
        }
    }
} 
