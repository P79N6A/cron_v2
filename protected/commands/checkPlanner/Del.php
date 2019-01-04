<?php

class Del {

	const CRON_NO = 13201;
	const USER_AGENT = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0';
	const ENCODING = 'gzip, deflate';
	const TIMEOUT = 10;
	const DEBUG = true; // debug

	private $multi_size = 200; //CURL批处理的个数

	#---------------

	public function delCache() {
		$file = '/home/xiaocheng/del_cache_planner_uids.log';

		$uids = explode("\n", file_get_contents($file));


		$i = 1;
		$data = [];
		foreach ($uids as $uid) {

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

	public function AddFiled() {
		$file_path = '/home/xiaocheng/checkplanner_changed.table.csv';
		$file = fopen($file_path, "r");
		$i = 0;
		$cons_new = "";
		while (!feof($file)) {
			$i++;
			$line_cons = fgets($file);
			$cons = explode(',', $line_cons);
			if ($i == 1) {
				$cons_new .= trim($line_cons) . ",是否冻结\r\n";
				continue;
			}
			if (count($cons) < 5)
				continue;
			$line = $cons[0];
			$uid = $cons[1];

			if (!$line || !$uid)
				continue;

			$status = $this->getLCSStatus($uid);
			if ($status == -2) {
				$cons_new .= trim($line_cons) . ",是\r\n";
			} else {
				$cons_new .= trim($line_cons) . ",\r\n";
			}
		}
		fclose($file);

		file_put_contents($file_path . '_new.csv', $cons_new);
	}

	public function getLCSStatus($s_uid) {
		$sql = 'SELECT `status` FROM licaishi.lcs_temp_lxc  where s_uid=' . $s_uid . ' limit 1';
		try {
			$status = Yii::app()->lcs_r->createCommand($sql)->queryScalar() ?: '';
			return $status;
		} catch (Exception $e) {
			echo $e->getMessage();
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
