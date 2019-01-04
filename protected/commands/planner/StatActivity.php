<?php
/**
 * 定时任务:统计理财师活跃度
 * （1）每访问一次+0.1，一个小时内只算一次，一天最多累计10次，即从访问记录中x的活跃贡献值最大为1
 * （2）发观点每发一条+0.7，每天最多累计10次，即从访问记录中x的活跃贡献值最大为7
 * （3）回答问题每回答的一条+0.7，每天最多累计10次，即从访问记录中x的活跃贡献值最大为7
 * （4）发计划，对每次计划的操作+5，每天最多累计1次，即从访问记录中x的活跃贡献值最大为5
 * （5）每发布一条说说+1（发布说说和回复都算一次），一天最多累计4次，即从发布说说中x的活跃贡献值最大为4

 * User: zwg
 * Date: 2015/5/18
 * Time: 17:33
 */

class StatActivity {


    const CRON_NO = 1001; //任务代码
    const STAT_DAYS = 10; //活跃度统计天数

    const STAT_VISIT_SCORE = 0.1; //访问得分
    const STAT_VISIT_LIMIT = 10;  //访问次数限制
    const STAT_VIEW_SCORE = 1.5;  //观点得分
    const STAT_VIEW_LIMIT = 4;   //观点条数限制
    const STAT_ANSWER_SCORE = 0.4;//回答问题得分
    const STAT_ANSWER_LIMIT = 20; //回答问题次数限制
    const STAT_PLAN_SCORE = 2.5;    //计划操作得分
    const STAT_PLAN_LIMIT = 2;    //计划操作次数限制
    const STAT_TALK_SCORE = 1;    //说说得分
    const STAT_TALK_LIMIT = 5;    //说说次数限制

    private $stat_date = '';

    public function __construct($stat_date=''){
        if(empty($stat_date)){
            $this->stat_date = date('Y-m-d');
        }else{
            $this->stat_date = $stat_date;
        }
    }


    /**
     * 统计活跃数据
     * @throws LcsException
     */
    public function statData(){
        try{
            $start_time = CommonUtils::getMillisecond();
            //统计发布观点
            $this->statView($this->stat_date);
            $view_time = CommonUtils::getMillisecond();
            $stat_times['view'] = $view_time - $start_time;
            //统计回答问题
            $this->statAsk($this->stat_date);
            $ask_time = CommonUtils::getMillisecond();
            $stat_times['ask'] = $ask_time - $view_time;
            //统计计划操作
            $this->statPlan($this->stat_date);
            $plan_time = CommonUtils::getMillisecond();
            $stat_times['plan'] = $plan_time - $ask_time;
            //统计说说数量
            $this->statComment($this->stat_date);
            $talk_time = CommonUtils::getMillisecond();
            $stat_times['talk'] = $talk_time - $plan_time;

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
            //$planners = array(array('p_uid'=>'2177007684'));
            $records = 0;
            if(!empty($planners)){
                $p_uids = array();
                $num = 0;
                foreach($planners as $item){
                    $p_uids[] = $item['p_uid'];
                    $num++;
                    if($num>=100){
                        $records += $this->statActivityScore($p_uids,$this->stat_date, $start_date, $end_date);
                        $num=0;
                        $p_uids = array();
                    }
                }
                if($num>0){
                    $records += $this->statActivityScore($p_uids,$this->stat_date, $start_date, $end_date);
                }
            }
            return $records;
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }

    /**
     * 计算理财师的活跃指标得分
     * @param $p_uids
     * @param $start_date
     * @param $start_date
     * @param $end_date
     */
    private function statActivityScore($p_uids, $stat_date, $start_date, $end_date){
        try{
            $activitys = Planner::model()->getPlannerEvaluationByStatDate($start_date, $end_date,$p_uids,array('id','p_uid','stat_date','login_num','view_num','answer_num','plan_trade_num','talk_num','activity_score'));
            if(!empty($activitys)){
                //计算统计数据
                $stat_data=array();

                foreach($activitys as $item){
                    $score = 0;
                    //if($item['stat_date'] == $this->stat_date){
                        $_score = self::STAT_VISIT_SCORE * intval($item['login_num']<=self::STAT_VISIT_LIMIT? $item['login_num'] : self::STAT_VISIT_LIMIT);
                        $_score += self::STAT_VIEW_SCORE * intval($item['view_num']<=self::STAT_VIEW_LIMIT? $item['view_num'] : self::STAT_VIEW_LIMIT);
                        $_score += self::STAT_ANSWER_SCORE * intval($item['answer_num']<=self::STAT_ANSWER_LIMIT? $item['answer_num'] : self::STAT_ANSWER_LIMIT);
                        $_score += self::STAT_PLAN_SCORE * intval($item['plan_trade_num']<=self::STAT_PLAN_LIMIT? $item['plan_trade_num'] : self::STAT_PLAN_LIMIT);
                        $_score += self::STAT_TALK_SCORE * intval($item['talk_num']<=self::STAT_TALK_LIMIT? $item['talk_num'] : self::STAT_TALK_LIMIT);

                        $score = ($_score>10?10:$_score);
                    //}else{
                    //    $score = $item['activity_score'];
                    //}

                    if(isset($stat_data[$item['p_uid']])){
                        $stat_data[$item['p_uid']]['score'] += $score;
                    }else{
                        $stat_data[$item['p_uid']]['score'] = $score;
                    }
                }

                unset($activitys);

                //计算得分
                $stat_score = array();
                $lasted_score=array();
                foreach($stat_data as $key=>$item){
                    $avg_score = number_format($item['score']/self::STAT_DAYS,2,'.','');
                    //最高10分  add by weiguang3 20150824
                    if($avg_score>10){
                        $avg_score=10;
                    }
                    if($avg_score<1){
                        $avg_score=1;
                    }

                    $stat_score[$key]['activity_score']=$avg_score;
                    $lasted_score[$key]['activity']=$avg_score;
                }


                //将得分更新数据库

                // 最近一次统计得分时，记录到理财师扩展表
                if($stat_date==date('Y-m-d',strtotime('-1day'))){
                    Planner::model()->updatePlannerExt($lasted_score,array("activity"));
                }

                return Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_score,array("activity_score"));
            }
        }catch (Exception $e) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }

    }

    /**
     * 统计理财师某日发观点数量
     * 发观点每发一条+0.7，每天最多累计10次，即从访问记录中x的活跃贡献值最大为7
     * @param $stat_date
     * @param $stat_result
     */
    public function statView($stat_date){
        $stat_result = array();
        $records = 0;
        try {
            $end_date = date('Y-m-d', strtotime($stat_date) + 24 * 3600);
            $views = View::model()->getViewCount('', $stat_date, $end_date);
            if (!empty($views)) {
                foreach ($views as $view) {
                    $stat_result[$view['p_uid']]['view_num'] = intval($view['num']);
                }
            }
            unset($views);
            $records = Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_result,array("view_num"));
        } catch (Exception $e) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
        return $records;
    }


    /**
     * 统计理财师某日回答问题数量
     * 回答问题每回答的一条+0.7，每天最多累计10次，即从访问记录中x的活跃贡献值最大为7
     * @param $stat_date
     * @param $stat_result
     */
    public function statAsk($stat_date){
        $stat_result = array();
        $records = 0;
        try {
            $end_date = date('Y-m-d', strtotime($stat_date) + 24 * 3600);
            $asks = Ask::model()->getAskCount('', $stat_date, $end_date);
            if (!empty($asks)) {
                foreach ($asks as $ask) {
                    $stat_result[$ask['p_uid']]['answer_num'] = intval($ask['num']);
                }
            }
            unset($asks);

            $records = Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_result,array("answer_num"));
        } catch (Exception $e) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
        return $records;
    }


    /**
     * 统计理财师的计划交易次数
     * 发计划，对每次计划的操作+5，每天最多累计1次，即从访问记录中x的活跃贡献值最大为5
     * @param $stat_date
     * @param $stat_result
     * @return mixed
     */
    public function statPlan($stat_date){
        $stat_result = array();
        $records = 0;
        try {
            $end_date = date('Y-m-d', strtotime($stat_date) + 24 * 3600);
            //查询计划的交易记录，获取计划的交易次数
            $transactions = Plan::model()->getPlanTransactionCount('', $stat_date, $end_date);
            if (!empty($transactions)) {
                $_tmp = array();
                foreach ($transactions as $item) {
                    $_tmp[$item['pln_id']] = intval($item['num']);
                }
                unset($transactions);
                //根据计划ID获取对应的理财师
                $plans = Plan::model()->getPlanInfoByIds(array_keys($_tmp),array('pln_id','p_uid'));
                foreach($plans as $plan){
                    if(isset($stat_result[$plan['p_uid']]['plan'])){
                        $stat_result[$plan['p_uid']]['plan_trade_num'] += intval($_tmp[$plan['pln_id']]);
                    }else{
                        $stat_result[$plan['p_uid']]['plan_trade_num'] = intval($_tmp[$plan['pln_id']]);
                    }

                }
                unset($plans);

                $records = Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_result,array("plan_trade_num"));
            }

        } catch (Exception $e) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }

        return $records;
    }


    /**
     * 统计理财师的说说数量
     * 每发布一条说说+1（发布说说和回复都算一次），一天最多累计4次，即从发布说说中x的活跃贡献值最大为4
     * @param $stat_date
     * @param $stat_result
     * @return mixed
     */
    public function statComment($stat_date){
        $stat_result = array();
        $records = 0;
        try {
            $end_date = date('Y-m-d', strtotime($stat_date) + 24 * 3600);
            $comments = Comment::model()->getCommentCount('', $stat_date, $end_date);
            if (!empty($comments)) {
                foreach ($comments as $item) {
                    $stat_result[$item['uid']]['talk_num'] = intval($item['num']);
                }
            }
            unset($comments);

            $records = Planner::model()->saveOrUpdateEvaluation($stat_date, $stat_result,array("talk_num"));

        } catch (Exception $e) {
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }

        return $records;
    }
}