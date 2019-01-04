<?php
/**
 * 订单基本信息数据库访问类
 */
class Orders extends CActiveRecord {

	private $_refund_url = 'http://i.licaishi.sina.com.cn/api/applyRefund?debug=1';
	private $_sign = 'b77927faeb4bbeeafa9c';

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
    
    //订单记录表
    public function tableNameRecord(){
        return TABLE_PREFIX .'orders_record';
    }

    public function tableNameOrders(){
        return TABLE_PREFIX.'orders';
    }
    
    public function tableNameOrdersRefund(){
        return TABLE_PREFIX.'orders_refund';
    }
    
    public function tableNameUserAccountRecord(){
        return TABLE_PREFIX.'user_account_record';
    }
    /**
     * 获取充值
     * @param type $pay_type
     * @param type $status
     * @param type $start_time
     * @param type $end_time
     * @return type
     */
    public function getFillListNew($pay_type,$status,$start_time,$end_time){        		
		$fill_sql = "select order_no, pay_number,uid,price,pay_time,type,1 AS status,'' as weibo_order_no,'' AS refund_result_time,pay_type,c_time "
				." from lcs_orders "
				." where pay_type IN ($pay_type) and status in ($status) and c_time>='{$start_time}' and c_time<='{$end_time}' and price>0 ";				
		$refund_sql = "select a.order_no, a.pay_number, a.uid, a.price, a.pay_time, a.type, -1 AS status, a.pay_number as weibo_order_no,r.refund_result_time,a.pay_type,r.c_time "
				." from lcs_orders a left join lcs_orders_refund r on a.order_no=r.order_no "
				." where pay_type IN ($pay_type) and a.status=4 and r.refund_result=4 and r.c_time>='{$start_time}' and r.c_time<='{$end_time}'";
        $sql = "({$fill_sql}) UNION ({$refund_sql})";          
		$result = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        return $result;
    }

    /**
     * 
     * @param type $incr_day
     * @param type $type
     * @param type $start_time
     * @param type $end_time
     * @return type
     */
    public function getFillList($pay_type,$incr_day = '',$type = 0,$start_time = '',$end_time = ''){
        $sql = "SELECT order_no,pay_number,uid,price,pay_time,type,status,pay_number as weibo_order_no,'refund_result_time',pay_type FROM  ".$this->tableNameOrders();
        $conditions[] = "status IN (-2,2,3,4)";
        if(!empty($pay_type)){
            $conditions[] = "pay_type IN ($pay_type)";
        }
        if ($incr_day) {//单日
            $conditions[] = "DATE_FORMAT(c_time,'%Y-%m-%d')='{$incr_day}'";
        } else if($start_time) {//区间时间内
            $conditions[] = "c_time>='{$start_time}'";
            $conditions[] = "c_time<='{$end_time}'";
        }
        if($type){
            $conditions[] = "type=".$type;
        }
        $where = empty($conditions) ? '' : ' WHERE '.implode(' AND ', $conditions);        
        $order = " ORDER BY c_time ASC";
        $sql .= $where.$order;        
        $result = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        return $result;
    }
    /**
     * 
     * @param type $pay_type
     * @param type $status
     * @param type $incr_day
     * @param type $type
     * @param type $start_time
     * @param type $end_time
     * @return type
     */
    public function getFillRefundList($pay_type,$incr_day = '',$type = 0,$start_time = '',$end_time = ''){
        $sql = "SELECT a.order_no,a.pay_number,a.uid,a.price,a.pay_time,a.type,a.status,a.pay_number AS weibo_order_no,r.refund_result_time,a.pay_type "
                . " FROM {$this->tableNameOrders()} a LEFT JOIN {$this->tableNameOrdersRefund()} r ON a.order_no=r.order_no "
                . " WHERE r.refund_result=4";
        $conditions = "a.status=4";
        if(!empty($pay_type)){
            $conditions[] = "a.pay_type IN ($pay_type)";
        }
        if ($incr_day) {//单日
            $conditions[] = "DATE_FORMAT(r.c_time,'%Y-%m-%d')='" . $incr_day . "'";
        } else if($start_time) {//区间时间内
            $conditions[] = "r.c_time>='{$start_time}'";
            $conditions[] = "r.c_time<='{$end_time}'";
        }
        if($type){
            $conditions[] = "a.type=".$type;
        }
        $order_refund = ' ORDER BY r.c_time ASC';
        $where = empty($conditions) ? '' : ' AND '.implode(' AND ', $conditions);        
        $sql .= $where.$order_refund;
        $result_fund = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        return $result_fund;
    }
    /**
     * 获取消费列表
     * @param type $status
     * @param type $start_time
     * @param type $end_time
     * @return type
     */
    public function getOrderListNew($pay_type,$status,$start_time,$end_time){        		
		$sql = "select '1' AS type,a.type as sub_type,a.relation_id,a.uid,a.p_uid,b.real_name,a.order_no,a.c_time,
			           a.pay_number,a.pay_time,a.price,a.u_time as refund_end_time, a.status as o_status,'1' AS is_fencheng,
		               a.pay_type, c.end_time as pkg_end_time,a.pay_time AS order_month,a.u_time,d.real_end_time as pln_end_time, d.status as pln_status "
			 ." from lcs_orders a left JOIN lcs_planner b ON a.p_uid=b.s_uid "
			 ." left join lcs_package_subscription_history c ON a.order_no=c.order_no "
			 ." left join lcs_plan_info d ON a.relation_id=d.pln_id "
			 ." where a.status in ({$status}) and a.pay_type in ({$pay_type}) and a.type!='41' and a.c_time>='{$start_time}' and a.c_time<='{$end_time}' AND a.price>0 ";                
		$result = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();        
        return $result;
    }                

    /**
     * 
     * @param type $pay_type
     * @param type $status
     * @param type $type
     * @param type $incr_day
     * @param type $start_time
     * @param type $end_time
     * @return type
     */
    public function getOrderList($pay_type,$type,$incr_day,$start_time,$end_time){
        //一级产品类型 二级产品类型 项目ID 用户UID 理财师ID 理财师姓名 业务订单号    订单提交时间    微博订单号    付款时间    订单金额    订单退款截止日期    订单状态    是否分成    支付类型    服务到期时间
        $sql = "SELECT a.type,a.type as sub_type,a.relation_id,a.uid,a.p_uid,b.name,a.order_no,a.c_time,
			           a.pay_number,a.pay_time,a.price,a.u_time as refund_end_time,a.status as o_status,'is_fencheng',
		               a.pay_type,c.end_time as pkg_end_time,d.real_end_time as pln_end_time,d.status as pln_status "
                . " FROM lcs_orders a LEFT JOIN lcs_planner b ON a.p_uid=b.s_uid "
                . " LEFT JOIN lcs_package_subscription_history c ON a.order_no=c.order_no "
                . " LEFT JOIN lcs_plan_info d ON a.relation_id=d.pln_id "
                . " WHERE a.type!='41' ";
        $order = ' ORDER BY a.c_time ASC';
        $conditions[] = "status IN (-2,2,3,4)";
        if(!empty($pay_type)){
            $conditions[] = "a.pay_type IN ($pay_type)";
        }
        if ($incr_day) {//单日
            $conditions[] = " DATE_FORMAT(a.c_time,'%Y-%m-%d')='" . $incr_day . "'";
        } else if($start_time) {//区间时间内
            $conditions[] = "a.c_time>='{$start_time}'";
            $conditions[] = "a.c_time<='{$end_time}'";
        }
        if(!empty($type)){
            $conditions[] = "a.type IN ($type)";
        }
        $where = empty($conditions) ? '' : ' AND '.implode(' AND ', $conditions);   
        $sql .= $where.$order;        
        $result = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        return $result;
    }
    /**
     * 退款列表
     * @param type $start_time
     * @param type $end_time
     * @return type
     */
    public function getRefundListNew($start_time,$end_time){
        $refund_sql = "SELECT a.pay_time as order_month,1 AS type,a.type AS sub_type,a.relation_id,a.uid,a.p_uid,b.real_name,a.order_no,r.c_time,r.pay_number,a.pay_time,a.price,a.status AS o_status,
				           a.refund_lock AS is_fencheng,a.pay_type,a.u_time "
                . " FROM lcs_orders a LEFT JOIN lcs_planner b ON a.p_uid=b.s_uid
                        LEFT JOIN lcs_orders_refund r ON a.order_no=r.order_no "
                . " WHERE a.status=4 AND a.pay_type IN (1,2,4,5,8,9) ";
        $account_sql = "SELECT a.pay_time as order_month,1 AS type,a.type as sub_type,a.relation_id,a.uid,a.p_uid,b.real_name,a.order_no,r.c_time, '' AS refund_number,a.pay_time,a.price,
					       a.status AS o_status,a.refund_lock AS is_fencheng,a.pay_type,a.u_time "
                . " FROM lcs_orders a LEFT JOIN lcs_planner b ON a.p_uid=b.s_uid
                            LEFT JOIN lcs_user_account_record r ON a.order_no=r.order_no "
                . " WHERE a.status=4 AND a.pay_type IN (3,6) AND r.type=1 ";
        $refund_sql .= " AND r.c_time>='{$start_time}' AND r.c_time<='{$end_time}'";
        $account_sql .= " AND r.c_time>='{$start_time}' AND r.c_time<='{$end_time}'";
        
        $sql = '(' . $refund_sql . ') UNION (' . $account_sql . ')';
        $result = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        return $result;
    }

    /**
     * 
     * @param type $incr_day
     * @param type $start_time
     * @param type $end_time
     * @return type
     */
    public function getRefundList($incr_day = '',$start_time = '',$end_time = ''){
        $refund_sql = "SELECT a.type,a.type AS sub_type,a.relation_id,a.uid,a.p_uid,b.name,a.order_no,r.c_time,r.refund_number,a.pay_time,a.price,a.status AS o_status,
				           a.refund_lock AS is_fencheng,a.order_no AS refund_order_no,a.pay_type "
                . " FROM lcs_orders a LEFT JOIN lcs_planner b ON a.p_uid=b.s_uid
                        LEFT JOIN lcs_orders_refund r ON a.order_no=r.order_no "
                . " WHERE a.status=4 AND a.pay_type IN (1,2) ";
        $account_sql = "SELECT a.type,a.type as sub_type,a.relation_id,a.uid,a.p_uid,b.name,a.order_no,r.c_time, '' AS refund_number,a.pay_time,a.price,
					       a.status AS o_status,a.refund_lock AS is_fencheng,a.order_no AS refund_order_no,a.pay_type "
                . " FROM lcs_orders a LEFT JOIN lcs_planner b ON a.p_uid=b.s_uid
                            LEFT JOIN lcs_user_account_record r ON a.order_no=r.order_no "
                . " WHERE a.status=4 AND a.pay_type=3 AND r.type=1 ";

        if ($incr_day) {//单日
            $refund_sql .= " AND DATE_FORMAT(r.c_time,'%Y-%m-%d')='" . $incr_day . "'";
            $account_sql .= " AND DATE_FORMAT(r.c_time,'%Y-%m-%d')='" . $incr_day . "'";
        } else if(!empty($start_time) && !empty ($end_time)){//区间时间内
            $refund_sql .= " AND r.c_time>='{$start_time}' AND r.c_time<='{$end_time}'";
            $account_sql .= " AND r.c_time>='{$start_time}' AND r.c_time<='{$end_time}'";
        }
        $sql = '(' . $refund_sql . ') UNION (' . $account_sql . ')';
        $result = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        return $result;
    }
    
    public function ordersRefund($data) {
    	$p_key = array(
    		'order_no', 'pay_number', 'price', 'type', 'reason'
    	);
    	if (empty($data)) {
    		return false;
    	}
    	foreach ($p_key as $key) {
    		if (!isset($data[$key]) || !$data[$key]) {
    			return false;
    		}
    	}
    	$data['sign'] = md5($data['order_no'] . $this->_sign);  //md5加密
    	
    	$curl = Yii::app()->curl;
    	$result = $curl->post($this->_refund_url, $data);
    	if ($result) {
    		$result = json_decode($result, true);
    		if ($result['code'] == 0) {
    			return true;
    		} else {
    			return false;
    		}
    	} else {
    		return false;
    	}
    }
    
    /**
     * 保存订单记录
     * @param $order_no
     * @param $uid
     * @param $u_type
     * @param $oper_type
     * @param $oper_note
     * @return int
     */
    public function saveOrdersRecord($order_no,$uid,$u_type,$oper_type,$oper_note){
        $allow_oper_type = array(
            'submit_orders',//提交订单
            'pay_orders',//付款
            'answer_question',//回答问题
            'close_orders',//关闭订单
            'apply_refund',//申请退款
            'refund_success',//退款成功
            'refund_fail',//退款失败
            'audit_refund_pass',//运营审核退款 通过
            'audit_refund_not_pass',//运营审核退款 未通过
        );

        if(!in_array($oper_type,$allow_oper_type)){
            return 0;
        }

        $c_time = date("Y-m-d H:i:s");
        $sql = "INSERT INTO ".$this->tableNameRecord()."(order_no,uid,u_type,oper_type,oper_note,c_time,u_time) ".
            "VALUES(:order_no,:uid,:u_type,:oper_type,:oper_note,:c_time,:u_time)";
        $cmd =  Yii::app()->lcs_w->createCommand($sql);
        $cmd->bindParam(':order_no',$order_no,PDO::PARAM_STR);
        $cmd->bindParam(':uid',$uid,PDO::PARAM_INT);
        $cmd->bindParam(':u_type',$u_type,PDO::PARAM_INT);
        $cmd->bindParam(':oper_type',$oper_type,PDO::PARAM_STR);
        $cmd->bindParam(':oper_note',$oper_note,PDO::PARAM_STR);
        $cmd->bindParam(':c_time',$c_time,PDO::PARAM_STR);
        $cmd->bindParam(':u_time',$c_time,PDO::PARAM_STR);
        return $cmd->execute();
    }
    
    public function getOrdersInfoByOrderNo($order_no = array()){
        $order_no = (array)$order_no;
        $result = array();

        if(!empty($order_no)){
            foreach($order_no as $key=>$val){
                $order_no[$key] = "'".$val."'";
            }

            $orders = Yii::app()->lcs_r->createCommand("select order_no,status from lcs_orders where order_no in (".implode(",",$order_no).")")->queryAll();

            if(!empty($orders)){
                foreach($orders as $val){
                    $result[$val['order_no']] = $val;
                }
            }
        }

        return $result;
    }
    /**
     * 获取单个观点包的累计收入
     * @param pkg_id 观点包id
     * 
     */    
    function getPkgTotalIncome($pkg_id,$s_time){
        $sql = "select sum(price) as totals from ". $this->tableNameOrders() ." where relation_id=:relation_id and type=31 and status=2 and pay_time < :pay_time";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':relation_id',$pkg_id,PDO::PARAM_INT);
        $cmd->bindParam(':pay_time',$s_time,PDO::PARAM_STR);
        return $cmd->queryScalar();
        
    }
    
    public function getOrderListsbyType($where_params = array() , $c_time = 0 , $limit = 100){
        $where_params = (array)$where_params;
        $where = "";
        if($c_time){
            $where .= " c_time > '" .date('Y-m-d H:i:s', time()-3600*$c_time). "' and ";
        }
        if(!empty($where_params)) {
            foreach($where_params as $field=>$val) {
                $where .= $field . "=" . $val ." and ";
            }
        }
         
        $sql = "select id,order_no,type,discount_type,relation_id,price,price_old,uid,p_uid,status,description,pay_type,pay_number,c_time,amount,sub_lock,price_old from ".$this->tableNameOrders()
                ." where ".$where . " 1=1 limit ".$limit;
        $result = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        
        return $result;
        
    }

    /**
     * 获取指定类型的订单数据
     *
     * @param int $relation_id
     * @param int $type
     * @param array $amount
     * @return bool
     */
    public function getSubOrders($relation_id , $type = 31, $amount = array(6, 12)) {
        if (empty($relation_id)) {
            return false;
        }
        $amount = join(",", $amount);
        $sql = "select order_no,uid from {$this->tableNameOrders()} where relation_id=:relation_id and amount in({$amount}) and status=2 and type=:type;";

        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(":relation_id", $relation_id, PDO::PARAM_INT);
        $cmd->bindParam(":type", $type, PDO::PARAM_INT);
        $res = $cmd->queryAll();

        return $res;
    }
    /**
     * 获取某时间段的订单信息
     *
     */
    public function getOrderInfo($start_time='',$end_time=''){
        $db_r=Yii::app()->lcs_standby_r;
        $cdn='';
        if(!empty($start_time)){
            $cdn.=' and u_time>="'.$start_time.'"';
        }
        if(!empty($end_time)){
            $cdn.=' and u_time<="'.$end_time.'"';
        }
        $sql="select `id`,`order_no`,`uid`,`p_uid`,`relation_id`,`type`,`status`,`description`,`price`,`pay_time`,`c_time`,`u_time`,`pay_type`,`pay_number`,`fr`,`tg_id`,`tg_name` from ".$this->tableNameOrders()." where 1=1".$cdn.' order by u_time asc';
        $data=$db_r->createCommand($sql)->queryAll();
        return $data;
    }

    /**
     * 获取内购充值订单
     *
     */
    public function getOrderNiuBiList($start_time='',$end_time='',$uids){
        $db_r=Yii::app()->lcs_standby_r;
        $cdn=" type=41 and status=2 and pay_type=4 and fr LIKE '%lcs_client_caidao_ios%'";
        if(!empty($start_time)){
            $cdn.=' and pay_time>="'.$start_time.'"';
        }
        if(!empty($end_time)){
            $cdn.=' and pay_time<="'.$end_time.'"';
        }
        if(!empty($uids)){

            $cdn .= ' and uid not in (' . implode(',', $uids) . ')';
        }
        $sql="select uid,sum(amount) as sum_price from ".$this->tableNameOrders()." where".$cdn.' group by uid HAVING sum_price>=900';
        $data=$db_r->createCommand($sql)->queryAll();
        return $data;
    }



    /**
     * 获取双12冲牛币订单
     *
     */
    public function getOrderNiuBiList12($start_time='',$end_time='',$uids){
        $db_r=Yii::app()->lcs_standby_r;
        $cdn=' price>=1000 and type=41 and status=2';
        if(!empty($start_time)){
            $cdn.=' and pay_time>="'.$start_time.'"';
        }
        if(!empty($end_time)){
            $cdn.=' and pay_time<="'.$end_time.'"';
        }
        if(!empty($uids)){

            $cdn .= ' and uid not in (' . implode(',', $uids) . ')';
        }
        $sql="select uid from ".$this->tableNameOrders()." where".$cdn.' group by uid';
        $data=$db_r->createCommand($sql)->queryAll();
        return $data;
    }

    /**
     * 获取订单信息
     *
     */
    public function getOrderInfoByOrderNo($order_no){
        $db_r=Yii::app()->lcs_standby_r;
        if(empty($order_no)){
            return false;
        }
        $sql="select `id`,`order_no`,`u_time` from ".$this->tableNameOrders()." where order_no='".$order_no."'";
        $data=$db_r->createCommand($sql)->queryRow();
        return $data;
    }

    /**
     * 获取指定时间段内包含圈子付费的订单列表
     * @param $start_time
     * @param $end_time
     * @param $circle_pkg_id 付费圈子所在套餐包ID数组
     * @return mixed1
     */
    public function getCircleOrders($start_time,$end_time,$circle_pkg_id){
        $db_r=Yii::app()->lcs_standby_r;
        $cdn = '';
        if(!empty($start_time)){
            $cdn .= ' and u_time>="'.$start_time.'"';
        }
        if(!empty($end_time)){
            $cdn .= ' and u_time<="'.$end_time.'"';
        }
        $sql = "select id,order_no,uid,relation_id,p_uid,pay_time,type from ".$this->tableNameOrders()." where status=2 and type=66 and price>0 and relation_id in (".implode(',',$circle_pkg_id).")".$cdn." order by id desc";
        $data=$db_r->createCommand($sql)->queryAll();
        return $data;
    }

    /**
     * 查询非指定订单号的其他类似订单数量
     * @param $order_no
     * @param $ext
     * @return mixed
     */
    public function getCountCircleOrders($order_no,$ext){
        $where = '';
        if(!empty($ext)){
            foreach ($ext as $k=>$v){
                $where .= " and ".$k."='".$v."'";
            }
        }
        $db_r=Yii::app()->lcs_standby_r;
        $sql = "select count(*) from ".$this->tableNameOrders()." where order_no!=".$order_no.$where;
        $data=$db_r->createCommand($sql)->queryScalar();
        return $data;
    }
}