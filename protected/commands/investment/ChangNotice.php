<?php
/**
 * 购买记录同步
 * 
 */
class ChangNotice
{
    //订单号
    private $order_no;
    //订单信息
    private $temp;
    //请求数据
    private $requestData;
    //任务代码
    const CRON_NO = 1902;
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
		//xiugai记录同步接口
		'crmOrder' => [
			'dev' => 'https://test-customer-api-crm.baidao.com/customer-api/v1/xlzx/change-customerCategory?token=8c53afa7-6514-11e7-8d47-000c294a5fdf',
            'pro' => 'http://customer-api-crm.baidao.com/customer-api/v1/XLZX/change-customerCategory?token=8c53afa7-6514-11e7-8d47-000c294a5fdf',
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
     * redisKey userbuynotice
     */
    public function run(){
        try{
            $start = time();
            $end = time()+60;
            while ($start<$end) {
                // 读取队列$order_no
                $sync_user_key = 'userchangnotice';
                $val = Yii::app()->redis_w->pop($sync_user_key);
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
        }catch(Exception $e){
            var_dump($e->getMessage());
        }
    }
    /**
     * 起点
     * 
     */
    private function begin(){
        //获取临时信息
        $this->getTemp();
        //拼接数据
        $this->joinData();
        //请求接口
        $this->curlCrm();
    }
    /**
     * 获取订单信息
     * 
     */
    private function getTemp(){
        //处理数据
        $data = explode("|",$this->order_no);
        //赋值处理
        $this->temp['uid'] = $data[0];
        $this->temp['p_uid'] = $data[1];
        $this->temp['end_time'] = $data[2];
        $this->temp['bz'] = $data[3];
        //获取用户的当前分类
        var_dump($this->temp);
        echo "\r\n";
        $uid = $this->temp['uid'];
        $p_uid = $this->temp['p_uid'];
        
        $data = Yii::app()->lcs_r->createCommand('select `id`,`order_no`,`uid`,`p_uid`,`setname`,`settype`,`setid`,`end_time`,`price`,`c_time`,`bz`,`setname` from `lcs_set_subscription` where uid=' . $uid . ' and p_uid=' . $p_uid)->queryAll();
        //比较获取用户分类
        foreach ($data as $key => $value) {
            $settype[] = $value['settype'];
        }
        if(isset($settype)){
            $this->temp['settype'] = intval(max($settype));
        }else{
            $this->temp['settype'] = 0;
        }
        
        //反查老师名称
        $p_uid = $this->temp['p_uid'];
        $teacherName = Yii::app()->lcs_r->createCommand('select `name` from `lcs_planner` where s_uid=' . $p_uid)->queryScalar();
        switch ($teacherName) {
            case '史月波':
                $this->temp['teacherName'] = 1;
                break;
            
            case '边风炜':
                $this->temp['teacherName'] = 2;
                break;

            case '王健':
                $this->temp['teacherName'] = 3;
                break;
            case '马力':
                $this->temp['teacherName'] = 4;
                break;
        }
        
    }
    /**
     * 拼接请求数据
     */
    private function joinData(){
        $this->requestData = array(
            //用户id
            'username' => $this->temp['uid'],
            //用户分类类型
            'categoryType' => intval($this->temp['settype']),
            //服务结束时间
            'serviceEndTime' => $this->temp['end_time'],
            //老师名称
            'teacherName' => $this->temp['teacherName'],
            //备注
            'remarks' => $this->temp['bz'],
        );
    }
    /**
     * 请求接口
     * 
     */
    private function curlCrm(){
        $data = Yii::app()->curl->setTimeOut(10)->post(
            self::getConfig('crmOrder'),
            $this->requestData
        );
        $reponse = json_decode($data,true);
        if(isset($reponse['code']) && $reponse['code'] == 0){
            echo date("Y-m-d H:i:s",time())."\r\n";
            print_r($this->requestData);
            echo "\r\n";
        }else{
            echo "false".$reponse['msg'];
            $error = $reponse['msg'] . date("Y-m-d H:i:s",time());
            // Yii::app()->redis_w->rpush('lcs_zhixuangu_crm_changnotice_error',$error);
            // Common::model()->saveLog("发送微信消息错误:".json_encode($item),'error','weixin_template');
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
