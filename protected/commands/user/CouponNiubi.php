<?php
/**
 * 冲牛币双十一活动
 */
class CouponNiubi
{

    //任务代码
    const CRON_NO=16001 ;
    /**
     * 入口
     */
    public function handle(){
        try{
            //查询上一个计划任务是否执行完
            $lock_key = MEM_PRE_KEY . 'lock_coupon_niubi_123';
            $is_lock = Yii::app()->redis_w->get($lock_key);
            if (empty($is_lock)){
                //Yii::app()->redis_w->set($lock_key,1);
                //1查询改用户是否发送过优惠券
                $user_coupon_key = MEM_PRE_KEY . 'user_coupon_key_123';
                $user_list = Yii::app()->redis_w->get($user_coupon_key);
                $uids = '';
                if (!empty($user_list)){

                    $uids = json_decode($user_list,true);
                }
                //2查询11月5日到11月10日期间 用户单笔满1000的牛币充值
                $order_list  = Orders::model()->getOrderNiuBiList('2018-12-03 00:00:00','2018-12-12 23:59:59',$uids);
                Common::model()->saveLog("已发放优惠券id:" . json_encode($uids), "info", "coupon_niubi_123");
                Common::model()->saveLog("优惠券订单列表:" . json_encode($order_list), "info", "coupon_niubi_123");
                if (!empty($order_list)){
                    $type = '';
                    $coupon_id_A = 1894;
                    $coupon_id_B = 1895;
                    $coupon_id_C = 1896;
                    $order_uids = [];
                    //优惠券信息
                    $coupon_info_A = Coupon::model()->getCouponInfoById([$coupon_id_A],'coupon_id,use_start_time,validity_date,coupon_type,amount,amount_left');
                    $coupon_info_B = Coupon::model()->getCouponInfoById([$coupon_id_B],'coupon_id,use_start_time,validity_date,coupon_type,amount,amount_left');
                    $coupon_info_C = Coupon::model()->getCouponInfoById([$coupon_id_C],'coupon_id,use_start_time,validity_date,coupon_type,amount,amount_left');
                    //3优惠券发放1
                    foreach ($order_list as $v){
                        if (!empty($coupon_info_C)){
                            if ($v['sum_price'] >=2700){
                                if ($coupon_info_C[$coupon_id_C]['amount_left']>0){
                                    $is_user_coupon = Coupon::model()->getCouponUserById($coupon_id_C,$v['uid']);
                                    $coupon_id = $coupon_id_C;
                                    $coupon_info = $coupon_info_C;
                                    //添加优惠券
                                    $order_uids[] = $this->addCouponUser($is_user_coupon,$coupon_id,$coupon_info,$v,$is_redis=1);
                                }
                            }
                        }else{
                            Common::model()->saveLog("优惠券不存在:" . $coupon_id_C, "error", "coupon_niubi_123");
                        }
                        if (!empty($coupon_info_B)){
                            if ($v['sum_price'] >=1800){
                                if ($coupon_info_B[$coupon_id_B]['amount_left']>0){
                                    $is_user_coupon = Coupon::model()->getCouponUserById($coupon_id_B,$v['uid']);
                                    $coupon_id = $coupon_id_B;
                                    $coupon_info = $coupon_info_B;
                                    //添加优惠券
                                    $this->addCouponUser($is_user_coupon,$coupon_id,$coupon_info,$v);
                                }
                            }
                        }else{
                            Common::model()->saveLog("优惠券不存在:" . $coupon_id_B, "error", "coupon_niubi_123");
                        }
                        if (!empty($coupon_id_A)) {
                            if ($v['sum_price'] >= 900) {
                                if ($coupon_info_A[$coupon_id_A]['amount_left']>0){
                                    $is_user_coupon = Coupon::model()->getCouponUserById($coupon_id_A, $v['uid']);
                                    $coupon_id = $coupon_id_A;
                                    $coupon_info = $coupon_info_A;
                                    //添加优惠券
                                    $this->addCouponUser($is_user_coupon, $coupon_id, $coupon_info, $v);
                                    Common::model()->saveLog("处理uid:" . json_encode($order_uids), "info", "coupon_niubi_123");
                                }
                                Common::model()->saveLog("充值900:", "info", "coupon_niubi_123");
                            }
                        }else{
                            Common::model()->saveLog("优惠券不存在:" . $coupon_id_A, "error", "coupon_niubi_123");
                        }

                    }
                    if (!empty($uids)){
                        if (!empty($order_uids)){

                            Yii::app()->redis_w->set($user_coupon_key,json_encode(array_merge_recursive($order_uids,$uids)));
                        }
                    }else{

                        if (!empty($order_uids)){
                            Yii::app()->redis_w->set($user_coupon_key,json_encode($order_uids));
                        }

                    }
                }
                Yii::app()->redis_w->set($lock_key,'');
            }

            Common::model()->saveLog("锁住了:", "info", "coupon_niubi_123");

        }catch (Exception $e){
            Yii::app()->redis_w->set($lock_key,'');
            Common::model()->saveLog("12月3日活动单笔冲牛币发优惠券失败:" . $e->getMessage() , "error", "coupon_niubi_123");
        }

    }
    public function addCouponUser($is_user_coupon,$coupon_id,$coupon_info,$v,$is_redis=0){

        if (empty($is_user_coupon)){
            $data = [];
            $data['uid'] = $v['uid'];
            $data['coupon_id'] = $coupon_id;
            $data['channel'] = 1;
            $data['use_start_time'] = $coupon_info[$coupon_id]['use_start_time'];
            $data['validity_date'] = $coupon_info[$coupon_id]['validity_date'];
            $data['c_time'] = date('Y-m-d H:i:s');
            $data['u_time'] = date('Y-m-d H:i:s');
            if ($coupon_info[$coupon_id]['coupon_type']==2){
                $data['type']=1;
            }elseif($coupon_info[$coupon_id]['coupon_type']==4){
                $data['type'] =2;
            }
            $request = Coupon::model()->addCouponUser($data);
            if ($request){
                Coupon::model()->reduceCouponNum($coupon_id);
                if ($is_redis){
                   return  $v['uid'];
                }
            }else{
                Common::model()->saveLog("12月3日活动单笔冲牛币发优惠券失败:" . $data, "error", "coupon_niubi_123");
            }
        }else{
            Common::model()->saveLog("12月3日活动单已发送过优惠券:" . json_encode($is_user_coupon), "info", "coupon_niubi_123");
        }

    }
}