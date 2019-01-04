<?php

class Check {

	const CRON_NO = 13201;
	const USER_AGENT = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0';
	const ENCODING = 'gzip, deflate';
	const TIMEOUT = 10;
	const DEBUG = true; // debug

	private $planner_arr = []; //所有的理财师的信息
	protected $company_fullName = []; //所有的公司名称
	protected $position_name = []; //所有的职位名称
	#
	private $cert_info_no_find = []; //资格证为空或者未找到的理财师ID
	private $cert_info_changed = []; //信息发生变化的理财师ID
	private $update_sql = []; //发生变更的详细字段
	private $multi_size = 200; //CURL批处理的个数
	#
	private $is_change = true; //是否执行修改
	private $is_send = false; //是否发送短信通知

	#---------------

	/**
	 * run
	 * @return type
	 */
	public function run() {
		//获取所有的理财师
		$this->planner_arr = $this->getPlannerAll();
		if (empty($this->planner_arr)) {
			self::p("理财师获取为空");
			return;
		}

		self::p("共获取到理财师" . count($this->planner_arr) . "位");

		//获取所有公司全名
		$this->company_fullName = $this->getAllCompanyFullName();
		//获取所有职位全名
		$this->position_name = $this->getAllPositionName();

		#---------------------------
		#STEP 1 直接使用资格证号码来查询
		#---------------------------
		//分${multi_size}位为一个批次，批量处理
		$new_arr = array_chunk($this->planner_arr, $this->multi_size, true);
		$curl_count = count($new_arr);
		self::p("CURL批处理每次" . $this->multi_size . "位，共需要" . $curl_count . "次");
		$fund_number_arr = []; //基金执业资格
		foreach ($new_arr as $i => $arr) {
			self::p("CURL批处理第 ${curl_count} - " . ($i + 1) . " 次:");

			$cert_number_arr = [];
			foreach ($arr as $planner_id => $info) {

				if ($info['cert_number'] == '') {
					$this->cert_info_no_find[$planner_id] = $planner_id;

					self::p("\$planner_id:$planner_id 资格证号码为空");
					continue;
				}

				if ($info['cert_id'] == 1) {
					$cert_number_arr[$planner_id] = $info['cert_number'];
				} else if ($info['cert_id'] == 2) {
					$fund_number_arr[$planner_id] = $info['cert_number'];
				}
			}

			if (empty($cert_number_arr))
				continue;

			//通过证券资格号批量获取对应信息
			$info_gets = $this->getCertInfoForMulti($cert_number_arr);
			$this->checkData($info_gets);

			unset($info_gets);
			unset($cert_number_arr);
		}
		unset($new_arr);

		//处理基金执业资格--人数较少因此一次性处理
		self::p("处理基金执业资格;一次性处理:");
		$info_gets = $this->getFundInfoForMulti($fund_number_arr);
		$this->checkData($info_gets);
		self::p("[STEP-1完成]finished...\r\n");

		#---------------------------
		#STEP 2 资格证号码如果查不到，再从身份证号码来查
		#---------------------------
		//处理资格证号码为空或者对应信息为空的--继续从身份证号码查询
		$stock_identity_number_arr = $fund_identity_number_arr = [];
		foreach ($this->cert_info_no_find ?: [] as $planner_id) {

			//如果省份证号也为空则处理
			if ($this->planner_arr[$planner_id]['identity'] == '') {
				continue;
			}

			unset($this->cert_info_no_find[$planner_id]);

			if ($this->planner_arr[$planner_id]['cert_id'] == 1) {
				$stock_identity_number_arr[$planner_id] = $this->planner_arr[$planner_id]['identity'];
			} else if ($this->planner_arr[$planner_id]['cert_id'] == 2) {
				$fund_identity_number_arr[$planner_id] = $this->planner_arr[$planner_id]['identity'];
			}
		}

		//1、分批处理-证券资格证
		$new_arr2 = array_chunk($stock_identity_number_arr, $this->multi_size, true);
		self::p("共有" . count($stock_identity_number_arr) . "位证券资格用户需要通过身份证来查询;CURL每批次" . $this->multi_size . "位;分" . (count($new_arr2)) . "次来查询 :");
		unset($stock_identity_number_arr);
		foreach ($new_arr2 as $i => $identity_number_arr) {

			self::p("第" . ($i + 1) . "次通过身份证来查询证券资格信息");

			//通过身份证号批量获取证券资格证对应信息
			$info_gets2 = $this->getCertInfoForMultiByIDCard($identity_number_arr) ?: [];
			$this->getStockJobStatus($info_gets2);
			$this->checkData($info_gets2);

			unset($info_gets2);
		}
		unset($new_arr2);

		//2、分批处理-基金资格证
		//通过身份证批量获取基金从业人员的资料
		if (!empty($fund_identity_number_arr)) {
			self::p("共有" . count($fund_identity_number_arr) . "位基金资格用户需要通过身份证来查询;CURL批处理一次查询");
			$info_gets3 = $this->getFundCertInfoForMultiByIDCard($fund_identity_number_arr) ?: [];
			//获取基金从业人员在职状态(在职/离职)
			$this->getFundJobStatus($info_gets3);
			$this->checkData($info_gets3);
		} else {
			self::p("共有0位基金资格用户需要通过身份证来查询");
		}
		self::p("[STEP-2完成]finished...\r\n");


		#---------------------------
		#STEP 3 处理数据变更并发送短信
		#---------------------------
		$this->update_sql_and_sendmsg();
		self::p("[STEP-3完成]finished...\r\n");

		#---------------------------
		#STEP 4 记录数据
		#---------------------------
		$this->print_info_unfind('checkplanner_unfind_信息未找到.csv');
		$this->print_info_changed('checkplanner_changed_信息变更过.csv');
		self::p("CSV文件生成完毕...\r\n");
		self::p("[STEP-4完成]finished...\r\n");


		#---------------------------
		#STEP 5 清除缓存 [http://licaishi.sina.com.cn/cacheApi/planner?p_uid=$p_uid]
		#---------------------------
		$this->clearCache();
		self::p("[STEP-5完成]finished...\r\n");
	}

	/**
	 * 获取所有的理财师
	 * @return type
	 * @throws type
	 */
	public function getPlannerAll() {
		$sql = 'SELECT `s_uid`,`name`,`real_name`,`gender`,`phone`,`identity`,`company_id`,`position_id`,`department`,`cert_id`,`cert_number`,`c_time`,`status` FROM `lcs_planner` where (cert_id=1 OR cert_id=2) ';
		//$sql .= ' and s_uid % 10 = 2 ';#测试数据用
		try {
			$list = Yii::app()->lcs_r->createCommand($sql)->queryAll() ?: [];
			$R = [];
			foreach ($list as $v) {
				$planner_id = $v['s_uid'];
				unset($v['s_uid']);
				$R[$planner_id] = $v;
			}
			return $R;
		} catch (Exception $e) {
			throw LcsException::errorHandlerOfException($e);
		}
	}

	/**
	 * 打印结果
	 * @return type
	 */
	public function print_info_unfind($file_name = 'cert_info_no_find') {
		if (empty($this->cert_info_no_find)) {
			return;
		}
		$str = "";

		$i = 1;
		$str .= "编号,"
			. "理财师ID,"
			. "理财师名称,"
			. "真实姓名,"
			. "性别,"
			. "电话,"
			. "资格证号,"
			. "身份证号,"
			. "公司名称,"
			. "职位名称,"
			. "部门,"
			. "创建时间,"
			. "是否冻结"
			. "\r\n";
		foreach ($this->cert_info_no_find as $planner_id) {
			$v = $this->planner_arr[$planner_id];
			$str .= $i . ","
				. $planner_id . ","
				. $v['name'] . ","
				. $v['real_name'] . ","
				. (strtolower($v['gender']) == 'm' ? '男' : '女') . ","
				. "\t" . $v['phone'] . ","
				. "\t" . $v['cert_number'] . ","
				. "\t" . $v['identity'] . ","
				. (isset($this->company_fullName[$v['company_id']]) ? $this->company_fullName[$v['company_id']] : "") . ","
				. (isset($this->position_name[$v['position_id']]) ? $this->position_name[$v['position_id']] : "") . ","
				. $v['department'] . ","
				. $v['c_time'] . ","
				. ($v['status'] == -2 ? "是" : "")
				. "\r\n"
			;
			$i++;
		}

		file_put_contents(dirname(dirname(dirname(__DIR__))) . '/log/' . $file_name . '.table.csv', $str);
		file_put_contents(dirname(dirname(dirname(__DIR__))) . '/log/' . $file_name . '.table.gbk.csv', mb_convert_encoding($str, "GBK"));
	}

	/**
	 * 打印结果
	 * @return type
	 */
	public function print_info_changed($file_name = 'cert_info_change') {
		if (empty($this->update_sql)) {
			return;
		}

		$str = "";
		$i = 1;
		$str .= "编号,"
			. "理财师ID,"
			. "理财师名称,"
			. "原真实姓名,"
			. "真实姓名,"
			. "原性别,"
			. "性别,"
			. "电话,"
			. "资格证号,"
			. "新资格证号,"
			. "身份证号,"
			. "原公司名称,"
			. "公司名称,"
			. "原职位名称,"
			. "职位名称,"
			. "原部门,"
			. "部门,"
			. "创建时间,"
			. "是否冻结"
			. "\r\n";

		foreach ($this->cert_info_changed as $planner_id) {
			$v = $this->planner_arr[$planner_id];
			$str .= $i . ","
				. $planner_id . ","
				. $v['name'] . ","
				. $v['real_name'] . ","
				. (isset($this->update_sql[$planner_id]['real_name']) ? $this->update_sql[$planner_id]['real_name'] : "" ) . ","
				. (strtolower($v['gender']) == 'm' ? "男" : "女") . ","
				. (isset($this->update_sql[$planner_id]['gender']) ? (strtolower($this->update_sql[$planner_id]['gender']) == 'm' ? "男" : "女") : "" ) . ","
				. "\t" . $v['phone'] . ","
				. "\t" . trim($v['cert_number']) . ","
				. "\t" . (isset($this->update_sql[$planner_id]['real_cert_num']) ? $this->update_sql[$planner_id]['real_cert_num'] : "") . ","
				. "\t" . $v['identity'] . ","
				. (($v['company_id'] && isset($this->company_fullName[$v['company_id']])) ? $this->company_fullName[$v['company_id']] : "") . ","
				. (isset($this->update_sql[$planner_id]['company_name']) ? $this->update_sql[$planner_id]['company_name'] : "" ) . ","
				. (($v['position_id'] && isset($this->position_name[$v['position_id']]) ) ? $this->position_name[$v['position_id']] : "") . ","
				. (isset($this->update_sql[$planner_id]['position_name']) ? $this->update_sql[$planner_id]['position_name'] : "") . ","
				. ($v['department'] ?: "") . ","
				. (isset($this->update_sql[$planner_id]['department']) ? $this->update_sql[$planner_id]['department'] : "" ) . ","
				. $v['c_time'] . ","
				. ($v['status'] == -2 ? "是" : "") . ","
				. "\r\n"
			;
			$i++;
		}

		file_put_contents(dirname(dirname(dirname(__DIR__))) . '/log/' . $file_name . '.table.csv', $str);
		file_put_contents(dirname(dirname(dirname(__DIR__))) . '/log/' . $file_name . '.table.gbk.csv', mb_convert_encoding($str, "GBK"));
	}

	/**
	 * clearCache
	 * @return type
	 */
	private function clearCache() {

		if (empty($this->planner_arr)) {
			return;
		}

		if ($this->is_change == FALSE) {
			self::p("不执行数据变更，不执行缓存清理");
			return;
		}

		self::p("共有" . count($this->planner_arr) . "位理财师需要清除缓存");

		$i = 1;
		$data = [];
		foreach ($this->planner_arr as $uid => $v) {

			if ($uid == '')
				continue;

			$data[$uid] = [
				'url' => 'http://licaishi.sina.com.cn/cacheApi/planner?p_uid=' . $uid,
				'postfields' => '',
				'referer' => '',
			];
			if (count($data) == 100) {
				$cons = $this->curl_multi($data);
				foreach ($cons as $u => $c) {
					echo $i . " - " . $u . " - " . $c . "\r\n";
				}
				$data = [];
			}
			$i++;
		}
		$cons = $this->curl_multi($data);
		foreach ($cons as $u => $c) {
			echo $i . " - " . $u . " - " . $c . "\r\n";
			$i++;
		}
	}

	/**
	 * 对比信息
	 * @param type $info_gets
	 * @param type $arr
	 */
	private function checkData(array &$info_gets) {
		foreach ($info_gets ?: [] as $planner_id => $gets) {

			$info = $this->planner_arr[$planner_id];

			//真实姓名
			if ($gets['RPI_NAME'] != $info['real_name']) {
				$this->update_sql[$planner_id]['real_name'] = $gets['RPI_NAME'];
			}

			//性别
			$gets['SCO_NAME'] = ($gets['SCO_NAME'] == '男') ? 'm' : 'f';
			if ($gets['SCO_NAME'] != strtolower($info['gender'])) {
				$this->update_sql[$planner_id]['gender'] = $gets['SCO_NAME'];
			}

			//公司
			if ($info['company_id'] == '' || !isset($this->company_fullName[$info['company_id']]) || $this->company_fullName[$info['company_id']] != $gets['AOI_NAME']) {
				$this->update_sql[$planner_id]['company_id'] = $this->getArrayIdByName($gets['AOI_NAME'], $this->company_fullName, 'company', $info['cert_id']);
				$this->update_sql[$planner_id]['company_name'] = $gets['AOI_NAME'];
			}
			//职位
			if ($info['position_id'] == '' || !isset($this->position_name[$info['position_id']]) || $this->position_name[$info['position_id']] != $gets['PTI_NAME']) {
				$this->update_sql[$planner_id]['position_id'] = $this->getArrayIdByName($gets['PTI_NAME'], $this->position_name, 'position', $info['cert_id']);
				$this->update_sql[$planner_id]['position_name'] = $gets['PTI_NAME'];
			}
			//部门
			if ($gets['ADI_NAME'] != $info['department']) {
				$this->update_sql[$planner_id]['department'] = $gets['ADI_NAME'];
			}
			//cert_num
			if (isset($gets['real_cert_num']) && $gets['real_cert_num'] != "" && $gets['real_cert_num'] != trim($info['cert_number'])) {
				$this->update_sql[$planner_id]['real_cert_num'] = $gets['real_cert_num'];
			}
			//cert_id
			if (isset($gets['cert_id']) && $gets['cert_id'] != "" && $gets['cert_id'] != $info['cert_id']) {
				$this->update_sql[$planner_id]['cert_id'] = $gets['cert_id'];
			}

			//记录原始值
			if (!empty($this->update_sql[$planner_id])) {
				$this->cert_info_changed[$planner_id] = $planner_id;
				self::p("对比信息：$planner_id 信息变更");
			} else {
				self::p("对比信息：$planner_id 信息未变更");
			}
		}
	}

	/**
	 * 通过数组元素值获取数组元素的键
	 */
	protected function getArrayIdByName($name, &$array, $key_name = '', $cert_id = 1) {
		foreach ($array as $id => $vname) {
			if ($name == $vname) {
				return $id;
			}
		}

		if ($key_name == 'company') {
			$id = $this->insert_company($name);
			$array[$id] = $name;
		} else if ($key_name == 'position') {
			$id = $this->insert_position($name, $cert_id);
			$array[$id] = $name;
		}
		return $id;
	}

	private function insert_company($company_name) {
		if ($company_name == '') {
			return 0;
		}

		$name = str_replace(['有限责任公司', '股份有限公司', '资产管理有限公司', '投资顾问有限公司', '有限公司'], ['', '', '资管', '投顾', ''], $company_name);

		$now = date('Y-m-d H:i:s');
		$sql = 'REPLACE INTO `lcs_company` (`name`,`full_name`,`c_type`,`address`,`license_no`,`license_pic`,`phone`,`contact`,`staff_uid`,`c_time`,`u_time`,`desc`)'
			. 'VALUES ("' . addslashes($name) . '","' . addslashes($company_name) . '",0,"","","","","","","' . $now . '","' . $now . '","")';
		try {
			if (Yii::app()->lcs_w->createCommand($sql)->execute()) {
				return Yii::app()->lcs_w->getLastInsertID();
			} else {
				return 0;
			}
		} catch (Exception $e) {
			throw LcsException::errorHandlerOfException($e);
		}
	}

	private function insert_position($position_name, $cert_id) {
		if ($position_name == '') {
			return 0;
		}
		$now = date('Y-m-d H:i:s');
		$sql = 'REPLACE INTO `lcs_position` (`cert_id`,`name`,`staff_uid`,`u_time`,`c_time`) VALUES (' . $cert_id . ',"' . addslashes($position_name) . '","","' . $now . '","' . $now . '")';
		try {
			if (Yii::app()->lcs_w->createCommand($sql)->execute()) {
				return Yii::app()->lcs_w->getLastInsertID();
			} else {
				return 0;
			}
		} catch (Exception $e) {
			throw LcsException::errorHandlerOfException($e);
		}
	}

	/**
	 * 获取所有公司全名
	 */
	protected function getAllCompanyFullName() {
		$sql = 'SELECT `id`,`full_name` FROM `lcs_company`';
		try {
			$list = Yii::app()->lcs_r->createCommand($sql)->queryAll() ?: [];
			$R = [];
			foreach ($list as $v) {
				$R[$v['id']] = $v['full_name'];
			}
			return $R;
		} catch (Exception $e) {
			throw LcsException::errorHandlerOfException($e);
		}
	}

	/**
	 * 获取所有职位全名
	 */
	protected function getAllPositionName() {
		$sql = 'SELECT `id`,`name` FROM `lcs_position`';
		try {
			$list = Yii::app()->lcs_r->createCommand($sql)->queryAll() ?: [];
			$R = [];
			foreach ($list as $v) {
				$R[$v['id']] = $v['name'];
			}
			return $R;
		} catch (Exception $e) {
			throw LcsException::errorHandlerOfException($e);
		}
	}

	/**
	 * curl_multi
	 */
	protected function curl_multi(array $data) {
		$debug = debug_backtrace(1, 2);
		$fun = $debug[1]['function'];

		if (empty($data)) {
			return [];
		}

		$time_start = microtime(true);

		$ch = [];
		$mh = curl_multi_init();
		foreach ($data as $planner_id => $v) {
			$ch[$planner_id] = curl_init();
			curl_setopt($ch[$planner_id], CURLOPT_URL, $v['url']);
			curl_setopt($ch[$planner_id], CURLOPT_HEADER, 0);
			curl_setopt($ch[$planner_id], CURLOPT_ENCODING, self::ENCODING);
			curl_setopt($ch[$planner_id], CURLOPT_TIMEOUT, self::TIMEOUT);
			curl_setopt($ch[$planner_id], CURLOPT_HTTPHEADER, [
				"Accept: application/json, text/javascript, */*; q=0.01",
				"Accept-Language: zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2",
				"Content-Type: application/x-www-form-urlencoded",
				"X-Requested-With: XMLHttpRequest",
				"Content-Length: " . strlen($v['postfields']),
				"Cookie: JSESSIONID=psJchTlbTYKYKL1v3yQH6fmTvlZ1QMTtZyhwJ3RJbFBhtnGxdSlQ!111413616",
				"Connection: keep-alive"
			]);
			curl_setopt($ch[$planner_id], CURLOPT_USERAGENT, self::USER_AGENT);
			curl_setopt($ch[$planner_id], CURLOPT_REFERER, $v['referer']);
			curl_setopt($ch[$planner_id], CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch[$planner_id], CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch[$planner_id], CURLOPT_POST, 1);
			curl_setopt($ch[$planner_id], CURLOPT_POSTFIELDS, $v['postfields']);

			curl_multi_add_handle($mh, $ch[$planner_id]);
		}

		$active = null;
		do {
			$mrc = curl_multi_exec($mh, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		while ($active && $mrc == CURLM_OK) {
			if (curl_multi_select($mh) == -1) {
				usleep(1);
			}
			do {
				$mrc = curl_multi_exec($mh, $active);
				$info = curl_multi_info_read($mh);
				if (false !== $info) {
					//print_r($info);
				}
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		}

		$cons = [];
		foreach ($data as $planner_id => $v) {
			$cons[$planner_id] = curl_multi_getcontent($ch[$planner_id]);
			curl_multi_remove_handle($mh, $ch[$planner_id]);
		}
		curl_multi_close($mh);

		$time_end = microtime(true);
		$time_use = round(($time_end - $time_start), 4);
		$count = count($data);
		self::p("${fun} CURL批处理 ${count}个，耗时${time_use}秒");

		return $cons;
	}

	/**
	 * 通过身份证号批量获取证券资格证对应信息
	 * @return type
	 */
	public function getCertInfoForMultiByIDCard(array &$identity_number_arr) {
		self::p("通过身份证号批量获取证券资格证对应信息: " . implode(",", array_keys($identity_number_arr)));

		//先获取"PPP_ID"
		$PPP_ID_arr = $this->getPPPIDByIDCard($identity_number_arr);

		//获取通过“PPP_ID”获取“RPI_ID”
		$RPI_ID_arr = $this->getRPIIDByPPPID($PPP_ID_arr);

		//通过“RPI_ID”获取 完整信息
		$cons = $this->getConsByRPIID($RPI_ID_arr, $PPP_ID_arr);

		//通过“RPI_ID”获取 注册变更记录中最后的证书编号
		$lastCertNums = $this->getLastCertNums_stock($RPI_ID_arr);
		foreach ($cons as $planner_id => $c) {
			$cons[$planner_id]['real_cert_num'] = $lastCertNums[$planner_id];
		}

		//检查获取为空的
		foreach ($identity_number_arr as $planner_id => $identity_number) {
			if (!isset($cons[$planner_id])) {
				$this->cert_info_no_find[$planner_id] = $planner_id;
				self::p("$planner_id 通过身份证号获取证券资格信息为空");
			} else {
				if (isset($this->cert_info_no_find[$planner_id])) {
					unset($this->cert_info_no_find[$planner_id]);
				}
			}
		}
		return $cons;
	}

	/**
	 * 通过证券资格号批量获取对应信息
	 * @return type
	 */
	public function getCertInfoForMulti(array &$cert_number_arr) {
		self::p("通过证券资格号批量获取对应信息: " . implode(',', array_keys($cert_number_arr)));

		if (empty($cert_number_arr)) {
			return [];
		}

		//先获取"PPP_ID"
		$PPP_ID_arr = $this->getPPPIDByCertNumber($cert_number_arr);

		//获取通过“PPP_ID”获取“RPI_ID”
		$RPI_ID_arr = $this->getRPIIDByPPPID($PPP_ID_arr);

		//通过“RPI_ID”获取 完整信息
		$cons = $this->getConsByRPIID($RPI_ID_arr, $PPP_ID_arr);

		//通过“RPI_ID”获取 注册变更记录中最后的证书编号
		$lastCertNums = $this->getLastCertNums_stock($RPI_ID_arr);
		foreach ($cons as $planner_id => $c) {
			$cons[$planner_id]['real_cert_num'] = $lastCertNums[$planner_id];
		}

		//检查获取为空的
		foreach ($cert_number_arr as $planner_id => $cert_number) {
			if (!isset($cons[$planner_id])) {
				$this->cert_info_no_find[$planner_id] = $planner_id;
				self::p("$planner_id 通过证券资格号获取证券资格信息为空");
			} else {
				if (isset($this->cert_info_no_find[$planner_id])) {
					unset($this->cert_info_no_find[$planner_id]);
				}
			}
		}

		return $cons;
	}

	/**
	 * 处理基金执业资格--人数较少因此一次性处理
	 * @param array $fund_number_arr
	 * @return type
	 */
	public function getFundInfoForMulti(array &$fund_number_arr) {

		if (empty($fund_number_arr)) {
			return [];
		}

		self::p("通过基金资格号批量获取对应信息: " . implode(',', array_keys($fund_number_arr)));

		//先获取"PPP_ID"
		$RPI_ID_arr = $this->getPPPIDByFundNumber($fund_number_arr);

		//通过“RPI_ID”获取 完整信息
		$cons = $this->getConsByRPIID_fund($RPI_ID_arr);

		//通过“RPI_ID”获取 注册变更记录中最后的证书编号
		$lastCertNums = $this->getLastCertNums_fund($RPI_ID_arr);
		foreach ($cons as $planner_id => $c) {
			$cons[$planner_id]['real_cert_num'] = $lastCertNums[$planner_id];
		}

		//检查获取为空的
		foreach ($fund_number_arr as $planner_id => $fund_number) {
			if (!isset($cons[$planner_id])) {
				$this->cert_info_no_find[$planner_id] = $planner_id;
				self::p("$planner_id 通过基金资格号获取证券资格信息为空");
			} else {
				if (isset($this->cert_info_no_find[$planner_id])) {
					unset($this->cert_info_no_find[$planner_id]);
				}
			}
		}
		return $cons;
	}

	/**
	 * 通过身份证批量获取基金从业人员的资料
	 * @param array $multi_data
	 * @return type
	 */
	public function getFundCertInfoForMultiByIDCard(array &$fund_identity_number_arr) {
		if (empty($fund_identity_number_arr)) {
			return [];
		}

		self::p("通过身份证批量获取基金从业人员的资料: " . implode(',', array_keys($fund_identity_number_arr)));

		//先获取"PPP_ID"
		$PRI_ID_arr = $this->getFundPRIIDByIDCard($fund_identity_number_arr);

		//通过“RPI_ID”获取 完整信息
		$cons = $this->getConsByRPIID_fund($PRI_ID_arr);

		//通过“RPI_ID”获取 注册变更记录中最后的证书编号
		$lastCertNums = $this->getLastCertNums_fund($PRI_ID_arr);
		foreach ($cons as $planner_id => $c) {
			$cons[$planner_id]['real_cert_num'] = $lastCertNums[$planner_id];
		}

		//检查获取为空的
		foreach ($fund_identity_number_arr as $planner_id => $identity_number) {
			if (!isset($cons[$planner_id])) {
				$this->cert_info_no_find[$planner_id] = $planner_id;
				self::p("$planner_id 通过身份证号获取基金资格信息为空");
			} else {
				if (isset($this->cert_info_no_find[$planner_id])) {
					unset($this->cert_info_no_find[$planner_id]);
				}
			}
		}

		return $cons;
	}

	/**
	 * 基金 - 通过“RPI_ID”获取注册变更记录中最后的证书编号
	 * @param type $PRI_ID_arr
	 */
	private function getLastCertNums_fund($PRI_ID_arr) {
		$data3 = [];
		foreach ($PRI_ID_arr ?: [] as $planner_id => $RPI_ID) {
			$data3[$planner_id] = [
				'url' => 'http://person.amac.org.cn/pages/registration/train-line-register!search.action',
				'referer' => 'http://person.amac.org.cn',
				'postfields' => 'filter_EQS_RH#RPI_ID=' . $RPI_ID . '&sqlkey=registration&sqlval=SEARCH_LIST_BY_PERSON', //SEARCH_LIST_BY_PERSON
			];
		}
		$cons_INFO = $this->curl_multi($data3);
		if (empty($cons_INFO)) {
			return [];
		}

		$CER_NUM_arr = [];
		foreach ($cons_INFO as $planner_id => $con) {
			$data_arr = json_decode($con, true);

			if (is_array($data_arr) && !empty($data_arr)) {
				$data = array_pop($data_arr); //取最后一条
				$CER_NUM = $data['CER_NUM'];
			} else {
				$CER_NUM = "";
			}

			$CER_NUM_arr[$planner_id] = $CER_NUM;
		}

		return $CER_NUM_arr;
	}

	/**
	 * 证券 - 通过“RPI_ID”获取注册变更记录中最后的证书编号
	 * @param type $PRI_ID_arr
	 */
	private function getLastCertNums_stock($PRI_ID_arr) {
		$data3 = [];
		foreach ($PRI_ID_arr ?: [] as $planner_id => $RPI_ID) {
			$data3[$planner_id] = [
				'url' => 'http://person.sac.net.cn/pages/registration/train-line-register!search.action',
				'referer' => 'http://person.sac.net.cn/',
				'postfields' => 'filter_EQS_RH#RPI_ID=' . $RPI_ID . '&sqlkey=registration&sqlval=SEARCH_LIST_BY_PERSON', //SEARCH_LIST_BY_PERSON
			];
		}
		$cons_INFO = $this->curl_multi($data3);
		if (empty($cons_INFO)) {
			return [];
		}

		$CER_NUM_arr = [];
		foreach ($cons_INFO as $planner_id => $con) {
			$data_arr = json_decode($con, true);

			if (is_array($data_arr) && !empty($data_arr)) {
				$data = array_pop($data_arr); //取最后一条
				$CER_NUM = $data['CER_NUM'];
			} else {
				$CER_NUM = "";
			}

			$CER_NUM_arr[$planner_id] = $CER_NUM;
		}

		return $CER_NUM_arr;
	}

	/**
	 * 执行SQL变更，并发送短信通知
	 * @return type
	 */
	private function update_sql_and_sendmsg() {

		if ($this->is_change == false) {
			self::p("关闭了执行数据修改，操作略过");
			return true;
		}

		/*
		  if ($this->is_send == true) {
		  foreach ($this->cert_info_no_find ?: [] as $planner_id) {
		  if ($this->planner_arr[$planner_id]['status'] != -2) {
		  self::p("向理财师[$planner_id]-[{$this->planner_arr[$planner_id]['phone']}]发送完善个人信息短信");
		  self::sendYunPianSms($this->planner_arr[$planner_id]['phone'], '理财师您好,新浪系统升级核实个人信息,请于12月30日前登录理财师管理后台完善个人身份证信息。详询021-36129996');
		  } else {
		  self::p("理财师[$planner_id]已被冻结，不发送短信");
		  }
		  }
		  } else {
		  self::p("关闭了发送通知，操作略过");
		  }
		 */

		foreach ($this->update_sql ?: [] as $planner_id => $fields) {

			$sql = 'UPDATE `lcs_planner` SET ';
			$field_str_arr = [];
			if (isset($fields['real_name'])) {
				$field_str_arr[] = '`real_name`="' . $fields['real_name'] . '"';
			}
			if (isset($fields['gender'])) {
				$field_str_arr[] = '`gender`="' . $fields['gender'] . '"';
			}
			if (isset($fields['company_id'])) {
				$field_str_arr[] = '`company_id`=' . $fields['company_id'];
			}
			if (isset($fields['position_id'])) {
				$field_str_arr[] = '`position_id`=' . $fields['position_id'];
			}
			if (isset($fields['department'])) {
				$field_str_arr[] = '`department`="' . $fields['department'] . '"';
			}
			if (isset($fields['cert_id'])) {
				$field_str_arr[] = '`cert_id`="' . $fields['cert_id'] . '"';
			}
			if (isset($fields['real_cert_num'])) {
				$field_str_arr[] = '`cert_number`="' . $fields['real_cert_num'] . '"';
			}

			if (empty($field_str_arr)) {
				continue;
			}

			$field_str_arr[] = '`is_check_pass`=1';
			$field_str_arr[] = '`u_time`="' . date('Y-m-d H:i:s') . '"';

			$sql .= implode(', ', $field_str_arr) . ' WHERE `s_uid`=' . $planner_id . ' LIMIT 1';
			try {
				Yii::app()->lcs_w->createCommand($sql)->execute();
			} catch (Exception $e) {
				throw LcsException::errorHandlerOfException($e);
			}

			if ($this->is_send == false) {
				self::p("关闭了发送通知，操作略过");
				continue;
			}

			if ($this->planner_arr[$planner_id]['status'] != -2) {
				self::p("向理财师[$planner_id]-[{$this->planner_arr[$planner_id]['phone']}]发送匹配更新短信");
				self::sendYunPianSms($this->planner_arr[$planner_id]['phone'], '您的理财师账号信息已被系统自动匹配更新。详询021-36129996');
			} else {
				self::p("理财师[$planner_id]已被冻结，不发送短信");
			}
		}
	}

	// 新短信发送逻辑 云片
	public static function sendYunPianSms($phone, $content) {
		$params = array(
			'phone' => $phone,
			'content' => $content,
			'source' => 1, //新浪理财师
			'type' => ''
		);
		$redis_key = MEM_PRE_KEY . 'sendPhoneMsg';
		$result = Yii::app()->redis_w->rPush($redis_key, json_encode($params));
		return $result;
	}

	/**
	 * 证券 - 获取最后一条记录的工作状态
	 * @param type $info_gets
	 * @return type
	 */
	public function getStockJobStatus(array &$info_gets) {
		if (empty($info_gets)) {
			return [];
		}

		$data = [];
		foreach ($info_gets as $planner_id => $info) {
			$data[$planner_id] = [
				'url' => 'http://person.sac.net.cn/pages/registration/train-line-register!search.action',
				'referer' => 'http://person.sac.net.cn/pages/registration/sac-finish-person.html',
				'postfields' => 'filter_EQS_RH#RPI_ID=' . $info['RPI_ID'] . '&sqlkey=registration&sqlval=SEARCH_LIST_BY_PERSON'
			];
		}
		$cons_PRI_ID = $this->curl_multi($data);
		if (empty($cons_PRI_ID)) {
			return [];
		}

		foreach ($cons_PRI_ID as $planner_id => $con) {
			$data_arr = json_decode($con, true);
			if (is_array($data_arr) && !empty($data_arr)) {
				$data = array_pop($data_arr);
			} //最后一条为准
			else {
				continue;
			}

			if ($data['CERTC_NAME'] == '离职') {
				$info_gets[$planner_id]['AOI_NAME'] = '理财达人';
				$this->update_sql[$planner_id] = [
					'company_id' => $this->getArrayIdByName('理财达人', $this->company_fullName, 'company', 15),
					'cert_id' => 15];
			}
		}
	}

	/**
	 * 基金 - 获取最后一条记录的工作状态
	 * @param type $info_gets
	 * @return type
	 */
	public function getFundJobStatus(array &$info_gets) {
		if (empty($info_gets)) {
			return [];
		}

		$data = [];
		foreach ($info_gets as $planner_id => $info) {
			$data[$planner_id] = [
				'url' => 'http://person.amac.org.cn/pages/registration/train-line-register!search.action',
				'referer' => 'http://person.amac.org.cn/pages/registration/amac-finish-person.html?rpiId=' . $info['RPI_ID'],
				'postfields' => 'filter_EQS_RH#RPI_ID=' . $info['RPI_ID'] . '&sqlkey=registration&sqlval=SEARCH_LIST_BY_PERSON'
			];
		}
		$cons_PRI_ID = $this->curl_multi($data);
		if (empty($cons_PRI_ID)) {
			return [];
		}

		foreach ($cons_PRI_ID as $planner_id => $con) {
			$data_arr = json_decode($con, true);
			if (is_array($data_arr) && !empty($data_arr)) {
				$data = array_pop($data_arr);
			} //最后一条为准
			else {
				continue;
			}

			if ($data['CERTC_NAME'] == '离职') {
				$info_gets[$planner_id]['AOI_NAME'] = '理财达人';
				$this->update_sql[$planner_id] = [
					'company_id' => $this->getArrayIdByName('理财达人', $this->company_fullName, 'company', 15),
					'cert_id' => 15];
			}
		}
	}

	/**
	 * getFundPRIIDByIDCard
	 * @param type $cert_number_arr
	 * @return type
	 */
	public function getFundPRIIDByIDCard($cert_number_arr) {
		$data = [];
		foreach ($cert_number_arr as $planner_id => $cert_number) {
			$data[$planner_id] = [
				'url' => 'http://person.amac.org.cn/pages/registration/train-line-register!search.action',
				'referer' => 'http://person.amac.org.cn/pages/registration/amac-publicity-report.html',
				'postfields' => 'filter_EQS_RPI_PAPER_NO=' . trim($cert_number) . '&sqlkey=registration&sqlval=SEARCH_CTI_ID_BY_CODE'
			];
		}
		$cons_PRI_ID = $this->curl_multi($data);
		if (empty($cons_PRI_ID)) {
			return [];
		}

		$RPI_ID_arr = [];
		foreach ($cons_PRI_ID as $planner_id => $con) {
			$data_arr = json_decode($con, true);
			if (is_array($data_arr) && !empty($data_arr)) {
				$data = array_shift($data_arr);
			} else {
				continue;
			}

			if (isset($data['RPI_ID'])) {
				$RPI_ID_arr[$planner_id] = $data['RPI_ID'];
			} else {
				continue;
			}
		}

		return $RPI_ID_arr;
	}

	/**
	 * 基金执业资格
	 * @param type $fund_number_arr
	 */
	private function getPPPIDByFundNumber(array &$fund_number_arr) {
		$data = [];
		foreach ($fund_number_arr as $planner_id => $fund_number) {
			$data[$planner_id] = [
				'url' => 'http://person.amac.org.cn/pages/registration/train-line-register!search.action',
				'referer' => 'http://person.amac.org.cn/pages/registration/amac-publicity-report.html',
				'postfields' => 'filter_EQS_CER_NUM=' . trim($fund_number) . '&sqlkey=registration&sqlval=SEARCH_CTI_ID_BY_CER_NUM'
			];
		}
		$cons_PPP_ID = $this->curl_multi($data);
		if (empty($cons_PPP_ID)) {
			return [];
		}

		$RPI_ID_arr = [];
		foreach ($cons_PPP_ID ?: [] as $planner_id => $con) {
			$data_arr = json_decode($con, true);
			if (is_array($data_arr) && !empty($data_arr)) {
				$data = array_shift($data_arr);
			} else {
				continue;
			}

			if (isset($data['RPI_ID'])) {
				$RPI_ID_arr[$planner_id] = $data['RPI_ID'];
			} else {
				continue;
			}
		}

		return $RPI_ID_arr;
	}

	/**
	 * 基金执业资格
	 * @param type $RPI_ID_arr
	 * @param type $PPP_ID_arr
	 */
	private function getConsByRPIID_fund($RPI_ID_arr) {
		$data3 = [];
		foreach ($RPI_ID_arr ?: [] as $planner_id => $RPI_ID) {
			$data3[$planner_id] = [
				'url' => 'http://person.amac.org.cn/pages/registration/train-line-register!search.action',
				'referer' => 'http://person.amac.org.cn/pages/registration/amac-finish-person.html?rpiId=' . $RPI_ID,
				'postfields' => 'filter_EQS_RPI_ID=' . $RPI_ID . '&sqlkey=registration&sqlval=SELECT_PERSON_INFO', //SELECT_PERSON_INFO | SELECT_OTHER_PERSON_INFO
			];
		}
		$cons_INFO = $this->curl_multi($data3);
		if (empty($cons_INFO)) {
			return [];
		}

		$cons = [];
		foreach ($cons_INFO as $planner_id => $con) {
			$data_arr = json_decode($con, true);
			if (is_array($data_arr) && !empty($data_arr)) {
				$data = array_shift($data_arr);
			} else {
				continue;
			}

			$data['RPI_ID'] = $RPI_ID_arr[$planner_id];
			$cons[$planner_id] = $data;
		}

		return $cons;
	}

	/**
	 * getPPPIDByCertNumber
	 * @param array $cert_number_arr
	 * @return type
	 */
	private function getPPPIDByCertNumber(array $cert_number_arr) {
		$data = [];
		foreach ($cert_number_arr as $planner_id => $cert_number) {
			$data[$planner_id] = [
				'url' => 'http://person.sac.net.cn/pages/registration/train-line-register!search.action',
				'referer' => 'http://person.sac.net.cn/pages/registration/sac-publicity-report.html',
				'postfields' => 'filter_EQS_CER_NUM=' . trim($cert_number) . '&sqlkey=registration&sqlval=SEARCH_CTI_ID_BY_CER_NUM'
			];
		}
		$cons_PPP_ID = $this->curl_multi($data);
		if (empty($cons_PPP_ID)) {
			return [];
		}

		$PPP_ID_arr = [];
		foreach ($cons_PPP_ID as $planner_id => $con) {
			$data_arr = json_decode($con, true);
			if (is_array($data_arr) && !empty($data_arr)) {
				$data = array_shift($data_arr);
			} else {
				continue;
			}

			if (isset($data['PPP_ID'])) {
				$PPP_ID_arr[$planner_id] = $data['PPP_ID'];
			}
		}

		return $PPP_ID_arr;
	}

	/**
	 * getPPPIDByIDCard
	 * @param array $multi_data
	 * @return type
	 */
	private function getPPPIDByIDCard(array $multi_data) {
		//先获取"PPP_ID"
		$data = [];
		foreach ($multi_data as $planner_id => $idcard_number) {
			$data[$planner_id] = [
				'url' => 'http://person.sac.net.cn/pages/registration/train-line-register!search.action',
				'referer' => 'http://person.sac.net.cn/pages/registration/sac-publicity-report.html',
				'postfields' => 'filter_EQS_RPI_PAPER_NO=' . trim($idcard_number) . '&sqlkey=registration&sqlval=SEARCH_CTI_ID_BY_CODE'
			];
		}
		$cons_PPP_ID = $this->curl_multi($data);
		if (empty($cons_PPP_ID)) {
			return [];
		}

		$PPP_ID_arr = [];
		foreach ($cons_PPP_ID as $planner_id => $con) {
			$data_arr = json_decode($con, true);
			if (is_array($data_arr) && !empty($data_arr)) {
				$data = array_shift($data_arr);
			} else {
				continue;
			}

			if (isset($data['PPP_ID'])) {
				$PPP_ID_arr[$planner_id] = $data['PPP_ID'];
			} else {
				continue;
			}
		}
		return $PPP_ID_arr;
	}

	/**
	 * getRPIIDByPPPID
	 * @param array $PPP_ID_arr
	 * @return type
	 */
	private function getRPIIDByPPPID(array $PPP_ID_arr) {
		$data2 = [];
		foreach ($PPP_ID_arr ?: [] as $planner_id => $PPP_ID) {
			$data2[$planner_id] = [
				'url' => 'http://person.sac.net.cn/pages/registration/train-line-register!search.action',
				'referer' => 'http://person.sac.net.cn/pages/registration/sac-finish-person.html?r2SS_IFjjk=' . $PPP_ID,
				'postfields' => 'filter_EQS_PPP_ID=' . $PPP_ID . '&sqlkey=registration&sqlval=SD_A02Leiirkmuexe_b9ID'
			];
		}
		$cons_RPI_ID = $this->curl_multi($data2);
		if (empty($cons_RPI_ID)) {
			return [];
		}

		$RPI_ID_arr = [];
		foreach ($cons_RPI_ID as $planner_id => $con) {
			$data_arr = json_decode($con, true);

			if (is_array($data_arr) && !empty($data_arr)) {
				$data = array_shift($data_arr);
			} else {
				continue;
			}

			if (isset($data['RPI_ID']) && $data['RPI_ID'] != '') {
				$RPI_ID_arr[$planner_id] = $data['RPI_ID'];
			}
		}
		return $RPI_ID_arr;
	}

	/**
	 * getConsByRPIID
	 * @param array $RPI_ID_arr
	 * @return type
	 */
	private function getConsByRPIID(array $RPI_ID_arr, &$PPP_ID_arr) {
		$data3 = [];
		foreach ($RPI_ID_arr ?: [] as $planner_id => $RPI_ID) {
			$data3[$planner_id] = [
				'url' => 'http://person.sac.net.cn/pages/registration/train-line-register!search.action',
				'referer' => 'http://person.sac.net.cn/pages/registration/sac-finish-person.html?r2SS_IFjjk=' . $PPP_ID_arr[$planner_id],
				'postfields' => 'filter_EQS_RPI_ID=' . $RPI_ID . '&sqlkey=registration&sqlval=SELECT_PERSON_INFO', //SELECT_PERSON_INFO | SELECT_OTHER_PERSON_INFO
			];
		}
		$cons_INFO = $this->curl_multi($data3);
		if (empty($cons_INFO)) {
			return [];
		}

		$cons = [];
		foreach ($cons_INFO as $planner_id => $con) {
			$data_arr = json_decode($con, true);
			if (is_array($data_arr) && !empty($data_arr)) {
				$data = array_shift($data_arr);
			} else {
				continue;
			}

			$data['RPI_ID'] = $RPI_ID_arr[$planner_id];
			$cons[$planner_id] = $data;
		}

		return $cons;
	}

	/**
	 * p打印
	 * @param type $string
	 */
	private static function p($string = "") {
		if (self::DEBUG) {
			echo $string . "\r\n";
		}

		file_put_contents(dirname(dirname(dirname(__DIR__))) . '/log/checkplanner.log', $string . "\r\n", FILE_APPEND);
	}

}
