<?php

class ChangeUserCircle{

    const CRON_NO = 20181029;

    public function __construct(){

    }

    /**
     * 直播间到期变更
     * @param $start_time 查询开始时间
     * @param $end_time 查询结束时间
     */
    public function expireChange($start_time,$end_time){
        $user = $this->getCircleSubUser($start_time,$end_time);
        $this->set($user);
    }

    /**
     * 分页获取付费圈子
     * @param $page
     * @return bool
     */
    public function getCircle($page)
    {
        $Circle = Circle::model()->getCircleInfoByPage('type=6001',$page,100);

        if(empty($Circle['data'])){
            return false;
        }
        return $Circle['data'];
    }

    /**
     * 获取指定时间段内的付费用户
     * @param $start_time
     * @param $end_time
     * @return array
     */
    public function getCircleSubUser($start_time,$end_time){
        $page = 1;
        $user = array();
        while(($circle = $this->getCircle($page))){
            foreach ($circle as $c){
                $users = array();
                $users['data'] = Circle::model()->getEndUser($c['id'],$start_time,$end_time);
                if(!empty($users['data'])){
                    $users['expire_cid'] = $c['expire_cid'];
                    $users['cid'] = $c['id'];
                    $user[] = $users;
                }
            }
            $circle = null;
            $page++;
        }

        return $user;
    }

    /**
     * 变更用户的付费直播间
     * @param $user
     */
    public function set($user,$flag = 0){
        if(empty($user)){
            return false;
        }
        foreach ($user as $circle){
            if(!empty($circle['expire_cid']) || (!empty($circle['expire_cid']) && $flag && $circle['expire_cid']!=$circle['cid'])){
                foreach ($circle['data'] as $u){
                    $u_time = date("Y-m-d H:i:s");
                    $data = [
                        'uid' => $u,
                        'u_type' => 1,
                        'circle_id' => $circle['expire_cid'],
                        'service_status' => 1,
                        'u_time' => $u_time,
                    ];
                    //检验用户是否订阅圈子
                    $circleUser = Circle::model()->getCircleUserInfo($circle['expire_cid'],$u);
                    if($flag){
                        $oldcircleUser = Circle::model()->getCircleSubInfo($circle['cid'],$u);
                    }
                    if(!empty($circleUser)){
                        echo 'update'.$u."\n";
                        //根据circle_id,uid更新circle_user
                        if($flag){
                            $data['end_time'] = $oldcircleUser['end_time'];
                        }else{
                            $data['end_time'] = date('Y-m-d H:i:s',strtotime("+1 year",strtotime($circleUser['end_time'])));
                        }
                        Circle::model()->updateCircleUser($circle['expire_cid'],$u,$data);
                    }else{
                        echo 'add'.$u."\n";
                        $data['c_time'] = $u_time;
                        if($flag){
                            $data['end_time'] = $oldcircleUser['end_time'];
                        }else {
                            $data['end_time'] = date('Y-m-d H:i:s', strtotime("+1 year"));
                        }
                        //增加circle_user订阅记录
                        Circle::model()->addCircleUser($data);
                    }
                    echo 'update_subscription'.$u.'expire_cid'.$circle['expire_cid']."\n";
                    //更新圈子服务时间
                    Circle::model()->updatePayCircleSubscription($circle['expire_cid'],$u,$data['end_time']);
                    if($circle['cid'] != $circle['expire_cid']){
                        echo 'delete circle user'.$u."cid".$circle['cid']."\n";
                        Circle::model()->deleteCircleUser($circle['cid'],$u);
                        Circle::model()->deleteCircleSub($circle['cid'],$u);
                    }
                    $data = null;
                }
            }
        }
    }

    /**
     * 直播间续费变更
     * @param $start_time
     */
    public function renewChange($start_time,$end_time){
        $orderInfo = $this->getOrders($start_time,$end_time);
        $this->checkOrderIsCircle($orderInfo);
    }
    /**
     * 获取某时间段内付费圈子相关套餐订单
     * @param $start_time
     * @return mixed
     */
    public function getOrders($start_time,$end_time){
        //套餐包ID详见web工程下model层CoursePackage
        $circle_pkg_id=array(3,4,6,7,9);
        $orderInfo = Orders::model()->getCircleOrders($start_time,$end_time,$circle_pkg_id);

        return $orderInfo;
    }

    /**
     * 根据订单查询圈子订阅，续费订阅做变更操作
     * @param $orderInfo
     * @return bool
     */
    public function checkOrderIsCircle($orderInfo){
        if(empty($orderInfo)){
            return false;
        }
        $user = array();
        foreach ($orderInfo as $orders){
            $where = 'type=6001 and p_uid='.$orders['p_uid'].' and status=0';
            $Circle = Circle::model()->getCircleInfoByPage($where,1,10000);
            if(empty($Circle['data'])){
                return false;
            }
            foreach($Circle['data'] as $c){
                //从表subscription中取订阅信息
                $subInfo = Circle::model()->getCircleSubInfo($c['id'],$orders['uid']);
                if(empty($subInfo)){
                    continue;
                }
                //lixiang add 订阅时间和订单时间在5秒内有效
                $sub_time = strtotime($subInfo['u_time']);
                $pay_time = strtotime($orders['pay_time']);
                if($sub_time>=$pay_time && $sub_time<=$pay_time+5){
                    $ext = [
                        'relation_id' => $orders['relation_id'],
                        'type' => $orders['type'],
                        'uid' => $orders['uid'],
                        'p_uid' => $orders['p_uid'],
                        'status' => 2,
                    ];
                    //若存在，则校验订单表是否是续费订阅，续费则变更操作
                    $orderNum = Orders::model()->getCountCircleOrders($orders['order_no'],$ext);
                    if($orderNum >= 1){
                        $user[] = [
                            'cid' => $c['id'],
                            'expire_cid' => $c['renew_cid'],
                            'data'=>[
                                $orders['uid'],
                            ],
                        ];
                    }
                }
            }
        }
//        var_dump($user);exit;
        $this->set($user,1);
    }
}
