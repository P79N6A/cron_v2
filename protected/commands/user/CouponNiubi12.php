<?php
/**
 * 冲牛币双12活动
 */
class CouponNiubi12
{

    //任务代码
    const CRON_NO=16001 ;
    /**
     * 入口
     */
    public function handle(){
        try{
             //Yii::app()->redis_w->set(MEM_PRE_KEY . 'user_coupon_key_12','');
            //查询上一个计划任务是否执行完
            $lock_key = MEM_PRE_KEY . 'lock_coupon_niubi_12';
            $is_lock = Yii::app()->redis_w->get($lock_key);
            if (empty($is_lock)){
                Yii::app()->redis_w->set($lock_key,1);
                //1查询改用户是否发送过优惠券
                $user_coupon_key = MEM_PRE_KEY . 'user_coupon_key_12';
                $user_list = Yii::app()->redis_w->get($user_coupon_key);
                $uids = '';
                if (!empty($user_list)){

                    $uids = json_decode($user_list,true);
                }
                //2查询11月5日到11月10日期间 用户单笔满1000的牛币充值
                $order_list  = Orders::model()->getOrderNiuBiList12('2018-12-06 17:00:00','2018-12-12 23:59:59',$uids);
                if (!empty($order_list)){
                    $coupon_id = 1976;
                    $order_uids = [];
                    //优惠券信息
                    $coupon_info = Coupon::model()->getCouponInfoById([$coupon_id],'coupon_id,use_start_time,validity_date,coupon_type,amount_left');
                    //3优惠券发放1
                    foreach ($order_list as $v){
                        $is_user_coupon = Coupon::model()->getCouponUserById($coupon_id,$v['uid']);
                        if (empty($is_user_coupon)){
                            if ($coupon_info[$coupon_id]['amount_left']>0){
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
                                    $order_uids[] = $v['uid'];
                                    $coupon_info[$coupon_id]['amount_left'] -=1;
                                    Coupon::model()->reduceCouponNum($coupon_id);
                                }else{
                                    Common::model()->saveLog("双12活动单笔冲牛币满1000发优惠券失败:" . $data, "error", "coupon_niubi12");
                                }
                            }

                        }else{
                            $order_uids[] = $v['uid'];
                        }

                    }
                    if (!empty($uids)){
                        Yii::app()->redis_w->set($user_coupon_key,json_encode(array_merge_recursive($order_uids,$uids)));
                    }else{
                        Yii::app()->redis_w->set($user_coupon_key,json_encode($order_uids));
                    }
                }
            }
            Yii::app()->redis_w->set($lock_key,'');

        }catch (Exception $e){
            Yii::app()->redis_w->set($lock_key,'');
            Common::model()->saveLog("双12活动单笔冲牛币满1000发优惠券失败:" . $e->getMessage() , "error", "coupon_niubi12");
        }

    }
}
