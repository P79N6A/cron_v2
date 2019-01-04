<?php

class Match extends CActiveRecord {

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    //邀请列表查询数据
    private static  $invited_list = 'phone_number,name,image,wechart_name,wechart_img,licaishi_u_type,licaishi_uid';
    //报名插入数据
    private static $sign_insert = "`match_id`,`licaishi_u_type`,`licaishi_uid`,`phone_number`,`name`,`image`,`parent_id`,`status`,
                                    `wechart_name`,`wechart_img`,`wechart_id`,`wechart_sign_time`,`sign_up_time`";

    private static $crop_color = ['#FF484A','#FF7854','#FF7854','#FFA627','#46D2E6','#55C1FF','#558EFF','#5568FF','#5568FF','#B255FF'];

    //用户参加大赛记录表
    public function getSignUpTable(){
        return TABLE_PREFIX . 'match_sign_up';
    }

    //大赛信息表
    public function getMatchInoTable(){
        return TABLE_PREFIX . 'match_info';
    }

    //战队表
    public function getMatchCorps(){
        return TABLE_PREFIX . 'match_corps';
    }

    //用户战队关系表
    public function getMatchCorpsRealtionTable(){
        return TABLE_PREFIX . 'match_corps_realtion';
    }

    //赛事报道
    public function getMatchEventReport(){
        return TABLE_PREFIX . 'match_report';
    }

    //获取邀请的总人数
    public function getInvitedNum($parent_id){
        $sql = 'select count(*) as num from '.$this->getSignUpTable(). ' where `parent_id`='.$parent_id;
        $res = Yii::app()->lcs_r->createCommand($sql)->queryRow();
        return $res;
    }

    //根据邀请类型获取邀请的人数
    public function getInvitedCount($type,$parent_id){
        $sql_status = '';
        switch ($type){
            case 'all':
                $sql_status = ' status!=-1 ';
                break;
            case 'invited':
                $sql_status = ' status=0 ';
                break;
            case 'sign_up':
                $sql_status = ' status=1 ';
                break;
            case 'in_match':
                $sql_status = ' status=2 ';
        }

        $sql = 'select count(*) as count from ' . $this->getSignUpTable() . ' where `parent_id`='. $parent_id .' and '.$sql_status;
        $number = Yii::app()->lcs_r->createCommand($sql)->queryRow();

        return $number;
    }

    //获取邀请列表
    public function getInvitedList($page=0,$num=10,$type='all',$parent_id){
        if($type == 'all'){
            $sql = "select ". self::$invited_list. " from " . $this->getSignUpTable() . ' where `parent_id`=' .$parent_id ." and `status`!=-1 order by c_time desc limit $page,$num ";
        }else if($type == 'sign_up'){
            $sql = "select ". self::$invited_list. " from " . $this->getSignUpTable() . ' where `parent_id`=' .$parent_id ." and `status`=1 order by c_time desc limit $page,$num ";
        }else{
            $sql = "select ". self::$invited_list. " from " . $this->getSignUpTable() . ' where `parent_id`=' .$parent_id . " and `status`=0 order by c_time desc limit $page,$num ";
        }

        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $res;
    }

    //获取用户报名状态
    public function getUserSignUpStatus($uid){
        $sql = 'select 1 from  ' . $this->getSignUpTable() . ' where licaishi_uid='.$uid;
        return Yii::app()->lcs_r->createCommand($sql)->queryRow();
    }

    //用户报名
    public function userSignUp($data){
        $sql = "insert into " . $this->getSignUpTable() . "(". self::$sign_insert .") values(:match_id,:licaishi_u_type,:licaishi_uid,:phone_number,:name,:image,:parent_id,:status,
                :wechart_name,:wechart_img,:wechart_id,:wechart_sign_time,:sign_up_time)";
        $cmd = Yii::app()->licaishi_w->createCommand($sql);
        $cmd->bindParam(':match_id',$data['match_id'],PDO::PARAM_INT);
        $cmd->bindParam(':licaishi_u_type',$data['licaishi_u_type'],PDO::PARAM_STR);
        $cmd->bindParam(':licaishi_uid',$data['licaishi_uid'],PDO::PARAM_STR);
        $cmd->bindParam(':phone_number',$data['phone'],PDO::PARAM_STR);
        $cmd->bindParam(':name',$data['name'],PDO::PARAM_STR);
        $cmd->bindParam(':image',$data['image'],PDO::PARAM_STR);
        $cmd->bindParam(':parent_id',$data['parent_id'],PDO::PARAM_STR);
        $cmd->bindParam(':status',$data['status'],PDO::PARAM_INT);
        $cmd->bindParam(':wechart_name',$data['wechart_name'],PDO::PARAM_STR);
        $cmd->bindParam(':wechart_img',$data['wechart_img'],PDO::PARAM_STR);
        $cmd->bindParam(':wechart_id',$data['wechart_id'],PDO::PARAM_STR);
        $cmd->bindParam(':wechart_sign_time',$data['wechart_sign_time'],PDO::PARAM_STR);
        $cmd->bindParam(':sign_up_time',$data['sign_up_time'],PDO::PARAM_STR);

        try{
            $res = $cmd->execute();
        }catch (Exception $e){
            return false;
        }

        return $res;
    }

    //获取大赛信息
    public function getMatchInfo($match_id=1){
        $sql = 'select id,name,summary,match_rule,sign_time,sign_end_time,play_start_time,play_end_time from ' . $this->getMatchInoTable() . ' where id='.$match_id.' and status=0';
        return Yii::app()->lcs_r->createCommand($sql)->queryRow();
    }

    //检查用户是否报名
    public function checkUserExist($open_id){
        $sql = 'select 1 from ' .$this->getSignUpTable() . ' where wechart_id ='.$open_id;
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }

    //更新用户报名信息
    public function updateSignByOpenId($openId,$data)
    {
        $sql = "update " . $this->getSignUpTable() . " set `match_id`=:match_id,`licaishi_u_type`=:licaishi_u_type,`licaishi_uid`=:licaishi_uid,`licaishi_suid`=:licaishi_suid,`phone_number`=:phone_number,`name`=:name,`image`=:image,`parent_id`=:parent_id,`status`=:status,
                `wechart_name`=:wechart_name,`wechart_img`=:wechart_img,`sign_up_time`=:sign_up_time where `wechart_id`=$openId";
        $cmd = Yii::app()->licaishi_w->createCommand($sql);
        $cmd->bindParam(':match_id', $data['match_id'], PDO::PARAM_INT);
        $cmd->bindParam(':licaishi_u_type', $data['licaishi_u_type'], PDO::PARAM_STR);
        $cmd->bindParam(':licaishi_uid', $data['licaishi_uid'], PDO::PARAM_STR);
        $cmd->bindParam(':licaishi_suid', $data['licaishi_suid'], PDO::PARAM_STR);
        $cmd->bindParam(':phone_number', $data['phone'], PDO::PARAM_INT);
        $cmd->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $cmd->bindParam(':image',$data['image'],PDO::PARAM_STR);
        $cmd->bindParam(':parent_id',$data['parent_id'],PDO::PARAM_STR);
        $cmd->bindParam(':status',$data['status'],PDO::PARAM_INT);
        $cmd->bindParam(':wechart_name', $data['wechart_name'], PDO::PARAM_INT);
        $cmd->bindParam(':wechart_img', $data['wechart_img'], PDO::PARAM_INT);
        $cmd->bindParam(':sign_up_time', $data['sign_up_time'], PDO::PARAM_INT);
        try {
            $res = $cmd->execute();
        }catch (Exception $e){
            return false;
        }
        return $res;
    }
    /**
     * 根据战队id返回战队uids
     *
     * @param $planner_id
     * @return mixed
     */
    public function getPlannerCorpsUids($planner_id){
        $sql = "select * from " . $this->getMatchCorpsRealtionTable() . " where p_uid=".$planner_id;
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }

    /**
     * 根据用户id返回战队信息
     *
     * @param $uid
     * @return mixed
     */
    public function getCorpsInfoByUids($uids){
        $uids = implode(",",$uids);
        $sql = "select n.p_uid,uid,planner_name,color from lcs_match_corps_realtion as n left join lcs_match_corps as s on n.p_uid=s.p_uid where uid in (".$uids.")";
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }
    /**
     * 根据用户id返回战队信息
     *
     * @param $uid
     * @return mixed
     */
    public function getCorpsInfoByUid($uid){
        $sql = "select n.p_uid,uid,planner_name,color from lcs_match_corps_realtion as n left join lcs_match_corps as s on n.p_uid=s.p_uid where uid =".$uid;
        return Yii::app()->lcs_r->createCommand($sql)->queryRow();
    }

    //随机获取战队颜色
    private function getRandColor(){
        $rand_number = rand(0,9);
        return self::$crop_color[$rand_number];
    }

    public function saveUserCrop($p_uid,$uid,$planner_name){
        //随机获取战队颜色
        $cmd_r = Yii::app()->lcs_r;
        $cmd_w = Yii::app()->lcs_w;
        $color = $this->getRandColor();
        $now_time = date('Y-m-d H:i:s');
        $sql = 'select id from '. $this->getMatchCorps() . ' where p_uid='.$p_uid;
        $is_exist = $cmd_r->createCommand($sql)->queryAll();
        //战队存在时,只需要插入战队关系表中
        if($is_exist){
            $sql_in = "insert into " .$this->getMatchCorpsRealtionTable() . " (`p_uid`,`uid`,`c_time`,`u_time`) values('$p_uid','$uid','$now_time','$now_time')";
            try{
                $res = Yii::app()->$cmd_w->createCommand($sql_in)->execute();
            }catch (Exception $e){
                return false;
            }
            return $res;
        }else{
            $transaction = $cmd_w->beginTransaction();
            $sql_crops = "insert into " . $this->getMatchCorps() . "(`p_uid`,`planner_name`,`color`,`c_time`,`u_time`,`type`,`status`) values('$p_uid','$planner_name'.'$color','$now_time','$now_time','0','0')";
            $sql_crops_real = "insert into " .$this->getMatchCorpsRealtionTable() . " (`p_uid`,`uid`,`c_time`,`u_time`) values('$p_uid','$uid','$now_time','$now_time')";
            try{
                $cmd_w->execute($sql_crops);
                $cmd_w->execute($sql_crops_real);
                $transaction->commit();
                return true;
            }catch (Exception $e){
                $transaction->rollback();
                return false;
            }
        }
    }
    //赛事报道
    public function getEventReport(){
        $sql = "select `id`,`c_time`,`v_id`,`is_top` from ".$this->getMatchEventReport()." where status=0;";
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }
    //获取感兴趣
    public function getInterested($c_time){
        echo "开始时间:".$c_time."\r\n";
        $sql = "select parent_id,count(parent_id) as count from licaishi.lcs_match_sign_up where status=0 and c_time>='".$c_time."' group by parent_id";
        echo "执行sql:\r\n".$sql."\r\n";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $data;
    }
    //获取奖金数及邀请人数及邀请总人数
    public function getSignUpPeople($c_time){
        echo "开始时间:".$c_time."\r\n";
        $sql = "select parent_id,count,money from (select parent_id,count(*) as count from lcs_match_sign_up where status in (1,2) and c_time >= '$c_time' group by parent_id)table1 LEFT JOIN (select match_uid,sum(money) as money from lcs_match_income_list where c_time >= '$c_time' group by match_uid)table2 ON table1.parent_id = table2.match_uid;";
        echo "执行sql:\r\n".$sql."\r\n";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        //过滤如果奖金为null将不会推送
        foreach ($data as $key=>$val){
            if(empty($val['money'])){
                unset($data[$key]);
            }
        }
        return $data;
    }
    //获取参赛人数
    public function matchTradePush(){
        $c_time = date("Y-m-d 00:00:00");
        echo "开始时间".$c_time."\r\n";
        $param = array(
            'debug'=>1,
            'active_id'=>2,
        );
        $url = "http://stock-trade.sinalicaishi.com.cn/stock_trade/api/getTodayTransactions";
        $response = Yii::app()->curl->get($url,$param);
        $data = json_decode($response,true);
        echo "接口请求返回:".$response;
        $trade_user = [];
        if($data['code'] == 0){
            $trade_user = $data['data'];
        }
        if(empty($trade_user)){
            return [];
        }

        //$sql = "select parent_id,count(parent_id) as count from lcs_match_sign_up as A inner join (select uid from lcs_trade_transactions as a inner join lcs_trade_account as b on a.account_id=b.id where a.c_time>='".$c_time."' group by account_id)M on A.licaishi_uid=M.uid group by parent_id;";
        $sql = "select parent_id,count(parent_id) as count from lcs_match_sign_up where licaishi_uid in (".implode(',',$trade_user).") group by parent_id;";
        echo "执行sql:\r\n".$sql."\r\n";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $data;
    }
    //推送奖金
    public function moneyPush(){
        $c_time = date("Y-m-d 00:00:00");
        $sql = "select match_uid,sum(money) as money from lcs_match_income_list where type in (3,4) AND `c_time`>='".$c_time."' group by match_uid";
        echo "执行sql:\r\n".$sql."\r\n";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $data;
    }


    //获取没有分配战队的用户进行处理
    public function syncUserCorps(){
        $sql = "select licaishi_uid from lcs_match_sign_up where trade_id='' and status in (1,2)";
        $user_info = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $user_info;
    }

    public function disCorps($uid){
        if(!$uid)
            return false;
        $sql = "select id from lcs_match_corps_realtion where uid='$uid'";
        if(!(Yii::app()->lcs_r->createCommand($sql)->queryRow())){
            $s_uid = $this->getPlannerId($uid);
            $this->saveUserCorp($s_uid,$uid,1);
        }else{
            echo $uid."用户已经分配战队\r\n";
        }
    }

    public function updateCorps($uid){
        if(!$uid)
            return false;

        $s_uid = $this->getPlannerId($uid);
        $this->saveUserCorp($s_uid,$uid,1);
    }

    public function getPlannerId($uid){
        //进行第一步判断
        $p_uid = '';
        $planner_info = $this->getPlannerTypeInCorp($uid,1,1);
        if(!$planner_info || ($planner_info && !isset($planner_info['s_uid']))){
            //进行第二步判断
            $corp_info = $this->getPlannerTypeInCorp($uid,2,1);
            if(!$corp_info || ($corp_info && !isset($corp_info['s_uid']))){
                //进行第三步判断
                $all_corps = Yii::app()->lcs_r->createCommand("select p_uid,planner_name,color from lcs_match_corps where status=0 and type=1 and fr=1")->queryAll();
                if($all_corps && is_array($all_corps)){
                    $count = count($all_corps) - 1;
                    $rand_num = rand(0,$count);
                    $p_uid = $all_corps[$rand_num]['p_uid'];
                }
            }else{
                $p_uid = $corp_info['s_uid'];
            }
        }else{
            $p_uid = $planner_info['s_uid'];
        }

        return $p_uid;
    }

    //获取用户关联的理财师是否存在于后台配置的战队列表
    //$type=1时,执行下面第一步,否则执行第二步
    //1.获取最近一次支付成功订单的理财师是否在后台配置的战队列表,成功返回老师p_uid,否则返回false
    //2.获取最近一次关注理财师是否在后台配置的战队列表,成功返回老师p_uid,否则返回false
    public function getPlannerTypeInCorp($uid,$type,$fr){
        if($type == 1){
            $sql = "select p_uid,(select p_uid from lcs_match_corps where status=0 and type=1 and fr='$fr' and p_uid=lcs_orders.p_uid) as s_uid from lcs_orders where uid=". intval($uid)." and status in (2,3,4) and type in (12,21,22,31,32,61,62,63,64,65,66,91) order by c_time desc limit 1";

        }else{
            $sql = "select p_uid,(select p_uid from lcs_match_corps where status=0 and type=1 and fr='$fr' and p_uid=lcs_attention.p_uid) as s_uid from lcs_attention where uid=". intval($uid)."  order by c_time desc limit 1";
        }

        $types = Yii::app()->lcs_r->createCommand($sql)->queryRow();
        return $types;

    }

    //保存用户战队信息
    public function saveUserCorp($p_uid,$uid,$fr,$u_type=1){
        //随机获取战队颜色
        $cmd_r = Yii::app()->lcs_r;
        $cmd_w = Yii::app()->lcs_w;

        $now_time = date('Y-m-d H:i:s');
        $color = $this->getRandColor();
        //查询战队是否存在
        $sql = "select id from ". $this->getMatchCorps() . " where p_uid='$p_uid' and fr='$fr'";
        //查询用户是否关注理财师
        $sql_attention = "select id from lcs_attention where uid='$uid' and p_uid='$p_uid'";
        //插入用户战队
        $sql_in = "insert into " .$this->getMatchCorpsRealtionTable() . " (`p_uid`,`uid`,`fr`,`c_time`,`u_time`) values('$p_uid','$uid','$fr','$now_time','$now_time')";
        //未关注理财师,进行关注操作
        $sql_in_att = "insert into lcs_attention (`uid`,`p_uid`,`c_time`) values('$uid','$p_uid','$now_time')";
        //获取理财师圈子信息
        $sql_circle = "select id from lcs_circle where type=0 and p_uid='$p_uid'";
        //插入理财师的圈子
        $is_att = $cmd_r->createCommand($sql_attention)->queryAll();
        $is_exist = $cmd_r->createCommand($sql)->queryAll();
        $circle_info = $cmd_r->createCommand($sql_circle)->queryRow();
        $circle_id = isset($circle_info['id']) ? $circle_info['id'] : '0';
        $user_id = $uid;
        $sql_join = "select id from lcs_circle_user where u_type=$u_type and uid=$user_id and circle_id=$circle_id";
        $circle_exist = Yii::app()->lcs_r->createCommand($sql_join)->queryAll();
        $sql_user_circle = "insert into lcs_circle_user (`u_type`,`uid`,`circle_id`,`c_time`) values('$u_type','$user_id','$circle_id','$now_time')";

        //战队存在且已经关注理财师
        if($is_exist && $is_att){
            try{
                $res = $cmd_w->createCommand($sql_in)->execute();
            }catch (Exception $e){
                Common::model()->saveLog("同步分配战队".$e->getMessage(),"error","update_user_corps");
                return false;
            }
            return $res;
        }else if($is_exist && !$is_att){//战队存在且没有关注理财师
            $transaction = $cmd_w->beginTransaction();
            try{
                $cmd_w->createCommand($sql_in_att)->execute();
                $cmd_w->createCommand($sql_in)->execute();
                if(!$circle_exist){
                    $cmd_w->createCommand($sql_user_circle)->execute();
                }
                $transaction->commit();
                return true;
            }catch (Exception $e){
                $transaction->rollBack();
                Common::model()->saveLog("同步分配战队".$e->getMessage(),"error","update_user_corps");
                return false;
            }
        }else if(!$is_exist){ //战队不存在
            $sql_planner = "select name from lcs_planner where s_uid='$p_uid'";
            $planner_name = $cmd_r->createCommand($sql_planner)->queryRow();
            $planner_name = isset($planner_name['name']) ? $planner_name['name'] : '';
            $sql_crops = "insert into " . $this->getMatchCorps() . "(`p_uid`,`planner_name`,`color`,`c_time`,`u_time`,`type`,`status`) values('$p_uid','$planner_name','$color','$now_time','$now_time','0','0')";
            $transaction = $cmd_w->beginTransaction();
            try{
                if($is_att){    //已经关注理财师
                    $cmd_w->createCommand($sql_crops)->execute();
                    $cmd_w->createCommand($sql_in)->execute();
                }else{          //没有关注理财师
                    $cmd_w->createCommand($sql_in_att)->execute();
                    $cmd_w->createCommand($sql_crops)->execute();
                    $cmd_w->createCommand($sql_in)->execute();
                    if(!$circle_exist){
                        $cmd_w->createCommand($sql_user_circle)->execute();
                    }
                }
                $transaction->commit();
                return true;
            }catch (Exception $e){
                $transaction->rollBack();
                Common::model()->saveLog("同步分配战队".$e->getMessage(),"error","update_user_corps");
                return false;
            }
        }
    }

    //更新交易信息
    public function updateTrade($trade_id,$uid,$match_id){
        if(!$trade_id || !$uid || !$match_id)
            return false;

        $sql = "update " . $this->getSignUpTable() ." set trade_id='$trade_id' where licaishi_uid='$uid' and match_id='$match_id'";
        try{
            $res = Yii::app()->lcs_w->createCommand($sql)->execute();
            return $res;
        }catch (Exception $e){
            var_dump($e->getMessage());exit();
        }
    }

    /**
     *  增加大赛奖金记录
     * @param   int $match_id     大赛id
     * @param   int $match_uid    用户uid
     * @param   int $money        奖金
     * @param   int $type         类型
     * @param   str $reason       原因
     * @param   int $relation_id  相关id
     * @param   int $rank         排名
     */
    public function addMatchIncome($match_id,$match_uid,$money,$type,$reason,$relation_id,$rank){
        $sql = "select count(*) from lcs_match_income_list where match_id=$match_id and match_uid=$match_uid and money=$money and type=$type and reason='$reason' and relation_id='$relation_id' and rank='$rank'";
        $total = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        if($total==0){
            $sql = "insert into lcs_match_income_list(match_id,match_uid,money,type,reason,relation_id,rank,c_time,u_time) values('$match_id','$match_uid','$money','$type','$reason','$relation_id','$rank',localtime(),localtime())";
            $data = Yii::app()->lcs_w->createCommand($sql)->execute();
        }
    }

    /**
     * 根据账户的uid和account_id获取parent数据
     */
    public function getParentByUid($uid,$account_id){
        $sql = "select licaishi_uid,match_id,parent_id from lcs_match_sign_up where licaishi_uid='$uid' and trade_id='$account_id'";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryRow();
        return $data;
    }

    /**
     * 按parent_id分组查询邀请人数
    */
    public function getInviteNum($offset,$count){
        $sql = "select count(*) as num,parent_id from ".$this->getSignUpTable()." 
                where parent_id>0 
                and status in (1,2) 
                group by parent_id 
                order by num desc,parent_id desc 
                limit {$offset},{$count}";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $data;
    }

    public function getCorpsError(){
        $sql = "select uid from lcs_match_corps_realtion where p_uid=''";
        $user_info = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $user_info;
    }

    //用户报名信息
    public function getUserSignUp(){
        $sql = "select licaishi_uid from  ".$this->getSignUpTable()." where status in(1,2)";
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }

    public function getUserCorpsExist($uid){
        $sql = "select id from " . $this->getMatchCorpsRealtionTable(). " where uid='$uid'";
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }

    //用户真实手机号为空
    public function getUserPhoneReal(){
        $sql = "select licaishi_uid,phone_number from  ".$this->getSignUpTable()." where phone_real='' and status in(1,2)";
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }


    public function updatePhoneReal($licaishi_uid,$phone){
        if(!$licaishi_uid)
            return false;
        $sql = "update  " . $this->getSignUpTable() ."  set phone_real='$phone'  where licaishi_uid='$licaishi_uid'";
        return Yii::app()->lcs_w->createCommand($sql)->execute();
    }

    /**
     * @return array
     */
    public function inviteNum(){
        $sql = "select count(*) as num,parent_id from lcs_match_sign_up  where parent_id>0  and status in (1,2) group by parent_id order by num desc,parent_id desc ";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $data;
    }

    public function getTradeError(){
        $sql = "select licaishi_uid from  ".$this->getSignUpTable()." where trade_id=0 and status in(1,2)";
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }

    public function getNameAndImage($type){

        if($type == 1){
            $sql = "select licaishi_uid,phone_real from " . $this->getSignUpTable() . " where status in(1,2) and name like '%null%'";
        }elseif ($type == 2){
            $sql = "select licaishi_uid,wechat_img,wechat_name from " . $this->getSignUpTable() . " where status in(1,2) and image like '%null%' and wechat_img!='' ";
        }else{
            $sql = "select licaishi_uid from " . $this->getSignUpTable() . " where status in(1,2) and image like '%null%' ";
        }

        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }

    public function updateSignUser($type,$data,$licaishi_uid){
        if(!$licaishi_uid)
            return false;

        if($type==1){
            $sql = "update " . $this->getSignUpTable() . " set name='$data' where licaishi_uid='$licaishi_uid'";
        }else{
            $sql = "update " . $this->getSignUpTable() . " set image='$data' where licaishi_uid='$licaishi_uid'";
        }

        Yii::app()->lcs_w->createCommand($sql)->execute();
    }
}
