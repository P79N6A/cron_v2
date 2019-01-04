<?php
/**
 * 优惠券定时任务入口
 * User: zwg
 * Date: 2015/5/18
 * Time: 17:34
 */

class CouponCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.coupon.*');
    }

    /**
     * 更新有效期内优惠券剩余数量
     * @param string $stat_date
     * @param int $is_stat_data
     * @param int $is_stat_score
     */
    public function actionCouponAmountLeft(){
        try{
            $CouponAmountLeft = new CouponAmountLeft();
            $result = $CouponAmountLeft->updateCouponAmountLeft();

            //记录任务结束时间
            $this->monitorLog(CouponAmountLeft::CRON_NO);
            if(!empty($result)){
                Cron::model()->saveCronLog(CouponAmountLeft::CRON_NO, CLogger::LEVEL_INFO, "更新过期优惠码状态：".json_encode($result));
            }
        }catch (Exception $e) {
            Cron::model()->saveCronLog(CouponAmountLeft::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }


    }

    /**
     * 修改用户过期的优惠券状态
     */
    public function actionUpdateExpiredCoupon(){
        try{
			//优惠券过期
			$result = Coupon::model()->updateExpiredCoupon();
			//折扣券过期
			$result = Coupon::model()->updateExpiredCoupon(0,1);
            //记录任务结束时间
			$cron_no = 3001;
            $this->monitorLog($cron_no);
            Cron::model()->saveCronLog($cron_no, CLogger::LEVEL_INFO, "更新用户过期优惠券状态：".json_encode($result));
        }catch (Exception $e) {
            Cron::model()->saveCronLog($cron_no, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }

    }
	
	public function actionMoveCoupon($run,$f){
		$sql = "select * from lcs_user_discount_coupon";
		if($f == 1){
			$sql .= " where uid in (105,22908942,1488,22909027,22979848,22978183,19818809)";
		}elseif($f == 2){
			$sql .= " where uid not in (105,22908942,1488,22909027,22979848,22978183,19818809)";
		}
		$list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		$insert_sql = "insert into lcs_user_coupon (type,uid,coupon_id,validity_date,status,order_no,ip,c_time,u_time,staff_id,channel,phone) values ";
		$init_values = "('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')";
		$values = array();
		foreach($list as $item){
			$values[] = sprintf($init_values,2,$item['uid'],$item['coupon_id'],$item['validity_date'],$item['status'],$item['order_no'],$item['ip'],$item['c_time'],$item['u_time'],$item['staff_id'],$item['channel'],$item['phone']);
		}
		$sql = $insert_sql.implode(",", $values);
		echo $sql;
		if($run == 1){
			$res = Yii::app()->lcs_w->createCommand($sql)->execute();
			echo $res;
		}
	}
	
	public function actionCouponCode($coupon_id,$num){
		if(empty($num)){
			exit("num err");
		}
		$cmap = Coupon::model()->getCouponInfoById(array($coupon_id));
		$coupon_info = isset($cmap[$coupon_id]) ? $cmap[$coupon_id] : '';
		if(empty($coupon_info)){
			exit("no coupon ");
		}
		$c = new CouponCode();
		$c->addCouponCode($coupon_info,$num);		
	}



}
