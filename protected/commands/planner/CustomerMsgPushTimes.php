<?php


/**
* 理财师客户消息推送
* wiki:
* add by zhihao6 2016/06/14
*/

class CustomerMsgPushTimes
{
	const CRON_NO = 1032; //任务代码
	const DEFAULT_PUSH_TIMES = 5; // 系统默认推送次数
    const DEFAULT_PUSH_RULER = 2; // 系统默认推送规则 1天  2周  3月

	function __construct()
	{}

	// 理财师剩余推送次数重置
	public function resetPlannerPushTimes()
	{
		$curr_time = date("Y-m-d H:i:s");

		$sql = "UPDATE lcs_planner_push_info SET times=". self::DEFAULT_PUSH_TIMES ." WHERE 1;";
		Yii::app()->lcs_w->createCommand($sql)->execute();

		Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "[OK]:理财师客户分组消息推送次数:{$curr_time}");
	}


}

