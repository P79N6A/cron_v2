<?php

/**
  第三方调用
  */
class ThirdCallService{
    /**
     * 获取上海行情的板块数据
     */
    public static function getStockPlate($type){
        if(defined("ENV") && ENV == "dev"){
            $url = "http://test-hq.caixun99.com/stock/queryPlateList";
        }else{
            $url = "http://stockhq.caixun99.com/stock/queryPlateList";
        }
        $param = array();
        $param['Type'] = $type;
        $param = json_encode($param);
        $headers = array(
            "Content-type:application/json",
            "Accept:application/json"
            );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        try{
            $result = curl_exec($ch);
            $result = json_decode($result,true);
            if($result['errorCode']==0){
                return $result['data'];
            }else{
                Common::model()->saveLog("上海行情板块数据解析错误".json_encode($result),"error","stock_plate");
            }
            return false;
        }catch(Exception $e){
            Common::model()->saveLog("上海行情板块数据获取错误".$e->getMessage(),"error","stock_plate");
            return false;
        }
    }

    /**
     * 获取上海行情的板块数据
     */
    public static function getStockPlateSymbol($type,$code){
        if(defined("ENV") && ENV == "dev"){
            $url = "http://test-hq.caixun99.com/stock/getPlateStock";
        }else{
            $url = "http://stockhq.caixun99.com/stock/getPlateStock";
        }
        $param = array();
        $param['Type'] = $type;
        $param['SCode'] = $code;
        $param = json_encode($param);
        $headers = array(
            "Content-type:application/json",
            "Accept:application/json"
            );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        try{
            $result = curl_exec($ch);
            $result = json_decode($result,true);
            if($result['errorCode']==0){
                return $result['data'];
            }else{
                Common::model()->saveLog("上海行情板块股票数据解析错误".json_encode($result),"error","stock_plate");
            }
            return false;
        }catch(Exception $e){
            Common::model()->saveLog("上海行情板块股票数据获取错误".$e->getMessage(),"error","stock_plate");
            return false;
        }
    }

    /**
     * 判断今天是不是交易日
     */
    public static function JudgeTradeDay(){
        $url = "http://licaishi.sina.com.cn/api/checkworkday";

        $headers = array(
            "Content-type:application/json",
            "Accept:application/json"
            );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        try{
            $result = curl_exec($ch);
            $result = json_decode($result,true);
            if($result['code']==0){
                return $result['data'];
            }else{
                Common::model()->saveLog("判断今天是不是交易日".json_encode($result),"error","judge_tradeday");
            }
            return false;
        }catch(Exception $e){
            Common::model()->saveLog("判断今天是不是交易日".$e->getMessage(),"error","judge_tradeday");
            return false;
        }
    }

    /**
     * 推送给投教微信模板消息
     */
    public static function push49800($data){
        if(defined("ENV") && ENV == "dev"){
            $url = "http://test-wechat-api.sinagp.com/api/share/sendMessage";
        }else{
            $url = "http://lcs-api.licaishisina.com/api/share/sendMessage";
        }
        $param = array();
        $param['push_info'] = json_encode($data);
        $param = json_encode($param);
        $headers = array(
            "Content-type:application/json",
            "Accept:application/json"
            );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        try{
            $result = curl_exec($ch);
            $result = json_decode($result,true);
            if($result['code']==0){
                return true;
            }else{
                Common::model()->saveLog("推送49800计划操作失败".json_encode($result),"error","push49800");
            }
            return false;
        }catch(Exception $e){
            Common::model()->saveLog("推送49800计划操作失败".$e->getMessage(),"error","push49800");
            return false;
        }
    }
}
