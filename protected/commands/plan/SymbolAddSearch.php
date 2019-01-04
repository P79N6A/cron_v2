<?php

/**
 * 更新计划的一些状态
 */
class  SymbolAddSearch{
	
	const CRON_NO = 5008; //任务代码
	
	
	public function __construct() {
	}
	
	/**
	 * 把计划的交易数据加入到搜索里面
	 *
	 */
	public function addSearch(){
		
		$_cur_time = date("Y-m-d H:i:s");

        $redis_key = "last_update_plan_search_time";
        $_last_time = Yii::app()->redis_r->get($redis_key);

        if($_last_time === false){
            $_last_time = date("Y-m-d H:i:s",time()-300);
        }
        Yii::app()->redis_w->set($redis_key,$_cur_time);
        
        $sql = "SELECT DISTINCT symbol,pln_id FROM  lcs_plan_transactions where type=2 and c_time>'".$_last_time."' and c_time<='".$_cur_time."'";
        $record_result = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        
        if(!empty($record_result))
		{
			foreach ($record_result as $v)
			{
                //记录是否已存在
                $isset = Yii::app()->lcs_r->createCommand("select count(pln_id) from lcs_plan_search where pln_id=".$v['pln_id']." and sec_symbol='".$v['symbol']."'")->queryScalar();
                if($isset==0){
                    Yii::app()->lcs_w->createCommand()->insert("lcs_plan_search",array(
                        "pln_id" => $v['pln_id'],
                        "sec_symbol" => $v['symbol'],
                        "c_time" => $_cur_time
                    ));

                }
			}
		}
        
	}
	
}