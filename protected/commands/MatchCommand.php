<?php
/**
 * 百万股神大赛  任务编号  20180608 - 20180618
 * User: lining
 * Date: 20180608
 */

class MatchCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.match.*');
    }

    /**
     * 20180608 感兴趣
     *
     */
    public function actionInterested(){
        try {
            $cv = new MatchPush();
            $cv->interested();
            $this->monitorLog(MatchPush::CRON_NO_INTERESTED);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(MatchPush::CRON_NO_INTERESTED, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 20180609 报名
     *
     */
    public function actionSignUp(){
        try {
            $cv = new MatchPush();
            $cv->signUp();
            $this->monitorLog(MatchPush::CRON_NO_SIGNUP);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(MatchPush::CRON_NO_SIGNUP, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 20180609 参赛
     *
     */
    public function actionMatchTrade(){
        try {
            $cv = new MatchPush();
            $cv->matchTradePush();
            $this->monitorLog(MatchPush::CRON_NO_TRADE);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(MatchPush::CRON_NO_TRADE, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     * 20180609 奖金
     *
     */
    public function actionMoney(){
        try {
            $cv = new MatchPush();
            $cv->moneyPush();
            $this->monitorLog(MatchPush::CRON_NO_MONEY);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(MatchPush::CRON_NO_MONEY, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 奖金收入
     */
    public function actionRankIncome(){
        try {
            $is_tradeday = ThirdCallService::JudgeTradeDay();
            if($is_tradeday){
                $cv = new RankIncome();
                $cv->dayRank();
                $cv->weekRank();
                $this->monitorLog(RankIncome::CRON_NO);
            }
        }catch (Exception $e) {
            Cron::model()->saveCronLog(RankIncome::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 重新排行邀请榜
    */
    public function actionReInviteRank(){
        try {
            $iv = new MatchInvite();
            $data = $iv->getinvitenum();
            if(!empty($data)){
                $iv->makeinvite($data);
            }
            $this->monitorLog(MatchInvite::CRON_NO_REINVITERANK);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(MatchInvite::CRON_NO_REINVITERANK, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    //同步战队和交易信息
    public function actionSyncUserCorpsAndTrade(){
        try {
            $sync = new SyncUserCorpsAndTrade();
            $sync->UserCorpsTrade();
            $this->monitorLog(SyncUserCorpsAndTrade::CRON_NO_UPDATE);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(SyncUserCorpsAndTrade::CRON_NO_UPDATE, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    //同步用户战队信息
    public function actionSyancUserCorps(){
        try {
            $sync = new SyncUserCorpsAndTrade();
            $sync->upUserTeam();
            $this->monitorLog(SyncUserCorpsAndTrade::CRON_NO_UPDATE);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(SyncUserCorpsAndTrade::CRON_NO_UPDATE, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    //同步真实手机号
    public function actionSyncUserPhone(){
        try {
            $sync = new SyncUserCorpsAndTrade();
            $sync->updateUserPhone();
            $this->monitorLog(SyncUserCorpsAndTrade::CRON_NO_UPDATE);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(SyncUserCorpsAndTrade::CRON_NO_UPDATE, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    //同步邀请榜单
    public function actionSyncInviteNumber(){
        try {
            $sync = new SyncUserCorpsAndTrade();
            $sync->inviteRank();
            $this->monitorLog(SyncUserCorpsAndTrade::CRON_NO_UPDATE);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(SyncUserCorpsAndTrade::CRON_NO_UPDATE, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
}
