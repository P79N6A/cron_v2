<?php
/**
 * 购买记录同步
 * 
 */
class BuyNotice
{
    //订单号
    private $order_no;
    //订单信息
    private $orderInfo;
    //请求数据
    private $requestData;
    //任务代码
    const CRON_NO = 1901;
    /**
     * 配置文件
     * 
     */
    private static $config = [
		//sc_phone（aes加密）
		'_aesKey' => [
			'dev' => 'aaaaaaabbbbcccc1',
			'pro' => 'yuDA4rnmzNlm9zYo'
		],
		//购买记录同步接口
		'crmOrder' => [
			'dev' => 'http://192.168.18.225:9192/customer-api/v1/XLZX/messageCenter?token=81b78ff7-b7c6-4b43-b1da-7f738af5dd36',
            'pro' => 'http://customer-api-crm.baidao.com/customer-api/v1/XLZX/messageCenter?token=81b78ff7-b7c6-4b43-b1da-7f738af5dd36',
		],
    ];
    /**
     * 获取配置信息
     * 
     */
    private static function getConfig($key)
	{
		if (!isset(self::$config[$key]))
			return null;
		if (defined('ENV') && ENV == 'dev')
			return self::$config[$key]['dev'];
		else
			return self::$config[$key]['pro'];
	}
    /**
     * 入口
     *
     * redisKey userbuynotice
     */
    public function run(){
        $start = time();
        $end = time()+60;
        $tick = 0;
        while ($start<$end) {
            // 读取队列$order_no
            $sync_user_key = 'lcs_userbuynotice';
            $val = Yii::app()->redis_w->lpop($sync_user_key);

            //设置超时时间
            if($tick%10==0){
                Yii::app()->lcs_r->setActive(false);
                Yii::app()->lcs_r->setActive(true);

                Yii::app()->lcs_w->setActive(false);
                Yii::app()->lcs_w->setActive(true);

                Yii::app()->lcs_comment_r->setActive(false);
                Yii::app()->lcs_comment_r->setActive(true);

                Yii::app()->lcs_comment_w->setActive(false);
                Yii::app()->lcs_comment_w->setActive(true);

                Yii::app()->lcs_standby_r->setActive(false);
                Yii::app()->lcs_standby_r->setActive(true);
            }
            $tick = $tick + 1;



            //Common::model()->saveLog($val, 'buy');
            if(!$val){
                echo "没有要同步的数据\n";
                sleep(2);
            }else{
                //订单号
                $this->order_no = $val;
                $this->begin();
            }
            $start = time();
        }
    }
    /**
     * 起点
     * 
     */
    private function begin(){
        //获取订单信息
        $this->getOrderInfo();
        //拼接数据
        $this->joinData();
        //请求接口
        $this->curlCrm();
    }
    /**
     * 获取订单信息
     * 
     */
    private function getOrderInfo(){
        $order_no = $this->order_no;
        //获取订单信息
        $this->orderInfo = Yii::app()->lcs_r->createCommand('select `id`,`order_no`,`uid`,`p_uid`,`relation_id`,`status`,`description`,`price`,`c_time` from `lcs_orders` where order_no=' . $order_no)->queryRow();
        $this->orderInfo['end_time'] = $this->orderInfo['c_time'];
        
        //反查老师名称
        $p_uid = $this->orderInfo['p_uid'];
        //反查老师名称
        $p_uid = $this->orderInfo['p_uid'];
        $teacherName = Yii::app()->lcs_r->createCommand('select `name` from `lcs_planner` where s_uid=' . $p_uid)->queryScalar();
        switch ($teacherName) {
            case '史月波':
                $this->orderInfo['teacherName'] = 1;
                break;
            
            case '边风炜':
                $this->orderInfo['teacherName'] = 2;
                break;

            case '王健':
                $this->orderInfo['teacherName'] = 3;
                break;
            case '马力':
                $this->orderInfo['teacherName'] = 4;
                break;
        }
        //反查订单信息
        $status = Yii::app()->lcs_r->createCommand('select `status` from `lcs_orders` where order_no=' . $order_no)->queryScalar();
        switch ($status) {
            //已付款
            case '2':
                $this->orderInfo['order_status'] = 2;
                $uid = $this->orderInfo['uid'];
                //获取最新服务结束时间
                $this->orderInfo['end_time'] = Yii::app()->lcs_r->createCommand('select `end_time` from `lcs_set_subscription` where uid=' . $uid)->queryScalar();
                break;
            //已退款
            case '4':
                $this->orderInfo['order_status'] = 3;
                break;
            //未付款
            default:
                Common::model()->saveLog($order_no, 'buy-wei-fu-kuan');
                $this->orderInfo['order_status'] = 1;
                break;
        }
        //反查用户手机号
        $uid = $this->orderInfo['uid'];
        $phone = Yii::app()->lcs_r->createCommand('select `phone` from `lcs_user_index` where id=' . $uid)->queryScalar();
        $this->orderInfo['phone'] = CommonUtils::decodePhoneNumber($phone);
    }
    /**
     * 拼接请求数据
     */
    private function joinData(){
        $this->requestData = array(
            //购买时间
            'purchaseTime' => $this->orderInfo['c_time'],
            //描述
            'content' => '',
            //升降级状态
            'state' => '',
            //购买的产品
            'product' => $this->orderInfo['description'],
            //价格
            'price' => $this->orderInfo['price'],
            //有效期开始时间
            'validPeriodStart' => $this->orderInfo['c_time'],
            //有效期结束时间
            'validPeriodEnd' => $this->orderInfo['end_time'],
            //用户id
            'username' => $this->orderInfo['uid'],
            //消息类型
            'msgType' => 'purchase_history',
            //老师名称
            'teacherName' => $this->orderInfo['teacherName'],
            //订单号
            'orderNo' => $this->orderInfo['order_no'],
            //支付状态
            'paymentStatus' => $this->orderInfo['order_status'],
            //手机号
            'phone' => ''
        );
        if(!empty($this->orderInfo['phone'])){
            $this->requestData['phone'] = self::encryptPhoneNumber($this->orderInfo['phone']);
            // 13308987676
            // $this->orderInfo['phone']
        }
    }
    /**
     * 请求接口
     * 
     */
    private function curlCrm(){
        $param_josn = json_encode($this->requestData);
        $param_josn = '['.$param_josn.']';

        $header = array(
            'Content-Type'=>'application/json; charset=utf-8',
        );
        try {
            $sh = Yii::app()->curl->setTimeOut(10)->setHeaders($header);
            $data = $sh->post(
                 self::getConfig('crmOrder'),
                 $param_josn
            );
            $code = $sh->getInfo();
        } catch (Exception $e) {
            echo date("Y-m-d H:i:s",time())."\r\n";
            print_r($e->getMessage());
            echo "\r\n";
            exit();
        }
        
        if($code['http_code']==204){
            echo date("Y-m-d H:i:s",time())."\r\n";
            print_r($this->requestData);
            echo "\r\n";

        }else{
            var_dump($code);
        }
    }

    /**
    * 对手机号码进行加密，以传输
    * @param type $phone_number
    * @return type
    */
    private static function encryptPhoneNumber($phone_number)
    {
        if ($phone_number == '')
            return '';
        $localIV = '1365127901262396';
        $encryptKey = self::getConfig('_aesKey');
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);
        mcrypt_generic_init($module, $encryptKey, $localIV);
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $pad = $block - (strlen($phone_number) % $block);
        $phone_number .= str_repeat(chr($pad), $pad);
        $encrypted = mcrypt_generic($module, $phone_number);
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
        return bin2hex($encrypted);
    }

    //解密
    private static function decryptPhoneNumber($phone_number_encode)
    {
        $localIV = '1365127901262396';
        $encryptKey = self::getConfig('_aesKey');
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);
        mcrypt_generic_init($module, $encryptKey, $localIV);
        $encryptedData = mdecrypt_generic($module, hex2bin($phone_number_encode));
        return $encryptedData;
    }
}
