<?php

/**
 * VIP服务类
 */
class VipService
{
    public static function init()
    {
        $sql = "select * from (SELECT id,p_uid,u_time,charge_time as start_time FROM lcs_package  WHERE subscription_price>0 AND status=0 order by  u_time desc)M group by M.p_uid order by  M.u_time desc";
        $data = Yii::app()->licaishi_w->createCommand($sql)->queryAll();
        $serviceALl = Service::model()->getServiceALl('1=1');
        $newService = [];
        $newData = [];
        if (!empty($data)){
            foreach ($data as $k=>$v){
                $newData[$v['p_uid']] = $v;
            }
        }
        if (!empty($serviceALl)){
            foreach ($serviceALl as $key=>$val){
                $newService[$val['p_uid']] = $val;
            }
        }
        if (!empty($newData)){
            $packageArray = array_diff_key($newData,$newService);
        }

        if (!empty($packageArray)){
            $packageArray = array_values($packageArray);
            foreach ($packageArray as $v){
                self::createVipService($v);
            }
        }

        echo "success";
    }

    public static function createVipService($params)
    {
        //添加VIP服务
        $id = Service::model()->createService($params);
        //创建默认的服务详情
        self::createDefaultServiceInfo($id,$params['p_uid']);
        //创建服务产品
        self::createServiceProduct($id,$params);
    }

    /**
     * 创建默认的服务详情
     * @param $serviceId
     * @param $lcsId
     */
    private static function createDefaultServiceInfo($serviceId,$lcsId)
    {
        $addArr = [];
        //顶部图片
        $topImg['service_id'] = $serviceId;
        $topImg['title'] = '顶部图片';
        $topImg['image'] = 'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1533552327629&di=3a795b15e20b6d68617d891e749f2748&imgtype=0&src=http%3A%2F%2Fbos.pgzs.com%2Frbpiczy%2FWallpaper%2F2011%2F6%2F4%2Ff8dfd9c810d24883ac84698022424598-18.jpg';
        $topImg['type'] = 10;
        $topImg['is_show'] = 1;
        $addArr[] = $topImg;

        //简介
        $lcsInfo = Planner::getPlannerInfoById($lcsId);
        $briefIntro['service_id'] = $serviceId;
        $briefIntro['title'] = '简介';
        $briefIntro['content'] = $lcsInfo['summary'];
        $briefIntro['type'] = 20;
        $briefIntro['is_show'] = 1;
        $addArr[] = $briefIntro;

        //淘股策略
        $strategy['service_id'] = $serviceId;
        $strategy['title'] = '淘股策略';
        $strategy['content'] = '';
        $strategy['type'] = 30;
        $strategy['is_show'] = 1;
        $addArr[] = $strategy;

        //深度观点
        $viewpoint['service_id'] = $serviceId;
        $viewpoint['title'] = '深度观点';
        $viewpoint['content'] = '';
        $viewpoint['type'] = 40;
        $viewpoint['is_show'] = 1;
        $addArr[] = $viewpoint;

        foreach ($addArr as $v){
            $v['status'] = 1;
            Service::model()->createServiceInfoAudit($v);
            Service::model()->createServiceInfo($v);
        }
    }

    /**
     * 创建服务产品
     * @param $serviceId
     * @param $lcsId
     */
    private static function createServiceProduct($serviceId,$params)
    {
        if(!isset($params['id'])){
            $pkg = Package::model()->getLastPkgByPuid($params['p_uid']);
            $params['id'] = $pkg['id'];
        }
        $addArr['sp_type'] = 1;
        $addArr['service_id'] = $serviceId;
        $addArr['sp_content_id'] = $params['id'];
        $addArr['audit_status'] = 2;
        $addArr['staff_uid'] = '';
        $addArr['reason'] = '';
        Service::model()->createServiceProduct($addArr);
    }

    public static function editVipService($params)
    {
        //先删除服务信息
        Service::model()->deleteServiceInfoAudit("service_id=".$params['service_id']);

        //再添加服务信息
        $serviceInfo = json_decode($params['service_info'],true);
        if(empty($serviceInfo)){
            throw new RuntimeException('参数service_info传值有误',2);
        }

        foreach ($serviceInfo as $v){
            if($params['service_id'] != $v['service_id']){
                throw new \RuntimeException('参数有误',2);
            }
            Service::model()->createServiceInfoAudit($v);
        }
    }

    public static function Vipby_tag()
    {
        //查询vip服务
        $serviceProduct = Service::model()->getServiceProductAll();

        foreach ($serviceProduct as $v){
            if (!empty($v['sp_content_id'])){
                $user_pack_all =  PackageSubscription::model()->getPlannerPackages($v['sp_content_id']);
                if (!empty($user_pack_all)){
                    foreach ($user_pack_all as $val){
                        $vipSubscription = Service::model()->getVipSubscriptionInfo($val['uid'],$v['service_id']);
                        if (empty($vipSubscription)){
                            $vipData = array(
                                "service_id" => $v['service_id'],
                                "uid" => $val['uid'],
                                "end_time" => $val['end_time'],
                                "c_time" => date("Y-m-d H:i:s"),
                                "u_time" => date("Y-m-d H:i:s")
                            );
                            //创建vip
                            $counter = Service::model()->saveVipSubscription($vipData);
                        }else{

                            $vipData = array(
                                "end_time" => $val['end_time'],
                                "u_time" => date("Y-m-d H:i:s")
                            );
                            $service_id = $v['service_id'];
                            $uid = $val['uid'];
                            //修改vip
                            $counter = Service::model()->UpdateVipSubscription($vipData,"service_id=$service_id and uid=$uid");
                        }
                    }
                }
            }

        }
    }

    public static function Vipby(){
        //产品创建
        $serviceALl = Service::model()->getServiceALl('1=1');
        foreach ($serviceALl as $v){
            $serviceProduct = Service::model()->getServiceProduct($v['id']);
            if (empty($serviceProduct)){
                $addArr = [];
                $addArr['sp_type'] = 3;
                $addArr['service_id'] = $v['id'];
                $addArr['sp_content_id'] = '';
                $addArr['audit_status'] = 2;
                $addArr['staff_uid'] = '';
                $addArr['reason'] = '';
                Service::model()->createServiceProduct($addArr);
                $viewpoint = [];
                //深度观点
                $viewpoint['service_id'] = $v['id'];
                $viewpoint['title'] = 'VIP解盘';
                $viewpoint['content'] = '每个交易日，向VIP用户实时传递行情点评，预判大盘和个股走势，解读盘中动态。';
                $viewpoint['type'] = 41;
                $viewpoint['is_show'] = 1;
                Service::model()->createServiceInfo($viewpoint);
            }
        }
    }
    /**
     * 获取用户的vip权限
     * @param p_uid int
     * @param uid int
     * @return userService int
     */
    public static function vipUserService($p_uid,$uid){
        // $user_info = User::model()->getUserInfo();
        // if($p_uid == $user_info['s_uid']){
        //     return 1;
        // }

        $serviceInfo = Service::model()->getIsService($p_uid);
        if(!empty($serviceInfo)){
            $sp_content_id = '';
            foreach ($serviceInfo as $v){
                if ($v['sp_type']==1){
                    $sp_content_id =$v['sp_content_id'];
                }
                if ($v['sp_type']==2){
                    $isTaotu = 1;
                }
            }
            $info = PackageSubscription::model()->getPackageSubscriptionInfo($uid,[$sp_content_id]);
        }
        $cmn['user_service'] = 0;
        if(!empty($info[$sp_content_id]) && !empty($sp_content_id)){
            if(strtotime($info[$sp_content_id]['end_time']) < time()){
                $cmn['user_service'] = 2;
            }else{
                $cmn['user_service'] = 1;
            }
        }
        return $cmn['user_service'];
    }
}
