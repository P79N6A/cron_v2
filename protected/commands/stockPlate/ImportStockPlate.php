<?php

/**
* 导入板块数据
*/
class ImportStockPlate{

	const CRON_NO = 14101;

    public function process(){
        $this->getPlate();
        $this->getPlateSymbol();
        Stock::model()->DeleteStockPlate();
    }

    /**
     * 获取板块数据
     */
    public function getPlate(){
        $region = array("HYBK","DYBK","GNBK");
        foreach($region as $item){
            $data = ThirdCallService::getStockPlate($item);
            if(count($data)>0){
                foreach($data as $val){
                    Stock::model()->SaveStockPlate($item,$val);
                }
            }
        }
    }

    /**
     * 获取板块相关股票数据
     */
    public function getPlateSymbol(){
        $start = 0;
        $limit = 100;
        while(true){
            $plate_list = Stock::model()->getStockPlateByPage($start,$limit);
            if(count($plate_list)>0){
                foreach($plate_list as $item){
                    if($start<$item['id']){
                        $start = $item['id'] + 1;
                    }
                    $symbol_list = ThirdCallService::getStockPlateSymbol($item['type'],$item['scode']);
                    var_dump("get ".$item['type']."-".$item['scode']."-".$item['sname']." 更新股票数:".count($symbol_list));
                    if($symbol_list && count($symbol_list)>0){
                        foreach($symbol_list as $symbol){
                            Stock::model()->SaveStockSymbol($item['ei'],$symbol);
                        }
                    }
                }
            }else{
                break;
            }
        }
    }
}
