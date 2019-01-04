<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CouponCode
 *
 * @author hailin5
 */
class CouponCode {

	//put your code here
	//创建优惠券
	public function addCouponCode($coupon, $need_add_num) {
		if (empty($coupon)) {
			exit("no coupon");
		}
		$coupon_id = $coupon['coupon_id'];
		$date = $coupon['start_time'];
		for ($i = 0; $i < $need_add_num; $i++) {
			$channel_id = 'ceshi' . $this->generate_password(10);
			$validity_date = $coupon['validity_date'];
			$code_data = array('coupon_id' => $coupon_id, 'code' => $this->generate_password(8), 'uid' => 0, 'channel_id' => $channel_id, 'status' => 0, 'ip' => '', 'c_time' => $date, 'validity_date' => $validity_date);
			Coupon::model()->addCouponCode($code_data);
			Coupon::model()->reduceCouponNum($coupon_id, -1);
		}
	}

	function generate_password($length = 8) {
		// 密码字符集，可任意添加你需要的字符 
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$password = '';
		for ($i = 0; $i < $length; $i++) {
			$password .= $chars[mt_rand(0, strlen($chars) - 1)];
		}
		return $password;
	}

}
