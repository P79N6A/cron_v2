<?php
/**
 * 统计理财师的影响力
 *  1.为了综合评价各位理财师在新浪理财师平台的影响力，以便对理财师进行差异化服务和管理，基于理财师在本平台发布观点、回答问题、发行计划等核心活动的事实及其反响，制定能够评价理财师影响力的综合指标。
 *  2.初步认定影响理财师影响力的因素包括以下几个方面：
    （1）计划: 购买计划 , 参与计划说说 , 关注计划
    （2）观点: 关注观点包, 购买观点包, 购买单条观点, 免费解锁单条观点, 在观点中发布说说
    （3）问答: 付费问答提问, 免费问答提问, 付费问答解锁, 免费问答解锁
    （4）其他: 添加私人理财师,（理财师）发生违规行为, 其他相关指标

 * User: zwg
 * Date: 2015/5/19
 * Time: 16:51
 */

class StatInfluence {

    const CRON_NO = 1002; //任务代码
    const DECAY_PLAN_DAYS = 20;   //计划衰减天数
    const STAT_DAYS = 5;//统计日期

    const PLAN_SUB_SCORE = 400; //购买计划
    const PLAN_ATTENTION_SCORE = 10; //关注计划
    const PLAN_TALK_SCORE = 40; //参与计划说说
    const PACKAGE_SUB_SCORE = 20; //购买观点包
    const PACKAGE_ATTENTION_SCORE = 2; //关注观点包
    const VIEW_PAY_SCORE = 20; //购买单条观点
    const VIEW_FREE_SCORE = 2; //免费解锁单条观点
    const VIEW_TALK_SCORE = 2; //在观点中发布说说
    const ASK_PAY_SCORE = 20; //付费问答提问
    const ASK_FREE_SCORE = 2; //免费问答提问
    const ASK_UNLOCK_PAY_SCORE = 15; //付费问答解锁
    const ASK_UNLOCK_FREE_SCORE = 2; //免费问答解锁
    const PLANNER_ATTENTION_SCORE = 5; //添加私人理财师
    const PLANNER_VIOLATION_SCORE = 0; //理财师违规

    private $stat_day_rate = array(0=>0.44,1=>0.22,2=>0.14,3=>0.11,4=>0.09);

    private $stat_date = '';

    public function __construct($stat_date=''){
        if(empty($stat_date)){
            $this->stat_date = date('Y-m-d');
        }else{
            $this->stat_date = $stat_date;
        }
    }



    public function statData($flag=0){
        try{
            $start_time = CommonUtils::getMillisecond();
            //计划的影响力指标
            if(($flag & 1) == 1){
                $m1 = memory_get_usage();
                $this->statPlan($this->stat_date);
                $stat_times['plan_mem'] = memory_get_usage()- $m1;
            }
            $plan_time = CommonUtils::getMillisecond();
            $stat_times['plan'] = $plan_time - $start_time;
            //问答
            if(($flag & 2) == 2) {
                $m1 = memory_get_usage();
                $this->statAsk($this->stat_date);
                $stat_times['ask_mem'] = memory_get_usage()- $m1;
            }
            $ask_time = CommonUtils::getMillisecond();
            $stat_times['ask'] = $ask_time - $plan_time;
            //观点
            if(($flag & 4) == 4) {
                $m1 = memory_get_usage();
                $this->statView($this->stat_date);
                $stat_times['view_mem'] = memory_get_usage()- $m1;
            }
            $view_time = CommonUtils::getMillisecond();
            $stat_times['view'] = $view_time - $ask_time;
            //观点包
            if(($flag & 8) == 8) {
                $m1 = memory_get_usage();
                $this->statPackage($this->stat_date);
                $stat_times['package_mem'] = memory_get_usage()- $m1;
            }
            $package_time = CommonUtils::getMillisecond();
            $stat_times['package'] = $package_time - $view_time;
            //理财师
            if(($flag & 16) == 16) {
                $m1 = memory_get_usage();
                $this->statPlanner($this->stat_date);
                $stat_times['planner_mem'] = memory_get_usage()- $m1;
            }
            $planner_time = CommonUtils::getMillisecond();
            $stat_times['planner'] = $planner_time - $package_time;

            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "统计日期:".$this->stat_date." 消耗时间:".json_encode($stat_times));
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }

    /**
     * 统计活跃得分
     * @throws LcsException
     */
    public function statScore(){
        try{
            //获取有活跃信息的理财师uid
            $start_date = date('Y-m-d', strtotime($this->stat_date) - (self::STAT_DAYS-1)*24*3600);
            $end_date = date('Y-m-d', strtotime($this->stat_date) + 24 * 3600);
            $planners = Planner::model()->getPlannerIDOfEvaluationByStatDate($start_date, $end_date);
            /*$planners = array(
                array('p_uid'=>'1239417764'), //裴毅
                array('p_uid'=>'3208007370'), //刘思山
                array('p_uid'=>'1572376091'), //付鹏鹏
                array('p_uid'=>'1773751933'), //靳文云
                array('p_uid'=>'3583878272'), //唐小晖
                array('p_uid'=>'1697340633'), //李永耀
                array('p_uid'=>'2530933535'), //任鑫磊
                array('p_uid'=>'2568185821'), //王树飞
                array('p_uid'=>'1721093414'), //杨得朝
                array('p_uid'=>'3564985583'), //石冬梅
                array('p_uid'=>'1402814403')  //刘涛
            );*/
            $records = 0;
            if(!empty($planners)){
                $p_uids = array();
                $num = 0;
                foreach($planners as $item){
                    $p_uids[] = $item['p_uid'];
                    $num++;
                    if($num>=100){
                        $records += $this->statInfluenceScore($p_uids,$this->stat_date, $start_date, $end_date);
                        $num=0;
                        $p_uids = array();
                    }
                }
                if($num>0){
                    $records += $this->statInfluenceScore($p_uids,$this->stat_date, $start_date, $end_date);
                }
            }
            return $records;
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }

    /**
     * 计算理财师的影响力得分
     * @param $p_uids
     * @param $start_date
     * @param $start_date
     * @param $end_date
     */
    private function statInfluenceScore($p_uids, $stat_date, $start_date, $end_date){
        try{
            $activitys = Planner::model()->getPlannerEvaluationByStatDate($start_date, $end_date,$p_uids,array('id','p_uid','stat_date','plan_sub_num','plan_attention_num','plan_talk_num','package_sub_num','package_attention_num','view_pay_num','view_free_num','view_talk_num','ask_pay_num','ask_free_num','ask_unlock_pay_num','ask_unlock_free_num','planner_attention_num','planner_violation_num'));
            if(!empty($activitys)){
                $stat_data = array();
                //计算统计数据
                foreach($activitys as $item){
                    //计算衰减率
                    $interval = date_diff(new DateTime(date('Y-m-d H:i:s',strtotime($stat_date))), new DateTime(date('Y-m-d H:i:s',strtotime($item['stat_date']))));
                    $days = $interval->format('%a');

                    if(isset($stat_data[$item['p_uid']])){
                        $stat_data[$item['p_uid']]['plan_attention_num'] += $this->stat_day_rate[$days]*intval(isset($item['plan_attention_num'])? $item['plan_attention_num'] : 0);
                        $stat_data[$item['p_uid']]['plan_talk_num'] += $this->stat_day_rate[$days]*intval(isset($item['plan_talk_num'])? $item['plan_talk_num'] : 0);
                        $stat_data[$item['p_uid']]['package_attention_num'] += $this->stat_day_rate[$days]*intval(isset($item['package_attention_num'])? $item['package_attention_num'] : 0);
                        $stat_data[$item['p_uid']]['view_pay_num'] += $this->stat_day_rate[$days]*intval(isset($item['view_pay_num'])? $item['view_pay_num'] : 0);
                        $stat_data[$item['p_uid']]['view_free_num'] += $this->stat_day_rate[$days]*intval(isset($item['view_free_num'])? $item['view_free_num'] : 0);
                        $stat_data[$item['p_uid']]['view_talk_num'] += $this->stat_day_rate[$days]*intval(isset($item['view_talk_num'])? $item['view_talk_num'] : 0);
                        $stat_data[$item['p_uid']]['ask_pay_num'] += $this->stat_day_rate[$days]*intval(isset($item['ask_pay_num'])? $item['ask_pay_num'] : 0);
                        $stat_data[$item['p_uid']]['ask_free_num'] += $this->stat_day_rate[$days]*intval(isset($item['ask_free_num'])? $item['ask_free_num'] : 0);
                        $stat_data[$item['p_uid']]['ask_unlock_pay_num'] += $this->stat_day_rate[$days]*intval(isset($item['ask_unlock_pay_num'])? $item['ask_unlock_pay_num'] : 0);
                        $stat_data[$item['p_uid']]['ask_unlock_free_num'] += $this->stat_day_rate[$days]*intval(isset($item['ask_unlock_free_num'])? $item['ask_unlock_free_num'] : 0);
                        $stat_data[$item['p_uid']]['planner_attention_num'] += $this->stat_day_rate[$days]*intval(isset($item['planner_attention_num'])? $item['planner_attention_num'] : 0);
                    }else{
                        $stat_data[$item['p_uid']]['plan_attention_num'] = $this->stat_day_rate[$days]*intval(isset($item['plan_attention_num'])? $item['plan_attention_num'] : 0);
                        $stat_data[$item['p_uid']]['plan_talk_num'] = $this->stat_day_rate[$days]*intval(isset($item['plan_talk_num'])? $item['plan_talk_num'] : 0);
                        $stat_data[$item['p_uid']]['package_attention_num'] = $this->stat_day_rate[$days]*intval(isset($item['package_attention_num'])? $item['package_attention_num'] : 0);
                        $stat_data[$item['p_uid']]['view_pay_num'] = $this->stat_day_rate[$days]*intval(isset($item['view_pay_num'])? $item['view_pay_num'] : 0);
                        $stat_data[$item['p_uid']]['view_free_num'] = $this->stat_day_rate[$days]*intval(isset($item['view_free_num'])? $item['view_free_num'] : 0);
                        $stat_data[$item['p_uid']]['view_talk_num'] = $this->stat_day_rate[$days]*intval(isset($item['view_talk_num'])? $item['view_talk_num'] : 0);
                        $stat_data[$item['p_uid']]['ask_pay_num'] = $this->stat_day_rate[$days]*intval(isset($item['ask_pay_num'])? $item['ask_pay_num'] : 0);
                        $stat_data[$item['p_uid']]['ask_free_num'] = $this->stat_day_rate[$days]*intval(isset($item['ask_free_num'])? $item['ask_free_num'] : 0);
                        $stat_data[$item['p_uid']]['ask_unlock_pay_num'] = $this->stat_day_rate[$days]*intval(isset($item['ask_unlock_pay_num'])? $item['ask_unlock_pay_num'] : 0);
                        $stat_data[$item['p_uid']]['ask_unlock_free_num'] = $this->stat_day_rate[$days]*intval(isset($item['ask_unlock_free_num'])? $item['ask_unlock_free_num'] : 0);
                        $stat_data[$item['p_uid']]['planner_attention_num'] = $this->stat_day_rate[$days]*intval(isset($item['planner_attention_num'])? $item['planner_attention_num'] : 0);
                    }

                    if($days==0){
                        $stat_data[$item['p_uid']]['plan_sub_num'] = isset($item['plan_sub_num'])? $item['plan_sub_num'] : 0;
                        $stat_data[$item['p_uid']]['package_sub_num'] = isset($item['package_sub_num'])? $item['package_sub_num'] : 0;
                    }

                    //($item['p_uid']=='3583878272'){
                    //    echo json_encode($stat_data),"\r\n";
                    //}
                }
                unset($activitys);
                //计算得分
                $stat_score = array();
                $lasted_score=array();
                foreach($stat_data as $key=>$val){
                    $score = 0;
                    $score += $val['plan_sub_num']*self::PLAN_SUB_SCORE;
                    $score += $val['plan_attention_num']*self::PLAN_ATTENTION_SCORE;
                    $score += $val['plan_talk_num']*self::PLAN_TALK_SCORE;
                    $score += $val['package_sub_num']*self::PACKAGE_SUB_SCORE;
                    $score += $val['package_attention_num']*self::PACKAGE_ATTENTION_SCORE;
                    $score += $val['view_pay_num']*self::VIEW_PAY_SCORE;
                    $score += $val['view_free_num']*self::VIEW_FREE_SCORE;
                    $score += $val['view_talk_num']*self::VIEW_TALK_SCORE;
                    $score += $val['ask_pay_num']*self::ASK_PAY_SCORE;
                    $score += $val['ask_free_num']*self::ASK_FREE_SCORE;
                    $score += $val['ask_unlock_pay_num']*self::ASK_UNLOCK_PAY_SCORE;
                    $score += $val['ask_unlock_free_num']*self::ASK_UNLOCK_FREE_SCORE;
                    $score += $val['planner_attention_num']*self::PLANNER_ATTENTION_SCORE;

                    $score = 400*sqrt(sqrt($score));
                    $score = $score<100?100:number_format($score,2,'.','');

                    $stat_score[$key]['influence_score'] = $score;

                    $lasted_score[$key]['influence'] = $stat_score[$key]['influence_score'];

                    //if($key=='3583878272'){
                    //    echo json_encode($stat_score),"\r\n";
                    //}
                }
                unset($stat_data);

                // 最近一次统计得分时，记录到理财师扩展表
                if($stat_date==date('Y-m-d',strtotime('-1day'))){
                    Planner::model()->updatePlannerExt($lasted_score,array("influence"));
                }

                //将得分更新数据库
                return Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_score,array("influence_score"));
            }
        }catch (Exception $e) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }

    }


    /**
     * 统计理财师有效计划的数据
     * 1. 购买用户数量  2关注用户数量   3发布说说的数量
     * @param $stat_date
     * @param $stat_result
     * @return mixed
     */
    private function statPlan($stat_date){
        //正在运行的计划  status=3
        //抢购日期内的计划 status=2 and
        //计划结束小于指定天数的计划
        $stat_result = array();
        $records = 0;
        try {
            $end_date = date('Y-m-d 00:00:00', strtotime($stat_date) - self::DECAY_PLAN_DAYS * 24 * 3600);
            $plans = Plan::model()->getPlanInfoOfInfluence($stat_date.' 23:59:59',$end_date);
            if (!empty($plans)) {
                $plan_map = array();
                $tmp_result = array(); //结构 p_uid -> pln_id -> sub_num reader_num cmn_user_num decay_ratio
                foreach ($plans as &$plan) {
                    $plan_map[$plan['pln_id']] = $plan;
                    //计划已经结束计算衰减率  结束时间大于统计时间时，衰减度为1
                    if(in_array($plan['status'], array(4, 5, 6, 7)) && date('Y-m-d',strtotime($plan['real_end_time']))<$stat_date){
                        $interval = date_diff(new DateTime(date('Y-m-d H:i:s',strtotime($stat_date.'00:00:00'))), new DateTime($plan['real_end_time']));
                        $days = $interval->format('%a');
                        if($days>=self::DECAY_PLAN_DAYS){
                            $decay_ratio = 1;
                        }else{
                            $decay_ratio = (self::DECAY_PLAN_DAYS - $days) * 0.05;
                        }
                        $tmp_result[$plan['p_uid']][$plan['pln_id']]['decay_ratio']=$decay_ratio;
                    }else{
                        $tmp_result[$plan['p_uid']][$plan['pln_id']]['decay_ratio']=1;
                    }
                    //TODO
                    /*if($plan['p_uid']=='3608437534'){
                        echo json_encode($tmp_result[$plan['p_uid']]),"\n";
                        echo json_encode($plan),"\n";
                        echo date('Y-m-d',strtotime($plan['real_end_time'])),'|',$stat_date,"\n";
                    }*/
                }
                unset($plans);

                $one_pln_ids = array(); //直接排重统计说说用户数量的计划ID
                $thr_pln_ids = array(); //需要排除多个计划的重复用户的ID
                foreach ($tmp_result as $key=>$item) {
                    if(count($item)>1){
                        //有多个计划 就需要特殊处理
                        foreach($item as $k=>$v){
                            $thr_pln_ids[] = $k;
                        }

                    }else if(count($item)==1){
                        foreach($item as $k=>$v){
                            $tmp_result[$key][$k]['sub_num'] = isset($plan_map[$k])?$plan_map[$k]['subscription_count']:0;
                            $tmp_result[$key][$k]['reader_num'] = isset($plan_map[$k])?$plan_map[$k]['reader_count']:0;
                            $one_pln_ids[] = $k;
                        }

                    }
                }

                $end_date = date('Y-m-d', strtotime($stat_date) + 24 * 3600);
                //直接统计的计划发布说说的用户数量
                $one_cmn_users = Comment::model()->getCommentUidCountOfRelation_id(1,$one_pln_ids,1,$stat_date, $end_date);
                $one_cmn_users_map = array();
                if(!empty($one_cmn_users)){
                    foreach($one_cmn_users as $item){
                        $one_cmn_users_map[$item['relation_id']] = $item['num'];
                    }
                    unset($one_cmn_users);
                }

                //需要排除多个计划的说说用户uid
                $thr_cmn_users = Comment::model()->getCommentByType(1,$thr_pln_ids,1,$stat_date,$end_date,array('uid','relation_id'));
                $thr_cmn_users_map = array();
                if(!empty($thr_cmn_users)){
                    foreach($thr_cmn_users as $item){
                        $thr_cmn_users_map[$item['relation_id']][] = $item['uid'];
                    }
                    unset($thr_cmn_users);
                }

                //获取计划的订阅用户 关注用户
                $sub_users = Plan::model()->getPlanSubInfoByPlanIds($thr_pln_ids,array('pln_id','uid'));
                $sub_users_map = array();
                if(!empty($sub_users)){
                    foreach($sub_users as $item){
                        $sub_users_map[$item['pln_id']][] = $item['uid'];
                    }
                    unset($sub_users);
                }
                //获取计划的关注用户
                $col_users = Collect::model()->getCollectByType(3, array_keys($plan_map), '', $end_date);
                $col_users_map = array();
                if(!empty($col_users)){
                    foreach($col_users as $item){
                        $col_users_map[$item['relation_id']][] = $item['uid'];
                    }
                    unset($col_users);
                }

                //整合数据 排重多个计划的用户
                foreach ($tmp_result as $key=>$item) {
                    if(count($item)>1){
                        //有多个计划 需要排除重复用户
                        $_sub_run_users = array();
                        $_reader_users = array();
                        $_cmn_users = array();
                        foreach($item as $k=>$v){
                            //if(!in_array($plan_map["$k"]['status'], array(4, 5, 6, 7))){
                            if($v['decay_ratio']==1){
                                $_sub_run_users = isset($sub_users_map["$k"])?array_merge($_sub_run_users,$sub_users_map["$k"]):$_sub_run_users;
                            }
                            $_reader_users = isset($col_users_map["$k"])?array_merge($_reader_users, $col_users_map["$k"]):$_reader_users;
                            $_cmn_users = isset($thr_cmn_users_map["$k"])?array_merge($_cmn_users, $thr_cmn_users_map["$k"]):$_cmn_users;
                        }

                        $_sub_run_users = array_unique($_sub_run_users);
                        $_reader_users = array_unique($_reader_users);
                        $_cmn_users = array_unique($_cmn_users);

                        //计算衰减的订阅用户
                        $_sub_stop_user_num = 0;
                        //if(!empty($_sub_run_users)){
                            foreach($item as $k=>$v){
                                //if(in_array($plan_map[$k]['status'], array(4, 5, 6, 7)) && isset($sub_users_map[$k])){
                                if($v['decay_ratio']!=1 && isset($sub_users_map[$k])){
                                    $_sub_stop_user_num += number_format($v['decay_ratio'] * (count($sub_users_map[$k])-count(array_intersect($_sub_run_users, $sub_users_map[$k]))),2,'.','');
                                }
                            }
                        //}

                        $stat_result[$key]['plan_sub_num'] = count($_sub_run_users)+$_sub_stop_user_num;
                        $stat_result[$key]['plan_attention_num'] = count($_reader_users);
                        $stat_result[$key]['plan_talk_num'] = count($_cmn_users);

                    }else if(count($item)==1){
                        foreach($item as $k=>$v){
                            //如果项目已经结束，订阅用户数量算衰减
                            //if(in_array($plan_map[$k]['status'], array(4, 5, 6, 7))){
                            if($v['decay_ratio']!=1){
                                $stat_result[$key]['plan_sub_num'] = number_format($v['sub_num']*$v['decay_ratio'],2,'.','');
                            }else{
                                $stat_result[$key]['plan_sub_num'] = $v['sub_num'];
                            }
                            $_reader_users=array();
                            $_reader_users = isset($col_users_map["$k"])?array_merge($_reader_users, $col_users_map["$k"]):$_reader_users;
                            $_reader_users = array_unique($_reader_users);
                            $stat_result[$key]['plan_attention_num'] = count($_reader_users);
                            $stat_result[$key]['plan_talk_num'] = isset($one_cmn_users_map[$k])?intval($one_cmn_users_map[$k]):0;
                        }

                    }
                }

                unset($one_cmn_users_map);
                unset($thr_cmn_users_map);
                unset($sub_users_map);
                unset($col_users_map);
            }

            if(!empty($stat_result)){
                $stat_result_tmp = array();
                $count = 0;
                foreach($stat_result as $k=>$v){
                    $stat_result_tmp[$k] = $v;
                    $count ++;
                    if($count>=200){
                        $count=0;
                        $records += Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_result_tmp,array("plan_sub_num","plan_attention_num","plan_talk_num"));
                    }
                }
                if($count>0){
                    $records += Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_result_tmp,array("plan_sub_num","plan_attention_num","plan_talk_num"));
                }
            }

        } catch (Exception $e) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
        return $records;
    }


    /**
     * 统计理财师观点的影响力数据
     * @param $stat_date
     * @param $stat_result
     */
    private function statView($stat_date){
        $stat_result = array();
        $records = 0;
        try {
            $end_date = date('Y-m-d', strtotime($stat_date) + 24 * 3600);
            //订阅观点  v.`id`,v.`p_uid`,vs.`uid`,vs.`subscription_price`
            $views = View::model()->getViewSubscriptionOfInfluence($stat_date,$end_date);
            if(!empty($views)){
                $planners = array();
                foreach($views as $item){
                    $is_price = $item['subscription_price']>0?1:0;
                    $planners[$item['p_uid']][$is_price][]=$item['uid'];
                }

                //排除重复用户
                foreach($planners as $key=>$item){
                    $free = isset($item[0])?array_flip(array_flip($item[0])):array();
                    $price = isset($item[1])?array_flip(array_flip($item[1])):array();
                    //如果免费解锁的用户在付费解锁里面，排除掉
                    if(!empty($free)){
                        $free_tmp = array();
                        foreach($free as $uid){
                            if(!in_array($uid,$price)){
                                $free_tmp[] = $uid;
                            }
                        }
                        $free = $free_tmp;
                    }
                    $stat_result[$key]['view_free_num'] = count($free);
                    $stat_result[$key]['view_pay_num'] = count($price);
                }
            }

            unset($views);

            if(!empty($stat_result)){
                $stat_result_tmp = array();
                $count = 0;
                foreach($stat_result as $key=>$v){
                    $stat_result_tmp[$key] = $v;
                    $count ++;
                    if($count>=200){
                        $count=0;
                        $records += Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_result_tmp,array("view_pay_num","view_free_num"));
                    }
                }
                if($count>0){
                    $records += Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_result_tmp,array("view_pay_num","view_free_num"));
                }
            }
        } catch (Exception $e) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
        return $records;
    }

    /**
     * 统计理财师观点包的影响力数据
     * @param $stat_date
     * @param $stat_result
     */
    private function statPackage($stat_date){
        $stat_result = array();
        $records = 0;
        try {

            // 记录观点包的订阅
            //1.正在订阅的观点包 用户
            $sub_users = Package::model()->getPackageSubscriptionInfo($stat_date,'');
            $sub_users_map = array();
            if(!empty($sub_users)){
                foreach($sub_users as $item){
                    $sub_users_map[$item['pkg_id']][] = $item['uid'];
                }
                unset($sub_users);
            }

            //2.订阅过期在20天只内的用户
            $start_date = date('Y-m-d', strtotime($stat_date) - self::DECAY_PLAN_DAYS * 24 * 3600);
            $sub_expire_users = Package::model()->getPackageSubscriptionInfo($start_date,$stat_date);
            $sub_expire_users_map = array();
            if(!empty($sub_expire_users)){
                foreach($sub_expire_users as $item){
                    $sub_expire_users_map[$item['pkg_id']]['uids'][] = $item['uid'];
                    //计算衰减率
                    $interval = date_diff(new DateTime(date('Y-m-d H:i:s',strtotime($stat_date))), new DateTime($item['end_time']));
                    $days = $interval->format('%a');
                    $decay_ratio = (self::DECAY_PLAN_DAYS - $days) * 0.05;
                    $sub_expire_users_map[$item['pkg_id']]['decay_ratio']=$decay_ratio;
                    //每个用户的衰减得分
                    $sub_expire_users_map[$item['pkg_id']]['u_scores'][] = array('uid'=>$item['uid'],'score'=>$decay_ratio);
                }
                unset($sub_expire_users);
            }

            //3.通过观点包id  汇总到理财师 对应的订阅用户uid
            $pkg_ids = array_unique(array_merge(array_keys($sub_users_map),array_keys($sub_expire_users_map)));
            $packages = Package::model()->getPackageInfoByIds($pkg_ids,array('id','p_uid'));
            $packages_map = array();
            if(!empty($packages)){
                foreach($packages as $item){
                    $packages_map[$item['id']] = $item['p_uid'];
                }
                unset($packages);
            }

            //4.排除重复的用户ID
            $sub_stat = array(); // p_uid => sub  expire
            if(!empty($sub_users_map)){
                //先统计正在订阅的用户
                foreach($sub_users_map as $pkg_id=>$uids){
                    $p_uid = isset($packages_map[$pkg_id]) ? $packages_map[$pkg_id]: '';
                    if(!empty($p_uid)){
                        $sub_stat[$p_uid]['sub'] = isset($sub_stat[$p_uid]['sub'])? array_merge($sub_stat[$p_uid]['sub'],$uids):$uids;
                    }
                }
                //统计过期订阅的用户
                foreach($sub_expire_users_map as $pkg_id=>$item){
                    $p_uid = isset($packages_map[$pkg_id]) ? $packages_map[$pkg_id]: '';
                    if(!empty($p_uid)){
                        $sub_uids = isset($sub_stat[$p_uid]['sub'])? $sub_stat[$p_uid]['sub']:array();
                        $sub_uids = array_unique($sub_uids);
                        /*$count = 0;
                        if(!empty($sub_uids)){
                            $count = count($item['uids'])-count(array_intersect($sub_uids, $item['uids']));
                        }else{
                            $count = count($item['uids']);
                        }
                        if(isset($sub_stat[$p_uid]['expire'])){
                            $sub_stat[$p_uid]['expire'] += number_format($item['decay_ratio'] * $count,2,'.','');
                        }else{
                            $sub_stat[$p_uid]['expire'] = number_format($item['decay_ratio'] * $count,2,'.','');
                        }*/



                        $pkg_u_scores = array();
                        foreach($item['u_scores'] as $v){
                            $pkg_u_scores[$v['uid']]=$v['score'];
                        }

                        foreach($item['uids'] as $uid){
                            if(!in_array($uid,$sub_uids)){
                                if(isset($sub_stat[$p_uid]['expire'])){
                                    $sub_stat[$p_uid]['expire'] += isset($pkg_u_scores[$uid]) ? $pkg_u_scores[$uid] : 0;
                                }else{
                                    $sub_stat[$p_uid]['expire'] = isset($pkg_u_scores[$uid]) ? $pkg_u_scores[$uid] : 0;
                                }

                            }
                        }
                    }
                }
            }
            if(!empty($sub_stat)){
                foreach($sub_stat as $key=>$item){
                    if(isset($item['sub']) && !empty($item['sub'])){
                        if(isset($stat_result[$key]['package_sub_num'])){
                            $stat_result[$key]['package_sub_num'] += count($item['sub']);
                        }else{
                            $stat_result[$key]['package_sub_num'] = count($item['sub']);
                        }
                    }
                    if(isset($item['expire']) && !empty($item['expire'])){
                        if(isset($stat_result[$key]['package_sub_num'])){
                            $stat_result[$key]['package_sub_num'] += $item['expire'];
                        }else{
                            $stat_result[$key]['package_sub_num'] = $item['expire'];
                        }

                    }
                }
            }

            unset($sub_users_map);
            unset($sub_expire_users_map);
            unset($packages_map);
            unset($sub_stat);


            //观点包关注用户 package_attention
            //获取观点包的关注用户
            $col_pkgs = Collect::model()->getDistinctRelationIdByType(4,'','');
            if(!empty($col_pkgs)){
                $pkg_ids = array();
                foreach($col_pkgs as $item){
                    $pkg_ids[] = $item['relation_id'];
                }
                unset($col_pkgs);
                $packages = Package::model()->getPackageInfoByIds($pkg_ids,array('id','p_uid,collect_num'));
                $temp_stat = array();
                $packages_colnum_map = array();
                foreach($packages as $item){
                    $temp_stat[$item['p_uid']][] = $item['id'];
                    $packages_colnum_map[$item['id']] = $item['collect_num'];
                }
                unset($pkg_ids);
                unset($packages);
                $num = 0;
                foreach($temp_stat as $p_uid=>$pkg_ids){

                    if(count($pkg_ids)==1){
                        $count=$packages_colnum_map[current($pkg_ids)];
                    }else{
                        //TODO 太耗时
                        //$count = Collect::model()->getCollectCountOfUidByType(4, $pkg_ids, '', $stat_date);
                        $count=0;
                        foreach($pkg_ids as $pkg_id){
                            $count += isset($packages_colnum_map[$pkg_id]) ? $packages_colnum_map[$pkg_id] : 0;
                        }
                    }
                    $stat_result[$p_uid]['package_attention_num'] = $count;
                }
                unset($temp_stat);
                unset($packages_colnum_map);
                //echo "两个以上的观点包的理财师数量:",$num,"\n";

            }

            //观点和观点包的说说用户
            $end_date = date('Y-m-d', strtotime($stat_date) + 24 * 3600);
            $view_comments = Comment::model()->getCommentByType(2,null,1,$stat_date,$end_date,array('uid','parent_relation_id'));
            if(!empty($view_comments)){
                $package_ids = array();
                foreach($view_comments as $item){
                    $package_ids[$item['parent_relation_id']]= $item['parent_relation_id'];
                }

                $packages = Package::model()->getPackageInfoByIds(array_keys($package_ids),array('id','p_uid'));
                $package_map = array();
                if(!empty($packages)){
                    foreach($packages as $item){
                        $package_map[$item['id']]=$item['p_uid'];
                    }
                    unset($packages);
                }

                $planners = array();
                foreach($view_comments as $item){
                    $planners[$package_map[$item['parent_relation_id']]][]=$item['uid'];
                }
                unset($package_map);
                unset($view_comments);

                //排除重复用户
                foreach($planners as $key=>$item){
                    $stat_result[$key]['view_talk_num'] = count(array_flip(array_flip($item)));
                }

            }

            if(!empty($stat_result)){
                $stat_result_tmp = array();
                $count = 0;
                foreach($stat_result as $key=>$v){
                    $stat_result_tmp[$key] = $v;
                    $count ++;
                    if($count>=200){
                        $count=0;
                        $records += Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_result_tmp,array("package_sub_num","package_attention_num","view_talk_num"));
                    }
                }
                if($count>0){
                    $records += Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_result_tmp,array("package_sub_num","package_attention_num","view_talk_num"));
                }
            }
        } catch (Exception $e) {
            //echo $e->getMessage();
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }


        return $records;
    }


    /**
     * 统计理财师问答的影响力数据
     * @param $stat_date
     * @param $stat_result
     * @return mixed
     */
    private function statAsk($stat_date){
        $stat_result = array();
        $records = 0;
        try {
            $end_date = date('Y-m-d', strtotime($stat_date) + 24 * 3600);
            //提问问题
            $ask_questions = Ask::model()->getQuestionOfInfluence($stat_date,$end_date);
            if(!empty($ask_questions)){
                $planners = array();
                foreach($ask_questions as $item){
                    $planners[$item['p_uid']][$item['is_price']][]=$item['uid'];
                }
                unset($ask_questions);
                //排除重复用户
                foreach($planners as $key=>$item){
                    $free = isset($item[0])?array_flip(array_flip($item[0])):array();
                    $price = isset($item[1])?array_flip(array_flip($item[1])):array();
                    //如果免费提问的用户在付费提问里面，排除掉
                    if(!empty($free)){
                        $free_tmp = array();
                        foreach($free as $uid){
                            if(!in_array($uid,$price)){
                                $free_tmp[] = $uid;
                            }
                        }
                        $free = $free_tmp;
                    }
                    $stat_result[$key]['ask_free_num'] = count($free);
                    $stat_result[$key]['ask_pay_num'] = count($price);

                }
            }

            //解锁问题
            $unlock_questions = Ask::model()->getUnlockQuestionOfInfluence($stat_date,$end_date);
            if(!empty($unlock_questions)){
                $planners = array();
                foreach($unlock_questions as $item){
                    $planners[$item['p_uid']][$item['is_price']][]=$item['uid'];
                }
                unset($unlock_questions);
                //排除重复用户
                foreach($planners as $key=>$item){
                    $free = isset($item[0])?array_flip(array_flip($item[0])):array();
                    $price = isset($item[1])?array_flip(array_flip($item[1])):array();
                    //如果免费提问的用户在付费提问里面，排除掉
                    if(!empty($free)){
                        $free_tmp = array();
                        foreach($free as $uid){
                            if(!in_array($uid,$price)){
                                $free_tmp[] = $uid;
                            }
                        }
                        $free = $free_tmp;
                    }
                    $stat_result[$key]['ask_unlock_free_num'] = count($free);
                    $stat_result[$key]['ask_unlock_pay_num'] = count($price);
                }
            }

            if(!empty($stat_result)){
                $stat_result_tmp = array();
                $count = 0;
                foreach($stat_result as $key=>$v){
                    $stat_result_tmp[$key] = $v;
                    $count ++;
                    if($count>=200){
                        $count=0;
                        $records += Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_result_tmp,array("ask_free_num","ask_pay_num","ask_unlock_free_num","ask_unlock_pay_num"));
                    }
                }
                if($count>0){
                    $records += Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_result_tmp,array("ask_free_num","ask_pay_num","ask_unlock_free_num","ask_unlock_pay_num"));
                }
            }

        } catch (Exception $e) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }

        return $records;
    }


    /**
     * 统计理财师
     * @return mixed
     */
    private function statPlanner($stat_date){
        $stat_result = array();
        $records = 0;
        try {
            //理财师关注用户数量
            $end_date = date('Y-m-d', strtotime($stat_date) + 24 * 3600);
            $attentions = Planner::model()->getAttentionUserCount();
            if(!empty($attentions)){
                foreach($attentions as $item){
                    $stat_result[$item['p_uid']]['planner_attention_num'] = $item['num'];
                }
                unset($attentions);
            }

            //TODO 理财师违规次数


            if(!empty($stat_result)){
                $stat_result_tmp = array();
                $count = 0;
                foreach($stat_result as $key=>$v){
                    $stat_result_tmp[$key] = $v;
                    $count ++;
                    if($count>=200){
                        $count=0;
                        $records += Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_result_tmp,array("planner_attention_num"));
                    }
                }
                if($count>0){
                    $records += Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_result_tmp,array("planner_attention_num"));
                }
            }


        } catch (Exception $e) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
        return $records;
    }
}