<?php
/*
 * 同步系统
 */
class Sync
{
    const CRON_NO = 12306;

    private static $config = [
		//sc_phone（aes加密）
		'_aesKey' => [
			'dev' => 'aaaaaaabbbbcccc1',
			'pro' => 'yuDA4rnmzNlm9zYo'
		],
		//crm配置投顾
		'crmTg' => [
			'dev' => 'http://test-sms.baidao.com:18080/common-webinf/newassign/realTime/savecus',
			'pro' => 'http://sms.baidao.com:18080/common-webinf/newassign/realTime/savecus'
		],
		//Im获取投顾接口
		'imGetTg' => [
			'dev' => 'http://test-xllcs-resource-dispatch-api.yk5800.com/api/1/customer/ios/adviser/deviceid',
			'pro' => 'http://xllcs-resource-dispatch.yk5800.com/api/1/customer/ios/adviser/deviceid'
        ],
        //获取未读消息接口
        'imUnread' => [
            'dev'  => 'http://test-xllcs-tzgw.yk5800.com/api/1/message/offline/count',
            'pro'  => 'http://xllcs-tzgw.yk5800.com/api/1/message/offline/count'
        ],
        //活动号
        '_activity' => [
            'dev'  => 180000,
            'pro'  => 180000
        ],
        //秘钥
        '_signKey' => [
            'dev'  => 'xgwzrf4pv25tu7y6begl',
            'pro'  => 'xgwzrf4pv25tu7y6begl'
        ]
    ];
    
    private static function getConfig($key)
	{
		if (!isset(self::$config[$key]))
			return null;
		if (defined('ENV') && ENV == 'dev')
			return self::$config[$key]['dev'];
		else
			return self::$config[$key]['pro'];
	}
    
    //程序入口
    public function run(){
        try{
            $start = time();
            $end = time()+60;
            while ($start<$end) {
                // 读取队列
                $sync_user_key = 'lcs_sync_im';
                $val = Yii::app()->redis_w->pop($sync_user_key);
                if(!$val){
                    echo "没有要同步的数据\n";
                    sleep(2);
                }else{
                    list($device,$phone) = explode("|",$val);
                    self::distribution($device,$phone);
                }
                $start = time();
            }
        }catch(Exception $e){
            var_dump($e->getMessage());
        }
    }
    /*
     * 给用户分配投顾
     * @param string $device 设备号
     * @param string $phone  手机号
     */
     public static function distribution($device,$phone)
     {
         //当前用户投顾ID
         $tgId = self::imTgId($device);
         //aes加密手机号
         $encryptPhone = self::encryptPhoneNumber($phone);
         //向crm添加资源派工
         return self::crmTg($tgId,$encryptPhone);
     }
     /*
      * 获取Im系统的投顾ID
      * @param  int  $deviceId 设备号
      * @return void 投顾ID
      */
     public static function imTgId($deviceId)
     {
         $url  = self::getConfig('imGetTg');
         $param = array(
             'deviceId'=>$deviceId,
         );
         //请求IM接口
         $data = Yii::app()->curl->setTimeOut(10)->get(
             $url,
             $param
         );
         $data = json_decode($data,true);
         if(empty($data['data'])){
             return ;
         }else{
             return $data['data']['adviserCode'];
         }
     }
     /*
      * 请求crm分配投顾ID
      * @param int $tgId   投顾ID
      * @param int $phone  要匹配用户的手机号(加密)
      * @reutrn 
      */
     public static function crmTg($tgId,$phone)
     {
         // 拼接数据
         $param = array(
             'sc_phone'=>$phone,
             'dt_commit_time'=>(float) (time() . '000'),
             'sport_id'=>self::getConfig('_activity'),
             'sport_name'=>'用户分配投顾',
             'ref_id' => 280001,
         );
         if(!empty($tgId)){
             $param['ext_no'] = $tgId;
         }
         $param_josn = json_encode($param);
         //md5加密生成sign串
         $sign = md5($param_josn . self::getConfig('_signKey'));
         $post_data = http_build_query(['content' => $param_josn, 'sign' => $sign]);
         //请求接口
         $data = Yii::app()->curl->setTimeOut(10)->post(
             self::getConfig('crmTg'),
             $post_data
         );
         //判断是否成功
         $response = json_decode($data,true);
         if($response['code'] != 1){
             return 0;
         }else{
             return 1;
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
