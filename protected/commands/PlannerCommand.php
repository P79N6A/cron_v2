<?php

/**
 * 理财师定时任务入口
 * User: zwg
 * Date: 2015/5/18
 * Time: 17:34
 */
class PlannerCommand extends LcsConsoleCommand {

    public function init() {
        Yii::import('application.commands.planner.*');
    }

   /**
    * 理财师聚合页v2改版
    */
   public function actionPlannerList(){
        try {
            $active = new PlannerList();
            $active->plannerList();
            //记录任务结束时间
            $this->monitorLog(PlannerList::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(PlannerList::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
   }

    /**
     * 初始化理财师的行为
     */
    public function actionInitActive() {
        try {
            $active = new TopPlannerActive();
            $active->initActive();
            //记录任务结束时间
            $this->monitorLog(TopPlannerActive::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(TopPlannerActive::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 统计活跃理财师的行为
     */
    public function actionPlannerActive() {
        try {
            $active = new TopPlannerActive();
            $active->process();
            //记录任务结束时间
            $this->monitorLog(TopPlannerActive::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(TopPlannerActive::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 统计理财师的活跃度
     * @param string $stat_date
     * @param int $is_stat_data
     * @param int $is_stat_score
     */
    public function actionStatActivity($stat_date = '', $is_stat_data = 1, $is_stat_score = 1) {
        try {
            $activity = new StatActivity($stat_date);
            $stat_data_num = 0;
            $stat_score_num = 0;
            if ($is_stat_data == 1) {
                $stat_data_num = $activity->statData();
            }
            if ($is_stat_score == 1) {
                $stat_score_num = $activity->statScore();
            }
            //记录任务结束时间
            $this->monitorLog(StatActivity::CRON_NO);
            Cron::model()->saveCronLog(StatActivity::CRON_NO, CLogger::LEVEL_INFO, "统计理财师活跃度,日期：" . $stat_date . " 数据:" . $stat_data_num . " 得分:" . $stat_score_num);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(StatActivity::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 统计理财师的影响力
     * @param string $stat_date
     * @param int $stat_data
     * @param int $is_stat_score
     * @throws LcsException
     */
    public function actionStatInfluence($stat_date = '', $stat_data = 1, $is_stat_score = 1) {
        try {
            $influence = new StatInfluence($stat_date);
            $stat_data_num = 0;
            $stat_score_num = 0;
            if ($stat_data > 0) {
                $stat_data_num = $influence->statData($stat_data);
            }
            if ($is_stat_score == 1) {
                $stat_score_num = $influence->statScore();
            }
            //记录任务结束时间
            $this->monitorLog(StatInfluence::CRON_NO);
            Cron::model()->saveCronLog(StatInfluence::CRON_NO, CLogger::LEVEL_INFO, "统计理财师影响力,日期：" . $stat_date . " 数据:" . $stat_data_num . " 得分:" . $stat_score_num);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(StatInfluence::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
            //echo $e->getMessage();
        }
    }

    public function actionSinaIdxRcmdPlanner() {
        try {
            $sinaIdxRcmdPlanner = new SinaIdxRcmdPlanner();

            $sinaIdxRcmdPlanner->rsync();

            //记录任务结束时间
            $this->monitorLog(SinaIdxRcmdPlanner::CRON_NO);
            //Cron::model()->saveCronLog(SinaIdxRcmdPlanner::CRON_NO, CLogger::LEVEL_INFO, "统计理财师影响力,数据:".$stat_data_num." 得分:".$stat_score_num);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(SinaIdxRcmdPlanner::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
            //echo $e->getMessage();
        }
    }

    /**
     * 1004
     * update lcs_planner_ext.pkg_comment_num
     */
    public function actionCommentNum() {
        try {
            $commentNum = new CommentNum();
            $commentNum->process();

            $this->monitorLog(CommentNum::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(CommentNum::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1005
     * 理财师回答问题的最近30天响应时间
     */
    public function actionMostAskOf30Days() {
        try {
            $mostAskOf30Days = new MostAskOf30Days();
            $mostAskOf30Days->state();
            //记录任务结束时间
            $this->monitorLog(MostAskOf30Days::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(MostAskOf30Days::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1006
     * 理财师回答问题的最近30天响应时间
     */
    public function actionQuestionRespTime() {
        try {
            $questionRespTime = new QuestionRespTime();
            $records = $questionRespTime->respTime();
            //记录任务结束时间
            $this->monitorLog(QuestionRespTime::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(QuestionRespTime::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1007
     * 理财师在指定交易日不上线，关掉该理财师的问答和限时特惠
     */
    public function actionAskClose() {
        try {
            $p = new AskClose();
            $p->updateAskPlanner();
            //记录任务结束时间
            $this->monitorLog(AskClose::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(AskClose::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1008
     * 更新推荐理财师 20个
     */
    public function actionPlannerRecommend() {
        try {
            $p = new PlannerRecommend();
            $p->updateRecommendList();
            $this->monitorLog(PlannerRecommend::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(PlannerRecommend::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 1040
     * 取30日内发表基金类观点最多的前10个理财师，每个理财师取（30日内发布的观点中）阅读量最多的一条观点
     */
    public function actionMostViewFundOf30Days(){
        try{
            $p = new MostViewFundOf30Days();
            $p->state();
            $this->monitorLog(MostViewFundOf30Days::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(MostViewFundOf30Days::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 1041
     * 取30日内回答基金类问题最多的前10个理财师，每个理财师取（30日回答问题中）解锁数最多的问题
     */
    public function actionMostAskFundOf30Days(){
        try{
            $p = new MostAskFundOf30Days();
            $p->state();
            $this->monitorLog(MostAskFundOf30Days::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(MostAskFundOf30Days::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /*
     * 8012
     * 获取理财师的前50名并更新redis中数据
     */

    public function actionPlannerRank() {
        try {
            $p = new PlannerRank();
            $p->updatePlannerRank();
            $this->monitorLog(PlannerRank::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(PlannerRank::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1010
     * 自动解禁理财师
     */
    public function actionUnFreezePlanner() {
        try {
            $p = new UnfreezePlanner();
            $p->unfreeze();
            $this->monitorLog(UnfreezePlanner::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(UnfreezePlanner::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1011
     * 理财师
     */
    public function actionStatPlannerViewNum() {
        try {
            $p = new StatPlannerViewNum();
            $p->update();
            $this->monitorLog(StatPlannerViewNum::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(StatPlannerViewNum::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 8013
     * 理财师名片折线图数据
     */
    public function actionPlannerCardChartData() {
        try {
            $p = new PlannerCardChartData();
            $count = $p->CalculateCardData();
            Cron::model()->saveCronLog(PlannerCardChartData::CRON_NO, CLogger::LEVEL_INFO, "理财师名片折线图数据, planner count:".$count);
            $this->monitorLog(PlannerCardChartData::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(PlannerCardChartData::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1013
     * 理财师评级  观点包
     */
    public function actionGradePackage(){
        try {
            $start_time = microtime(true);
            $gradePackage = new GradePackage();
            $gradePackage->statData();
            $end_time = microtime(true);
            $run_time = $end_time - $start_time;
            $this->monitorLog(GradePackage::CRON_NO);
            Cron::model()->saveCronLog(GradePackage::CRON_NO, CLogger::LEVEL_INFO, "统计理财观点包评级,日期：" . date('Y-m-d H:i:s')."运行时间:".$run_time);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(GradePackage::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
    /**
     * 1012
     * 理财师评级 计划
     */
    public function actionGradePlan(){
        
        try {
            $start_time = microtime(true);
            $gradePlan = new GradePlan();
            $gradePlan->statData();
            $end_time = microtime(true);
            $run_time = $end_time - $start_time;
            $this->monitorLog(GradePlan::CRON_NO);
            Cron::model()->saveCronLog(GradePlan::CRON_NO, CLogger::LEVEL_INFO, "统计理财师计划评级,日期：" . date('Y-m-d H:i:s')."运行时间:".$run_time );
        } catch (Exception $e) {
            Cron::model()->saveCronLog(GradePlan::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1014
     * 每天定时给理财师发送  计划和观点包 新评价通知
     */
    public function actionGradeCommentToPlanner(){
        try {
            $action = new GradeCommentToPlanner();
            $action->process();
            $this->monitorLog(GradeCommentToPlanner::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(GradeCommentToPlanner::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1015
     * 给用户发送可以评价的通知
     */
    public function actionGradeCommentToUser(){
        try {
            $action = new GradeCommentToUser();
            $action->process();
            $this->monitorLog(GradeCommentToUser::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(GradeCommentToUser::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }


    // 理财师客户组
    public function actionCustomerGroup($type='', $time='')
    {
        try {
            if ($type == 'clear') { // 清除客户组数据
                $customer_group = new CustomerGroup();
                $customer_group->clrUserGrpData();
            } elseif ($type == 'year') { // $time那一年的全部数据
                $customer_group = new CustomerGroup('year');
                $customer_group->initUserGrp($time);
            } elseif ($type == 'day') { // $time当天的数据
                $customer_group = new CustomerGroup('day');
                $customer_group->dailyUserGrp($time);
            } else {}
            $this->monitorLog(CustomerGroup::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(CustomerGroup::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    // 理财师消息推送
    public function actionCustomerMsgPush($time="")
    {
        try {
            $customer_msg_push = new CustomerMsgPush();
            $customer_msg_push->pushMsgStatistics($time);
            $this->monitorLog(CustomerMsgPush::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(CustomerMsgPush::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    // 理财师消息推送次数
    public function actionCustomerMsgPushTimes()
    {
        try {
            $customer_msg_push_times = new CustomerMsgPushTimes();
            $customer_msg_push_times->resetPlannerPushTimes();
            $this->monitorLog(CustomerMsgPushTimes::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(CustomerMsgPushTimes::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1050
     * 圈子直播公告开始提醒
     */
    public function actionCircleLiveNoticeStart()
    {
        try {
            $circle_list_notice = new CircleLiveNotice();
            $circle_list_notice->process();
            $this->monitorLog(CircleLiveNotice::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(CircleLiveNotice::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1111
     * 获取理财师潜在用户
     * shixi_shifeng add 2016-05-03
     */
    public function actionPotentialUser() {
        try {
            $potentialUser = new PotentialUser();
            $potentialUser->insertOrUpdatePotentialData();
            $this->monitorLog(PotentialUser::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(GradePlan::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
    /**
     * 1024
     * 付费客户组
     */
    public function actionPayCustomer(){
       try {
           $start_time = microtime(true);
           $payCustomer = new PayCustomer();
           $affect_num = $payCustomer->statData();
           $end_time = microtime(true);
           $run_time = $end_time - $start_time;
           $this->monitorLog(GradePlan::CRON_NO);
           Cron::model()->saveCronLog(PayCustomer::CRON_NO, CLogger::LEVEL_INFO, "统计理财师付费客户组,日期：" . date('Y-m-d H:i:s')."运行时间:".$run_time ." 写入或更新行数:".$affect_num);
       } catch (Exception $e) {
           Cron::model()->saveCronLog(PayCustomer::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
       } 
    }
    
    /**
	 * 1016
     * 统计观点包星级、理财师星级，打败用户百分比
     */
    public function actionGradePercent(){
        try {
            $start_time = microtime(true);
            
            $gradePercent =  new GradePercent();
            //计划星级打败百分比
            $gradePercent->statPlanGradePercent();
            //观点星级打败百分比
            $gradePercent->statPkgGradePercent();            
            $end_time = microtime(true);
            $run_time = $end_time - $start_time;
            $this->monitorLog(GradePercent::CRON_NO);
            Cron::model()->saveCronLog(GradePercent::CRON_NO,CLogger::LEVEL_INFO, "统计理财师付费客户组,日期：" . date('Y-m-d H:i:s')."运行时间:".$run_time);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(GradePercent::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    
    public function actionMatchRank(){                       
        try{
            $start_time = microtime(true);
            $rank = new PlannerMatchRank();            
            // $rank->handle('10004');
            // $rank->handle('10005');
            $rank->handle('10006');
            $end_time = microtime(true);
            $run_time = $end_time - $start_time;
            $this->monitorLog(PlannerMatchRank::CRON_NO);
            Cron::model()->saveCronLog(PlannerMatchRank::CRON_NO,CLogger::LEVEL_INFO, "更新机构投顾大赛排行榜：" . date('Y-m-d H:i:s')."运行时间:".$run_time);
        } catch (Exception $ex) {
            Cron::model()->saveCronLog(PlannerMatchRank::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($ex)->toJsonString());
        }        
    }
    
    public function actionImportMatchPlanner(){
        $i = new ImportMatchPlanner();
        $i->handle('10004');
    }
    public function actionCreateOnePlanner(){
        $i = new ImportMatchPlanner();
        $i->hanlePlanner();
    }
    
    public function actionCreatePlan(){
        $i = new ImportMatchPlanner();                
        $planner[] = array('p_uid'=>'1789578644','name'=>'史小凯');
        $planner[] = array('p_uid'=>'2374359905','name'=>'李悦');
        $matchid = 10006;
        $i->createPlan($planner,$matchid);
    }
    
    public function actionImportnanjing(){
        $i = new ImportMatchPlanner();
        $i->hanleNanjing('10002');
    }
    
    public function actionImportRedis(){
        $i = new ImportMatchPlanner();
        $i->importRedisPlanner();
        // $i->createXinda();
    }
    
    
    public function actionFixXinda(){
        $i = new ImportMatchPlanner();
        $i->fixDepartment();
    }
    
    public function actionFixRepeat(){
        $i = new ImportMatchPlanner();
        $i->fixRepeat();
    }
    public function actionGetCoupon($matchid=10001){
        $i = new ImportMatchPlanner();
        $i->checkLeave($matchid);
        
    }
    public function actionAddMatch(){
        $i = new ImportMatchPlanner();
        $i->addMatch();
    }
    
    public function actionWangHong(){
        $w = new ImportWanghong();
        $w->handle();
    }
}
