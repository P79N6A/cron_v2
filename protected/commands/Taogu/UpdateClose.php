<?php
/**
 * 更新收盘价
 */

class UpdateClose
{

    //任务代码
    const CRON_NO_CLOSE='20180809';


    /**
     * 批量获取股票代码，批量更新股票价格
     */
    public static function ModifyClosePrice(){
        $field = ['symbol'];
        $p=1;
        $num = 100;
        while(($symbols = Taogu::model()->getSymbolList(3,$field,$p,$num))){
            $symbols = array_flip($symbols);
            $symbols = array_flip($symbols);
            $now = date('Y-m-d H:i:s');
            $symbol_info = TaoguService::getBetchSymbolPrice($symbols);
            foreach ($symbol_info as $item){
                $symbol_str[] = "('{$item['symbol']}',{$item['curr_price']},'{$now}','{$now}')";
            }
            Taogu::model()->updateSymblClose($symbol_str);
            $p++;
        }
    }
}