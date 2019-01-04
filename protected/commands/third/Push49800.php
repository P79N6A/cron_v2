<?php
/**
 * 推送49800套餐的计划订阅信息
 */
class Push49800
{

    //任务代码
    const CRON_NO = '20180823';

    /**
     * 具体的处理逻辑
     */
    public function process(){
    
        $plan_ids = array('46559','47020');
        foreach($plan_ids as $pln_id){
            $key = MEM_PRE_KEY."course_49800push_plan_".$pln_id;
            $last_id = Yii::app()->redis_r->get($key);
            $last_id = empty($last_id)?1:intval($last_id);
            $today = date("Y-m-d");
            $tran_list = PlanTransactions::model()->getPlanTransactionsByPlnID($pln_id,"id,pln_id,symbol,ind_id,type,deal_price,deal_amount,hold_avg_cost,status,profit,transaction_cost,wgt_before,wgt_after,reason,c_time",$today,"","asc");

            ///处理第一次为空的情况，不退送过时的消息
            if(empty($last_id)){
                foreach($tran_list as $item){
                    if($item['id']>$last_id){
                        $last_id = $item['id'];
                    }
                }
                $last_id = empty($last_id)?1:$last_id;
                Yii::app()->redis_w->set($key,$last_id);
                continue; 
            }

            if(!empty($tran_list)){
                $uids = $this->getPlanSubUid($pln_id);
                if(count($uids)>0){
                    $user_info_list = User::model()->getUserPhone($uids);
                    if(count($user_info_list)>0){
                        $phone_arrays = array();
                        foreach($user_info_list as $user){
                            $phone_arrays[] = CommonUtils::decodePhoneNumber($user['phone']);
                        }
                        echo "last_id:".$last_id;
                        foreach($tran_list as $item){
                            if($item['id']>$last_id){
                                $last_id = $item['id'];
                                Yii::app()->redis_w->set($key,$last_id);
                                $symbol_info = Symbol::model()->getTagsBySymbol("stock_cn",$item['symbol']);
                                if(isset($symbol_info[$item['symbol']])){
                                    $symbol_name = $symbol_info[$item['symbol']]['name']."(".$item['symbol'].")";
                                }else{
                                    Common::model()->saveLog("推送49800错误,没有找到股票名称,symbol:".$item['symbol'],"error","push49800");
                                    continue;
                                }
                                //$weight_str = ($item['wgt_before']*100)."%到".($item['wgt_after']*100)."%";
                                $position = 0.00;
                                $buy_avg_cost = 0;
                                $amount =0;
                                //100*((买入均价*持有量)/可用资金)
                                $secArr = PlanAsset::model()->getAssetsByPlnId( $pln_id );
                                if (!empty($secArr)){
                                    foreach ($secArr as $v){
                                        if ($v['symbol'] == $item['symbol']){
                                            $buy_avg_cost = $v['buy_avg_cost'];
                                            $amount = $v['amount'];
                                        }
                                    }
                                }
                                $planDetailArr = Plan::model()->getPlanDetail($pln_id);
                                if (!empty($planDetailArr)){
                                    $position = sprintf("%01.2f",100*round((($buy_avg_cost*$amount)/$planDetailArr['available_value']),4));
                                }
                                $weight = $item['wgt_before'] - $item['wgt_after'];
                                $weight = $weight>0?"减少".(sprintf("%01.2f",$weight*100))."%（个股仓位变为 $position%)":"增加".(sprintf("%01.2f",-1*$weight*100))."%（个股仓位变为 $position%)";

                                $phone_tmp = array();
                                foreach($phone_arrays as $phone){
                                    $phone_tmp[] = $phone;
                                    if(count($phone_tmp)==200){
                                        $this->push($pln_id,$phone_tmp,$symbol_name,$item['type'],$item['c_time'],$item['deal_price'],$weight,$item['reason']);
                                        $phone_tmp = array();
                                    }
                                }

                                if(count($phone_tmp)>0){
                                    $this->push($pln_id,$phone_tmp,$symbol_name,$item['type'],$item['c_time'],$item['deal_price'],$weight,$item['reason']);
                                    $phone_tmp = array();
                                }
                            }
                        }
                    }
                }
            }

        }
    }

    public function push($pln_id,$phones,$symbol_name,$type,$deal_time,$deal_price,$weight,$reason){
        $data = array();
        $type = $type==1?"买入":"卖出";
        foreach($phones as $item){
            $data[] = array("phone"=>$item,"name"=>$symbol_name,"type"=>$type,"time"=>$deal_time,"price"=>$deal_price,"rate"=>$weight,"summary"=>$reason,"url"=>"http://licaishi.sina.com.cn/wap/planBrief?pln_id=".$pln_id);
        }
        Common::model()->saveLog("push 49800".json_encode($data),"info","push49800");
var_dump($data);
        ThirdCallService::push49800($data);
    }

    /**
     * 获取订阅计划的用户uid
     */
    public function getPlanSubUid($pln_id){
        $user_list = PlanSubscription::model()->getPlanSubList($pln_id);
        $uids = array();
        if($user_list){
            $now = date("Y-m-d H:i:s");
            foreach($user_list as $user){
                if($user['status'] == 1 || $user['expire_time']>$now){
                    $uids[] = $user['uid'];
                }
            }
        }
        return $uids;
    }
}

