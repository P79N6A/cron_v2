<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ImportMatchPlanner
 *
 * @author hailin3
 */
class ImportMatchPlanner {
    private $match_id = 0;
    public function handle($match_id){
        $this->match_id = $match_id;
        $file = fopen('/usr/home/finance/projects/cron_v2/protected/commands/planner/match_planner_csv.csv', 'r');
//        $file = fopen('/usr/home/hailin3/www/cron_v2/protected/commands/planner/match_planner_csv.csv','r');
        $planner_list = array();        
        $content = '';
        $i = 1;
        while ($line = fgetcsv($file)){    
//            print_r($line);die;
//            if(trim($line[0]) == '表格 1'){
//                continue;
//            }           
            if(empty($line[0])){
                continue;
            }
            $department = trim($line[0]);
            $name = trim($line[1]);
            $phone = trim($line[2]);
            $s_uid = trim($line[3]);
            $gender = trim($line[4]) == '男' ?  'm' : 'f';
            $cert_id = 1;
            $cert_number = trim($line[5]);
            $email = '';
            $location = trim($line[6]);                             
            $summary = trim($line[7]);
            $position_id = 2;
            $company_id = 93;
            $ins_data = array();
            $ins_data['ind_id'] = 1;
            $ins_data['s_uid'] = $s_uid;            
            $ins_data['staff_uid'] = ""; //TODO
            $ins_data['auth_id'] = 'LCS'.date('Ymd').rand(1000, 9999);;
            $ins_data['name'] = $name;
            $ins_data['real_name'] = $name;            
            $ins_data['gender'] = $gender;         
            $ins_data['location'] = $location;
            $ins_data['phone'] = $phone;            
            $ins_data['position_id'] = $position_id;
            $ins_data['company_id'] = $company_id;
            $ins_data['cert_id'] = $cert_id;
            $ins_data['cert_number'] = $cert_number;
            $ins_data['summary'] = $summary;            
            $ins_data['status'] = 0;
            $ins_data['c_time'] = date(DATE_ISO8601);
            $ins_data['u_time'] = date(DATE_ISO8601);

            $ins_data['email'] = $email;                        
            $ins_data['department'] = $department;                        
            $planner_list[] = array('p_uid'=>$s_uid,'name'=>$name);
            //判断是否已经存在
            $planner = Planner::model()->getPlannerById($s_uid);
            if(!empty($planner)){
                $content .= $i."、理财师已入驻：微博id：".$s_uid."名字：".$name."\n";
                $i++;
                continue;
            }                                    
            $content .= $i."添加新理财师:微博id：".$s_uid."名字：".$name."\n";
            $i++;
            
            Planner::model()->savePlanner($ins_data);                        
        }        
        file_put_contents("import_planner.txt", $content);
        $this->createPlan($planner_list);
    }
    
    public function hanleNanjing($match_id){
        $this->match_id = $match_id;
        $file = fopen('/usr/home/finance/projects/cron_v2/protected/commands/planner/nanjing_match.csv', 'r');
//        $file = fopen('/usr/home/hailin3/www/cron_v2/protected/commands/planner/nanjing_match.csv','r');
        $planner_list = array();        
        $content = '';
        $i = 1;
        while ($line = fgetcsv($file)){             
            if(trim($line[0]) == '姓名'){
                continue;
            }
            $department = mb_substr(trim($line[5]),3,  mb_strlen(trim($line[5])));
            $name = trim($line[0]);
            $phone = trim($line[1]);
            $s_uid = trim(trim(trim($line[2]),"\r"),"\n");
            $gender = trim($line[3]) == '男' ?  'm' : 'f';
            $cert_id = 1;
            $cert_number = trim($line[4]);
            $email = '';
            $location = trim($line[6]);                             
            $summary = trim($line[7]);
            $position_id = 2;
            $company_id = 66;
            $ins_data = array();
            $ins_data['ind_id'] = 1;
            $ins_data['s_uid'] = $s_uid;            
            $ins_data['staff_uid'] = ""; //TODO
            $ins_data['auth_id'] = 'LCS'.date('Ymd').rand(1000, 9999);;
            $ins_data['name'] = $name;
            $ins_data['real_name'] = $name;            
            $ins_data['gender'] = $gender;         
            $ins_data['image'] = 'http://tp2.sinaimg.cn/'.$s_uid.'/180/1';
            $ins_data['location'] = $location;
            $ins_data['phone'] = $phone;            
            $ins_data['position_id'] = $position_id;
            $ins_data['company_id'] = $company_id;
            $ins_data['cert_id'] = $cert_id;
            $ins_data['cert_number'] = $cert_number;
            $ins_data['summary'] = $summary;            
            $ins_data['status'] = 0;
            $ins_data['c_time'] = date(DATE_ISO8601);
            $ins_data['u_time'] = date(DATE_ISO8601);

            $ins_data['email'] = $email;                        
            $ins_data['department'] = $department;
            
            $planner_list[] = array('p_uid'=>$s_uid,'name'=>$name);
            //判断是否已经存在
            $planner = Planner::model()->getPlannerById($s_uid);
            if(!empty($planner)){
                $content .= $i."、理财师已入驻：微博id：".$s_uid."名字：".$name."\n";
                $i++;
                continue;
            }                                    
            $content .= $i."添加新理财师:微博id：".$s_uid."名字：".$name."\n";
            $i++;
            
            Planner::model()->savePlanner($ins_data);                        
        }        
        file_put_contents("import_planner.txt", $content);
        $this->createPlan($planner_list,'10002');
    }
    
    public function hanlePlanner(){            
        $department = '零售业务部';
        $name = '李丽';
        $phone = '13683282522';
        $s_uid = '2113318332';
        $gender = 'f';
        $cert_id = 1;
        $cert_number = 'S1500611030036';
        $email = '115148160@qq.com';
        $location = '北京市';
        $summary = '顺势而为';
        $position_id = 2;
        $company_id = 93;
        $ins_data = array();
        $ins_data['ind_id'] = 1;
        $ins_data['s_uid'] = $s_uid;            
        $ins_data['staff_uid'] = ""; //TODO
        $ins_data['auth_id'] = 'LCS'.date('Ymd').rand(1000, 9999);;
        $ins_data['name'] = $name;
        $ins_data['real_name'] = $name;            
        $ins_data['gender'] = $gender;         
        $ins_data['location'] = $location;
        $ins_data['phone'] = $phone;            
        $ins_data['position_id'] = $position_id;
        $ins_data['company_id'] = $company_id;
        $ins_data['cert_id'] = $cert_id;
        $ins_data['cert_number'] = $cert_number;
        $ins_data['summary'] = $summary;            
        $ins_data['status'] = 0;
        $ins_data['c_time'] = date(DATE_ISO8601);
        $ins_data['u_time'] = date(DATE_ISO8601);

        $ins_data['email'] = $email;                        
        $ins_data['department'] = $department;                        
        $planner_list[] = array('p_uid'=>$s_uid,'name'=>$name);
        //判断是否已经存在
        $planner = Planner::model()->getPlannerById($s_uid);
        if(!empty($planner)){
            echo "理财师已入驻：微博id：".$s_uid."名字：".$name."\n";            
            return;
        }                                    
        echo "添加新理财师:微博id：".$s_uid."名字：".$name."\n";        

        Planner::model()->savePlanner($ins_data);                                        
        
        $this->createPlan($planner_list);
    }
    
    public function createPlan($planner,$match_id='10001'){
        $this->match_id = $match_id;
        $pln_ids = array();
        $match_list = array();
        $now = date('Y-m-d H:i:s',time());
        $log = '';
        $i = 1;
        foreach ($planner as $p){
            $pln_name = $p['name'].'2016投顾大赛';
//            Yii::app()->lcs_w->createCommand("delete from lcs_plan_info where p_uid='{$p['p_uid']}' and name='{$pln_name}'")->execute();
            $item = array();
            $item['p_uid'] = $p['p_uid'];
            $item['name'] = $pln_name;
            $item['number'] = 1;
            $item['image'] = '141127/1806263434.jpeg';
            $item['summary'] = $p['name'].'2016投顾大赛参赛计划';
            $item['ind_id'] = 1;
            $item['subscription_price'] = 1000;
            $item['target_ror'] = 0.3;
            $item['performance_promise'] = 2;
            $item['invest_days'] = 60;
            $item['universe_type'] = 2;
            $item['stop_loss'] = '-0.2';
            $item['init_value'] = '1000000';
            $item['available_value'] = '1000000';
            $item['market_value'] = '1000000';
            $item['start_date'] = '2016-10-21';
            $item['end_date'] = '2016-12-20';
            $item['status'] = 3;
            $item['c_time'] = $now;
            $item['u_time'] = $now;
            $item['panic_buy_time'] = '2016-10-19';
            $item['max_follower_amt'] = '1000000';
            $item['curr_draft_step'] = 4;
            $pln_id = Plan::model()->savePlanInfo($item);
            $pln_ids['leave_'.$pln_id] = 100;
            $match_list[] = array('pln_id'=>$pln_id,'p_uid'=>$p['p_uid']);
            echo '添加计划：'.$pln_id."理财师id：".$p['name']."\n";
            $i++;
            if($pln_id){
                $panic_buy = array(
                    'relation_id'=>$pln_id,
                    'type'=>1,
                    'start_time'=>'2016-10-19',
                    'end_time'=>'2016-10-19',
                    'day_start'=>'10:00',
                    'day_end'=>'10:00',
                    'day_max'=>300,
                    'max'=>300,
                    'is_stop'=>'2016-10-19',
                    'c_time'=>$now,
                    'u_time'=>$now
                );
                Yii::app()->lcs_w->createCommand()->insert('lcs_panic_buy', $panic_buy);
            }
        }        
        $redis_key = MEM_PRE_KEY.'match_plan_prop_num_'.$this->match_id;
        Yii::app()->redis_w->hmset($redis_key,$pln_ids);
        $str = '';
        foreach ($match_list as $m){
            $str .= sprintf("('%s','%s','%s','%s'),",$m['p_uid'],  $this->match_id,$m['pln_id'],$now);
            if($this->match_id == '10001'){
                $str .= sprintf("('%s','%s','%s','%s'),",$m['p_uid'],10002,$m['pln_id'],$now);
            }
        }
        $str = trim($str,',');
        $sql = 'insert into lcs_planner_match (p_uid,match_id,pln_id,c_time) values '.$str;        
        Yii::app()->lcs_w->createCommand($sql)->execute();
    }
    
    public function importRedisPlanner(){
        $this->match_id = 10002;
        $p_uids = Yii::app()->redis_w->smembers('lcs_investment_adviser_sign_up_planner');
        if(empty($p_uids)){
            return;
        }
        $planner_list = Planner::model()->getPlannerById($p_uids);
        $sql = "select distinct p_uid from lcs_planner_match where status=0 and match_id in (10001,10002)";
        $exist = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        $p_list = array();
        foreach ($planner_list as $p){
            if($p['company_id'] == '92'){
                echo $p['s_uid'];
                continue;
            }
            if(in_array($p['p_uid'], $exist)){
                continue;
            }
            $p_list[] = array('p_uid'=>$p['p_uid'],'name'=>$p['name']);
        }
//        print_r($p_list);
        $this->createPlan($p_list,'10002');
    }
    
    public function fixDepartment(){
        $sql = "select id,s_uid,department from lcs_planner where company_id=93 and department like '%信达证券%'";
        $list = Yii::app()->lcs_w->createCommand($sql)->queryAll();
        foreach ($list as $item){
            $department = mb_substr($item['department'], 4,  mb_strlen($item['department']),'utf-8');
            $u_sql = "update lcs_planner set department='{$department}' where id='{$item['id']}' and s_uid='{$item['s_uid']}'";
            echo $u_sql."\n";
            Yii::app()->lcs_w->createCommand($u_sql)->execute();
        }
        
    }
    
    public function fixRepeat(){
        $sql = "select p_uid,count(*) as total from lcs_planner_match where match_id in (10001,10002) group by p_uid order by total desc";
        $list = Yii::app()->lcs_w->createCommand($sql)->queryAll();
        $s = array();
        foreach ($list as $i){
            if($i['total'] >= 2){
                $ids = null;
                $d_sql = "select pln_id from lcs_planner_match where p_uid='{$i['p_uid']}' and match_id=10002";
                $ids = Yii::app()->lcs_w->createCommand($d_sql)->queryColumn();
                if(empty($ids)){
                    $s[] = $i['p_uid'];
                    continue;
                }
                $delete_match_sql = "delete from lcs_planner_match where p_uid='{$i['p_uid']}' and match_id=10002";
                $delete_plan_sql = "delete from lcs_plan_info where pln_id in (".  implode(',', $ids).")";
                Yii::app()->lcs_w->createCommand($delete_match_sql)->execute();
                Yii::app()->lcs_w->createCommand($delete_plan_sql)->execute();
                echo $delete_match_sql."\n";
                echo $delete_plan_sql."\n";
            }
        }
        echo implode(',', $s);
    }
    
    public function checkLeave($matchid){
        $pln_ids = PlannerMatch::model()->getMatchPlan($matchid);
        if(empty($pln_ids)){
            return;
        }
        $pln_list = Plan::model()->getPlanInfoByIds($pln_ids);
        $fields = array();
        $redis_key = MEM_PRE_KEY.'match_plan_prop_num_'.$matchid;
        $c = '';
        foreach ($pln_ids as $pln_id){
            if(!isset($pln_list[$pln_id])){
                continue;
            }
            $fields = 'leave_'.$pln_id;
            $sum = Yii::app()->redis_w->hget($redis_key,$fields);
            $sub_sql = "select count(*) from lcs_plan_subscription where pln_id='{$pln_id}' and status>0";
            $count = Yii::app()->lcs_w->createCommand($sub_sql)->queryScalar();
            $c .= '计划id:'.$pln_id.' 理财师id：'.$pln_list[$pln_id]['p_uid'].' 计划名称:'.$pln_list[$pln_id]['name'].' 剩余体验券：'.$sum." 计划收益:".$pln_list[$pln_id]['curr_ror']." 实际持有：".$count."\n";
        }
        file_put_contents('match_coupon.txt', $c);
        
        
    }
    /**
     * 添加南京证券大赛，将信达证券加入到平台大赛
     */
    public function addMatch(){
        $p_sql = "select s_uid from lcs_planner where company_id=66";
        $p_uids = Yii::app()->lcs_w->createCommand($p_sql)->queryColumn();
        $nanjing_sql = "select p_uid,pln_id from lcs_planner_match where match_id=10002 and p_uid in (".  implode(',', $p_uids).")";
        $nanjing_list = Yii::app()->lcs_w->createCommand($nanjing_sql)->queryAll();
        $str = '';
        $now = date('Y-m-d H:i:s');
        foreach ($nanjing_list as $m){
            $str .= sprintf("('%s','%s','%s','%s'),",$m['p_uid'],'10003',$m['pln_id'],$now);
        }
        $sql = 'insert into lcs_planner_match (p_uid,match_id,pln_id,c_time) values '.$str;        
        $sql = trim($sql,',');
        echo $sql."\n";
        Yii::app()->lcs_w->createCommand($sql)->execute();
        
        $xinda_sql = "select p_uid,pln_id from lcs_planner_match where match_id=10001";
        $xinda_list = Yii::app()->lcs_w->createCommand($xinda_sql)->queryAll();
        $str = '';
        $now = date('Y-m-d H:i:s');
        foreach ($xinda_list as $m){
            $str .= sprintf("('%s','%s','%s','%s'),",$m['p_uid'],'10002',$m['pln_id'],$now);
        }
        $sql = 'insert into lcs_planner_match (p_uid,match_id,pln_id,c_time) values '.$str;        
        $sql = trim($sql,',');
        echo $sql;
        Yii::app()->lcs_w->createCommand($sql)->execute();
    }
       
}
