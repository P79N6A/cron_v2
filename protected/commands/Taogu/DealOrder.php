<?php
/**
 * 处理委托订单
 */

class DealOrder
{

    //任务代码
    const CRON_NO_DEAL='20180806';


    /**
     * 检查log_dir文件中记录的进程是否在运行
     * @param $log_dir
     * @return int
     */
    public function checkPidRunning($log_dir){
        if(file_exists($log_dir)){
            $pid = file_get_contents($log_dir);
            if(isset($pid)){
                $cmd = "ps axu|grep \"".$pid."\"|grep -v \"grep\"|wc -l";
                $ret = shell_exec($cmd);
                if($ret == 0){
                    @unlink($log_dir);
                }else{
                    echo "The process is running!\n";
                    return 1;
                }
            }else{
                @unlink($log_dir);
            }
        }
        $pid = getmypid();
        file_put_contents($log_dir,$pid);
    }

    /**
     * 向推送队列push数据
     * @param $s
     */
    public function pushQueue($s){
        $planner = Planner::model()->getPlannerById($s['p_uid']);
//        $symbol_name = Stock::model()->getStockName($s['symbol']);
        $symbol_name = Symbol::model()->getTagsBySymbol('stock_cn',(array)$s['symbol']);
        $symbol_name = $symbol_name[$s['symbol']];
        $action = $s['status'] == 1 ? '成功调入' : '成功调出';
        $message = $action.'：'.$planner[$s['p_uid']]['name'].$action.$symbol_name['name'].' '.$s['symbol'].'，点击查看详情。';
        $key = 'lcs_common_message_queue';
        $body = array('type'=>'TaoGuStrategy','p_uid'=>$s['p_uid'],'message'=>$message);
        $body1 = array('type'=>'TaoGuWeiXin','p_uid'=>$s['p_uid'],'message'=>array('info'=>$planner[$s['p_uid']]['name'].'淘股策略更新','title'=>'思路：'.$s['summary'],'name'=>$symbol_name['name'],'code'=>$s['symbol'],'type'=>$action,'price'=>strval(round($s['curr_price'],2)),'time'=>date('Y-m-d H:i:s')));
        Yii::app()->redis_w->rPush($key,json_encode($body));
        Yii::app()->redis_w->rPush($key,json_encode($body1));
    }

    /**
     * 更新数据
     * @param $s
     */
    public function updateData($s){
        //拿去当前价格和涨停，跌停状态，判断能否成交
        $symbol_info = TaoguService::getNewSymbolPrice($s['symbol']);
        if($symbol_info['status'] != '00'){
            return;
        }
        $high_price = TaoguService::getLimitHigh($symbol_info);
        $low_price = TaoguService::getLimitLow($symbol_info);
        if(( ($s['status'] == '2') && ($symbol_info['curr_price'] > $low_price)) || ( ($s['status'] == 1) && ($symbol_info['curr_price'] < $high_price))){
            $status = $s['status'] == 1 ? 0 : 1;
            $summary = Taogu::model()->getSummary($s['p_uid'],$s['symbol'],$status);
            $data = array('status' => $status,'symbol' => $s['symbol'],'deal_price' => $symbol_info['curr_price'],'rate' => $s['deal_price'] == 0 ? 0 :($symbol_info['curr_price']-$s['deal_price'])/$s['deal_price'],'id'=>$s['id'],'p_uid'=>$s['p_uid'],'summary' => empty($summary)?($s['status'] == 1?'成功调入':'成功调出'):$summary);
            if(Taogu::model()->updateStatus($data)) {
                $s['curr_price'] = $symbol_info['curr_price'];
                $s['summary'] = $summary;
                $this->pushQueue($s);
            }
        }
    }

    /**
     * 处理委托交易
     */
    public function dealOrder(){
        $log_dir = dirname(__FILE__).'/../../../log/taogu_dealorder_pid';
        if($this->checkPidRunning($log_dir)){
            return;
        }
        $field = ['id','symbol','status','p_uid','deal_price'];
        $symbols = Taogu::model()->getSymbolByStatus(4,$field);
        if(empty($symbols)){
            @unlink($log_dir);
            return;
        }
        foreach ($symbols as $s){
            $this->updateData($s);
        }
        @unlink($log_dir);
    }
}