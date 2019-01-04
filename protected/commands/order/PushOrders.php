<?php

/**
 * Description of PushOrders
 * @datetime 2016-1-20  15:18:05
 * @author hailin3
 */
class PushOrders {

    const CRON_NO = 8201;

    private static $rsync_address = ' 10.210.225.11::di/data/'; //前面留空格    
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
    //消费订单渠道
    public static $order_channel = array(
        1=>'支付宝',2=>'支付宝',3=>'支付宝',4=>'苹果',6=>'苹果',
    );
    private static $filename_map = array(
        'fill' => 'wbpay_otherfinance_fill.txt',
        'order' => 'wbpay_otherfinance_order.txt',
        'strike' => 'wbpay_otherfinance_strike.txt',
        'author' => 'wbpay_otherfinance_author.txt',
        'pay' => 'wbpay_otherfinance_pay.txt',
        'project' => 'wbpay_otherfinance_project.txt'
    );
    private $start_time = '';
    private $end_time = '';

    public function __construct($start_time = '', $end_time = '') {
        if (!empty($start_time) && !empty($end_time)) {
            $this->start_time = $start_time. ' 00:00:00';
            $this->end_time = $end_time . ' 23:59:59';
        } else {
            $incr_day = date('Y-m-d', strtotime("-4 days"));
            $this->start_time = $incr_day . ' 00:00:00';
            $this->end_time = $incr_day . ' 23:59:59';
        }
    }

    /**
     * 推送充值数据
     * @param type $pay_type 充值类型
     * @param type $start_time 开始时间
     * @param type $end_time 结束时间
     */
    public function pushIncrFill($pay_type) {
        //业务订单号    微博订单号   用户UID 充值金额   充值时间   充值类型(牛币/直充/…)  订单状态(1:正常；-1：退款)  原微博订单号   退款时间    渠道
        $columns = "order_no,wbpay_no,uid,price,pay_time,pay_type,status,original_wbpay_no,refund_time,channel";
        $condition = array();
        if (!empty($pay_type)) {
            $condition[] = "channel IN ({$pay_type})";
        }
        if (!empty($this->start_time)) {
            $condition[] = "c_time>='{$this->start_time}'";
        }
        if (!empty($this->end_time)) {
            $condition[] = "c_time<='{$this->end_time}'";
        }
        $where = '';
        if (!empty($condition)) {
            $where = 'WHERE ' . implode(' AND ', $condition);
        }
        $sql = "SELECT {$columns} FROM lcs_wbpay_fill " . $where;                
        $result = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        if (empty($result)) {
            return 0;
        }
        $row_str = '';
        foreach ($result as $value) {
            if ($value['refund_time'] == '0000-00-00 00:00:00') {
                $value['refund_time'] = '';
            }        
            $value['price'] = trim($value['price'],'-');            
            $value['pay_type'] = self::$use_text[$value['pay_type']];
            $value['channel'] = self::$pay_text[$value['channel']];
            $row_str .= mb_convert_encoding(implode("\t", $value) . "\n", 'gb18030', 'utf-8');
        }
        self::saveFile('fill', $row_str);
        self::execRsync('fill');
    }

    /**
     * 推送订单信息
     * @param type $pay_type
     * @return int
     */
    public function pushIncrOrder($pay_type) {
        //一级产品类型 二级产品类型 项目ID 用户UID 理财师ID 理财师姓名 业务订单号 订单提交时间 微博订单号    
        //付款时间    订单金额    订单退款截止日期    订单状态    是否分成    支付类型    服务到期时间        
        $columns = "'理财师' as product_type,product_sub_type,relation_id,uid,p_uid,p_name,order_no,"
                . "order_c_time,wbpay_no,pay_time,price,refund_end_time,status,is_fencheng,pay_type,pkg_end_time,pay_type as channel,remarks";
        $condition = array('(price>0 OR bill_month>0)');
        if (!empty($pay_type)) {
            $condition[] = "pay_type IN ({$pay_type})";
        }
        if (!empty($this->start_time)) {
            $condition[] = "order_c_time>='{$this->start_time}'";
        }
        if (!empty($this->end_time)) {
            $condition[] = "order_c_time<='{$this->end_time}'";
        }
        $where = '';
        if (!empty($condition)) {
            $where = 'WHERE ' . implode(' AND ', $condition);
        }
        $sql = "SELECT {$columns} FROM lcs_wbpay_order " . $where;            
        $result = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        if (empty($result)) {
            return 0;
        }
        $row_str = '';
        foreach ($result as $value) {
            if ($value['pkg_end_time'] == '0000-00-00 00:00:00') {
                $value['pkg_end_time'] = '';
            }
            if ($value['refund_end_time'] == '0000-00-00 00:00:00') {
                $value['refund_end_time'] = '';
            }
            $value['p_uid'] = ($value['p_uid'] == '0') ? '' : $value['p_uid'];
            $value['is_fencheng'] = ($value['is_fencheng'] == 1) ? '是' : '否';
            $value['product_sub_type'] = self::$type_text[$value['product_sub_type']];
            $value['status'] = self::$o_status_text[$value['status']];
            $value['pay_type'] = self::$pay_type_text[$value['pay_type']];
            $value['channel'] = self::$order_channel[$value['channel']];
            $row_str .= mb_convert_encoding(implode("\t", $value) . "\n", 'gb18030', 'utf-8');
        }
        self::saveFile('order', $row_str);
        self::execRsync('order');
    }

    /**
     * 推送退款信息
     * @param type $pay_type
     * @return int
     */
    public function pushIncrStrike($pay_type) {
        //一级产品类型 二级产品类型 项目ID 用户UID 理财师ID 理财师姓名 退款业务订单号 退款订单提交时间 退款微博订单号 退款付款时间 退款订单金额
        //订单状态 是否分成 被退业务订单号 支付类型	
        $columns = "'理财师' as product_type,product_sub_type,relation_id,uid,p_uid,p_name,order_no,order_c_time,"
                . "wbpay_no,pay_time,price,status,is_fencheng,order_no,pay_type,pay_type as channel,remarks";
        $condition = array('price<0 AND bill_month=0');
        if (!empty($pay_type)) {
            $condition[] = "pay_type IN ({$pay_type})";
        }
        if (!empty($this->start_time)) {
            $condition[] = "order_c_time>='{$this->start_time}'";
        }
        if (!empty($this->end_time)) {
            $condition[] = "order_c_time<='{$this->end_time}'";
        }
        $where = '';
        if (!empty($condition)) {
            $where = 'WHERE ' . implode(' AND ', $condition);
        }
        $sql = "SELECT {$columns} FROM lcs_wbpay_order " . $where;        
        $result = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        if (empty($result)) {
            return 0;
        }
        $row_str = '';
        foreach ($result as $value) {
            $value['price'] = trim($value['price'],'-');            
            $value['is_fencheng'] = ($value['is_fencheng'] == 1) ? '是' : '否';
            $value['product_sub_type'] = self::$type_text[$value['product_sub_type']];
            $value['status'] = self::$o_status_text[$value['status']];
            $value['pay_type'] = self::$pay_type_text[$value['pay_type']];
            $value['channel'] = self::$order_channel[$value['channel']];
            $row_str .= mb_convert_encoding(implode("\t", $value) . "\n", 'gb18030', 'utf-8');
        }
        self::saveFile('strike', $row_str);
        self::execRsync('strike');
    }

    /**
     * 推送项目信息
     * @return int
     */
    public function pushIncrProject() {
        //项目ID  项目名称    预计开始日期    预计结束日期    二级产品类型    实际结束时间
        $columns = "relation_id,title,start_time,end_time,product_sub_type,real_end_time,refund_end_time";       
        $pre_sql = "SELECT relation_id FROM lcs_wbpay_order where order_month=".date('Ym')." group by relation_id";          
        $idlist = Yii::app()->lcs_standby_r->createCommand($pre_sql)->queryColumn();        
        if(empty($idlist)){
            return;
        }                
        $sql = "SELECT {$columns} FROM lcs_wbpay_project WHERE relation_id IN (".  implode(',', $idlist).")";         
        $result = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        if (empty($result)) {
            return 0;
        }
        $row_str = '';
        $sub_type = array(
            '11' => '问答',
            '12' => '解锁',
            '32' => '观点'
        );
        foreach ($result as $value) {
            $value['title'] = isset($sub_type[$value['product_sub_type']]) ? $sub_type[$value['product_sub_type']] : $value['title'];
            $value['product_sub_type'] = self::$type_text[$value['product_sub_type']];
            $value['start_time'] = ($value['start_time'] == '0000-00-00') ? '' : $value['start_time'];
            $value['end_time'] = ($value['end_time'] == '0000-00-00') ? '' : $value['end_time'];
            $value['real_end_time'] = ($value['real_end_time'] == '0000-00-00 00:00:00') ? '' : $value['real_end_time'];
            $value['refund_end_time'] = ($value['refund_end_time'] == '0000-00-00 00:00:00') ? '' : $value['refund_end_time'];
            $row_str .= mb_convert_encoding(implode("\t", $value) . "\n", 'gb18030', 'utf-8');
        }
        self::saveFile('project', $row_str);
        self::execRsync('project');
    }

    /**
     * 推送理财师信息
     */
    public function PushAuthor() {
        $sql = 'SELECT p.s_uid AS p_uid,p.real_name,b.id_number,b.card_number,b.bank_name FROM lcs_planner p LEFT JOIN lcs_bank_card b ON p.s_uid=b.uid ';
        $result = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
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
        self::execRsync('author');
    }

    /**
     * rsync同步文件，并备份
     * @param type $type
     * @return type
     */
    private static function execRsync($type) {
        return 0;
        $output = '';
        $result = '';
        if (!isset(self::$filename_map[$type])) {
            return;
        }
        $filename = self::$filename_map[$type];
        $path = CommonUtils::createPath(DATA_PATH . DIRECTORY_SEPARATOR, 'wbpay');
        if (!file_exists($path .DIRECTORY_SEPARATOR.$filename)) {
            return -1;
        }
        try {
            exec("rsync " . $path . $filename . self::$rsync_address, $output, $result);
            rename($path .DIRECTORY_SEPARATOR.$filename, $path .DIRECTORY_SEPARATOR.$filename.'_'.date('Ymd'));
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, '同步文件-' . $filename . '--ouput:' . json_encode($output) . '--result:' . json_encode($result));
        } catch (Exception $e) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
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
        $path = CommonUtils::createPath(DATA_PATH . DIRECTORY_SEPARATOR, 'wbpay');        
        if (!$handle = fopen($path .DIRECTORY_SEPARATOR. $filename, 'w')) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, $filename . '——不能打开文件');
        }
        if (fwrite($handle, $data) === FALSE) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, $filename . '——不能写入文件');
        }
        fclose($handle);
    }

}
