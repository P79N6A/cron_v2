<?php
/**
 * 
 */
class StocksBS
{

	//任务代码
	const CRON_NO='2018121101';
	/**
	 * 更新数据
	 */
	public function SaveStocksBS(){
		$stocks = array();
		$stocks=Yii::app()->tzy_r->createCommand('SELECT distinct STOCK_CODE from STOCK_INDICATOR_KLINE_SMART_DAY')->queryAll();
		if(!empty($stocks)){
			foreach($stocks as $v){
				$bs = $this->GetBS($v['STOCK_CODE']);
				$res_redis = $this->SetRedis($v['STOCK_CODE'], $bs);
			}
		}
	}

	private function GetBS($code){
		$res=Yii::app()->tzy_r->createCommand("SELECT TRADE_DATE as dt,VALUE as val from STOCK_INDICATOR_KLINE_SMART_DAY where STOCK_CODE='".$code."'"." order by TRADE_DATE asc")->queryAll();
		$result = '';	
		if(!empty($res)){
			$result = '';
			$result .= $this->FormatCode($code);
			foreach($res as $info){
				$tmp_str = $info['dt'].','.$info['val'];
				$result .= '|'.$tmp_str;
			}			
		}
		return $result;
		//return empty($res) ? array() : array('code'=>$this->FormatCode($code),'data'=>$res); 
	}
	
	private function SetRedis($code,$value){
		$key = 'lcs_stock_bs_'.$this->FormatCode($code);
		return Yii::app()->redis_w->set($key, $value);
	}
	
        private function FormatCode($code){
		$res = explode('.', $code);
		return $res[1].$res[0];
	}	
}
