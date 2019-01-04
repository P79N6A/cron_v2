<?php

/**
 * 生成增量文件
 */
class IncrementOrders {

    const CRON_NO = 8889;

    private static $rsync_address = ' 1.210.225.11::di/data/'; //前面留空格
    private static $data_path = '/usr/home/hailin3/www/cron_v2/data/';
    private static $filename_map = array(
        'fill' => 'wbpay_otherfinance_fill.txt',
        'order' => 'wbpay_otherfinance_order.txt',
        'strike' => 'wbpay_otherfinance_strike.txt',
        'author' => 'wbpay_otherfinance_author.txt',
        'pay' => 'wbpay_otherfinance_pay.txt',
        'project' => 'wbpay_otherfinance_project.txt'
    );
    //增量时间
    private $start_time = '';
    private $end_time = '';
    private $incr_day = FALSE;
    private $is_sync = true;  //是否同步文件
    private $type = null;    //订单类型
    private $pay_type = null; //订单支付类型
    private $fixid = null; //修复id
    
    //理财师订单状态与财务订单状态的映射
    public static $status_text = array(
        -2 => '1', 2 => '1', 3 => '1', 4 => '-1'
    );
    public static $o_status_text = array(
        -2 => '退款失败', 2 => '已付款', 3 => '退款中', 4 => '已退款'
    );
    //订单类型
    public static $type_text = array(
        11 => '问答提问', 12 => '问答解锁', 21 => '理财计划', 31 => '观点包', 32 => '单条观点', 41 => '牛币', 51 => '财学会',
    );
    //充值类型
    public static $use_text = array(
        11 => '直充', 12 => '直充', 21 => '直充', 31 => '直充', 32 => '直充', 41 => '牛币', 51 => '直充',
    );
    //支付类型
    public static $pay_type_text = array(
        1 => '直充', 2 => '直充', 3 => '牛币',
    );
    //渠道
    public static $pay_text = array(
        1 => '支付宝', 2 => '支付宝', 3 => '牛币',
    );

    /**
     * 
     * @param type $start_time 开始时间
     * @param type $end_time 结束时间
     * @param type $is_sync 是否同步 默认同步
     */
    public function __construct($pay_type = '',$type = '') {
        $this->incr_day = date('Y-m-d', strtotime("-2 days"));        
        $this->type = $type;
        $this->pay_type = $pay_type;
    }

    /**
     * 充值订单：包括直充和购买牛币
     */
    public function doRechargeIncr() {
        $row_str = '';
        $data = array();                
        $status = '-2, 2,3,4';
        $result = Orders::model()->getFillList($this->pay_type,$status,$this->incr_day,$this->type,$this->start_time,$this->end_time);
        //业务订单号    微博订单号   用户UID 充值金额   充值时间   充值类型(牛币/直充/…)  订单状态(1:正常；-1：退款)  原微博订单号   退款时间    渠道
        foreach ($result as $v) {
            $v['status'] = 1;
            $v['weibo_order_no'] = '';
            $v['refund_result_time'] = '';
            $data[] = $v;
            $v['type'] = self::$use_text[$v['type']];
            $v['pay_type'] = self::$pay_text[$v['pay_type']];
            $row_str .= mb_convert_encoding(implode("\t", $v) . "\n", 'gb18030', 'utf-8');
        }
        unset($result);
        //退款订单（status=4）
        $result_fund = Orders::model()->getFillRefundList($this->pay_type,4,  $this->incr_day,  $this->type,$this->start_time,$this->end_time);
        foreach ($result_fund as $value) {
            $value['status'] = -1;
            if ($value['refund_result_time'] == '0000-00-00 00:00:00') {
                $value['refund_result_time'] = '';
            }
            $data[] = $value;
            $value['type'] = self::$use_text[$value['type']];
            $value['pay_type'] = self::$pay_text[$value['pay_type']];
            $row_str .= mb_convert_encoding(implode("\t", $value) . "\n", 'gb18030', 'utf-8');
        }
        unset($result_fund);
        //插入数据
        if (empty($data)) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '无订单充值增量数据');
            return;
        }
        StatWbpay::model()->saveFill($data);
        self::saveFile('fill', $row_str);
        if ($this->is_sync) {
            $this->execRsync('fill');
        }
    }

    /**
     * 消费订单
     */
    public function doConsumerIncr() {
        $data = array();
        $row_str = '';
        //一级产品类型 二级产品类型 项目ID 用户UID 理财师ID 理财师姓名 业务订单号    订单提交时间    微博订单号    付款时间    订单金额    订单退款截止日期    订单状态    是否分成    支付类型    服务到期时间        
        $result = Orders::model()->getOrderList($this->pay_type, $this->type,  $this->incr_day,  $this->start_time,  $this->end_time);
        foreach ($result as $value) {
            $sub_type = $value['sub_type'];
            $value['is_fencheng'] = 1;
            $value['refund_end_time'] = '';
            if ($sub_type != '31' || $value['pkg_end_time'] == '0000-00-00 00:00:00') {
                $value['pkg_end_time'] = '';
            }
            if (($sub_type == '21' && $value['price'] == '88') || $sub_type == '51') {
                $value['is_fencheng'] = 2;
            }
            if ($sub_type == '21' && $value['pln_status'] == 5) {
                $value['refund_end_time'] = date('Y-m-d', strtotime('+30 days', strtotime($value['pln_end_time'])));
            }
            $value['type'] = '理财师';
            unset($value['pln_end_time'], $value['pln_status']);
            $data[] = $value;
            $value['is_fencheng'] = ($value['is_fencheng'] == 1) ? '是' : '否';
            $value['sub_type'] = self::$type_text[$sub_type];
            $value['o_status'] = self::$o_status_text[$value['o_status']];
            $value['pay_type'] = self::$pay_type_text[$value['pay_type']];
            $row_str .= mb_convert_encoding(implode("\t", $value) . "\n", 'gb18030', 'utf-8');
        }
        unset($result);
        StatWbpay::model()->saveOrder($data);
        self::saveFile('order', $row_str);
        if ($this->is_sync) {
            $this->execRsync('order');
        }
    }
    
    public function doConsumerIncrFix() {
        exec("ps aux | grep 'consumerIncrFix'",$output,$result);
        if(sizeof($output) > 4){
            echo 'task running';
            return;
        }
        $list = StatWbpay::model()->getFixData();
        if(empty($list)){
            echo '无任务';
            return;
        }   
        $func = array(
            '1'=>'doRechargeIncr',
            '2'=>'doConsumerIncr',
            '3'=>'doRefundIncr',
        );
        $del_func = array(
            '1'=>'deleteFill',
            '2'=>'deleteOrder',
            '3'=>'deleteRefund'
        );
        foreach ($list as $item){
            if(!isset($func[$item['action']])){
                continue;
            }
            $this->fixid = $item['id'];
            $this->start_time = ($item['start_time'] == '0000-00-00 00:00:00') ? '' : $item['start_time'];
            $this->end_time = ($item['end_time'] == '0000-00-00 00:00:00') ? '' : $item['end_time'];
            $this->incr_day = null;
            $this->is_sync = $item['is_sync'];
            $this->type = $item['type'];
            $this->pay_type = $item['pay_type'];
            
            StatWbpay::model()->$del_func[$item['action']]($this->pay_type,  $this->type,  $this->start_time,  $this->end_time);
            $this->$func[$item['action']]();
        }
        
    }

    /**
     * 退款订单
     */
    public function doRefundIncr() {
        $data = array();
        $row_str = '';
        //一级产品类型 二级产品类型 项目ID 用户UID 理财师ID 理财师姓名 退款业务订单号 退款订单提交时间 退款微博订单号 退款付款时间 退款订单金额
        //订单状态 是否分成 被退业务订单号 支付类型		
        $result = Orders::model()->getRefundList($this->incr_day,  $this->start_time,  $this->end_time);
        foreach ($result as &$value) {
            $value['type'] = '理财师';
            $value['is_fencheng'] = 1;
            if ($value['type'] == '21' && $value['price'] == '88') {
                $value['is_fencheng'] = 2;
            }
            $data[] = $value;
            $value['is_fencheng'] = ($value['is_fencheng'] == 1) ? '是' : '否';
            $value['sub_type'] = self::$type_text[$value['sub_type']];
            $value['o_status'] = self::$o_status_text[$value['o_status']];
            $value['pay_type'] = self::$pay_type_text[$value['pay_type']];
            $row_str .= mb_convert_encoding(implode("\t", $value) . "\n", 'gb18030', 'utf-8');
        }
        StatWbpay::model()->saveStrike($data);
        self::saveFile('strike', $row_str);
        if ($this->is_sync) {
            $this->execRsync('strike');
        }
    }

    /**
     * 问答、观点包、观点
     */
    public function doProjectIncr() {
        $data = array();
        $row_str = '';
        //问答、观点、观点包
        $sql = "SELECT relation_id,description,c_time AS start_date,u_time AS end_date,type,c_time AS real_end_time FROM lcs_orders 
				WHERE type IN (11,12,31,32) AND status in (-2,2,3,4)";
        if ($this->incr_day) {//单日
            $sql .= " AND (DATE_FORMAT(u_time,'%Y-%m-%d')='{.$this->incr_day.}' OR (DATE_FORMAT(pay_time,'%Y-%m-%d')='{$this->incr_day}')";
        } else {//区间时间内
            $sql .= " AND ((u_time>='{$this->start_time}' AND u_time<='{$this->end_time}') OR (pay_time>='{$this->start_time}' AND pay_time<='{$this->end_time}'))";
        }
        $result = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        //项目ID  项目名称    预计开始日期    预计结束日期    二级产品类型    实际结束时间
        //fwrite($file, $f_title);
        $relation_ids = array();
        foreach ($result as $value) {
            if (!empty($relation_ids) && in_array($value['relation_id'] . '_' . $value['type'], $relation_ids)) {
                continue;
            }
            if ($value['type'] == 11 || $value['type'] == 12) {
                $value['description'] = ($value['type'] == 11) ? '问答' : '解锁';
            } elseif ($value['type'] == 32) {
                $value['description'] = '观点';
            }
            $value['start_date'] = '';
            $value['end_date'] = '';
            $value['real_end_time'] = '';
            $data[] = $value;
            $value['type'] = self::$type_text[$value['type']];
            $row_str .= mb_convert_encoding(implode("\t", $value) . "\n", 'gb18030', 'utf-8');
            $relation_ids[] = $value['relation_id'] . '_' . $value['type'];
        }
        unset($relation_ids);
        //计划
        $plan_order_sql = "SELECT DISTINCT relation_id FROM lcs_orders WHERE type=21 AND status IN (-2,2,3,4)";
        $plan_sql = "SELECT pln_id FROM lcs_plan_info";
        if ($this->incr_day) {//单日
            $plan_order_sql .= " AND (DATE_FORMAT(u_time,'%Y-%m-%d')='{.$this->incr_day.}' OR (DATE_FORMAT(pay_time,'%Y-%m-%d')='{$this->incr_day}')";
            $plan_sql .= " WHERE DATE_FORMAT(real_end_time,'%Y-%m-%d')='" . $this->incr_day . "'";
        } else {//区间时间内
            $plan_order_sql .= "AND ((u_time>='{$this->start_time}' and u_time<='{$this->end_time}') or (pay_time>='{$this->start_time}' AND pay_time<='{$this->end_time}'))";
            $plan_sql .= " WHERE real_end_time>='{$this->start_time}' AND real_end_time<='{$this->end_time}'";
        }
        $id_in_order = Yii::app()->lcs_r->createCommand($plan_order_sql)->querycolumn();
        $id_end = Yii::app()->lcs_r->createCommand($plan_sql)->querycolumn();
        $ids = array_merge($id_in_order, $id_end);
        if (!empty($ids)) {
            $sql = "SELECT pln_id,name,number,start_date,end_date,real_end_time FROM lcs_plan_info WHERE pln_id IN (" . implode(',', $ids) . ")";
            $result = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            //项目ID  项目名称    预计开始日期    预计结束日期    二级产品类型    实际结束时间
            foreach ($result as $value) {
                $item = array(
                    'pln_id' => $value['pln_id'],
                    'title' => $value['name'] . $value['number'] . "期",
                    'start_date' => $value['start_date'],
                    'end_date' => $value['end_date'],
                    'type' => "21",
                    'real_end_time' => $value['real_end_time'],
                );
                $data[] = $item;
                $item['type'] = '理财计划';
                $row_str .= mb_convert_encoding(implode("\t", $item) . "\n", 'gb18030', 'utf-8');
            }
            unset($result);
        }
        StatWbpay::model()->saveProject($data);
        self::saveFile('project', $row_str);
        if ($this->is_sync) {
            $this->execRsync('project');
        }
    }

    /**
     * 理财师信息  全量推送
     */
    public function doAuthor() {
        $sql = 'SELECT p.s_uid AS p_uid,p.name,b.id_number,b.card_number,b.bank_name FROM lcs_planner p LEFT JOIN lcs_bank_card b ON p.s_uid=b.uid ';
        $result = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        //理财师ID  理财师姓名    身份证号    银行卡号    开户行
        $p_uids = array();
        $row_str = '';
        foreach ($result as $value) {
            if (!empty($p_uids) && in_array($value['p_uid'], $p_uids)) {
                continue;
            }
            $row_str .= mb_convert_encoding(implode("\t", $value) . "\n", 'gb18030', 'utf-8');
            $p_uids[] = $value['p_uid'];
        }
        self::saveFile('author', $row_str);
        if ($this->is_sync) {
            $this->execRsync('author');
        }
        return;
    }

    /**
     * rsync同步文件，并备份
     * @param type $type
     * @return type
     */
    private function execRsync($type) {
        $output = '';
        $result = '';
        if (!isset(self::$filename_map[$type])) {
            return;
        }
        $filename = self::$filename_map[$type];
        try {
            exec("rsync " . self::$data_path . $filename . self::$rsync_address, $output, $result);
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '同步文件-' . $filename . '--ouput:' . json_encode($output) . '--result:' . json_encode($result));
        } catch (Exception $e) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
        $ext = empty($this->fixid) ? $this->incr_day : $this->fixid;
        rename(self::$data_path . $filename, self::$data_path . $filename .$ext);
    }

    /**
     * 数据写入文件
     * @param type $type  类型
     * @param type $data  数据
     * @return type
     */
    private static function saveFile($type, &$data) {
        if (!isset(self::$filename_map[$type])) {
            return;
        }
        $filename = self::$filename_map[$type];
        if (!$handle = fopen(self::$data_path . $filename, 'w')) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, $filename . '——不能打开文件');
        }
        if (fwrite($handle, $data) === FALSE) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, $filename . '——不能写入文件');
        }
        fclose($handle);
    }

}
