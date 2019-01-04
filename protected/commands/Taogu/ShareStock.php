<?php
/**
 * 分红送股
 */

class ShareStock
{

    //任务代码
    const CRON_NO_SHARE='20180802';

    /**
     * 处理分红数据
     * @param $res
     * @return array
     */
    public function setFgpg($res){
        $fgpg_array = array();
        foreach ($res as $val) {
            $symbol = $val['symbol'];
            if (isset($fgpg_array["$symbol"])) {
                if (!empty($val['px'])) {
                    $fgpg_array["$symbol"]['px'] = $val['px'];
                    $fgpg_array["$symbol"]['shpx'] = $val['shpx'];
                } elseif (!empty($val['sg'])) {
                    $fgpg_array["$symbol"]['sg'] = $val['sg'];
                } elseif (!empty($val['zz'])) {
                    $fgpg_array["$symbol"]['zz'] = $val['zz'];
                }
            } else {
                $fgpg_array["$symbol"] = $val;
            }
        }
        return $fgpg_array;
    }

    /**
     *获取今日分红送股的股票信息
     */
    public static function getShareStock(){
        $date = date('y-m-d 00:00:00');
        $fgpg_array = array();
        //px税前红利 shpx 税后 sg 10送 zz 10增 cqcxr除权除息日
        $sql  = "select symbol,DISHTY3 as px,DISHTY4 as shpx,DISHTY7 as sg ,DISHTY8 as zz, DISHTY13 as cqcxr,DISHTY2 as type
					FROM DISHTY WHERE DISHTY13='$date' AND DISHTY2 IN ('111','151','181','211','251','281','311','351','381','411','451','481', '511','581','551') order by DISHTY2 asc";
        $res = Yii::app()->fcdb_r->createCommand($sql)->queryAll();
        if( is_array($res) && sizeof($res) > 0 ) {
            //把分红送股的多条信息组合起来
            $fgpg_array = self::setFgpg($res);
        }
        return $fgpg_array;
    }

    /**
     * 根据股票code获取股票带有市场的代码
     * @param $code
     * @return array|void
     */
    public function getSymbolByCodes($code){
        if(empty($code)){
            return;
        }
        foreach ($code as $symbol) {
            $symbol_code[] = $symbol['symbol'];
        }
        $symbol_name = Stock::model()->getStockSymbols($symbol_code);
        foreach ($symbol_name as $name) {
            $symbol_symbol[] = "'" . $name['symbol'] . "'";
        }
        return $symbol_symbol;
    }

    /**
     * 更新调入价格
     * @param $share
     * @param $s
     * @param $symbol_info
     */
    public function updatePrice($share,$s,$symbol_info){
        $symbol_share = $share[substr($s['symbol'],2)];
        $deal_price = $s['deal_price'];
        if(!empty($symbol_share['px'])){
            $curr_price = $symbol_info[$s['symbol']] - ($symbol_share['px']/10);
            $new_price = $deal_price*$curr_price/$symbol_info[$s['symbol']];
        }
        if(!empty($symbol_share['sg']) || !empty($symbol_share['zz'])){
            if(!empty($new_price)){
                $deal_price = $new_price;
            }
            $sg_zz = floatval($symbol_share['sg'])+floatval($symbol_share['zz']) + 10;
            $new_price = floatval($deal_price*10/$sg_zz);
        }
        $data = [
            'new_price' => round($new_price,2),
            'id' => $s['id'],
        ];
        Taogu::model()->updatePrice($data);
    }

    /**
     * 修改调入价格
     * @param $share
     */
    public static function ModifyPrice($share){
        $symbol_symbol = self::getSymbolByCodes($share);
        if(empty($symbol_symbol)){
            return;
        }
        $field = ['id','symbol','status','deal_price'];
        $symbols = Taogu::model()->getSymbolByStatus(3,$field,$symbol_symbol);
        if(empty($symbols)){
            return;
        }
        $symbol_info = Taogu::model()->getClosePrice($symbol_symbol);
        foreach ($symbols as $s){
            self::updatePrice($share,$s,$symbol_info);
        }
    }
}