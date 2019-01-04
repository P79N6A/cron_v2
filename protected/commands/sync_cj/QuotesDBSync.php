<?php
/**
 * quotesdb数据库同步任务 
 * Date:2014-11-17
 * songyao@staff.sina.com.cn
 * 每天收盘后运行一次
 */

class QuotesDBSync
{
	const CRON_NO = 8101; //任务代码
	private $_sync_symbols = "'sh000001','sh000300','sz399001','sz399006','sz399102','sz399106'";


	public function SyncDailyK()
	{
		try
		{
			$db_quotes_r = Yii::app()->quotes_r;
			$db_w = Yii::app()->lcs_w;

			//从quotes_db获取指数的daily数据
			$begin_date = date("Y-m-d", strtotime("-2 week"));
			$sql = "select * from cn_daily_10 where symbol in (".$this->_sync_symbols.") and day>'".$begin_date."' ";			
			$daily_k_1 = $db_quotes_r->createCommand($sql)->queryAll();
			$sql = "select * from cn_daily_9 where symbol in (".$this->_sync_symbols.") and day>'".$begin_date."' ";			
			$daily_k_2 = $db_quotes_r->createCommand($sql)->queryAll();
			$daily_k = array_merge($daily_k_1,$daily_k_2);
			//更新 licaishi.lcs_daily_k 表相应数据
			$sql_u = 'insert into lcs_daily_k (symbol, day, open, high, low ,close, volume, amount) VALUES ';
			if (is_array($daily_k))
			{
				foreach($daily_k as  $k=>$v)
				{
					$sql_u .= "('".$v['symbol']."','".$v['day']."',".$v['open'].",".$v['high'].",".$v['low'].",".$v['close'].",".$v['volume'].",".$v['amount']."),";				
				}
			}
			$sql_u = substr($sql_u, 0, -1);
			$sql_u .= " ON DUPLICATE KEY UPDATE `open`=VALUES(`open`),`high`=VALUES(`high`),`low`=VALUES(`low`),`close`=VALUES(`close`),`volume`=VALUES(`volume`),`amount`=VALUES(`amount`)";
			$result = $db_w->createCommand($sql_u)->execute();
			echo date("Y-m-d H:i:s").": ".$sql_u;
		}
		catch  (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}
	
	public function SyncTradeDay()
	{
		try
		{
			$db_quotes_r = Yii::app()->quotes_r;
			$db_w = Yii::app()->lcs_w;

			//从quotes_db获取指数的tradeday数据
			$begin_date = date("Y-01-01");
			$sql = "select day from cn_trade_day where day>'".$begin_date."'";
			$trade_days = $db_quotes_r->createCommand($sql)->queryAll();

			//更新 licaishi.lcs_calendar 表相应数据
			$sql_u = 'insert into lcs_calendar (`cal_date`) VALUES  ';
			foreach($trade_days as $v)
			{
				$sql_u .= "('".$v['day']."'),";
			}
			$sql_u = substr($sql_u, 0, -1);
			$sql_u .= " ON DUPLICATE KEY UPDATE `cal_date`=VALUES(`cal_date`)";
			$result = $db_w->createCommand($sql_u)->execute();
			echo date("Y-m-d H:i:s").": ".$sql_u;
		}
		catch  (Exception $e)
		{
			throw LcsException::errorHandlerOfException($e);
		}
	}


	
}
