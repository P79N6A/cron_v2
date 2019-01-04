<?php

/**
 * Description of CheckPlannerCommand
 * @require_title 理财师资格号码 官网匹配
 * @require_url http://wiki.ggt.sina.com.cn/pages/viewpage.action?pageId=4784543
 *
 * @author lixiaocheng<xiaocheng.li@yintech.cn>
 */
class CheckPlannerCommand extends LcsConsoleCommand {

	public function init() {
		Yii::import('application.commands.checkPlanner.*');
	}

	/**
	 * Run
	 * @command yiic.php checkplanner run
	 */
	public function actionRun() {
		try {
			(new Check())->run();
			$this->monitorLog(Check::CRON_NO);
			Cron::model()->saveCronLog(Check::CRON_NO, CLogger::LEVEL_INFO, 'run...');
		} catch (Exception $e) {
			Cron::model()->saveCronLog(Check::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

	/**
	 * 迁移理财师身份证号码到理财师主表
	 * @command yiic.php checkplanner moveplaneridcarddata
	 */
	public function actionMovePlanerIDCardData() {
		try {
			(new Move())->run();
			$this->monitorLog(Move::CRON_NO);
			Cron::model()->saveCronLog(Move::CRON_NO, CLogger::LEVEL_INFO, 'moveing...');
		} catch (Exception $e) {
			Cron::model()->saveCronLog(Move::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

	/**
	 * temp
	 * @command yiic.php checkplanner addfiled
	 */
	public function actionAddFiled() {
		(new Del())->AddFiled();
	}

	/**
	 * temp
	 * @command php yiic.php checkplanner delcache
	 */
	public function actionDelcache() {
		(new Del())->delCache();
	}

	/**
	 * ChangeCompany
	 * @command php yiic.php checkplanner changeCompany
	 */
	public function actionChangeCompany() {
		(new ChangeCompany())->run();
	}

}
