<?php

/**
 * 投票活动 - 世界中心之新浪理财师崛起
 *
 * @author Administrator
 */
class ActionVoteCommand extends LcsConsoleCommand
{

	public function init()
	{
		Yii::import('application.commands.actionVote.*');
	}

	public function actionCalculate()
	{
		try {
			$VOTE = new Vote();
			$VOTE->run();
			$this->monitorLog(Vote::CRON_NO);
		} catch (Exception $e) {
			Cron::model()->saveCronLog(Vote::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

	public function actionVoteCount()
	{
		try {
			$vote = new VoteCount();
			$vote->run();
			$this->monitorLog(VoteCount::CRON_NO);
		} catch (Exception $e) {
			Cron::model()->saveCronLog(VoteCount::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

	public function actionSetOldData()
	{
		try {
			$VOTE = new Vote();
			$VOTE->setOldData();
			$this->monitorLog(Vote::CRON_NO);
		} catch (Exception $e) {
			Cron::model()->saveCronLog(Vote::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

}
