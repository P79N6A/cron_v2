<?php

/**
 * 股票相关服务
 * 识别文本内股票代码
 *
 */
class TaoguService{

    /**
     * 获取股票的当前价、收盘价、开盘价
     */
    public static function getNewSymbolPrice($symbol) {
        if(empty($symbol)){
            return;
        }
        $stock = Yii::app()->curl->setTimeOut(8)->get("http://hq.sinajs.cn/format=text&rm=" . time() . "&list=" . strtolower($symbol));
        if (!empty($stock)) {
            $stock = substr($stock,0,-1);
            $val = explode('=', $stock);
            $val = explode(',', $val[1]);
            $symbol_info = array('curr_price'=>($val[3] == 0)?$val[2]:$val[3],'close_price'=>$val[2],'open_price'=>$val[1],'status'=>$val[32]);
        }

        if(empty($symbol_info)){
            $symbol_info = self::getNewSymbolPriceBak($symbol);
        }
        return $symbol_info;
    }

    /**
     * getNewSymbolPrice函数补充查询
     * @param $symbol
     * @return array
     */
    public function getNewSymbolPriceBak($symbol){
        $stock = Yii::app()->curl->setTimeOut(8)->get("http://hq.sinajs.cn/list=" . strtolower($symbol));
        if (!empty($stock)) {
            $val = explode('=', $stock);
            $val = explode(',', $val[1]);
            $symbol_info = [
                'curr_price'=>($val[3] == 0)?$val[2]:$val[3],
                'close_price'=>$val[2],
                'open_price'=>$val[1],
                'status'=>$val[32],
            ];
        }
        return $symbol_info;
    }

    /**
     * 批量获取股票的当前价、收盘价、开盘价
     */
    public static function getBetchSymbolPrice($symbol) {
        if(empty($symbol)){
            return;
        }
        $symbol = (array)$symbol;
        $stock_str = Yii::app()->curl->get("http://hq.sinajs.cn/format=text&rm=" . time() . "&list=" . implode(',',$symbol));
        if (!empty($stock_str)) {
            $stock_arr = explode("\n",$stock_str);
            foreach ($stock_arr as $stock){
                $val = explode('=', $stock);
                $code = $val[0];
                if(empty($code)){
                    continue;
                }
                $val = explode(',', $val[1]);
                $symbol_info[] = array('symbol' => $code,'curr_price'=>($val[3] == 0)?$val[2]:$val[3],'close_price'=>$val[2],'open_price'=>$val[1],'name'=>iconv('GBK','UTF-8',$val[0]));
            }
        }
        if(empty($symbol_info)){
            $symbol_info = self::getBetchSymbolPriceBak($symbol);
        }
        return $symbol_info;
    }

    /**
     * getBetchSymbolPrice函数补充函数
     * @param $symbol
     * @return mixed
     */
    public function getBetchSymbolPriceBak($symbol) {
        $stock_str = Yii::app()->curl->get("http://hq.sinajs.cn/list=" . implode(',',$symbol));
        if (!empty($stock_str)) {
            $stock_arr = explode("\n",$stock_str);
            foreach ($stock_arr as $stock){
                if(empty($stock)){
                    continue;
                }
                $val = explode('=', $stock);
                $code = substr($val[0],11);
                $val = explode(',', $val[1]);
                if(empty($code)){
                    continue;
                }
                $symbol_info[$code] = array('symbol' => $code,'curr_price'=>($val[3] == 0)?$val[2]:$val[3],'close_price'=>$val[2],'open_price'=>$val[1],'name'=>iconv('GBK','UTF-8',substr($val[0],1)));
            }
        }
        return $symbol_info;
    }

    /**
     * 获取跌停价
     */
    public static function getLimitLow($symbol_info){
        $low_price = $symbol_info['close_price']*0.9;
        $low_low_price = $low_price - 0.01;

        $low_rate = ($symbol_info['close_price'] - $low_price)/$symbol_info['close_price'];
        $low_low_rate = ($symbol_info['close_price'] - $low_low_price)/$symbol_info['close_price'];

        $low_rate = abs($low_rate - 0.1);
        $low_low_rate = abs($low_low_rate - 0.1);

        return $low_rate>$low_low_rate?round($low_low_price,2):round($low_price,2);
    }

    /**
     * 获取涨停价
     */
    public static function getLimitHigh($symbol_info){
        $high_price = $symbol_info['close_price']*1.1;
        $high_high_price = $high_price + 0.01;

        $high_rate = ($high_price - $symbol_info['close_price'])/$symbol_info['close_price'];
        $high_high_rate = ($high_high_price - $symbol_info['close_price'])/$symbol_info['close_price'];

        $high_rate = abs($high_rate - 0.1);
        $high_high_rate = abs($high_high_rate - 0.1);

        return $high_rate>$high_high_rate?round($high_high_price,2):round($high_price,2);
    }
}
