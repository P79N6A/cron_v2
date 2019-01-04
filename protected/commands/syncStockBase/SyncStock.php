<?php

/**
 * Description of SyncStock
 *
 * @author Administrator
 */
class SyncStock
{

	const CRON_NO = 13001;

	private static function tableName()
	{
		return 'lcs_stock_base';
	}

	/**
	 * 全量
	 */
	public static function run()
	{

		$version = self::version();
		SyncStock::writeLog('runExt start');
		self::runExt($version ?: '20170929,2017-09-29 00:00:00'); //增量接口必须给定版本号
		SyncStock::writeLog('runExt end');

		//get Data
		SyncStock::writeLog('getdata_start');
		$R = self::getData($version);
		SyncStock::writeLog('getdata_end');

		if ($R['errorCode'] != 0) {
			self::writeLog($R['errorCode'] . ' : ' . $R['errorMsg']);
			self::writeLog("end\r\n");
			return;
		}

		if (empty($R['data'])) {
			self::writeLog('empty data');
			return;
		}

		//记录版本号
		self::version($R['Ver']);

		//开启事物
		$transaction = Yii::app()->lcs_w->beginTransaction();
		try {
			$sql_delete = 'DELETE FROM ' . self::tableName() . ' WHERE 1 ';
			if (Yii::app()->lcs_w->createCommand($sql_delete)->execute() == false) {
				$transaction->rollBack();
				self::writeLog('delete sql Error');
				return false;
			}

			//sql拼装
			$sql_insert = 'insert into ' . self::tableName() . ' (`id`,`Ei`,`code`,`symbol`,`name`,`pinyin`) values ';
			$sql_insert_val = [];
			$m = $n = $i = 0;
			$x = 100;
			foreach ($R['data'] as $v) {

				if ( ! isset($v['ExchID']) || ! isset($v['Inst']) || ! isset($v['SecNm']) || ! isset($v['Py']) || ! isset($v['Ei']))
					continue;

				$symbol = $v['ExchID'] . $v['Inst'];
				Yii::app()->redis_w->delete(MEM_PRE_KEY . 'cache_stock_name_' . $symbol);

				//把股票的名称加入到缓存中
				$json = json_encode(['Ei' => $v['Ei'], 'code' => $v['Inst'], 'symbol' => $symbol, 'name' => $v['SecNm'], 'pinyin' => strtoupper($v['Py'])]);
				Yii::app()->redis_w->setex(MEM_PRE_KEY . 'cache_stock_name_' . $symbol, 24 * 3600, $json);
				//
				//sql拼装
				$sql_insert_val[$m][$n] = '(' . ($i + 1) . ',' . $v['Ei'] . ',"' . $v['Inst'] . '","' . $symbol . '","' . $v['SecNm'] . '","' . strtoupper($v['Py']) . '")';

				$n ++;
				if ($n % $x == 0)
					$m ++;

				$i ++;
			}

			self::writeLog("获取到${i}条数据");

			//批量执行sql
			foreach ($sql_insert_val as $m => $v) {
				$sql_in = $sql_insert . implode(',', $v);
				self::writeLog("Line ${m} execute.");
				if (Yii::app()->lcs_w->createCommand($sql_in)->execute() == false) {
					$transaction->rollBack();
					return false;
				}
			}
		} catch (Exception $e) {
			self::writeLog($e->getMessage());
			$transaction->rollBack();
			return false;
		}

		$transaction->commit();
		return true;
	}

	/**
	 * 增量
	 * 对增量变更中的股票的所在的分组的版本号自增
	 */
	private static function runExt($version = '')
	{
		$R = self::getExtData($version);
		if ( ! is_array($R) || empty($R) || $R['errorCode'] != 0 || ! isset($R['data'])) {
			self::writeLog('调用增量接口失败');
			return;
		}

		$changed_symbols = [];
		foreach ($R['data'] ?: [] as $v) {
			//只操作A股
			if ($v['Type'] != 'A')
				continue;
			$Ei = $v['Ei'];
			$symbol_old = self::getSymbolByEi($Ei);
			if ($symbol_old)
				$changed_symbols[$symbol_old] = $symbol_old;
			$symbol = $v['ExchID'] . $v['Inst'];
			$changed_symbols[$symbol] = $symbol;
		}
		if (empty($changed_symbols)) {
			self::writeLog('A股增量数据为空');
			return;
		}

		$symbols = '"' . implode('","', $changed_symbols) . '"';
		self::writeLog('A股变更的股票：(' . $symbols . ')');
		$sql = 'UPDATE `lcs_user_stock_group` SET `version`=`version`+1 WHERE id IN( SELECT gid AS id FROM `lcs_user_stock` WHERE `symbol` in (' . $symbols . '))';
		self::writeLog($sql);
		$num = Yii::app()->lcs_w->createCommand($sql)->execute();
		self::writeLog($num . '条数据发生修改');
	}

	/**
	 * getSymbolByEi
	 * @param type $Ei
	 * @return type
	 */
	private static function getSymbolByEi($Ei)
	{
		return Yii::app()->lcs_w->createCommand('select symbol from `lcs_stock_base` where Ei=' . $Ei . ' limit 1')->queryScalar();
	}

	/**
	 * 调用接口，获取数据
	 * @param type $version
	 * @return type
	 */
	private static function getData($version = '')
	{
		$url = 'http://stockhq.caixun99.com/stockbase/queryInstCode';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['Ver' => $version]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$cons = curl_exec($ch);
		$data = json_decode($cons, true);
		curl_close($ch);
		return $data;
	}

	/**
	 * 调用接口，获取数据
	 * @param type $version
	 * @return type
	 */
	private static function getExtData($version = '')
	{
		$url = 'http://stockhq.caixun99.com/stockbase/queryAdditionalInstCode';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['Ver' => $version]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$cons = curl_exec($ch);
		$data = json_decode($cons, true);
		curl_close($ch);
		return $data;
	}

	/**
	 * 获取或设置接口的版本号
	 * @param type $val
	 * @return type
	 */
	private static function version($val = null)
	{
		if (is_null($val))
			return Yii::app()->redis_w->get(MEM_PRE_KEY . 'stockbase_version') ?: '';
		else
			return Yii::app()->redis_w->set(MEM_PRE_KEY . 'stockbase_version', $val);
	}

	/**
	 * write
	 * @param type $msg
	 */
	public static function writeLog($msg, $type = 'INFO')
	{
		echo $massage = '[' . date('Y-m-d H:i:s') . '][' . $type . '] ' . $msg . "\n";
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
	public static function mycurl($url = '', $data_type = 'json', $method = 'GET', $post_data = array(), $header = array())
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
		if ($method != 'GET')
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		if ( ! empty($header))
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		if ( ! empty($post_data)) {
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
