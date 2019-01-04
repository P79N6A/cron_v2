<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ImportWanghongAction
 *
 * @author hailin3
 */
class ImportWanghong {
    //put your code here
    public function handle(){        
        $cert_id = 15;
        $position_id = 27;
        $cert_number = 1231231;
        $company_id = 945;
        $department = '新浪特约';
        $tags = '创业';
        // $data = array(
        //     array('s_uid'=>'579900989','name'=>'T+胡亚伟','phone'=>'18217002470','gender'=>'m','summary'=>'利用量价取牛股'),
        //     array('s_uid'=>'1453286222','name'=>'孙怀青','phone'=>'18910797116','gender'=>'m','summary'=>'实时解盘，对当下行情的深度分析，消息面解读，个人投资经验分享。'),
        // );
        $file = fopen('/usr/home/finance/projects/cron_v2/protected/commands/planner/keji.csv', 'r');
        // $file = fopen('/usr/home/hailin3/www/cron_v2/protected/commands/planner/keji.csv', 'r');        
        $power_sql = "insert into lcs_planner_power (p_uid,video,c_time) values ";
        $config_sql = "insert into lcs_planner_live_config (s_uid,limit_month,image_limit_month,summary,auth_type) values ";
        $now = date('Y-m-d H:i:s');
        $power_values = '';
        $config_values = '';
        // foreach ($data as $line){
        while ($line = fgetcsv($file)){            
            $s_uid = trim($line[0]);
            $name = trim($line[1]);
            $phone = trim($line[2]);
            $gender = trim($line[3]) == '男' ? 'm' : 'f';
            $location = trim($line[4]);
            $summary = trim($line[5]);
            if(empty($s_uid) || empty($name) || empty($phone) || empty($gender) || empty($summary)){
                echo "缺少信息：".$s_uid."||".$name."\n";
                continue;
            }
            $ins_data = array();
            $ins_data['ind_id'] = 1;
            $ins_data['s_uid'] = $s_uid;  
            $ins_data['image'] = 'http://tp2.sinaimg.cn/'.$s_uid.'/180/1';
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
            $ins_data['tags'] = $tags;            
            $ins_data['department'] = $department;                        
            
            //判断是否已经存在
            $planner = Planner::model()->getPlannerById($s_uid);
            if(!empty($planner)){
                echo "理财师已入驻：微博id：".$s_uid."名字：".$name."\n";                
                continue;
            }                                    
            echo "添加新理财师:微博id：".$s_uid."名字：".$name."\n";                        
            Planner::model()->savePlanner($ins_data);
            $power_values .= sprintf("('%s','1','%s'),",$s_uid,$now);
            $config_values .= sprintf("('%s','20','20','科技创业','6'),",$s_uid);             
        }
        $config_sql .= trim($config_values,',');
        $power_sql .= trim($power_values,',');
        echo $power_sql."\n";
        echo $config_sql."\n";
        Yii::app()->lcs_w->createCommand($power_sql)->execute();
        Yii::app()->lcs_w->createCommand($config_sql)->execute();
    }
}
