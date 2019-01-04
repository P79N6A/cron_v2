
<?php

/**
 * Description of SyncUser
 *
 * @author Administrator
 */
class SyncUser
{

	const CRON_NO = 13101;

	private static $try_again = false;
	private static $returnData = array();
	private static $config = [
		//sc_phone（aes加密）
		'sc_phone_aes_key' => [
			'dev' => 'aaaaaaabbbbcccc1',
			'pro' => 'yuDA4rnmzNlm9zYo'
		],
		//资源实时派工
		'savecus' => [
			// 'dev' => 'http://test-api-ucrm.baidao.com/common-webinf/newassign/realTime/savecus',
			//20180823
			// 'dev' => 'http://test-resource-assign.baidao.com/resource-assign-server/api/v1/RH/WEB/TJ/newassign/new/savecus?token=RH',
			'dev' => 'http://test-api-ucrm.baidao.com/common-webinf/newassign/realTime/savecus',
			'pro' => 'http://sms.baidao.com:18080/common-webinf/newassign/realTime/savecus'
		],
		//理财师信息变更接口
		'updateCusContact' => [
			'dev' => 'https://test-customer-api-crm.baidao.com/customer-api/v1/sfa/updateCusContact?token=42275b09-5d66-4c8e-b8f2-8940dbf0d20f',
			'pro' => 'https://customer-api-crm.baidao.com/customer-api/v1/sfa/updateCusContact?token=42275b09-5d66-4c8e-b8f2-8940dbf0d20f'
		],
        //微信公众号接口
        'wechat' => [
            'dev' => 'http://lcs-api.licaishisina.com.cn/api/user/investInfo',
            'pro' => 'http://lcs-api.licaishisina.com/api/user/investInfo',
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

	public static function run()
	{
		echo "\r\n\r\n" . date("Y-m-d H:i:s\r\n");
		self::send();
	}

	private static function send()
	{
		$sync_user_key = 'lcs_sync_user_2_crm';
		$val = Yii::app()->redis_w->pop($sync_user_key);
		
		if (!$val) {
			echo "运行完毕,未有需要同步的数据\r\n";
			return true;
		} else {
			//redis value => 171429906|1|(add,toujiao,zunxiang)
			list($id, $bus_type, $action) = explode('|', $val);
			echo "sync : \$id:$id, \$bus_type:$bus_type \$action:$action \r\n";
			if ($action == 'add') {
				self::sendUser2CRM($id, $bus_type, 1); //默认理财师用户
			} elseif($action == 'toujiao') {
		        self::sendUser2Crm($id, $bus_type, 2);//理财师投教公众号
            } elseif($action == 'zunxiang') {
		        self::sendUser2Crm($id, $bus_type, 3);//理财师尊享版
            } else {
				self::updateLcs2CRM($id, $bus_type); //$p_uid,$old_mibile
			}


			usleep(10000); //0.01s
			//递归回调
			self::send();
		}
	}

	/**
	 * 理财师与CRM对接
	 */
	public static function sendUser2CRM($id, $bus_type, $type=1)
	{
		//$bus_type 1:普通客户；2：理财师
		if($bus_type == 1){
			//判断用户所属投顾的crm及投顾工号
			echo "计算用户所属投顾>>>>>>>>>>>>>>\r\n";
			self::getUserToCrm($id);

			$crm_type = self::$returnData['type'];
			$crm_ext_no = self::$returnData['ext_no'];
			if($crm_type == 3){
				echo "被邀请人为投顾不进行派发(跳出本次派发)\r\n";
				return;
			}

			var_dump(self::$returnData);
			//$type为用户进线的渠道
			if($type == 1){
				echo "=========================\r\n";
				echo "正在同步CRM\r\n";
				//同步...
				$user_info = self::getCommonUserInfo($id);
				// 判断用户进入的crm 1 投教 2 期货
				switch ($crm_type) {
					case '1':
						$user_info['sport_id'] = 2640;
						$user_info['username'] = $id;
						$user_info['sc_reffer'] = "百万股神第一季";
						break;
					case '2':
						$user_info['sport_id'] = 2641;
						$user_info['username'] = $id;
						$user_info['sc_reffer'] = "百万股神第一季";
						break;
					default:
						$user_info['username'] = $id;
						# 额....不处理
						break;
				}
				//当前用户邀请人的投顾id
				$user_info['ext_no'] = $crm_ext_no;
				//获取当前用户的战队信息
				// $corp = $this->getUserCorps($user_info['lcs_id']);
				$corp = Match::model()->getCorpsInfoByUid($user_info['lcs_id']);
				if(!empty($corp['planner_name'])){
					$user_info['ext_pro_cusExts'] = $corp['planner_name']."战队";
				}
			}elseif($type == 2){
				echo "正在同步自选股\r\n";
				///同步智选股数据
				$user_info = self::getCommonUserInfo($id);
				//修改数据
				$user_info['sport_id'] = 2625;
				$user_info['sport_name'] = "理财师投教公众号";
				$user_info['ref_id'] = 376;
				$user_info['sc_reffer'] = "理财师投教公众号";
				$user_info['username'] = $id;
				echo "jiekou";
				//是否存在投顾
				$phone = "";
				if(!empty($user_info['sc_phone'])){
					$phone = self::decryptPhoneNumber($user_info['sc_phone']);
				}
				try {
					$url = self::getConfig('wechat');

					$data = Yii::app()->curl->setTimeOut(10)->get($url,array('phone'=>intval($phone)));
					
				} catch (Exception $e) {
					var_dump($e->getMessage());
					die();

				}

				$data = json_decode($data,true);
				
				$user_info['ext_no'] = isset($data['data']['personCode'])?$data['data']['personCode']:"";
			    //微信昵称
                $user_info['ext_pro_wechatNickname'] = isset($data['data']['nickName'])?$data['data']['nickName']:"";

            }elseif($type == 3){
				echo "正在同步尊享版用户\r\n";
				///获取用户信息
				$user_info = self::getCommonUserInfo($id);
				//修改数据
				$user_info['sport_id'] = 2640;
				$user_info['sport_name'] = "理财师尊享版";
				$user_info['ref_id'] = 376;
				$user_info['sc_reffer'] = "百万股神第一季";
				$user_info['ext_no'] = 600709;
				$user_info['username'] = $id;
				//获取当前用户的战队信息
				// $corp = $this->getUserCorps($user_info['lcs_id']);
				$corp = Match::model()->getCorpsInfoByUid($user_info['lcs_id']);
				if(!empty($corp['planner_name'])){
					$user_info['ext_pro_cusExts'] = $corp['planner_name']."战队";
				}
            }
			//判断手机号是否为空
			if(!empty($user_info['sc_phone'])){
				//被加密的手机号
				$phone = CommonUtils::encodePhoneNumber(self::decryptPhoneNumber($user_info['sc_phone']));
				//判断用户是否内部员工
				$isAlso = self::isAlsoUser($phone);
				if($isAlso){
					$user_info['ext_pro_cusExts'] = $user_info['ext_pro_cusExts']."|"."内部员工";
				}

			}
		}elseif($bus_type == 2 && $type == 1){
			//理财师同步
			$user_info = self::getLcsUserInfo($id);
		}
		
		echo "数据拼接完成\r\n";
		
		if (empty($user_info)) {
			echo "sendUser2CRM $id $bus_type empty-userInfo\r\n";
			return;
		}

		$url = self::getConfig('savecus');
		$sign_key = 'xgwzrf4pv25tu7y6begl';
		$user_info_json = json_encode($user_info);
		$sign = md5($user_info_json . $sign_key);

		$post_data = http_build_query(['content' => $user_info_json, 'sign' => $sign]);
		// $url .= "&sign=".$sign;
		// $post_data = $user_info_json;

		echo $url . "\r\n\$user_info:";
		print_r($user_info);
		echo "\r\n\$sign:";
		print_r($sign);
		echo "\r\n";
		
		echo "请求准备\r\n";
		try {
			// $header['content-type']="application/json; charset=UTF-8";
			// $res_json = Yii::app()->curl->setHeaders($header)->setTimeOut(10)->post($url, $post_data);
			$res_json = Yii::app()->curl->setTimeOut(10)->post($url, $post_data);
		} catch (Exception $e) {
			echo "请求失败,重发请求\r\n";
			echo $e->getMessage()."\r\n";
			switch ($type) {
				case '1':
					$action = 'add';
					break;
				case '2':
					$action = 'toujiao';
					break;
				case '3':
					$action = 'zunxiang';
					break;
				default:
					$action = 'add';
					break;
			}
			self::addJob($id, $bus_type, $action);
			return;
		}
		echo "请求完成\r\n";
		$reponse = array(
			'user_info' => $user_info,
			'sign' => $sign,
			'response_json' => $res_json,
			'returnData' => self::$returnData,
			'post_data' => $post_data,

		);
		Common::model()->saveLog(json_encode($reponse), 'info', 'crm_lcs');


		if ($res_json == '') {
			echo "sendUser2CRM $id $bus_type empty-return\r\n";
			self::$try_again && self::addJob($id, $bus_type, 'add');
			return false;
		}

		$res = json_decode($res_json, TRUE);
		if (isset($res['code']) && $res['code'] == 1) {
			echo "sendUser2CRM $id $bus_type OK\r\n";
			return true;
		} else {
			echo "sendUser2CRM $id $bus_type Error : \r\n";
			echo "\$url $url \r\n";
			echo "\$res_json:$res_json\r\n";
			echo "\r\n";
			self::$try_again && self::addJob($id, $bus_type, 'add');
			return false;
		}
	}

	/**
	 * 理财师信息修改接口
	 * @param type $p_uid
	 * @param type $old_mobile
	 * @return boolean
	 */
	public function updateLcs2CRM($p_uid, $old_mobile = '')
	{
		$url = self::getConfig('updateCusContact');
		$user_info = self::getLcsUpdateInfo($p_uid, $old_mobile);

		if (empty($user_info)) {
			echo "updateLcs2CRM $p_uid $old_mobile empty-userInfo\r\n";
			return;
		}

		echo $url . "\r\n";
		print_r($user_info);
		echo "\r\n";

		$res_json = Yii::app()->curl->post($url, http_build_query($user_info));
		if ($res_json == '') {
			echo "updateLcs2CRM $p_uid $old_mobile empty-return\r\n";
			self::$try_again && self::addJob($p_uid, $old_mobile, 'update');
			return false;
		}

		$res = json_decode($res_json, TRUE);
		if (isset($res['code']) && $res['code'] == 0) {
			echo "updateLcs2CRM $p_uid $old_mobile OK\r\n";
			return true;
		} else {
			echo "updateLcs2CRM $p_uid $old_mobile Error : \r\n";
			echo "\$res_json:$res_json\r\n";
			echo "\r\n";
			self::$try_again && self::addJob($p_uid, $old_mobile, 'update');
			return false;
		}
	}

	/**
	 * 任务出错时把任务重新退回任务池
	 * @param type $id
	 * @param type $bus_type
	 * @return boolean
	 */
	private static function addJob($id, $bus_type, $action_type)
	{
		$sync_user_key = 'lcs_sync_user_2_crm';
		Yii::app()->redis_w->push($sync_user_key, implode('|', func_get_args()));
		return true;
	}

	/**
	 * 获取理财师变更后的信息
	 * @param type $p_uid
	 */
	private static function getLcsUpdateInfo($p_uid, $old_mobile = '')
	{
		$sql = 'select * from ' . TABLE_PREFIX . 'planner where s_uid=' . ((int) $p_uid) . ' limit 1 ';
		$R = Yii::app()->lcs_r->createCommand($sql)->queryRow();
		if (empty($R))
			return [];
		$province = $city = $companyName = $certName = '';
		if (!empty($R['location']))
			list($province, $city ) = explode('-', $R['location']);
		$companyName = $R['company_id'] ? self::getCompanyName((int) $R['company_id']) : '';
		$certName = $R['cert_id'] ? self::getCertName((int) $R['cert_id']) : '';
		$new_phoneNumber = $R['phone'];
		return $data = [
			'token' => '42275b09-5d66-4c8e-b8f2-8940dbf0d20f', //令牌
			'lcsId' => $p_uid, //理财师ID
			'oldMoblie' => $old_mobile ?: $new_phoneNumber, //理财师旧手机号
			'newMoblie' => $new_phoneNumber, //理财师新手机号
			'sc_sex' => $R['gender'] == 'f' ? 0 : 1, //性别(0:女 1:男)
			'sc_address' => $R['location'], //地址
			'sc_province' => $province, //省份
			'cusExts' => $companyName . '|' . $R['department'] . '|' . $certName . '|' . $R['cert_number'], //备注信息(内容：所在单位|所属营业部|资格证书|资格代码)
		];
	}

	/**
	 * 获取普通用户的同步信息
	 */
	private static function getCommonUserInfo($uid)
	{
		$ext_num = ((int) $uid) % 10;
		$sql = 'select * from ' . TABLE_PREFIX . 'user_' . $ext_num . ' where uid=' . $uid . ' limit 1';
		$R = Yii::app()->lcs_r->createCommand($sql)->queryRow();
		if (empty($R))
			return false;
		$return = [
			'sc_name' => $R['name'], //客户姓名
			'sc_sex' => $R['gender'] == 'f' ? 0 : 1, //性别(0:女 1:男)
			'sc_phone' => self::encryptPhoneNumber(CommonUtils::decodePhoneNumber($R['phone'])), //电话(aes加密) key: aaaaaaabbbbcccc1
			'sc_address' => '', //地址
			'sc_province' => '', //省份
			'dt_commit_time' => (float) (time() . '000'), //客户提交时间
			'sport_id' => 180000, //活动ID(180000)生产到时候告知
			'sport_name' => '用户注册', //活动名称（理财师注册）
			'ref_id' => 280001, //来源ID(280000)生产到时候告知
			'sc_reffer' => '普通客户官方通道', //来源渠道
			'username' => $R['wb_name'] ?: ($R['wx_name'] ?: ''), //用户名如： username
			'ext_no' => '', //指定派工的工号
			'ext_pro_cusExts' => '|||', //Name：cusExts:客户扩展信息（值）内容：所在单位|所属营业部|资格证书|资格代码
			'lcs_id' => (float) $uid, //普通客户ID
			'old_lcs_id' => (int) '', //普通用户为空
			'bus_type' => 1, //1:普通客户；2：理财师
		];
		return $return;
	}

	/**
	 * 获取理财师的同步信息
	 */
	private static function getLcsUserInfo($s_uid)
	{
		$sql = 'select * from ' . TABLE_PREFIX . 'planner where s_uid=' . ((int) $s_uid) . ' limit 1 ';
		$R = Yii::app()->lcs_r->createCommand($sql)->queryRow();
		if (empty($R))
			return false;
		$province = $city = '';
		if (!empty($R['location']))
			list($province, $city ) = explode('-', $R['location']);
		$companyName = '';
		if ($R['company_id'])
			$companyName = self::getCompanyName((int) $R['company_id']);
		$certName = '';
		if ($R['cert_id'])
			$certName = self::getCertName((int) $R['cert_id']);
		$return = [
			'sc_name' => $R['real_name'], //客户姓名
			'sc_phone' => self::encryptPhoneNumber($R['phone']), //电话(aes加密)
			'sc_sex' => (int) $R['gender'] == 'f' ? 0 : 1, //性别(0,1:女，男)
			'sc_address' => $R['location'], //地址
			'sc_province' => $province, //省份
			'dt_commit_time' => (float) (time() . '000'), //客户提交时间
			'sport_id' => 180000, //活动ID(180000)生产到时候告知
			'sport_name' => '理财师注册', //活动名称（理财师注册)
			'ref_id' => 280000, //来源ID(280000)生产到时候告知
			'sc_reffer' => '公司网站', //来源渠道
			'username' => $R['name'],
			'ext_no' => '', //指定派工的工号
			'ext_pro_cusExts' => $companyName . '|' . $R['department'] . '|' . $certName . '|' . $R['cert_number'], //Name：cusExts:客户扩展信息（值）内容：所在单位|所属营业部|资格证书|资格代码
			'lcs_id' => (float) $R['s_uid'], //理财师ID
			'old_lcs_id' => (int) self::getCommonUidByPuid($R['s_uid']), //理财师需要反查原普通用户ID
			'bus_type' => 2, //1:普通客户；2：理财师
		];
		return $return;
	}

	/**
	 * 反查理财师的原普通用户ID
	 * @param type $p_uid
	 */
	private static function getCommonUidByPuid($p_uid)
	{
		return Yii::app()->lcs_r->createCommand('select `id` from `lcs_user_index` where s_uid=' . $p_uid . ' limit 1')->queryScalar() ?: '';
	}

	/**
	 * 获取公司名称
	 * @param type $company_id
	 * @return type
	 */
	private static function getCompanyName($company_id)
	{
		return Yii::app()->lcs_r->createCommand('select `name` from ' . TABLE_PREFIX . 'company where id=' . $company_id . ' limit 1')->queryScalar() ?: '';
	}

	/**
	 * 获取证书名称
	 * @param type $cert_id
	 * @return type
	 */
	private static function getCertName($cert_id)
	{
		return Yii::app()->lcs_r->createCommand('select `name` from ' . TABLE_PREFIX . 'certification where id=' . $cert_id . ' limit 1')->queryScalar() ?: '';
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
		$encryptKey = self::getConfig('sc_phone_aes_key');
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
		$encryptKey = self::getConfig('sc_phone_aes_key');
		$module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);
		mcrypt_generic_init($module, $encryptKey, $localIV);
		$encryptedData = mdecrypt_generic($module, hex2bin($phone_number_encode));
		return $encryptedData;
	}
	//判断邀请人是否为投顾
	private static function getUserToCrm($uid){
		$sql = "select parent_id from lcs_match_sign_up where licaishi_uid=".$uid;
		echo $sql."\r\n";
		$parent_id = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
		var_dump($parent_id);
		//如果邀请人为零则正常派发
		if($parent_id == '0'){
			self::$returnData = array(
				'type'=>0,
				'ext_no'=>'',
			);
			return;
		}
		//判断被邀请人是否为投顾
		$sql3 = "select ext_no,to_crm from lcs_customer where uid=".$uid;
		$customer = Yii::app()->lcs_r->createCommand($sql3)->queryRow();
		if($customer){
			echo "被邀请人为投顾不进行派发\r\n";
			$returnData['type'] = 3;
			$returnData['ext_no'] = $customer['ext_no'];
			self::$returnData = $returnData;
			return;
		}
		//查询邀请人是否为投顾如果为投顾则进行派发
		$sql2 = "select ext_no,to_crm from lcs_customer where uid=".$parent_id;
		$customer = Yii::app()->lcs_r->createCommand($sql2)->queryRow();
		echo $sql2."\r\n";
		//如果邀请人不是投顾则判断邀请人的邀请人是否为投顾
		if(!$customer){
			self::getUserToCrm($parent_id);
		}else{
			$returnData['type'] = $customer['to_crm'];
			$returnData['ext_no'] = $customer['ext_no'];
			self::$returnData = $returnData;
			return;
		}
	}
	//判断用户是否为内部员工
	private static function isAlsoUser($phone){
		echo "计算是否为内部员工\r\n";
		$sql = "select parent_id from lcs_match_sign_up where phone_number='".$phone."';";
		echo $sql."\r\n";
		$phone = CommonUtils::decodePhoneNumber($phone);
		$parent_id = Yii::app()->lcs_r->createCommand($sql)->queryScalar();

		$sql = "select id from lcs_visit_log where utype=0 and r_id='".$phone."'";
		echo $sql."\r\n";
		$id = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
		if(!empty($id)){
			return true;
		}

		echo "邀请人uid".$parent_id."\r\n";
		if(!empty($parent_id)){
			//获取上级用户的用户信息
			$ext_num = ((int) $parent_id) % 10;
			$sql = 'select * from ' . TABLE_PREFIX . 'user_' . $ext_num . ' where uid=' . $parent_id . ' limit 1';
			echo $sql."\r\n";
			$user_info = Yii::app()->lcs_r->createCommand($sql)->queryRow();
			$parent_phone = CommonUtils::decodePhoneNumber($user_info['phone']);

			$sql = "select id from lcs_visit_log where utype=0 and r_id=".$parent_phone;
			echo $sql."\r\n";
			$id = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
			if(!empty($id)){
				return true;
			}
		}
	}
}
