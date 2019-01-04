<?php

/**
 * Desc  :普通牛币充值 和 ios充牛币对账
 * Author: meixin
 * Date  : 2015-9-7 16:49:52
 */
class SumAccount {
    const CRON_NO = 8001; //任务代码

	private $check_type = array('balance' ,  'balance_ios');
	private $recharge_type = array('balance'=>'1,2,8,9' , 'balance_ios'=>'4,5');
	private $cost_type = array('balance' => '3,15' , 'balance_ios' => 6); 

    public function __construct(){
    }
    /*
     * 用户剩余牛币 = 牛币充值 - 牛币消费
     */
    public function check() {
        $start = date('Y-m-d H:i:s');
		$redis_key = "lcs_check_account_balance";

		//初始化上一次check的牛币数
		$account = array(
                    'balance'=>array(
                        'check_time' => '' , 'balance' => '0'),
                    'balance_ios'=>array(
                        'check_time' => '' , 'balance_ios' => '0'),
                    );
        
		$beforeAccount = Yii::app()->redis_r->get($redis_key);
		$log_str = "last-day:\n".$beforeAccount."\n";
		$datafile = CommonUtils::saveDateFile(self::CRON_NO, $log_str);
		$beforeAccount = '';
		if(!empty($beforeAccount)){
			$account = json_decode($beforeAccount , true);
		}
        $alert = false;
        $todayAccount = array();
        foreach($this->check_type as $type){
            $todayAccount[$type]['sumRecharge'] = Account::model()->getSumRecharge($this->recharge_type[$type],$account[$type]['check_time']);
            $todayAccount[$type]['sumCost'] = Account::model()->getSumCost($this->cost_type[$type],$account[$type]['check_time']);
            $todayAccount[$type]['check_balance'] = $account[$type][$type] + $todayAccount[$type]['sumRecharge'] - $todayAccount[$type]['sumCost'];
            $sumRefund = 0;
            if(!empty($account[$type]['check_time'])) {
                $todayAccount[$type]['sumRefund'] = Account::model()->getSumRefund($this->cost_type[$type],$account[$type]['check_time']);
                $todayAccount[$type]['check_balance'] += $todayAccount[$type]['sumRefund'];
            }
            $todayAccount[$type][$type] = Account::model()->getSumBalance($type);
            $todayAccount[$type]['check_time'] = $start;
            $todayAccount[$type]['diff'] = $todayAccount[$type][$type]- $todayAccount[$type]['check_balance'];
            if(0 !== (int)$todayAccount[$type]['diff']){
                $alert=true;
            }
        }

        $diff = 0;
        foreach($this->check_type as $type){
            $diff = $diff + $todayAccount[$type]['diff'];
        }
        if($diff != 0){
            $alert = true;
        }else{
            $alert = false;
        }
        $todayAccount['total_diff'] = $diff;

        $json_info =  json_encode($todayAccount);
		$datafile = CommonUtils::saveDateFile(self::CRON_NO,"today:\n". var_export($json_info,true)."\n");

		if($alert){

			if(!empty($beforeAccount)){
				$res = Yii::app()->redis_w->delete($redis_key);
			}
			$title = "牛币对账业务";
			$mailRes = new NewSendMail($title, $json_info, Account::$toMailer); 	

		}else{
			Yii::app()->redis_w->set($redis_key , $json_info);
		}

		return $json_info;
        
    }
    
    
}
