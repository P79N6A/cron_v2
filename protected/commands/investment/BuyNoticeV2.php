<?php
/**
 * 购买记录同步
 * 
 */
class BuyNoticeV2
{
    //任务代码
    const CRON_NO = 1901;
    private $order_no = '';
    private $orderInfo = '';
    private $requestData = [];
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
            // $val = "6646429";
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
        echo sprintf("Time:%s,订单号:%s\r\n",date("Y-m-d H:i:s"),$this->order_no);
        //获取订单信息
        $this->getOrderInfo();
        //拼接数据
        $this->joinData();
        $netWork = new NetWork();
        $netWork->requestByOrder($this->requestData);
    }
    /**
     * 获取订单信息
     * 
     */
    private function getOrderInfo(){
        $order_no = $this->order_no;
        //获取订单信息
        $this->orderInfo = Yii::app()->lcs_r->createCommand('select `id`,`order_no`,`uid`,`p_uid`,`relation_id`,`status`,`description`,`price`,`c_time`,`pay_type` from `lcs_orders` where order_no=' . $order_no)->queryRow();
        $this->orderInfo['end_time'] = $this->orderInfo['c_time'];
        
        //反查老师名称
        $p_uid = $this->orderInfo['p_uid'];
        //反查老师名称
        $p_uid = $this->orderInfo['p_uid'];
        $teacherName = Yii::app()->lcs_r->createCommand('select `name` from `lcs_planner` where s_uid=' . $p_uid)->queryScalar();
        $this->orderInfo['teacherName'] = $teacherName;
        // switch ($teacherName) {
        //     case '史月波':
        //         $this->orderInfo['teacherName'] = 1;
        //         break;
            
        //     case '边风炜':
        //         $this->orderInfo['teacherName'] = 2;
        //         break;

        //     case '王健':
        //         $this->orderInfo['teacherName'] = 3;
        //         break;
        //     case '马力':
        //         $this->orderInfo['teacherName'] = 4;
        //         break;
        // }
        //反查订单信息
        $status = Yii::app()->lcs_r->createCommand('select `status` from `lcs_orders` where order_no=' . $order_no)->queryScalar();
        switch ($status) {
            //已付款
            case '2':
                $this->orderInfo['order_status'] = 2;
                $uid = $this->orderInfo['uid'];
                //获取最新服务结束时间
                $this->orderInfo['end_time'] = Yii::app()->lcs_r->createCommand('select `end_time` from `lcs_set_subscription` where uid=' . $uid . " order by c_time desc")->queryScalar();
                break;
            //已退款
            case '4':
                $this->orderInfo['order_status'] = 3;
                break;
            //未付款
            default:
                Common::model()->saveLog($order_no, 'buy-wei-fu-kuan');
                $this->orderInfo['order_status'] = 1;
                $this->orderInfo['pay_type'] = "";
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
        // $this->requestData = array(
        //     //购买时间
        //     'purchaseTime' => $this->orderInfo['c_time'],
        //     //描述
        //     'content' => '',
        //     //升降级状态
        //     'state' => '',
        //     //购买的产品
        //     'product' => $this->orderInfo['description'],
        //     //价格
        //     'price' => $this->orderInfo['price'],
        //     //有效期开始时间
        //     'validPeriodStart' => $this->orderInfo['c_time'],
        //     //有效期结束时间
        //     'validPeriodEnd' => $this->orderInfo['end_time'],
        //     //用户id
        //     'username' => $this->orderInfo['uid'],
        //     //消息类型
        //     'msgType' => 'purchase_history',
        //     //老师名称
        //     'teacherName' => $this->orderInfo['teacherName'],
        //     //订单号
        //     'orderNo' => $this->orderInfo['order_no'],
        //     //支付状态
        //     'paymentStatus' => $this->orderInfo['order_status'],
        //     //手机号
        //     'phone' => ''
        // );

        $this->requestData = [
            'order_no' => $this->order_no,
            'header' => [
                'app' => 'lcs',
                'biz' => '7001',
                'custId' => $this->orderInfo['uid'],
                'empId' => '',
                'org' => Config::getConfig('buCode'),
            ],
            'body' => [
                "custBaseOrder" =>  [
                    "buOrderId" =>  $this->orderInfo['order_no'],
                    "originalPrice" => $this->orderInfo['price']*100,
                    "payType" =>  $this->orderInfo['pay_type'],
                    "orderStatus" => intval($this->orderInfo['order_status']),
                    "totalPrice" =>  $this->orderInfo['price']*100,
                    "orderSource" =>  "订单来源",
                    "remark" => "订单描述",
                    "custOrderExtMap" =>  [
                        [
                            "phone" => '',
                            "custName" => '',
                            "purchaseTime" => $this->orderInfo['c_time'],
                        ],
                    ],
                ],
                "itemVoList" => [
                    "prodType" => "2",
                    "prodId" =>"0",
                    "prodName" => $this->orderInfo['description'],
                    "prodSubNumber" => "1",
                    "prodOriginalPrice" => $this->orderInfo['price']*100,
                    "prodPrice" => $this->orderInfo['price']*100,
                    "custOrderItemExtMap" =>
                    [
                        [
                            "teacherName" => $this->orderInfo['teacherName'],
                            "validperiodStart" => $this->orderInfo['c_time'],
                            "validperiodEnd" => $this->orderInfo['end_time'],
                            "categoryType" => ""
                        ],
                    ],
                ]
            ],
        ];
        if(!empty($this->orderInfo['phone'])){
            $key = count($this->requestData['body']['custBaseOrder']['custOrderExtMap']);
            $this->requestData['body']['custBaseOrder']['custOrderExtMap'][$key-1]['phone'] = self::encryptPhoneNumber($this->orderInfo['phone']);
            // 13308987676
            // $this->orderInfo['phone']
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
        $encryptKey = Config::getConfig('_aesKey');
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
        $encryptKey = Config::getConfig('_aesKey');
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);
        mcrypt_generic_init($module, $encryptKey, $localIV);
        $encryptedData = mdecrypt_generic($module, hex2bin($phone_number_encode));
        return $encryptedData;
    }
}
