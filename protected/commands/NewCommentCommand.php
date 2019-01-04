<?php

/*
 * Function: 新版大家说
 * Desc: 1 从分表中批量删除说说
 * Author: meixin@staff.sina.com.cn
 * Date: 2015/11/52
 */

class NewCommentCommand extends LcsConsoleCommand {

	public function init() {
		Yii::import('application.commands.newComment.*');
	}

	/**
	 * 从队列里pop数据， 把分表里的数据删除掉 并删除缓存
	 */
	public function actionDeleteComment() {
		try {
			$end = time()+60;
			while(time()<=$end){
				$cmn_obj = new DeleteComment();
				$res_num = $cmn_obj->delComment();
				if ($res_num > 0) {
					$string = date('Y-m-d H:i:s') . ' 从分表里批量删除说说: ' . $res_num;
					Cron::model()->saveCronLog(DeleteComment::CRON_NO, CLogger::LEVEL_INFO, $string);
					echo $string . "\r\n";
				}
				sleep(1);
			}
			//记录任务结束时间
			$this->monitorLog(DeleteComment::CRON_NO);  //update  cron监控任务表
		} catch (Exception $e) {
			Cron::model()->saveCronLog(DeleteComment::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

	/**
	 * 删除master主表数据
	 */
	public function actionDeleteCommentMaster() {
		try {
			$cmn_obj = new DeleteCommentMaster();
			$cmn_obj->deleteMaster();
			//记录任务结束时间
			$this->monitorLog(DeleteComment::CRON_NO);  //update  cron监控任务表
		} catch (Exception $e) {
			Cron::model()->saveCronLog(DeleteCommentMaster::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

	public function actionSyncUserStaff() {
		$s = new SyncUserStaff();
		$s->sync();
	}

	/**
	 * 迁移大家说
	 */
	public function actionUpgradeCommentList() {
		$u = new CommentUpgrade();
		$u->dbUpgrade();
		$u->updateHotComment();
	}

	public function actionUpgradeCommentNum() {
		$u = new CommentUpgrade();
		$u->commentNumUpgrade();
		$u->addCommentMaster();
		$u->updateQuality();
		$u->updateRootReplyid();
	}

	public function actionPlanMove($minid = 1, $maxid = 2) {
		$u = new CommentMove();
		$u->planMove($minid, $maxid);
	}

	public function actionViewMove($minid = 1, $maxid = 2) {
		$u = new CommentMove();
		$u->viewMove($minid, $maxid);
	}

	public function actionOtherMove($minid = 1, $maxid = 2) {
		$u = new CommentMove();
		$u->otherMove($minid, $maxid);
	}

	public function actionCommentNum() {
		$u = new CommentMove();
		$u->commentNumUpgrade();
	}

	public function actionHotComment() {
		$u = new CommentMove();
		$u->updateQuality();
		// $u->updateHotComment();
	}

	public function actionAddMaster() {
		$u = new CommentMove();
		$u->addCommentMaster();
	}

	public function actionDelComment() {
		$u = new CommentMove();
		$u->deleteComment();
	}

	public function actionFixReply() {
		$u = new CommentMove();
		$u->fixReplyid();
	}

	public function actionRoot() {
		$u = new CommentMove();
		$u->updateRootReplyid();
	}

	public function actionFixNum($r = 0) {
		$u = new CommentMove();
		$u->finxCommentNum($r);
	}

	public function actionXin($run = 0) {
		$u = new CommentMove();
		$u->updateXinComment($run);
	}

	public function actionInitGoim($domain){
		$i = new InitGoimRedis();
		$i->run($domain);
	}
}
