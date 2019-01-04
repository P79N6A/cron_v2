<?php
/**
 * 
 */
class TouJiaoOrder
{

    //任务代码
    const CRON_NO=14000 ;
    /**
     * 入口
     */
    public function TouJiaoOrders(){
        //获取一分钟修改过的订单信息
        $key=MEM_PRE_KEY.'order_u_time';
        $start_time=Yii::app()->redis_r->get($key);
        $order_no=Yii::app()->redis_r->get('lcs_order_no');
        if(!empty($order_no)){
            $order_info= Orders::model()->getOrderInfoByOrderNo($order_no);
        }
        if(empty($start_time)|| $start_time=='0000-00-00 00:00:00' ){
            $start_time=date('Y-m-d H:i:s',strtotime('-1 Minute',time()));
        }
        $orderInfo= Orders::model()->getOrderInfo($start_time);
        echo '<pre>';var_dump($orderInfo);
        if(!empty($orderInfo) && !empty($order_info) ){
            foreach ($orderInfo as $k=>$val){
                if($val['u_time']==$order_info['u_time'] && $val['u_time']==$start_time ){
                    unset($orderInfo[$k]);
                }
            }
        }
        echo '<pre>';var_dump($orderInfo);
        if(empty($orderInfo)){
            echo "暂无信息\n";
            return;
        }else{
            foreach ($orderInfo as &$v){
                $phone = User::model()->getUserInfoByUid($v['uid']);
                $v['phone'] = isset($phone['phone'])?$phone['phone']:'';
                $certInfo=User::model()->getCertInfo($v['phone']);
                $v['id_number']=isset($certInfo['id_number'])?$certInfo['id_number']:'';
                $v['real_name']=isset($certInfo['real_name'])?$certInfo['real_name']:'';
                $plannerInfo=Planner::model()->getPlannerById($v['p_uid']);
                $v['plannerName'] = isset($plannerInfo[$v['p_uid']]['name'])?$plannerInfo[$v['p_uid']]['name']:'';
                //获取参数
                $order=$this->getParams($v);
                if($v['status']==4){
                    Yii::app()->redis_w->rpush('lcs_userbuynotice',$v['order_no']);
                }
                $param_josn = json_encode($order);
                Yii::app()->redis_w->set($key,$v['u_time']);
                Yii::app()->redis_w->set('lcs_order_no',$v['order_no']);
                echo $param_josn;
                //推送订单
                $this->pushOrder($param_josn);

            }
        }
    }
    private function pushOrder($param_josn){
        $header = array(
            'Content-Type'=>'application/json; charset=utf-8',
        );
        $sh = Yii::app()->curl->setTimeOut(10)->setHeaders($header);
        $data = $sh->post(
            "https://beidou-api.yk5800.com/api/RestService",
            $param_josn
        );
        $data = json_decode($data,true);
        var_dump($data);
        if(!empty($data)){
            Common::model()->saveLog("订单信息推送成功:".json_encode($data),"success","push_order");
        }else{
            Common::model()->saveLog("订单信息推送失败:".$param_josn,"error","push_order");
        }
    }
    private function getParams($v){
        $order=array();
        try{
            $res=Yii::app()->curl->setTimeOut(10)->get("http://lcs-api.licaishisina.com/api/user/investInfo?phone=".$v['phone']);
        }catch(Exception $e){
            Yii::app()->redis_w->set('lcs_order_u_time',$v['u_time']);
            throw new Exception($e,-1);
        }
        var_dump($res);
        if(!$res){
            $order['sIAdata']="";
        }else{
            $res=json_decode($res,true);
            if(!empty($res['data']['personCode'])){
                $order['sIAdata']=$res['data']['personCode'];
            }else{
                $order['sIAdata']="";
            }
        }
        $order['order_no']=$v['order_no'];
        $order['sItemAcount']=$v['price'];
        $order['sTradeTime']=$v['pay_time'];
        $order['sMibleNum']=md5($v['phone']);
        $order['sItemContent']=$v['description'];
        $order['sOrderType']=$v['type'];
        $order['sMible']=$v['phone'];
        $order['iUid']=(int)$v['uid'];
        $order['sName']=$v['plannerName'];
        $order['p_uid']=$v['p_uid'];
        $order['status']=$v['status'];
        $order['c_time']=$v['c_time'];
        $order['u_time']=$v['u_time'];
        $order['pay_type']=$v['pay_type'];
        $order['pay_number']=$v['pay_number'];
        $order['fr']=$v['fr'];
        $order['tg_id']=$v['tg_id'];
        $order['tg_name']=$v['tg_name'];
        $order['sIDC']=$v['id_number'];
        $order['sCustName']=$v['real_name'];
        return $order;
    }
}