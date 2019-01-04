<?php
/**
 * 系统撤销订单
 */

class CancelOrder
{

    //任务代码
    const CRON_NO_CANCEL='20180803';


    /**
     * 清理委托数据并向推送队列push数据
     * @param $data
     */
    public function pushQueue($data){
        $summary = array();
        foreach ($data as $order){
            if(Taogu::model()->cancelOrder($order)){
                $planner = Planner::model()->getPlannerById($order[0]['p_uid']);
                foreach ($order as $symbol){
//                    $symbol_name = Stock::model()->getStockName($symbol['symbol']);
                    $symbol_name = Symbol::model()->getTagsBySymbol('stock_cn',(array)$symbol['symbol']);
                    $symbol_name = $symbol_name[$symbol['symbol']];
                    $summary[] = $symbol_name['name'].' '.$symbol['symbol'];
                    $code[] = $symbol['symbol'];
                    $name[] = $symbol_name['name'];
                }
                $message = '自动撤单：'.$planner[$order[0]['p_uid']]['name'].'委托长时间未成交，自动撤单'.implode('，',$summary);
                $key = 'lcs_common_message_queue';
                $body = [
                    'type'=>'TaoGuStrategy',
                    'p_uid'=>$order[0]['p_uid'],
                    'message'=>$message,
                ];
                $body1 = [
                    'type'=>'TaoGuWeiXin',
                    'p_uid'=>$order[0]['p_uid'],
                    'message'=>[
                        'info'=>$planner[$order[0]['p_uid']]['name'].'淘股策略更新',
                        'title'=>'',
                        'name'=>implode('、',$name),
                        'code'=>implode('、',$code),
                        'type'=>'系统撤单',
                        'price'=>'--',
                        'time'=>date('Y-m-d H:i:s')
                    ]
                ];
                Yii::app()->redis_w->rPush($key,json_encode($body));
                Yii::app()->redis_w->rPush($key,json_encode($body1));
            }
        }
    }

    /**
     * 撤销委托订单
     */
    public function cancelOrders(){
        //1.查出当前委托的理财师id
        //2.按照id取出委托列表
        //3.清除调入委托,或恢复调出委托为成交状态。
        //4.记录调仓记录
        $field = array('id','p_uid','symbol','status');
        $p_uids = Taogu::model()->getSymbolByStatus(4,$field);

        if(empty($p_uids)){
            return;
        }

        foreach ($p_uids as $item){
            $data[$item['p_uid']][] = $item;
        }

        $this->pushQueue($data);
    }

}