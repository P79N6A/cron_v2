<?php

class CommonUtils {
	public static $key_3des_symbol = 'lcssymbol2015';
	public static $is_ios_check = true;

	const FR_F_CLIENT = 'f_client';  //财经客户端来源
	const FR_LCS_CLIENT = 'lcs_client';   //理财师用户android 客户端
	const FR_LCS_CLIENT_IOS = 'lcs_client_ios';  //理财师用户ios 客户端
	const FR_LCS_PLANNER_CLIENT = 'lcs_planner_client';  //理财师 理财师android客户端
	const FR_LCS_PLANNER_CLIENT_IOS = 'lcs_planner_client_ios'; //理财师 理财师ios客户端
	const FR_LCS_SERVICE_PLATFORM = 'lcs_service_platform'; // 理财师服务平台
	const FR_LCS_CLIENT_CAIDAO_ANDROID = 'lcs_client_caidao_android'; // 理财师财道用户版 android
	const FR_LCS_CLIENT_CAIDAO_IOS = 'lcs_client_caidao_ios'; // 理财师财道用户版 ios 
	const FR_LCS_CLIENT_CAIDAO_TJ_ANDROID = 'lcs_client_caidao_tj_android'; // 理财师财道用户版 android
	const FR_LCS_TJ_CAIDAO_ANDROID = 'lcs_tj_caidao_android'; // 理财师财道用户版 android，错误的fr，客户端的锅，做个fr兼容
	const FR_LCS_CLIENT_CAIDAO_TJ_IOS = 'lcs_client_caidao_tj_ios'; // 理财师财道用户版 ios 
	const MATCH_DES_KEY = '4a5e6dd1a7d94b6f'; //投顾大赛des key
	const FR_LCS_WEB = 'lcs_web'; // 理财师web平台版
	const FR_LCS_WEB_VIP = 'lcs_web_vip'; // 理财师web尊享版
	const FR_LCS_MP = 'lcs_miniprograme'; //微信小程序
	
	/**
	 * 生成access_token
	 */
	public static function buildAccessToken($params)
	{
		$secret = "OrKY3UQyMTTcCYpFjWM0kLg0Pq4S2J4j";

		// 加密算法
		// $_access_str = "param1=param1_val&param2=param2_val&timestramp=1476257698";
		$_access_str = http_build_query($params);
		$_access_str .= "&timestramp=" . time();
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND);
		$access_token = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $secret, $_access_str, MCRYPT_MODE_ECB, $iv));

		return $access_token;
	}

	/**
	 * 用户昵称
	 * @param unknown $uid
	 * @return string
	 */
	public static function getShowName($uid) {
		return '财友' . self::encodeId(intval($uid));
	}
	    /**
     * 用户头像
     * @param  string $ori_img 原始头像地址
     * @param  int $size    需要的头像大小，如30、50、180、....
     * @return string          转换后的头像地址
     */
    public static function convertUserImage($ori_img, $to_size, $from_size=30) {
        $new_img = str_replace($from_size.'/', $to_size.'/', $ori_img);
        return $new_img;
    }

	/**
	 * 去掉emoji表情字符
	 */
	public static function removeEmoji($str) {
		if (!is_string($str)) {
			return $str;
		}
		if (!$str || $str == 'undefined' || empty($str)) {
			return '';
		}

		$text = json_encode($str); //暴露出unicode
		$text = preg_replace('/(\\\u[ed][0-9a-f]{3})/i', '', $text);
		return json_decode($text);
	}

	public static function getMillisecond() {
		return intval(microtime(true) * 1000);
	}

	public static function sendSms($phone, $content) {
		$url = 'http://qxt.intra.mobile.sina.cn/cgi-bin/qxt/sendSMS.cgi';
		$post_data_string = "msg=$content&usernumber=$phone&count=1&from=86546&longnum=1065750241300018&ext=-1";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data_string);
		$result = curl_exec($ch);

		//OptLog::model()->saveOptLog($this->getStaffUid(),strtolower($this->getRoute()),$phone,array('phone'=>$phone, 'sms_content'=>$content, 'rs_send' => $result));

		return $result;
	}

	public static function parseIndustry($ind_id) {
		if (empty($ind_id)) {
			return '';
		}

		switch ($ind_id) {
			case '1':
				return 'A股';
			case '2':
				return '基金';
			case '3':
				return '期货';
			case '4':
				return '金银油';
			case '5':
				return '其他理财';
			case '6':
				return '美股';
			case '7':
				return '港股';
			case '8':
				return '保险';

			default:
				return '';
		}
	}

	/**
	 * 格式化响应时间
	 *
	 * @param unknown_type $minute
	 */
	public static function formatRespTime($minute) {

		if ($minute <= 0) {
			$return = '-';
		} elseif ($minute < 60) {//1一小时内
			$return = $minute . '分钟';
		} elseif ($minute < 60 * 24) {
			$return = round($minute / 60) . '小时';
		} else if ($minute <= 60 * 36) {
			$return = '1天';
		} elseif ($minute <= 60 * 60) {
			$return = '2天';
		} else {
			$return = '3天';
		}
		return $return;
	}

	/**
	 * 格式化日期
	 * @param string $date 日期
	 *               xx秒前    xx分钟前   H:i mm-dd H:i Y-m-d H:i
	 * @return string
	 */
	public static function formatDate($date, $type = 'wap') {
		if (empty($date)) {
			return '';
		}
		$date_time = strtotime($date);
		if ($date_time < 1) {
			return '';
		}
		$seconds = time() - $date_time;
		if ($seconds <= 0) {
			return '1秒前';
		} else if ($seconds < 60) {
			return $seconds . '秒前';
		} else if ($seconds < 3600) { //小时
			return floor($seconds / 60) . '分钟前';
		}
		if ($type == 'wap') {
			if (date('m-d') == date('m-d', $date_time)) {
				return date('H:i', $date_time);
			} else {
				return date('m-d', $date_time);
			}
		} else {
			if (date('y') != date('y', $date_time)) {
				return date('Y-m-d', $date_time);
			} elseif (date('m-d') == date('m-d', $date_time)) {
				return date('H:i', $date_time);
			} else {
				return date('m-d H:i', $date_time);
			}
		}
	}

	/**
	 * 获取文本内容
	 * @param string $content
	 * @return string
	 */
	public static function getTextContent($content) {
		if (empty($content)) {
			return '';
		}
		$content = strip_tags($content); //去除html标签
		$content = self::htmlSpecialCharsDecode($content);
		//中文全角和英文空格 特殊字符
		//$content = preg_replace(array('/[\x{200d}]+/iu','/[\x{3000|0020}]+/iu'), array('',' '), $content);
		$content = preg_replace(array('/[\x{200d}]+/iu', '/[\x{3000}|\x{0020}]+/iu', '/[\x{10}]+/iu'), array('', ' '), $content);
		return trim($content);
	}

	/**
	 * html特殊字符进行转换
	 * SpecialChars：&lt; &gt; &#039; &quot; &amp; &nbsp;
	 * decodeChars: <    >     '     "       &    ' '(空格)
	 * @param string $str
	 * @return string
	 */
	public static function htmlSpecialCharsDecode($str) {
		if (empty($str)) {
			return $str;
		}
		$str = htmlspecialchars_decode($str, ENT_QUOTES);
		$str = str_replace('&nbsp;', ' ', $str);

		return $str;
	}

	/**
	 * 输出子字符串  输出的子字符串长度就是给定的长度
	 * @param unknown $str  原字符串
	 * @param number $len 子字符串长度    为0时全部输出
	 * @param string $etc 截断后追加在子字符串后面的内容
	 *
	 * @return string
	 */
	public static function getSubStrNew($str, $length, $suffix = '', $start = 0, $charset = "UTF-8") {
		if (trim($str) == '') {
			return '';
		}
		//判断起始位置
		$_strLen = mb_strlen($str, $charset);
		if ($start < 0 || $start >= $_strLen) {
			return '';
		}

		$returnstr = '';
		$returnstr1 = '';
		$i = $start;
		$l = 0;
		//添加后缀的字符串长度
		$len = !empty($suffix) ? $length - ceil(strlen($suffix) / 2) : $length;

		while ($l < $length) {
			$sub = mb_substr($str, $i, 1, $charset);
			$i++;
			$returnstr .= $sub;
			if ($l < $len) {
				$returnstr1 .= $sub;
			}
			if (strlen($sub) == 1) {
				$l += (($sub == "\r") || ($sub == "\n")) ? 0 : 0.5;
			} else {
				$l++;
			}
		}
		if (mb_strlen($str, $charset) > mb_strlen($returnstr, $charset) && !empty($suffix)) {
			$returnstr = $returnstr1 . $suffix;
		}
		return $returnstr;
	}

	/**
	 * 对整数id进行可逆混淆
	 */
	public static function encodeId($id) {
		$sid = ($id & 0xff000000);
		$sid += ($id & 0x0000ff00) << 8;
		$sid += ($id & 0x00ff0000) >> 8;
		$sid += ($id & 0x0000000f) << 4;
		$sid += ($id & 0x000000f0) >> 4;
		$sid ^= 21184816;
		return $sid;
	}

	/**
	 * 对通过encodeId混淆的id进行还原
	 */
	public static function decodeId($sid) {
		if (!is_numeric($sid)) {
			return false;
		}
		$sid ^= 21184816;
		$id = ($sid & 0xff000000);
		$id += ($sid & 0x00ff0000) >> 8;
		$id += ($sid & 0x0000ff00) << 8;
		$id += ($sid & 0x000000f0) >> 4;
		$id += ($sid & 0x0000000f) << 4;
		return $id;
	}

	public static function encodePhoneNumber($phoneNumber) {
		$phoneNumberPrefix = substr($phoneNumber, 0, 2);
		$phoneNumberSuffix = substr($phoneNumber, 2);
		return $phoneNumberPrefix . self::encodeId($phoneNumberSuffix);
	}

	public static function decodePhoneNumber($hash) {
		if (($phoneNumberPrefix = substr($hash, 0, 2)) && ($phoneNumberSuffix = substr($hash, 2))) {
			//return $phoneNumberPrefix . self::decodeId($phoneNumberSuffix);
			$decode = self::decodeId($phoneNumberSuffix);
			$len = 9 - strlen($decode);
			return $phoneNumberPrefix . str_repeat('0', $len) . $decode;
		}
		return $hash;
	}

	public static function getServerIp() {
		$ips = array();
		exec("/sbin/ifconfig -a|grep inet|grep -v 127.0.0.1|grep -v inet6|awk '{print $2}'|tr -d \"addr:\"", $ips);

		return empty($ips) ? '' : implode(',', $ips);
	}

	/**
	 * 对给定的二维数组按照某一键值进行排序
	 *
	 * @param unknown_type $arr
	 * @param unknown_type $key
	 * @param unknown_type $type
	 * @return unknown
	 */
	public static function arrayMultiSort($arr, $key, $type = 'asc') {

		$sort_array = array();
		foreach ($arr as $k => $v) {
			$sort_array[] = $v[$key];
		}
		if ($type == 'asc') {
			array_multisort($sort_array, SORT_ASC, $arr);
		} else {
			array_multisort($sort_array, SORT_DESC, $arr);
		}

		return $arr;
	}

	/**
	 * 保存数据文件
	 * @param $cron_no
	 * @param string $msg
	 * @param string $fileName
	 * @param string $filePath
	 * @param int $flags FILE_APPEND or FILE_NO_DEFAULT_CONTEXT
	 */
	public static function saveDateFile($cron_no, $msg = '', $fileName = '', $filePath = '', $flags = FILE_APPEND) {
		$dataFile = DATA_PATH . DIRECTORY_SEPARATOR;
		if (!empty($filePath) && is_dir($filePath)) {
			$dataFile = $filePath;
		}


		if (empty($fileName)) {
			$fileName = $cron_no . '_' . date('Y-m-d') . '.log';
		}

		$dataFile .= $fileName;

		file_put_contents($dataFile, $msg, $flags);

		return $dataFile;
	}

	/**
	 * 创建文件目录
	 * @param $basePath
	 * @param $path
	 */
	public static function createPath($basePath, $path) {
		if (!file_exists($basePath . $path)) {
			mkdir($basePath . $path, 0777);
			chmod($basePath . $path, 0777);
		}

		return $basePath . $path;
	}

	/**
	 * 获取字符串的crc32值
	 * @param string $str
	 * @return number 返回正整数
	 */
	public static function getCRC32($str) {
		return sprintf("%u", crc32($str));
	}

	/**
	 * 将一个数组n等分
	 * @param type $data 数组
	 * @param type $n 多少分
	 */
	public static function divideArray($data, $n) {
		$total = count($data);
		if (is_array($data) && $total > $n) {
			$res = Array();
			$index = Array();
			$p = round($total / (1.0 * $n), 2);
			$temp = explode('.', $p);
			$left = end($temp);
			if ((int) $left > 50) {
				$p = (int) $temp[0] + 0.4;
			}
			$start = 0;
			for ($i = 1; $i < $n; $i++) {
				$res[] = $data[$start];
				$index[] = $start;
				$start = ceil($i * $p);
			}
			$res[] = $data[$total - 1];
			$index[] = $total - 1;
			return $res;
		} else {
			return $data;
		}
	}

	/**
	 * 输出CSV表格样式
	 */
	public static function outputCSVTable($table_head, $data) {
		$html = "";
		foreach ($table_head as $item) {
			$html = $html . "\t" . $item;
		}

		foreach ($data as $row => $value) {
			$html = $html . "\n";
			foreach ($value as $item) {
				$html = $html . "\t" . $item;
			}
		}
		return $html;
	}

	/**
	 * 输出html表格样式
	 */
	public static function outputHtmlTable($table_head, $data) {

		$html = "<!DOCTYPE HTML><html><head><meta charset='utf8'></head><body><table border='1'>";
		$html = $html . "<thead><tr>";
		foreach ($table_head as $item) {
			$html = $html . "<th>$item</th>";
		}
		$html = $html . "</tr></thead><tbody>";

		foreach ($data as $row => $value) {
			$html = $html . "<tr>";
			foreach ($value as $item) {
				$html = $html . "<td>" . $item . "</td>";
			}
			$html = $html . "</tr>";
		}
		$html = $html . "</tbody></table></body></html>";
		return $html;
	}

	/**
	 * 处理说说内容中含有股票(A股)名或代码的信息，并转换为 $股票名(股票代码)$ 格式
	 * @param  string  $content 待处理的内容
	 * @param  boolean $cached  true:使用缓存缓存所有股票信息
	 * @return string           处理后的内容
	 */
	public static $special_content_stock_replace = array(
		'841529754' => array(// 交易时间 A股
			array('pattern' => '@(?<![a-z])000001@u', 'replace' => 'sz000001'),
			array('pattern' => '@大盘@u', 'replace' => '上证指数'),
			array('pattern' => '@沪指@u', 'replace' => '上证指数'),
			array('pattern' => '@创业板(?![指])@u', 'replace' => '创业板指'),
			array('pattern' => '@深成指@u', 'replace' => '深证成指'),
		),
		'1160743180' => array(// 交易时间 金银油
			array('pattern' => '@(?<![a-z])000001@u', 'replace' => 'sz000001'),
			array('pattern' => '@大盘@u', 'replace' => '上证指数'),
			array('pattern' => '@沪指@u', 'replace' => '上证指数'),
			array('pattern' => '@创业板(?![指])@u', 'replace' => '创业板指'),
			array('pattern' => '@深成指@u', 'replace' => '深证成指'),
		),
	);
	public static $content_stock_replace_type = array(
		'841529754' => array('stock_cn'), // 交易时间 A股
		'1160743180' => array('stock_cn'), // 交易时间 金银油
		'635961577' => array(), // 交易时间 美股
		'1391407231' => array(), // 交易时间 港股
		'3420873157' => array(), // 交易时间 期货
	);

	public static function formatConetentStock($content, $cached = false, $crc32_id = "841529754") {
		$crc32_id = isset(self::$content_stock_replace_type["$crc32_id"]) ? $crc32_id : "841529754";
		$content = trim($content);
		if (!empty($content)) {
			$redis_key = MEM_PRE_KEY . "balacmn_content_replacemap_{$crc32_id}_stock";
			$symbols = Yii::app()->redis_r->get($redis_key);
			if (!$cached || empty($symbols)) {
				$stock_types = self::$content_stock_replace_type["$crc32_id"];
				if (!empty($stock_types)) {
					$sql = "select type,symbol,name,code from lcs_symbol where type in ('" . implode("','", $stock_types) . "');";
					$symbols = Yii::app()->lcs_r->CreateCommand($sql)->queryAll();
				}
				if (empty($symbols)) {
					$symbols = array();
				}

				if ($cached) {
					Yii::app()->redis_w->setex($redis_key, 86400, json_encode($symbols));
				}
			} elseif (!empty($symbols)) {
				$symbols = json_decode($symbols, true);
			} else {
				$symbols = array();
			}

			// 特殊处理部分
			$special_replace = self::$special_content_stock_replace["$crc32_id"];
			if (!empty($special_replace)) {
				foreach ($special_replace as $scsr) {
					$result = preg_replace($scsr['pattern'], $scsr['replace'], $content);
					if (!empty($result))
						$content = $result;
				}
			}

			foreach ($symbols as $ss) {
				$t_name = str_replace('*', '\*', $ss['name']);

				$pattern = "@(?<![$]){$t_name}[（( 　]*({$ss['symbol']}|{$ss['code']})[　 )）]*|({$ss['symbol']}|{$ss['code']})[（( 　]*({$t_name})[　 )）]*(?![$])@u";
				$replace = " \${$ss['name']}({$ss['symbol']})\$ ";
				$result = preg_replace($pattern, $replace, $content);
				if (!empty($result))
					$content = $result;

				$pattern = "@(?<![$]){$t_name}(?![(])@u";
				$replace = " \${$ss['name']}({$ss['symbol']})\$ ";
				$result = preg_replace($pattern, $replace, $content);
				if (!empty($result))
					$content = $result;

				$pattern = "@(?<![(=]){$ss['symbol']}(?![&)])|(?<![(a-zA-Z0-9=]){$ss['code']}(?![0-9&)])@u";
				$replace = " \${$ss['name']}({$ss['symbol']})\$ ";
				$result = preg_replace($pattern, $replace, $content);
				if (!empty($result))
					$content = $result;
			}
		}
		return $content;
	}

	/*
	 * 过滤Ａ标签
	 */

	public static function filterAlink($content) {
		try {
			$apattern = "@<a.*/a>@u";
			$result = preg_match($apattern, $content, $matchs);
			if (!empty($result)) {
				$pattern = "@>.*<@u";
				$url = preg_match($pattern, $matchs[0], $new_match);
				if ($url) {
					$url = $new_match[0];
					$res = substr($url, 1, strlen($url) - 2);
					if ($res) {
						$result = preg_replace($apattern, $res, $content);
						if (!empty($result)) {
							$content = $result;
						}
					}
				}
			}
		} catch (Exception $e) {

		}
		return $content;
	}

	/**
	 * 输出excel文件
	 *
	 */
	public static function outputExcelTable($table_head, $info) {
		try {
			$today = date("Ymd");
			$objxls = new PHPExcel();
			$objxls->setActiveSheetIndex(0);
			$active = $objxls->getActiveSheet();
			$col_symbol = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

			$index = 0;
			foreach ($table_head as $item) {
				$temp = "";
				$pos = CommonUtils::getExcelCol($index);
				$active->setCellValue($pos . '1', $item);
				$index = $index + 1;
			}
			$index = 2;
			foreach ($info as $item) {
				$p = 0;
				foreach ($item as $value) {
					$pos = CommonUtils::getExcelCol($p);
					$active->setCellValue($pos . $index, $value);
					$p = $p + 1;
				}
				$index = $index + 1;
			}
			#header('Content-Type : application/vnd.ms-excel');
			#header('Content-Disposition:attachment;filename="'.$filename.'"');
			$objWriter = PHPExcel_IOFactory::createWriter($objxls, 'Excel5');
			$file_name = DATA_PATH . '/' . $today . '.xlsx';
			$objWriter->save($file_name);
			return $file_name;
		} catch (Exception $e) {
			var_dump($e->getMessage());
			throw LcsException::errorHandlerOfException($e);
		}
	}

	/**
	 * 获取表行
	 */
	public static function getExcelCol($pos) {
		$col_symbol = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
		$result = $col_symbol;
		$index = 0;
		while ($index < 26) {
			foreach ($col_symbol as $item) {
				$temp = $col_symbol[$index] . $item;
				$result[] = $temp;
			}
			$index = $index + 1;
		}
		return $result[$pos];
	}

	/**
	 * 记录、获取脚本最后运行时间
	 * @param type $key
	 * @param type $timeZone
	 */
	public static function lastRuntime($key, $timeZone = null) {
		if (is_null($timeZone)) {
			return Yii::app()->redis_r->get($key);
		} else {
			return Yii::app()->redis_w->set($key, $timeZone);
		}
	}

	/**
	 * 操作es数据
	 * @param  $index_name 索引名字
	 * @param  $data json字符串
	 */
	public static function esdatabulk($index_name,$type_name, $data) {
        var_dump($data);
		$obj= new Common();
		$url = $obj->url;
		$url.=$index_name.'/' . $type_name."/_bulk";
		$header['content-type']="application/json; charset=UTF-8";
		Yii::app()->curl->setHeaders($header);
        var_dump($url);
		if(defined('ENV') && ENV == 'dev'){
           $res=Yii::app()->curl->setTimeOut(10)->post($url,$data."\n");
            var_dump($res);
        }else{
           $res=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->post($url,$data."\n");
        }
		var_dump($res);
	}

	/**
	 * 操作es数据
	 * @param  $index_name 索引名字
	 * @param  $data json字符串
	 */
	public static function esdata($index_name,$type_name, $data) {
        var_dump($data);
		$obj= new Common();
		$url = $obj->url;
		$url.=$index_name.'/' . $type_name;
		$header['content-type']="application/json; charset=UTF-8";
		Yii::app()->curl->setHeaders($header);
		if(defined('ENV') && ENV == 'dev'){
           $res=Yii::app()->curl->post($url,$data."\n");
        }else{
           $res=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->post($url,$data."\n");
        }
		var_dump($res);
	}

	/**
	 * 更新es数据
	 * @param  $index_name 索引名字
	 * @param  $data json字符串
	 */
	public static function updateEsData($index_name,$type_name, $data) {
		$obj= new Common();
		$url = $obj->url;
		$url.=$index_name.'/'.$type_name.'/_bulk';
		$header['content-type']="application/json; charset=UTF-8";
		Yii::app()->curl->setHeaders($header);
		if(defined('ENV') && ENV == 'dev'){
           $res=Yii::app()->curl->post($url,$data."\n");
        }else{
           $res=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->post($url,$data."\n");
        }
        print_r($res);
		echo "\n";
	}
	//创建索引
    public function initEs($url,$data=''){
        $header['content-type']="application/json; charset=UTF-8";
        Yii::app()->curl->setHeaders($header);
        if(defined('ENV') && ENV == 'dev'){
            $res=Yii::app()->curl->put($url,$data);
        }else{
            $res=Yii::app()->curl->setOption(CURLOPT_USERPWD,"elastic:h*!ZN5dL_VP#7niL15Q5")->setOption( CURLOPT_HTTPAUTH,CURLAUTH_BASIC)->put($url,$data);
        }
        var_dump($res);
    }
}
