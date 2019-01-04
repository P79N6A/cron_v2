<?php
class OrderDailyStat{
    private $partnerid = null;
    public function setPartnerId($id){
        $this->partnerid = $id;
    }

    public $order_type = array(
        '0'=>'全部',
        '11'=>'问答',
        '12'=>'解锁',
        '21'=>'计划',
        '22'=>'偷看',
        '31'=>'观点包',
        '32'=>'观点解锁',
        '71'=>'体验卡'
    );    

    public function all($date=''){
        $sqlarr = array(
            '订单总数'=>"select count(*) from lcs_orders where partner_id=18 and pay_time>'2017-04-12' ",
            '成功金额'=>"select sum(price) from lcs_orders where partner_id=18 and status=2 and pay_time>'2017-04-12' ",
            '成功单数'=>"select count(*) from lcs_orders where partner_id=18 and status=2 and pay_time>'2017-04-12' ",
        );
        $result = array();
        foreach($this->order_type as $type=>$typevalue){
            foreach($sqlarr as $s=>$sql){                        
                $where = '';
                if(!empty($date)){
                    $where = " and pay_time>='{$date} 00:00:00' and pay_time<='{$date} 23:59:59'";
                }
                if(!empty($type)){
                    $where .= " and type=".$type;
                }
                $fsql = $sql.$where;     
                if(!empty($date)){
                    $desc = $typevalue."-".$s."-{$date}:";
                }else{
                    $desc = $typevalue."-".$s.":";
                }                
                $result[$desc] = Yii::app()->lcs_standby_r->createCommand($fsql)->queryScalar();
            }
        }
        return $result;
    }
  
}
?>