<?php

/**
 * Desc  :更新剩余优惠券数量
 * Author: haohao
 * Date  : 2015-9-7 16:49:52
 */
class CouponAmountLeft {
    const CRON_NO = 3002; //任务代码

    public function __construct(){
    }
    /*
     * 获取有效期内优惠券活动列表
     */
    public function updateCouponAmountLeft() {
		$data = array();
    		try{
			//$date = date('Y-m-d H:i:s',strtotime('-12 hours'));
				$date = date('Y-m-d H:i:s');
        		$data = Coupon::model() -> getValidityCouponCodeForUpdateAmountLeft($date);
			$transaction = Yii::app() -> lcs_w -> beginTransaction();
			if (!empty($data)) {
				foreach($data as $k=>$v){
					$coupon_id = $k;
					$count = $v;
					if($count>0){
						Coupon::model() -> reduceCouponNum($coupon_id, $count);
					}
				}
			}
			//更新优惠码状态为已过期
			$res = Coupon::model() -> updateCouponCodeStatus($date, array('status' => '-1', 'is_update' => '1','u_time'=>date('Y-m-d H:i:s')));
			if($res){
				$transaction->commit();
			}else{
				$transaction->rollBack();
			}
		}catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
		return $data;
    }


}
