<?php
/**
 * 合并冲突帐号
 */
class MergeUser
{

    //任务代码
    const CRON_NO = 20181210;
    /**
     * 入口
     */
    public function process(){
        try{
            //退出时间 每次随机向后推30-60秒
            $stop_time = time()+rand(2,4)*15;
            $data_page = 99999999999;
            $ctype = "phone";
            while (true) {
                if(time()>$stop_time){
                    break;
                }
                ///先获取冲突的手机号或者微博
                $data = $this->getConflictAccount($data_page,$ctype);
                $data_page = $data;
                if(empty($data)){
                    echo "没有冲突用户1\n";
                    break;
                }
                ///根据冲突的手机号以及微博查找冲突用户并解决，需要考虑超过2个以上的冲突用户
                $user_page = 999999999999;
                while(true){
                    $user = $this->getConflictUser($ctype,$data,$user_page);
                    if(empty($user) || count($user)<2){
                        $data_page = $data_page - 1;
                        echo "没有冲突用户2\n";
                        break;
                    }
                    echo "*******************************************************************\n";
                    $user_page = $user[0]>$user[1]?$user[0]:$user[1];
                    ///判断这两个帐号是否可以合并
                    $if_merge = $this->judgeIfMerge($user[0],$user[1]);
                    if($if_merge){
                        $user_page = $user_page - 1;
                        continue;
                        ///获取两个帐号的主次
                        $account_info = $this->getMasterAccount($user[0],$user[1]);
                        $master = $account_info['master'];
                        $slave = $account_info['slave'];
                        ///提取两个帐号的公共属性，求同存异
                        $new_user_info = $this->getCommonAttribute($master,$slave);
                        $this->saveUserInfo("before",$master,$slave);
                        ///合并帐号
                        $this->mergeUserInfo($new_user_info,$master,$slave);
                        $this->saveUserInfo("after",$master,$slave);
                    }else{
                        $user_page = $user_page - 1;
                        continue;
                    }
                }
            }
        }catch (Exception $e){
            var_dump($e->getMessage());
            Common::model()->saveLog("合并帐号出错".$e->getMessage() , "error", "merge user");
        }
    }

    /*
     * 找到两个冲突的帐号
     * @return uid 
     */
    public function getConflictAccount($start_id,$type="phone"){
        $res = array();
        if($type=="phone"){
            $sql = "select phone,count(*) as total from lcs_user_index where phone!='' and phone<'".$start_id."' group by phone having total>1 order by phone desc limit 1;";
            $data = Yii::app()->lcs_r->createCommand($sql)->queryRow();
            return $data['phone'];
        }else if($type=="weibo"){
            $sql = "select s_uid,count(*) as total from lcs_user_index where s_uid!='' and s_uid<'".$start_id."' group by s_uid having total>1 order by s_uid desc limit 1;";
            $data = Yii::app()->lcs_r->createCommand($sql)->queryRow();
            return $data['s_uid'];
        }
        return $res;
    }

    /**
     * 根据冲突的手机号或者微博获取两个冲突帐号
     */
    public function getConflictUser($type,$data,$start_id){
        $res = array();
        if($type=="phone"){
            $sql = "select id from lcs_user_index where phone='".$data."' and id<='$start_id' order by id desc limit 2";
            $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            foreach($data as $item){
                $res[] = $item['id'];
            }
        }elseif($type=="weibo"){
            $sql = "select id from lcs_user_index where s_uid='".$data."' and id<='$start_id' order by id desc limit 2";
            $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            foreach($data as $item){
                $res[] = $item['id'];
            }
        }
        return $res;
    }

    /*
     * 判断是否可以合并
     * @param   uid $a 帐号A
     * @Param   uid $b 帐号B
     */
    public function judgeIfMerge($user_a,$user_b){
        $user_a_order = $this->getUserOrder($user_a);
        $user_b_order = $this->getUserOrder($user_b);

        if($user_a_order>0 && $user_b_order>0){
            echo "因为都有订单不能合并 a:".$user_a." b:".$user_b."\n";
            return false;
        }

        $user_a_sub = $this->getUserSub($user_a);
        $user_b_sub = $this->getUserSub($user_b);
        if($user_a_sub>0 && $user_b_sub>0){
            echo "因为用户订阅不能合并 a:".$user_a." b:".$user_b."\n";
            return false;
        }

        $user_a_info = $this->getUserInfo($user_a);
        $user_b_info = $this->getUserInfo($user_b);
        if(empty($user_a_info) || empty($user_b_info)){
            echo "因为用户为空不能合并 a:".$user_a." b:".$user_b."\n";
            return false;
        }

        if(!empty($user_a_info['phone']) && !empty($user_b_info['phone']) && $user_a_info['phone']!=$user_b_info['phone']){
            echo "因为phone不能合并 a:".$user_a." b:".$user_b."\n";
            return false;
        }

        if(!empty($user_a_info['s_uid']) && !empty($user_b_info['s_uid']) && $user_a_info['s_uid']!=$user_b_info['s_uid']){
            echo "因为s_uid不能合并 a:".$user_a." b:".$user_b."\n";
            return false;
        }

        if(!empty($user_a_info['wx_unionid']) && !empty($user_b_info['wx_unionid']) && $user_a_info['wx_unionid']!=$user_b_info['wx_unionid']){
            echo "因为wx_unionid不能合并 a:".$user_a." b:".$user_b."\n";
            return false;
        }

        if(!empty($user_a_info['wx_open_uid']) && !empty($user_b_info['wx_open_uid']) && $user_a_info['wx_open_uid']!=$user_b_info['wx_open_uid']){
            echo "因为wx_open_uid不能合并 a:".$user_a." b:".$user_b."\n";
            return false;
        }

        $user_a_index_info = $this->getUserIndex($user_a);
        $user_b_index_info = $this->getUserIndex($user_b);
        if(!empty($user_a_index_info['s_uid']) && !empty($user_b_index_info['s_uid'])){
            if($user_a_index_info['s_uid']!=$user_b_index_info['s_uid']){
                echo "因为user_index_s_uid不能合并 a:".$user_a." b:".$user_b."\n";
                return false;
            }
        }
        if(!empty($user_a_index_info['phone']) && !empty($user_b_index_info['phone'])){
            if($user_a_index_info['phone']!=$user_b_index_info['phone']){
                echo "因为user_index_phone不能合并 a:".$user_a." b:".$user_b."\n";
                return false;
            }
        }
        if(!empty($user_a_index_info['wx_unionid']) && !empty($user_b_index_info['wx_unionid'])){
            if($user_a_index_info['wx_unionid']!=$user_b_index_info['wx_unionid']){
                echo "因为wx_unionid不能合并 a:".$user_a." b:".$user_b."\n";
                return false;
            }
        }
        if(!empty($user_a_index_info['password']) && !empty($user_b_index_info['password'])){
            if($user_a_index_info['password']!=$user_b_index_info['password']){
                echo "因为密码不能合并 a:".$user_a." b:".$user_b."\n";
                return false;
            }
        }
        return true;
    }

    /**
     * 提取两个帐号的公共属性
     * @param   uid $a 帐号A
     * @param   uid $b 帐号B
     * @param   array   帐号属性
     */
    public function getCommonAttribute($master,$slave){
        $master_info = $this->getUserInfo($master);
        $slave_info = $this->getUserInfo($slave);
        $new_user_info = array();
        $field = array("uid","w_uid","s_uid","phone","name","name_u_time","gender","image","wb_name","wb_image","wx_unionid","wx_open_uid","wx_public_uid","wx_name","wx_image","source","ind_id","client_token","is_first_login","pact","ranking_lv","c_time","u_time","client_time","r_time","cert_id","wx_type");
        foreach($field as $item){
            $new_user_info[$item] = empty($master_info[$item])?$slave_info[$item]:$master_info[$item];
        }
        $new_user_info['status'] = 0;

        $master_index_info = $this->getUserIndex($master);
        $slave_index_info =$this->getUserIndex($slave);
        $new_user_index = array();
        $field = array("s_uid","w_uid","phone","name","wx_unionid","password","device_number","wx_type");
        foreach($field as $item){
            $new_user_index[$item] = empty($master_index_info[$item])?$slave_index_info[$item]:$master_index_info[$item];
        }
        return array("user_info"=>$new_user_info,"user_index"=>$new_user_index);
    }

    /**
     * 获取主帐号以及从帐号
     * @param   uid $a
     * @param   uid $b
     */
    public function getMasterAccount($user_a,$user_b){
        $user_a_order = $this->getUserOrder($user_a);
        $user_b_order = $this->getUserOrder($user_b);

        if($user_a_order>$user_b_order){
            return array("master"=>$user_a,"slave"=>$user_b);
        }elseif($user_a_order<$user_b_order){
            return array("master"=>$user_b,"slave"=>$user_a);
        }

        $user_a_sub = $this->getUserSub($user_a);
        $user_b_sub = $this->getUserSub($user_b);
        if($user_a_sub>$user_b_sub){
            return array("master"=>$user_a,"slave"=>$user_b);
        }elseif($user_a_sub<$user_b_sub){
            return array("master"=>$user_b,"slave"=>$user_a);
        }
        return array("master"=>$user_a,"slave"=>$user_b);
    }

    /**
     * 合并帐号
     * @param   uid $a 主帐号
     * @param   uid $b 从帐号
     * @param   array $data 公共属性
     */ 
    public function mergeUserInfo($new_user_info,$master,$slave){
        if(empty($new_user_info) || empty($master) || empty($slave)){
            echo "empty info\n";
            echo json_encode($new_user_info)."\n";
            echo $master;
            echo $slave;
            exit;
        }
        $db_w = Yii::app()->lcs_w;
        try{
            $transaction = $db_w->beginTransaction();

            $user_info = $new_user_info['user_info'];
            $user_index = $new_user_info['user_index'];
            $table_name = "lcs_user_".($master%10);
            $res = $db_w->createCommand()->update($table_name,$user_info,"uid=".$master);
            $res = $db_w->createCommand()->update("lcs_user_index",$user_index,"id=".$master);

            $table_name_slave = "lcs_user_".($slave%10);
            $res = $db_w->createCommand()->update($table_name_slave,array("status"=>-1),"uid=".$slave);
            $empty_index = array("s_uid"=>0,"w_uid"=>0,"phone"=>'',"name"=>'',"wx_unionid"=>'',"password"=>'',"device_number"=>'',"wx_type"=>'');
            $res = $db_w->createCommand()->update("lcs_user_index",$empty_index,"id=".$slave);
            $transaction->commit();
        }catch(Exception $e){
            $transaction->rollBack();
        }
    }

    /**
     * 根据用户uid获取详情
     */
    private function getUserInfo($uid){
        $table = "lcs_user_".($uid%10);
        $sql = "select * from ".$table." where uid='".$uid."'";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryRow();
        return $data;
    }

    /**
     * 获取用户订单
     */
    private function getUserOrder($uid){
        $sql = "select count(*) from lcs_orders where uid='".$uid."'";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        return $data;
    }
    
    /**
     * 获取用户订阅信息
     */
    private function getUserSub($uid){
        $sql = "select count(*) from lcs_set_subscription where uid='".$uid."'";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        return $data;
    }

    /**
     * 获取用户userIndex数据
     *
     */
    private function getUserIndex($uid){
        $sql = "select * from lcs_user_index where id='".$uid."'";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryRow();
        return $data;
    }

    private function saveUserInfo($type,$a,$b){
        $user_a_info = $this->getUserInfo($a);
        $user_b_info = $this->getUserInfo($b);
        $user_a_index = $this->getUserIndex($a);
        $user_b_index = $this->getUserIndex($b);
        $message = "";
        $message = "$type---$a------$b--------------------------------\n";
        $message .= "user_a_info:".json_encode($user_a_info)."\n";
        $message .= "user_b_info:".json_encode($user_b_info)."\n";
        $message .= "user_a_index:".json_encode($user_a_index)."\n";
        $message .= "user_b_index:".json_encode($user_b_index)."\n";
        $message .= "---------------------------------------------\n\n";
        echo $message;
        Common::model()->saveLog($message,"info","mergeUser");
    }
}
