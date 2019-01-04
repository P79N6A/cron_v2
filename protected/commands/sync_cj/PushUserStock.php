<?php
/**
 * 生成用户包含的所有自选股 并且推送到上海
 * Created by PhpStorm.
 * User: PanChaoYi
 * Date: 2017/9/6
 * Time: 16:58
 */

class PushUserStock {
    const CRON_NO = 8107; //任务代码
    public function pushProcess(){
        $data = Stock::model()->pushUserStock();
        if(empty($data))
            exit();
        foreach ($data as $item=>$value){
            $this->pushToStockInfo(array('uid'=>$item,'ei'=>$value));
            Common::model()->saveLog(json_encode(array('uid'=>$item,'ei'=>$value)),"push_pcy","stat");
        }

    }

    private function pushToStockInfo($param){
        //$url = "http://test-hq.caixun99.com/stockpull/updateSelfSelectStock";
        $url = 'http://stockhq.caixun99.com/stockpull/updateSelfSelectStock';

        $redis_key = MEM_PRE_KEY.'push_user_stock';

        $param = json_encode($param);
        $headers = array(
            "Content-type:application/json",
            "Accept:application/json"
        );
        $ch = curl_init($url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch,CURLOPT_HEADER,FALSE);
        curl_setopt($ch,CURLOPT_POST,TRUE);
        curl_setopt($ch,CURLOPT_TIMEOUT,4);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

        try{
            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result,TRUE);
            if($result['errorCode']){
                Common::model()->saveLog('推送自选股操作失败:'.$result['errorMsg'],"error","remote_filter");
            }else{
                Yii::app()->redis_w->set($redis_key,date('Y-m-d H:i:s'));
            }
        }catch (Exception $e){
            Common::model()->saveLog("推送自选股操作失败:{$e->getMessage()}","error","remote_filter");
        }
    }
}