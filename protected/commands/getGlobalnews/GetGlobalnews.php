<?php

define('CURL_USERAGENT', 'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:54.0) Gecko/20100101 Firefox/54.0');
define('LOG_FILE', dirname(dirname(dirname(__DIR__))) . '/log/getGlobalnews.log');
define('PROCESS_TITLE', 'lxc-GetGlobalnews'); //本进程名称

/**
 * Description of GetGlobalnews
 *
 * @author Administrator
 */
class GetGlobalnews {

	const CRON_NO = 2901; //任务代码
	const SLEEP_TIME = 5; //每次暂停的时间

	private static $id;

	public static function start($dire = 'f') {
		//注册一个退出函数.在任何退出的情况下检测是否由于错误引发的.包括die,exit等都会触发
		register_shutdown_function(function () {
			self::writeLog('Run shutdown');
		});

		//Error
		set_error_handler(function() {
			self::writeLog('Run error');
		});

		self::setProcessTitle(PROCESS_TITLE);

		$dire = ( $dire == 'b') ? 'b' : 'f';
		$order = ($dire == 'b') ? 'ASC' : 'DESC';
		do {
			self::$id = Globalnews::model()->getLastId($order) ?: 81982;
			self::run(self::$id, $dire);
			sleep(self::SLEEP_TIME);
		} while (TRUE);
	}

	private static function setProcessTitle($title) {
		self::writeLog("setProcessTitle : \$title:$title");

		if (function_exists('cli_set_process_title')) {
			cli_set_process_title($title);
		} elseif (extension_loaded('proctitle') && function_exists('setproctitle')) {
			setproctitle($title);
		}
	}

    private static function run($id,$dire = 'f'){
        $data = self::getDataZhibo();

		if (!isset($data['result']) || !isset($data['result']['status']) || !isset($data['result']['data'])) {
			self::writeLog('接口调用失败');
			return;
		}

		if ($data['result']['status']['code'] !== 0) {
			self::writeLog($data['result']['status']['msg'] ?: '接口调用出错' );
			return;
		}

		if (empty($data['result']['data']['feed']['list'])) {
			self::writeLog("获取数据为空");
			return;
		}

		$data_num = count($data['result']['data']['feed']['list']);
		$suc_num = 0;
		foreach ($data['result']['data']['feed']['list'] ?: [] as $v) {

			$echo = "";
			$echo .= $v['id'] . " : ";

			if (!$v['id'] || !$v['rich_text'] || !$v['create_time']) {
				$echo .= "数据错误";
				self::writeLog($echo);
				continue;
			}

			$echo .= date('Y-m-d H:i:s', $v['create_time']) . " : ";

			$find = Globalnews::model()->isExists($v['id']);
			if ($find) {
				$echo .= "数据重复";
				self::writeLog($echo);
				continue;
			}
			///标记股票和过滤a标签
			try {
				$v['rich_text'] = CommonUtils::formatConetentStock($v['rich_text']);
			} catch (Exception $e) {
				//$e->getMessage();
			}
			$v['rich_text'] = CommonUtils::filterAlink($v['rich_text']);
			$res = Globalnews::model()->addNews($v['id'], $v['rich_text'], '', strtotime($v['create_time']), $v['tag']);
			if (!$res) {
				$echo .= "insert err";
			} else {
				$suc_num++;
				$echo .= "导入数据成功";
			}

			self::writeLog($echo);
		}
		self::writeLog("本次抓取到${data_num}条数据，导入成功${suc_num}条数据");
    }

    private static function getDataZhibo(){
        $url = "http://zhibo.sina.com.cn/api/zhibo/feed?page=1&page_size=20&zhibo_id=152&tag_id=0&dire=f&dpc=1&pagesize=20";
        $data = self::mycurl($url);
        return $data;
    }

	/**
	 * 抓取数据
	 * @param type $id
	 * @param type $dire
	 * @return type
	 */
	private static function get_data($id, $dire = 'f', $tag = 0) {
		$url = 'http://live.sina.com.cn/zt/api/f/get/finance/globalnews1/index.htm?format=json&id=' . $id . '&tag=' . $tag . '&pagesize=45&dire=' . $dire . '&dpc=1';
		$data_html = self::mycurl($url, 'html');

		self::writeLog($data_html);

		$data = json_decode($data_html, true);
		return $data;
	}

	/**
	 * mycurl
	 * @param type $url
	 * @param type $data_type
	 * @param type $method
	 * @param type $post_data
	 * @param type $header
	 * @param type $curl_info
	 * @return type
	 */
	public static function mycurl($url = '', $data_type = 'json', $method = 'GET', $post_data = array(), $header = array()) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
		curl_setopt($ch, CURLOPT_USERAGENT, CURL_USERAGENT);
		if ($method != 'GET')
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		if (!empty($header))
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		if (!empty($post_data)) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		}
		if (preg_match('/https/i', $url)) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$cons = curl_exec($ch);
		if ($data_type == 'json')
			$cons = json_decode($cons, true);
		curl_close($ch);
		return $cons;
	}

	/**
	 * write
	 * @param type $msg
	 */
	public static function writeLog($msg, $type = 'INFO') {
		$massage = '[' . date('Y-m-d H:i:s') . '][' . $type . '] ' . $msg . "\n";
		echo $massage;
		return;
		$handle = fopen(LOG_FILE, 'a');
		if (!$type)
			$type = 'INFO';
		fwrite($handle, $massage);
		fclose($handle);
		if ($type == 'FATAL') {
			exit;
		}
	}

}
