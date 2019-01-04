<?php

/**
 * Description of ImportSymbol
 * @datetime 2015-11-5  13:40:53
 * @author hailin3
 */
class ImportSymbol {

	const CRON_NO = 8102;

	public $symbol_url = array(
		'stock_cn' => 'http://biz.finance.sina.com.cn/suggest/lookup_suggest.php?type=cn&category=a_stock',
		'stock_hk' => 'http://biz.finance.sina.com.cn/suggest/lookup_suggest.php?type=hk&category=hk_stock',
		'fund_open' => 'http://biz.finance.sina.com.cn/suggest/lookup_suggest.php?type=fund&category=open_fund',
		'fund_close' => 'http://biz.finance.sina.com.cn/suggest/lookup_suggest.php?type=fund&category=close_fund',
		'fund_etf' => 'http://biz.finance.sina.com.cn/suggest/lookup_suggest.php?type=fund&category=etf_fund',
		'fund_money' => 'http://biz.finance.sina.com.cn/suggest/lookup_suggest.php?type=fund&category=money_fund',
		'fund_qdii' => 'http://biz.finance.sina.com.cn/suggest/lookup_suggest.php?type=fund&category=qdii_fund',
		'fund_lof' => 'http://biz.finance.sina.com.cn/suggest/lookup_suggest.php?type=fund&category=lof_fund',
		'future_inner' => 'http://stock.finance.sina.com.cn/futures/view/viewInnerFuturesList.php',
		'future_global' => 'http://stock.finance.sina.com.cn/futures/view/viewGlobalFuturesList.php',
		'stock_us' => 'http://biz.finance.sina.com.cn/suggest/lookup_suggest.php?type=us&category=us_stock', #'http://202.108.6.137:8000/us_quote/us_suggest_file.txt'
	);

	/**
	 * 同步股票信息
	 * @param type $type
	 * @return string
	 * @throws type
	 */
	public function doSymbol($type) {
		try {
			echo "$type:\r\n";
			$url = isset($this->symbol_url[$type]) ? $this->symbol_url[$type] : '';
			if (empty($url)) {
				echo "\tbad params\r\n";
				return;
			}
			$res = self::mycurl($url, 'html', 'GET', null, ['Accept: application/xml']);
			if ($res == "") {
				echo "\t获取数据为空\r\n";
				return;
			}

			try {
				$xml = @simplexml_load_string($res);
			} catch (Exception $ex) {
				echo "[" . $res . "]\r\n";
				return;
			}

			$xml = json_decode(json_encode($xml));

			if (!empty($xml->item)) {
				Symbol::model()->delSymbol($type);
				//对内盘期货单独处理
				$values = '';
				$m = 1;
				$n = 100;
				if ($type == 'future_inner') {
					$has_array = array();
					foreach ($xml->item as $val) {
						$code = preg_replace("/\d{2,}$/", '', $val->code);
						$symbol = preg_replace("/\d{2,}$/", '', $val->symbol);
						$name = preg_replace("/\d{2,}$/", '', $val->name);
						$pinyin = preg_replace("/\d{2,}$/", '', $val->pinyin);
						if (!in_array($code, $has_array)) {
							$has_array[] = $code;
							$has_array[] = $code . '0';
							$search_content = $symbol . $name . $pinyin;
							$values .= "('$type','$code','$symbol','$name','$pinyin','$search_content'),";
							$m++;
							if ($m % $n == 0) {
								self::addSymbol($values);
							}
						}
					}
				} else {
					$all_exist = array();
					foreach ($xml->item as $val) {
						$jsonStr = json_encode($val);
						$jsonArray = json_decode($jsonStr, true);

						if (is_array($jsonArray['pinyin'])) {
							$val->pinyin = $jsonArray['pinyin'] = '';
						}
						if (isset($jsonArray['cname'])) {
							if (is_array($jsonArray['cname'])) {
								$val->cname = $jsonArray['cname'] = '';
							}
						}

						//美股单独处理
						if ('stock_us' == $type) {
							$val->code = $jsonArray['code'] = $jsonArray['symbol'];
							if ($val->cname != '') {
								$val->name = $jsonArray['name'] = $val->cname;
							}
						}

						if ('stock_hk' == $type) {
							$val->symbol = $jsonArray['symbol'] = 'hk' . $jsonArray['code'];
						}

						if ($jsonArray['symbol'] == '' && $jsonArray['name'] == '') {
							continue;
						}

						if (is_array($jsonArray['code']) || is_array($jsonArray['symbol']) || is_array($jsonArray['name']) || is_array($jsonArray['pinyin'])) {
							continue;
						}

						if (is_array($jsonArray['symbol'])) {
							$val->symbol = $jsonArray['symbol'] = $jsonArray['code'];
						}


						if (in_array($type . $jsonArray['symbol'], $all_exist)) {
							continue;
						} else {
							if (is_string($jsonArray['symbol'])) {
								$all_exist[$type . $jsonArray['symbol']] = $type . $jsonArray['symbol'];
							} else {
								continue;
							}
						}

						$search_content = $val->symbol . $val->name . $val->pinyin;
						if (isset($jsonArray['cname']) && !empty($jsonArray['cname'])) {
							$search_content .= is_string($jsonArray['cname']) ? $jsonArray['cname'] : '';
						}
						$values .= "('$type','" . addslashes($val->code) . "','" . addslashes($val->symbol) . "','" . addslashes($val->name) . "','" . addslashes($val->pinyin) . "','" . addslashes($search_content) . "'),";

						$m++;
						if ($m % $n == 0) {
							self::addSymbol($values);
						}
					}
				}
				self::addSymbol($values);
				echo "\t$type\tinsert num : " . ($m - 1) . " \r\n";
			} else {
				echo "\t$type\tempty object.\r\n";
			}
		} catch (Exception $e) {
			echo "[" . $e->getCode() . "] " . $e->getMessage() . " in file [" . $e->getFile() . "] at line " . $e->getLine() . " \r\n";
			throw LcsException::errorHandlerOfException($e);
		}
	}

	private static function addSymbol(&$values) {
		if ($values != '') {
			$values = substr($values, 0, -1);
			$res = Symbol::model()->addSymbol($values);
			$values = '';
		}
	}

	/**
	 * 同步问答股票信息
	 * @param string $type
	 * @return string
	 * @throws type
	 */
	public function doAskSymbol($type) {
		try {
			$url = isset($this->symbol_url[$type]) ? $this->symbol_url[$type] : '';
			if (empty($url)) {
				return 'bad params';
			}
			$res = file_get_contents($url);
			$xml = simplexml_load_string($res);
			//把基金和期货整理在一起
			if (in_array($type, array('fund_open', 'fund_close', 'fund_etf', 'fund_money', 'fund_qdii', 'fund_lof'))) {
				$type = 'fund';
			}
			if (!empty($xml->item)) {
				$res = Symbol::model()->getAskTagsList($type);
				$symbols = array();
				if (!empty($res)) {
					foreach ($res as $val) {
						$symbols[] = $val['symbol'];
					}
				}
				unset($res);
				$now = date('Y-m-d H:i:s');
				//对期货要做排重处理
				$has_array = array();
				$insertvalues = '';
				foreach ($xml->item as $val) {
					//对内盘期货单独处理不要合数期数
					if ($type == 'future_inner') {
						$code = preg_replace("/\d{2,}$/", '', $val->code) . '0';
						$symbol = preg_replace("/\d{2,}$/", '', $val->symbol) . '0';
						$name = preg_replace("/\d{2,}$/", '', $val->name);
						$pinyin = preg_replace("/\d{2,}$/", '', $val->pinyin);
						//对期货要做排重处理
						if (in_array($code, $has_array)) {
							continue;
						}
						$has_array[] = $code;
						$has_array[] = $code . '0';
					} else {
						$code = $val->code;
						if ($type == 'stock_hk') {
							$symbol = $code;
						} else {
							$symbol = $val->symbol;
						}
						if ($type == 'stock_us') {
							if (!preg_match("/([\x81-\xfe][\x40-\xfe])/", $val->cname, $match)) {
								continue;
							}
							$name = $val->cname;
						} else {
							$name = $val->name;
						}
						$pinyin = $val->pinyin;
					}
					if (!in_array($symbol, $symbols)) {
						$name = Yii::app()->lcs_w->getPdoInstance()->quote($name);
						$insertvalues .= "('$type','$code','$symbol',$name,'$pinyin','$now','$now'),";
					} else {
						$condition = "type='$type' and symbol='$symbol'";
						$u_data = array(
							'code' => $code,
							'symbol' => $symbol,
							'name' => $name,
							'pinyin' => $pinyin,
							'u_time' => $now
						);
						Symbol::model()->updateAskTags($condition, $u_data);
					}
				}
				if (!empty($insertvalues)) {
					Symbol::model()->insertAskTags(substr($insertvalues, 0, -1));
				}
			}
		} catch (Exception $e) {
			echo "[" . $e->getCode() . "] " . $e->getMessage() . " in file [" . $e->getFile() . "] at line " . $e->getLine() . " \r\n";
			throw LcsException::errorHandlerOfException($e);
		}
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
		curl_setopt($ch, CURLOPT_TIMEOUT, 300);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:59.0) Gecko/20100101 Firefox/59.0');
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

}
