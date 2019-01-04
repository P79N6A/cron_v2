<?php

/**
 * 同步观点包的用户订阅到vip服务中
 */
class UpdateSub{
    const CRON_NO = 20180822;
    public function __construct() {
    }
    
    public function process(){ 
        $end_time = time()+60;
        $page = 1;
        $num = 200;
        $start_time = date("Y-m-d H:i:s",strtotime("-2 minute"));
        Service::model()->updatePkg();
        while($end_time>time()){
            $pkg_sub = Service::model()->getPkgSub($start_time,$page,$num);
            if(empty($pkg_sub)){
                sleep(2);
                continue;
            }

            $pkg_ids = array();
            foreach($pkg_sub as $item){
                $pkg_ids[] = $item['pkg_id'];
            }
            $sp = Service::model()->getVipPackage($pkg_ids);
            if(!empty($sp)){
                foreach($pkg_sub as $item){
                    if(isset($sp[$item['pkg_id']])){
                        Service::model()->updateUserServiceSub($item['uid'],$sp[$item['pkg_id']],$item['end_time']);
                    }
                }
            }
            $page = $page + 1;
        }
    }
}
