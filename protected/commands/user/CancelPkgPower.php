<?php
/**
 * 课程过期取消三个付费观点包权限
 */
class CancelPkgPower
{

    //任务代码
    const CRON_NO=14050 ;
    /**
     * 入口
     */
    public function handle(){
        try{
            $pkg_ids=array(10038,10036,9813);
            $uids=Package::model()->getSubscriptionUids($pkg_ids);
            $setUids=CoursePackage::model()->getSubscriptionUids();
            $collect_uids=Package::model()->getCollect($pkg_ids,4);
            $res=array();
            $res1=array();
            if(!empty($uids)){
                $res=array_diff($uids,$setUids);
            }
            if(!empty($collect_uids)){
                $res1=array_diff($collect_uids,$uids);
            }
            if(!empty($res)){
                foreach ($res as $v){
                    $data['end_time']=date('Y-m-d 00:00:00');
                    $data['u_time']=date('Y-m-d H:i:s');
                    foreach ($pkg_ids as $vv){
                        Package::model()->updatePkgSub($vv,$v,$data);
                        Package::model()->deleteCollect($vv,$v,4);
                    }
                }
            }
            if(!empty($res1)){
                foreach ($res1 as $v){
                    foreach ($pkg_ids as $vv){
                        Package::model()->deleteCollect($vv,$v,4);
                    }
                }
            }
        }catch (Exception $e){
            Common::model()->saveLog("取消付费观点包权限失败:" . $e->getMessage() , "error", "cancel_package_power");
        }

    }
}