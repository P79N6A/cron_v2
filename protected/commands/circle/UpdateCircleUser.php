<?php

class UpdateCircleUser{

    const CRON_NO = 10002;
    private $vip_planner = array('1750914764','1813592103','1878259823','6020458946','6385751728','3305759713','2827205010','1576291842','3221307142','5295669143','1657765690','1014989264','6283858385','1378170044','1967527352','6257090875','2005350035','3215877627','1603147504','5436868582','5228859324','3208007370','3097582345','1609469470','6341594872','1239417764','5308069788','3799154545','1578031122','6412513679'); ///拥有付费圈子的理财师

    public function __construct(){

    }

    public function handle(){
        $this->UpdateCircleUser();
        $this->InitPayCircle();
    }

    /**
     * 初始化付费圈子
     */
    public function InitPayCircle(){
        $planner_list = Planner::model()->getPlannerById($this->vip_planner);
        $sql = "select id,p_uid from lcs_circle where title like '%的私密圈子'";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        foreach($data as $item){
            if(isset($planner_list[$item['p_uid']])){
                $title = $planner_list[$item['p_uid']]['name']."VIP俱乐部";
                $circle_id = $item['id'];
                if(!empty($circle_id)){
                    $sql = "update lcs_circle set title='$title' where id='$circle_id'";
                    var_dump($sql);
                    continue;
                    Yii::app()->lcs_w->createCommand($sql)->execute();
                }
            }
        }

        $data = Circle::model()->getCircleIdByPuid($this->vip_planner,6001);
        foreach($this->vip_planner as $p_uid){
            if(!isset($data[$p_uid])){
                ///该理财师不存在圈子
                Circle::model()->createCircle($p_uid,"6001");
            }
        }
    }

    /**
     * 新增或者修改圈子用户
     */
    public function UpdateCircleUser(){
        try{
            $planner_circle_ids = Circle::model()->getCircleIdByPuid($this->vip_planner,"6001");
            foreach($planner_circle_ids as $p_uid=>$circle_id){
                $pkg_ids = Package::model()->getPackageIdsByPuid($p_uid,1);
                $exist_user = Circle::model()->getCircleUser($circle_id,1);
                $pay_user = Circle::model()->getPayCircleSubUser($circle_id,1);

                $uids = array(21987603,20727521,172,21908143,23348589);
                foreach($pkg_ids as $pkg_id){
                    $temp_uid = Package::model()->getSubscriptionUid($pkg_id);
                    $uids = array_merge($uids,$temp_uid);
                }

                ///剔除过期用户
                foreach($exist_user as $uid){
                    if(!in_array($uid,$uids)){
                        $data = array();
                        $data['uid'] = $uid;
                        $data['u_type'] = 1;
                        $data['circle_id'] = $circle_id;
                        $data['service_status'] = 2;
                        $data['end_time'] = date("Y-m-d H:i:s");
                        $data['u_time'] = date("Y-m-d H:i:s");
                        Circle::model()->deleteCircleUser($circle_id,$uid,$data);
                    }
                }
                ///新增订阅用户
                foreach($uids as $uid){
                    if(!in_array($uid,$exist_user)){
                        $data = array();
                        $data['uid'] = $uid;
                        $data['u_type'] = 1;
                        $data['circle_id'] = $circle_id;
                        $data['service_status'] = 1;
                        $data['c_time'] = date("Y-m-d H:i:s");
                        $data['u_time'] = date("Y-m-d H:i:s");
                        $data['end_time'] = date("Y-m-d H:i:s",strtotime("+1 year"));
                        Circle::model()->addCircleUser($data);
                    }
                }

                $exist_user = Circle::model()->getCircleUser($circle_id,1);
                ///剔除过期付费用户
                foreach($pay_user as $uid){
                    if(!in_array($uid,$exist_user)){
                        Circle::model()->updatePayCircleSubscription($circle_id,$uid,date("Y-m-d H:i:s"));
                    }
                }
                ///新增订阅付费用户
                foreach($exist_user as $uid){
                    if(!in_array($uid,$pay_user)){
                        Circle::model()->updatePayCircleSubscription($circle_id,$uid,date("Y-m-d H:i:s",strtotime("+1 year")));
                    }
                }
            }
        }catch(Exception $e){
            var_dump($e->getMessage());
        }
    }

}
?>
