<?php

/**
 * 超过24小时的订单自动关闭
 */
class  Close24HOrders{

	const CRON_NO = 8301; //任务代码

	public function __construct() {

	}

	public function CloseOrders(){
		try{
			$datetime = date("Y-m-d H:i:s",time()-82800);
			//$datetime = date("Y-m-d H:i:s",time()-5*60);

			$sql = "SELECT id,order_no,type,uid,relation_id FROM lcs_orders WHERE status=1 AND c_time<'$datetime'";
			$orders = Yii::app()->lcs_r->createCommand($sql)->queryAll();

			if(!empty($orders) && is_array($orders)){
				foreach($orders as $o_info){
					$transaction = Yii::app()->lcs_w->beginTransaction();
					try{
						//更新订单表
						$update_orders_c = Yii::app()->lcs_w->createCommand("update lcs_orders set status=-1,u_time='".date("Y-m-d H:i:s")."' where order_no='".$o_info['order_no']."'")->execute();
						if($update_orders_c > 0){

							$coupon_id = UserCoupon::model()->getCouponByOrderNo($o_info['uid'],$o_info['order_no']);

							if($coupon_id) {
								UserCoupon::model()->updateUserCouponByOrderNO($o_info['order_no'], $o_info['uid'], array('order_no' => '', 'status' => 0));

								Coupon::model()->reduceCouponUseNum($coupon_id, -1);

							}
							//添加订单记录
							Orders::model()->saveOrdersRecord($o_info['order_no'],'system',0,'close_orders','关闭订单');
							//提问订单
							if(isset($o_info['type']) && $o_info['type']==11){
								$update_question_c = Yii::app()->lcs_w->createCommand("update lcs_ask_question set status=-1,u_time='".date("Y-m-d H:i:s")."' where id=".intval($o_info['relation_id']))->execute();

								if($update_question_c > 0){
									//清除缓存
									Yii::app()->curl->get("http://i.licaishi.sina.com.cn/cacheApi/actionMyQuestions?uid=".$o_info['uid']."&type=1");

									$transaction->commit();
								}else{
									throw new Exception("Error");
								}
							}
							else{
								$transaction->commit();
							}

						}else{
							throw new Exception("Error");
						}
					}catch (Exception $e){
						$transaction->rollBack();
					}
				}
			}
		}catch (Exception $e){
			throw LcsException::errorHandlerOfException($e);
		}
	}
}