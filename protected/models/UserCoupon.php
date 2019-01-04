<?php
/*
 *用户优惠券模型
 *
 * */
class UserCoupon extends CActiveRecord {

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function getDbConnection($table_key = 'lcs_w') {

        return Yii::app() -> $table_key;
    }

    //用户优惠券
    public function tableName(){
        return TABLE_PREFIX .'user_coupon';
    }

    //优惠券基础信息表
    public function tableCouponName(){
        return TABLE_PREFIX . 'coupon';
    }

    /**
     * 根据用户订单信息获取优惠券详情
     *
     * @param unknown_type $uid
     * @param unknown_type $order_no
     */
    public function getCouponByOrderNo($uid,$order_no){

        $sql = "select coupon_id from ".$this -> tableName()." where uid=:uid and order_no=:order_no";
        $cmd = $this-> getDbConnection()-> createCommand($sql);
        $cmd-> bindParam(':uid' , $uid , PDO::PARAM_INT);
        $cmd-> bindParam(':order_no' , $order_no , PDO::PARAM_STR);
        return $cmd-> queryScalar();
    }


    /**
     * 更新用户的优惠券信息
     *
     * @param unknown_type $order_no
     */
    public function updateUserCouponByOrderNO($order_no,$uid,$update_data){
        $cmd = $this->getDbConnection('lcs_w');
        $result = $cmd->createCommand()->update($this->tableName(),$update_data,"order_no='$order_no' and uid='$uid' and status=1 limit 1");
        return $result;
    }


}
