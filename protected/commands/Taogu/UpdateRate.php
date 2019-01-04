<?php
/**
 * Created by PhpStorm.
 * User: pcy
 * Date: 18-11-27
 * Time: 下午4:10
 */

class UpdateRate{
    //任务代码
    const CRON_NUM='20181127';


    /**
     * 更新正在持仓的收益率
     */
    public  function updateTaoGuRate(){
        sleep(5);
        $symbol_list = Taogu::model()->getSymbol();
        echo '更新的股票列表如下：';
        var_dump($symbol_list);
        echo "\r\n";
        if($symbol_list){
            foreach ($symbol_list as $v){
                $symbolInfo=TaoguService::getNewSymbolPrice($v['symbol']);
                $curr_price=isset($symbolInfo['curr_price'])?$symbolInfo['curr_price']:0;
                $rate =round(($curr_price-$v['deal_price'])/$v['deal_price'],4);
                Taogu::model()->updateRecordRate($v['p_uid'],$v['symbol'],$rate);
                echo "更新".$v['p_uid']." 的股票:".$v['symbol'].",收益率为:$rate \r\n";
            }
        }

    }
}