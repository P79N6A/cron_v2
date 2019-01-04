<?php

/**
 * 理财师客户分组
 * Wiki: http://wiki.intra.sina.com.cn/pages/viewpage.action?pageId=99092313
 * add by zhihao6 - 2016/05/11
 *
 * 以增量方式，跑出前一天客户分组信息
 */


class CustomerGrpStart
{
}

class CustomerGroup
{
	const CRON_NO = 1030; //任务代码

	public static $time_deadline = array(
		"view"   => 14, // 观点包未续费期限
		"plan_1" => 30, // 计划停止期限1
		"plan_2" => 60, //计划停止期限2
	);

	public static $user_grp_type = array(
		"default_init_view"     => -1, // 初始化占用的grp_id, type=0
		"default_init_plan"     => -2, // 初始化占用的grp_id, type=0
		"planner_def"           => 1, // 自定义客户组
		"star_user"             => 2, // 星标客户组
		"potential_user"        => 3, // 潜在客户组
		"paid_user"             => 4, // 付费客户组
		"discontinue_paid_user" => 5, // 未续费客户组
		"lost_user"             => 6, // 流失客户组
	);

	private $is_daily; // 定时任务跑的类型  1每天跑  0初始化

	private $planner; // 理财师集合
	private $grp_info; // 理财师客户分组信息
	private $day_range;	// 用于标识哪一{天 | 区间}的数据
	private $free_pkg_ids; // 理财师免费观点包集合
	private $paid_pkg_ids; // 理财师付费观点包集合
	private $pln_ids; // 2待运行 3运行中 4成功 5失败 6止损失败 7到期冻结 的计划id，按创建时间倒序排列
	public function __construct($type='')
	{
		if ($type == 'day') {
			$curr_time = date("Y-m-d H:i:s");
		} else {
			$curr_time = "";
		}

		$this->planner = $this->getPlanner($curr_time);
		// $this->planner = array(
		// 	2318006357,  // 测试环境
		// 	3270084643,  // 测试环境
		// 	1871038017,  // 测试环境
		//	2700739381,  // 测试环境
		// 	5696300284,  // 测试环境
		//	3218584774,  // 测试环境
		// 	1789578644,  // 测试环境
		// 	3046552733,  // 测试环境
			// 1239417764,  // 线上理财师
			// 1657765690,  // 线上理财师
			// 2177007684,  // 线上理财师
			// 1655008812,  // 线上理财师
			// 3583878272,  // 线上理财师
			// 2373637277,  // 线上理财师
		// );
	}

	public function getRedisGrpStatKey($p_uid, $time='')
	{
		if (empty($time)) {
			return MEM_PRE_KEY . "cstm_grp_{$p_uid}_stat_".date("Ymd", time()-86400);
		} else {
			return MEM_PRE_KEY . "cstm_grp_{$p_uid}_stat_".date("Ymd", strtotime($time));
		}
	}

	// =====================================================================
	// 清数据
	public function clrUserGrpData()
	{
    	foreach ($this->planner as $cc => $p_uid) {
    		// old redis key
    		$redis_key = MEM_PRE_KEY . "cstm_grp_{$p_uid}_stat";
    		print_r("[{$cc}] delete redis key [{$redis_key}]\t.......");
    		Yii::app()->redis_w->delete($redis_key);
    		print_r(".......\t[OK]\n");

    		// 
    		// print_r("[{$cc}] clear group data\t.......");
    		// $sql = "DELETE FROM lcs_planner_customer WHERE p_uid={$p_uid};
    		// 		DELETE FROM lcs_planner_customer_group WHERE p_uid={$p_uid};
    		// 		DELETE FROM lcs_planner_group WHERE p_uid={$p_uid};
    		// 		DELETE FROM lcs_planner_group_stat WHERE p_uid={$p_uid};
    		// 		DELETE FROM lcs_planner_push_info WHERE p_uid={$p_uid};
    		// 		DELETE FROM lcs_planner_push_msg WHERE p_uid={$p_uid};";
    		// $this->doSqlExecute($sql);
    		// print_r(".......\t[OK]\n");
    	}
    	print_r("\n");
	}

	// =====================================================================
    // 客户分组初始化
    public function initUserGrp($the_time)
    {
    	$this->is_daily = 0;

    	if (empty($the_time)) {
    		print_r("请先传入待初始化数据的年份日期\n");
    		return ;
    	}

    	$before_loop_time = $this->markTime();
    	foreach ($this->planner as $cc => $p_uid) {
    		print_r("[{$cc}] {$p_uid}:\t.......");

    		$this->initSystemDefaultPushInfo($p_uid); // 初始化理财师推送信息

			$this->grp_info     = $this->getPlannerGrp($p_uid);
			$this->free_pkg_ids = $this->getPlannerPkg($p_uid, -1);
			$this->paid_pkg_ids = $this->getPlannerPkg($p_uid, 1);
			$this->pln_ids      = $this->getPlannerPlan($p_uid);

	    	// 当前日期三个月内的符合客户 ---> 潜在客户组
	    	if (date("Y") == date("Y", strtotime($the_time))) {
				$this->day_range['start'] = date("Y-m-d 00:00:00", mktime(0,0,0,date("m")-2,1,date("Y")));
				$this->day_range['end']   = date('Y-m-d 23:59:59', time() - 86400);
		    	$this->potentialUserGrp($p_uid);
	    	}
	    	
	    	// 指定日期当年的所有数据，根据{观点包 | 计划}进行初始化
			$this->day_range['start'] = date('Y-01-01 00:00:00', strtotime($the_time));
			$this->day_range['end']   = date('Y-12-31 23:59:59', strtotime($the_time));
	    	// 购买过观点包的客户 ---> 临时标签：[观点包订阅中客户]
	    	$uids_pkg_paid = $this->getPkgPaidUser($this->paid_pkg_ids);
			$this->updateCustomerInfo($p_uid, self::$user_grp_type['default_init_view'], $uids_pkg_paid, array('t_new' => 0, 't_view' => 1));
			unset($uids_pkg_paid);
			// 购买过计划的客户 ---> 临时标签：[购买当期计划客户]
			$uids_plan_paid = $this->getPlanPaidUser($this->pln_ids);
			$this->updateCustomerInfo($p_uid, self::$user_grp_type['default_init_plan'], $uids_plan_paid, array('t_new' => 0, 't_plan' => 1));
			unset($uids_plan_paid);

			// 初始化完毕的客户分组数据进行分组的转移
			if (date("Y") == date("Y", strtotime($the_time))) {
				$this->day_range['start'] = date('Y-m-d 00:00:00', time() - 86400);
				$this->day_range['end']   = date('Y-m-d 23:59:59', time() - 86400);
				$this->pkgStatusMoveGrp($p_uid); // 观点包状态对应客户组的转移
				$this->PlanStatusMoveGrp($p_uid); // 计划状态对应客户组的转移
				$this->moveToPaidGrp($p_uid); // 付费客户组的转移
			}

			$this->restPaidUserMoveToLostGrp($p_uid); // 剩余的付费客户归为流失客户组

			print_r(".......\t[OK]\n");
    	}
    	$after_loop_time = $this->markTime();
		$this->timeUsagePrint("loop time", $before_loop_time, $after_loop_time);
    }
    // 观点包状态转移客户组
    // time line:  -----14日外-----|第14日|-----14日内-----|当前时间|
    private function pkgStatusMoveGrp($p_uid)
    {
    	if (!empty($this->paid_pkg_ids)) {
	    	// 14日的期限
	    	$view_deadline = date("Y-m-d", strtotime($this->day_range['end']) - self::$time_deadline['view'] * 86400);

	    	// 观点包过期14日内 ---> 未续费客户组
	    	$sql = "SELECT DISTINCT uid FROM lcs_package_subscription WHERE pkg_id IN (". implode(',', $this->paid_pkg_ids) .") AND '{$view_deadline}' < end_time AND end_time <= '{$this->day_range['end']}'";
	    	$uids_discontinue = $this->doSqlQueryColumn($sql, true);
	    	$this->updateCustomerInfo($p_uid, self::$user_grp_type['discontinue_paid_user'], $uids_discontinue, array('t_new' => 0, 't_view' => 1));
	    	unset($uids_discontinue);

	    	// 观点包过期14日外 ---> 流失客户组
	    	$sql = "SELECT DISTINCT uid FROM lcs_package_subscription WHERE pkg_id IN (". implode(',', $this->paid_pkg_ids) .") AND end_time <= '{$view_deadline}'";
	    	$uids_lost = $this->doSqlQueryColumn($sql, true);
	    	$this->updateCustomerInfo($p_uid, self::$user_grp_type['lost_user'], $uids_lost, array('t_new' => 0, 't_view' => 1));
	    	unset($uids_lost);
    	}
    }
    // 计划状态转移客户组
    // time line:  -----|之前的计划|----|上一期计划|----|当期计划|
    // 当期计划 time line:  -----60天外-----|第60天|------30天外------|第30天|------|中止时间|
    private function PlanStatusMoveGrp($p_uid)
    {
    	$all_pln_ids = $this->pln_ids;
    	if (!empty($all_pln_ids)) {
    		$curr_plan = array_shift($all_pln_ids);

    		// 购买当期计划
    		$sql = "SELECT DISTINCT uid FROM lcs_plan_subscription WHERE pln_id={$curr_plan}";
    		$uids_paid = $this->doSqlQueryColumn($sql, true);

    		$plan_deadline_1 = date("Y-m-d", strtotime($this->day_range['end']) - self::$time_deadline['plan_1'] * 86400);
    		$plan_deadline_2 = date("Y-m-d", strtotime($this->day_range['end']) - self::$time_deadline['plan_2'] * 86400);
    		$curr_plan_info = $this->getPlanInfo($curr_plan);
    		if (!in_array($curr_plan_info['status'], array(2,3)) &&
    					$plan_deadline_2 < $curr_plan_info['real_end_time'] && 
    					$curr_plan_info['real_end_time'] <= $plan_deadline_1) { // 已中止、30~60天内未开计划 ---> 未续费客户组
    			$this->updateCustomerInfo($p_uid, self::$user_grp_type['discontinue_paid_user'], $uids_paid, array('t_new' => 0, 't_plan' => 1));
    		} elseif (!in_array($curr_plan_info['status'], array(2,3)) &&
    				$curr_plan_info['real_end_time'] <= $plan_deadline_2) { // 已中止、60天外未开计划 ---> 流失客户组
    			$this->updateCustomerInfo($p_uid, self::$user_grp_type['lost_user'], $uids_paid, array('t_new' => 0, 't_plan' => 1));
    		} else {}

    		if (!empty($all_pln_ids)) {
    			$prev_plan = array_shift($all_pln_ids);

    			// 上一期买了，当期没买 ---> 未续费客户组
    			$sql = "SELECT DISTINCT uid FROM lcs_plan_subscription WHERE pln_id={$prev_plan}";
    			$uids_paid_prev = $this->doSqlQueryColumn($sql, true);
    			$uids_paid_prev = array_diff($uids_paid_prev, $uids_paid);
    			$this->updateCustomerInfo($p_uid, self::$user_grp_type['discontinue_paid_user'], $uids_paid_prev, array('t_new' => 0, 't_plan' => 1));
    		
    			if (!empty($all_pln_ids)) {
    				// 连续两期没买 ---> 流失客户组
    				$sql = "SELECT DISTINCT uid FROM lcs_plan_subscription WHERE pln_id IN (".implode(',', $all_pln_ids).")";
    				$uids_paid_before = $this->doSqlQueryColumn($sql, true);
    				$uids_paid_before = array_diff($uids_paid_before, $uids_paid_prev, $uids_paid);
    				$this->updateCustomerInfo($p_uid, self::$user_grp_type['lost_user'], $uids_paid_before, array('t_new' => 0, 't_plan' => 1));
    			}
    		}
    	}
    }
    // 付费客户组转移
    // 只要观点包，或计划有一个在符合条件的付费状态
    private function moveToPaidGrp($p_uid)
    {
    	if (!empty($this->paid_pkg_ids)) {
	    	// 观点包仍在订阅中 ---> 付费客户组
	    	$sql = "SELECT DISTINCT uid FROM lcs_package_subscription WHERE pkg_id IN (". implode(',', $this->paid_pkg_ids) .") AND end_time > '{$this->day_range['end']}'";
	    	$uids_paid = $this->doSqlQueryColumn($sql, true);
	    	$this->updateCustomerInfo($p_uid, self::$user_grp_type['paid_user'], $uids_paid, array('t_new' => 0, 't_view' => 2));
	    	unset($uids_paid);
	    }

	    if (!empty($this->pln_ids)) {
    		$curr_plan = $this->pln_ids['0'];

    		// 购买当期计划
    		$sql = "SELECT DISTINCT uid FROM lcs_plan_subscription WHERE pln_id={$curr_plan} AND status>=1";
    		$uids_paid = $this->doSqlQueryColumn($sql, true);

    		$plan_deadline_1 = date("Y-m-d", strtotime($this->day_range['end']) - self::$time_deadline['plan_1'] * 86400);
    		$curr_plan_info = $this->getPlanInfo($curr_plan);
    		if (in_array($curr_plan_info['status'], array(2,3)) || 
    				$plan_deadline_1 < $curr_plan_info['real_end_time']) { // 待运行、运行中、30天内未开计划 ---> 付费客户组
    			$this->updateCustomerInfo($p_uid, self::$user_grp_type['paid_user'], $uids_paid, array('t_new' => 0, 't_plan' => 2));
	    	}
	    }
    }
    // 剩余的初始化数据归为流失客户组
    private function restPaidUserMoveToLostGrp($p_uid)
    {
    	$sql = "SELECT DISTINCT uid FROM lcs_planner_customer_group WHERE p_uid={$p_uid} AND grp_id=".self::$user_grp_type['default_init_view'];
    	$res = $this->doSqlQueryColumn($sql, true);
    	if (!empty($res)) {
    		$this->updateCustomerInfo($p_uid, self::$user_grp_type['lost_user'], $res, array('t_new' => 0, 't_view' => 1));
    	}

    	$sql = "SELECT DISTINCT uid FROM lcs_planner_customer_group WHERE p_uid={$p_uid} AND grp_id=".self::$user_grp_type['default_init_plan'];
    	$res = $this->doSqlQueryColumn($sql, true);
    	if (!empty($res)) {
    		$this->updateCustomerInfo($p_uid, self::$user_grp_type['lost_user'], $res, array('t_new' => 0, 't_plan' => 1));
    	}
    }

    // =====================================================================
    // 客户分组每日增量跑
    public function dailyUserGrp($day_time='')
    {
    	$this->is_daily = 1;

    	if (empty($day_time)) { // 前一天的数据
	    	$the_day = date('Y-m-d', time() - 86400);
    	} else { // 传入时间当天
    		$the_day = date('Y-m-d', strtotime($day_time));
    	}

		$this->day_range['start'] = date('Y-m-d 00:00:00', strtotime($the_day));
		$this->day_range['end']   = date('Y-m-d 23:59:59', strtotime($the_day));
	    print_r($this->day_range);

		$before_loop_time = $this->markTime();
		Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "[START]:理财师客户分组数据:{$this->day_range['start']}:".json_encode(array("before_loop_time" => $before_loop_time)));

		foreach ($this->planner as $cc => $p_uid) {
			print_r("[{$cc}] {$p_uid}:\t.......");

			$this->grp_info = $this->getPlannerGrp($p_uid);
			$this->free_pkg_ids = $this->getPlannerPkg($p_uid, -1);
			$this->paid_pkg_ids = $this->getPlannerPkg($p_uid, 1);
			$this->pln_ids = $this->getPlannerPlan($p_uid);

			// 指定时间段内新增的
			$this->potentialUserGrp($p_uid);
			$this->paidUserGrp($p_uid);
			$this->discontinuePaidUserGrp($p_uid);
			$this->lostUserGrp($p_uid);

			// 其他随时间状态转移的
			$this->restCaseGrpMove($p_uid);

			// 分组信息统计
			$this->logGrpUpdateStat($p_uid);

			print_r(".......\t[OK]\n");
		}
		$after_loop_time = $this->markTime();

		$this->groupCustomerTotalStat();
		$this->historyGrpDataClr();
		$after_clear_time = $this->markTime();

		$this->timeUsagePrint("loop time", $before_loop_time, $after_loop_time);
		$this->timeUsagePrint("rest time", $after_loop_time, $after_clear_time);

		Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "[END]:理财师客户分组数据:{$this->day_range['start']}:".json_encode(array("before_loop_time" => $before_loop_time,
																																			"after_loop_time" => $after_loop_time,
																																			"after_clear_time" => $after_clear_time,
																																			"total_run_time" => $this->elapsedTime($before_loop_time, $after_clear_time)."秒",
																																			"total_planner" => count($this->planner))));
    }

    // =====================================================================
    // 分组详情
    // 新增潜在客户组
	private function potentialUserGrp($p_uid)
	{
		// 关注理财师的客户 ----> 标签：[粉丝-关注理财师]、[新客户]
		$uids_attention = $this->getAttentionUser($p_uid);

		// 免费提问的客户 ----> 标签：[新客户]
		$uids_free_question = $this->getAskQuestion($p_uid, 0);

		// 关注观点包（付费观点包+免费观点包） ----> 标签：[新客户]
		$pkg_ids = array_merge($this->free_pkg_ids, $this->paid_pkg_ids);
    	$uids_pkg_collect = $this->getPkgCollectUser($pkg_ids);
    	unset($pkg_ids);

    	// 关注计划 ----> 标签：[新客户]
    	$uids_plan_collect = $this->getPlanCollectUser($this->pln_ids);

		// 有支付记录客户（区分未付费，而不是依赖后续其他组更新的原因，是为了更精确的统计新增客户数）
		$all_uids = array_merge($uids_attention, $uids_free_question, $uids_pkg_collect, $uids_plan_collect);
    	$uids_paid = $this->filterUnpaidUser($p_uid, $all_uids);

		// 差集，取未支付的其他情况下的客户
		$uids_new = array_diff($all_uids, $uids_attention, $uids_paid);
		$this->updateCustomerInfo($p_uid, self::$user_grp_type['potential_user'], $uids_new, array('t_new' => 1, 't_follower' => 0));

		// 差集，取未支付的关注理财师客户
    	$uids_unpaid_attention = array_diff($uids_attention, $uids_paid);
		$this->updateCustomerInfo($p_uid, self::$user_grp_type['potential_user'], $uids_unpaid_attention, array('t_new' => 1, 't_follower' => 1));

		// 交集，有过支付记录的更新标签信息
		$uids_paid_attention = array_intersect($uids_attention, $uids_paid);
		$this->updateCustomerTagInfo($p_uid, $uids_paid_attention, array('t_follower' => 1));
	}
	// 新增付费客户组
	private function paidUserGrp($p_uid)
	{
		// 标签：[观点包订阅中客户]
		// 付费观点包订阅中
    	$uids_pkg_continue_paid = $this->getPkgPaidUser($this->paid_pkg_ids, 1);
		$this->updateCustomerInfo($p_uid, self::$user_grp_type['paid_user'], $uids_pkg_continue_paid, array('t_new' => 0, 't_view' => 2));

		// 标签：[购买当期计划客户]
		// 购买最新一期计划的客户
		if (!empty($this->pln_ids)) {
			$pln_id = $this->pln_ids['0'];
    		$uids_plan_continue_paid = $this->getPlanPaidUser($pln_id);
    		$this->updateCustomerInfo($p_uid, self::$user_grp_type['paid_user'], $uids_plan_continue_paid, array('t_new' => 0, 't_plan' => 2));
		}
	}
	// 新增未续费客户组
	private function discontinuePaidUserGrp($p_uid)
	{
		// 标签：[观点包已过期客户]
		// 1. 未续费观点包（end_time）
		// 2. 过滤掉购买过计划的
    	$uids_pkg_discontinue_paid = $this->getPkgPaidUser($this->paid_pkg_ids, -1);
		$uids_pkg_paid_plan = $this->filterPaidPlanUser($uids_pkg_discontinue_paid);
		$uids_pkg_discontinue_paid = array_diff($uids_pkg_discontinue_paid, $uids_pkg_paid_plan);
		$this->updateCustomerInfo($p_uid, self::$user_grp_type['discontinue_paid_user'], $uids_pkg_discontinue_paid, array('t_new' => 0, 't_view' => 1));
		unset($uids_pkg_discontinue_paid);

		// 标签：[当期计划没再购买]
		// 1. 上一期购买、本期未够买计划（当天刚开始）
		// 2. 过滤掉购买观点包的
		$uids_plan_paid_pkg = array();
		if (count($this->pln_ids) >= 2) {
			$pln_id = $this->pln_ids['0'];
			$plan_info = $this->getPlanInfo($pln_id);
			if (date("Y-m-d") == date("Y-m-d", strtotime($plan_info['panic_buy_time']))) {
				$uids_curr_plan_paid = $this->getPlanPaidUser($pln_id);

				$pln_id = $this->pln_ids['1'];
				$uids_prev_plan_paid = $this->getPlanPaidUser($pln_id);
				$uids_plan_discontinue_paid = array_diff($uids_prev_plan_paid, $uids_curr_plan_paid);
				unset($uids_curr_plan_paid);unset($uids_prev_plan_paid);

				$uids_plan_paid_pkg = $this->filterPaidPkgUser($uids_plan_discontinue_paid);

				$uids_plan_discontinue_paid = array_diff($uids_plan_discontinue_paid, $uids_plan_paid_pkg);
				$this->updateCustomerInfo($p_uid, self::$user_grp_type['discontinue_paid_user'], $uids_plan_discontinue_paid, array('t_new' => 0, 't_plan' => 1));
				unset($uids_plan_discontinue_paid);
			}
		}
		
		// 标签：[观点包已过期客户]、[当期计划没再购买]
		// 未续费观点报且未购买本期计划
		$uids_and_discontinue = array_intersect($uids_plan_paid_pkg, $uids_pkg_paid_plan);
		$this->updateCustomerInfo($p_uid, self::$user_grp_type['discontinue_paid_user'], $uids_and_discontinue, array('t_new' => 0, 't_view' => 1, 't_plan' => 1));
	}
	// 新增流失客户组
	private function lostUserGrp($p_uid)
	{
		// 标签：[观点包已过期客户]
		// 14日观点包过期（end_time）
		// 只够买观点包
    	$ori_time_start = $this->day_range['start'];
    	$ori_time_end = $this->day_range['end'];
    	$this->day_range['start'] = date("Y-m-d 00:00:00", strtotime($ori_time_start) - (self::$time_deadline['view'])*86400);
    	$this->day_range['end'] = date("Y-m-d 23:59:59", strtotime($ori_time_end) - (self::$time_deadline['view'])*86400);
    	$uids_pkg_lost_paid = $this->getPkgPaidUser($this->paid_pkg_ids, -2);
    	$this->day_range['start'] = $ori_time_start;
    	$this->day_range['end'] = $ori_time_end;

		$uids_pkg_paid_plan = $this->filterPaidPlanUser($uids_pkg_lost_paid);

		$uids_pkg_lost_paid = array_diff($uids_pkg_lost_paid, $uids_pkg_paid_plan);
		$this->updateCustomerInfo($p_uid, self::$user_grp_type['lost_user'], $uids_pkg_lost_paid, array('t_new' => 0, 't_view' => 1));
		unset($uids_pkg_lost_paid);

		// 标签：[当期计划没再购买]
		// 连续两期计划未购买
		// 只够买过计划
		$uids_plan_paid_pkg = array();
		if (count($this->pln_ids) >= 2) {
	    	$uids_plan_lost_paid = $this->getPlanPaidUser($this->pln_ids);

	    	$pln_id = $this->pln_ids['0'];
			$plan_info = $this->getPlanInfo($pln_id);
			if (date("Y-m-d") == date("Y-m-d", strtotime($plan_info['panic_buy_time']))) {
				$uids_curr_plan_paid = $this->getPlanPaidUser($pln_id);
				$pln_id = $this->pln_ids['1'];
				$uids_prev_plan_paid = $this->getPlanPaidUser($pln_id);
				$uids_plan_lost_paid = array_diff($uids_plan_lost_paid, $uids_curr_plan_paid, $uids_prev_plan_paid);
				unset($uids_curr_plan_paid);unset($uids_prev_plan_paid);

				$uids_plan_paid_pkg = $this->filterPaidPkgUser($uids_plan_lost_paid);

				$uids_plan_lost_paid = array_diff($uids_plan_lost_paid, $uids_plan_paid_pkg);
				$this->updateCustomerInfo($p_uid, self::$user_grp_type['lost_user'], $uids_plan_lost_paid, array('t_new' => 0, 't_plan' => 1));
			}
			unset($uids_plan_lost_paid);
		}
		
		// 观点包过期且连续两期计划未购买		
		$uids_and_lost = array_intersect($uids_pkg_paid_plan, $uids_plan_paid_pkg);
		$this->updateCustomerInfo($p_uid, self::$user_grp_type['lost_user'], $uids_and_lost, array('t_new' => 0, 't_view' => 1, 't_plan' => 1));
	}
	// 其他情况下的客户组转移
	private function restCaseGrpMove($p_uid)
	{
		// 只够买计划，且购买本期计划已中止超30天，客户移至未续费客户组
		// 只够买计划，且购买本期计划已中止超60天，客户移至流失客户组
		if (!empty($this->pln_ids)) {
			$pln_id = $this->pln_ids['0'];
			$plan_info = $this->getPlanInfo($pln_id);
			if (!empty($pln_info) && !in_array($pln_info['status'], array(2,3))) {
				$plan_deadline_1 = date("Y-m-d H:i:s", time() - (self::$time_deadline['plan_1'])*86400);
				$plan_deadline_2 = date("Y-m-d H:i:s", time() - (self::$time_deadline['plan_2'])*86400);
				if ($plan_deadline_2 < $pln_info['real_end_time'] && 
						$pln_info['real_end_time'] <= $plan_deadline_1) { // 超30天
					$uids_paid_plan = $this->getPlanPaidUser(array($pln_info['pln_id']));
					$uids_plan_paid_pkg = $this->filterPaidPkgUser($uids_paid_plan);
					$uids_paid_plan = array_diff($uids_paid_plan, $uids_plan_paid_pkg);
					$this->updateCustomerInfo($p_uid, self::$user_grp_type['discontinue_paid_user'], $uids_paid_plan, array('t_new' => 0, 't_plan' => 1));
				} elseif ($pln_info['real_end_time'] <= $plan_deadline_2) { // 超60天
					$uids_paid_plan = $this->getPlanPaidUser(array($pln_info['pln_id']));
					$uids_plan_paid_pkg = $this->filterPaidPkgUser($uids_paid_plan);
					$uids_paid_plan = array_diff($uids_paid_plan, $uids_plan_paid_pkg);
					$this->updateCustomerInfo($p_uid, self::$user_grp_type['lost_user'], $uids_paid_plan, array('t_new' => 0, 't_plan' => 1));
				} else {}
			}
		}
		
		// 如果还有其他情况
		// ...
	}

	// =====================================================================
	// 客户组分析统计
    private function groupCustomerTotalStat()
    {
    	$curr_time = date("Y-m-d H:i:s");

    	// 总客户数
    	$sql = "SELECT p_uid,COUNT(*) AS total FROM lcs_planner_customer GROUP BY p_uid";
    	$res = $this->doSqlQueryAll($sql, true);
    	if (!empty($res)) {
    		foreach ($res as $pc) {
    			$redis_key = $this->getRedisGrpStatKey($pc['p_uid'], $this->day_range['start']);
    			$field_key = "grp_0_cstm_total_num";
    			Yii::app()->redis_w->hset($redis_key, $field_key, $pc['total']);
    			Yii::app()->redis_w->setTimeout($redis_key, 86400);
    		}
    	}
		// 星标客户组数、潜在客户组数、付费客户组数、未续费客户组数、流失客户组数
		$sql = "SELECT p_uid,grp_id,COUNT(*) AS total FROM lcs_planner_customer_group GROUP BY grp_id";
		$res = $this->doSqlQueryAll($sql, true);
		if (!empty($res)) {
			$sql_1 = "";
			$sql_2 = "";
			foreach ($res as $gc) {
				$sql_1 .= " UPDATE lcs_planner_group SET cstm_total_num={$gc['total']}, u_time='{$curr_time}' WHERE id={$gc['grp_id']};";
				$sql_2 .= " UPDATE lcs_planner_group_stat SET total_num={$gc['total']}, u_time='{$curr_time}' WHERE day_time='{$this->day_range['start']}' AND p_uid={$gc['p_uid']} AND grp_id={$gc['grp_id']};";
			}
			$this->doSqlExecute($sql_1);
			$this->doSqlExecute($sql_2);
		}

		// 没有查询统计到的分组，置为0
		$sql_1 = "UPDATE lcs_planner_group SET cstm_total_num=0, u_time='{$curr_time}' WHERE u_time!='{$curr_time}';";
		$this->doSqlExecute($sql_1);
    }
    // 历史旧数据清理
    private function historyGrpDataClr()
    {
    	// 新客标签属性清除，7天前的那一天
    	$eight_day_start = date("Y-m-d H:i:s", strtotime($this->day_range['start']) - 7*86400);
    	$eight_day_end = date("Y-m-d H:i:s", strtotime($this->day_range['end']) - 7*86400);
    	$sql = "UPDATE lcs_planner_customer SET t_new=0 WHERE '{$eight_day_start}' <= c_time AND c_time <= '{$eight_day_end}' AND t_new=1";
    	$this->doSqlExecute($sql);
    	
    	// ...
    }


    // =====================================================================
    // 数据获取部分
    private function doSqlQueryAll($sql, $is_w=false)
    {
    	$db = Yii::app()->lcs_r;
    	if ($is_w) {
    		$db = Yii::app()->lcs_w;
    	}
    	$res = $db->createCommand($sql)->queryAll();
    	if (empty($res)) {
    		return array();
    	} else {
    		return $res;
    	}
    }
    private function doSqlQueryRow($sql, $is_w=false)
    {
    	$db = Yii::app()->lcs_r;
    	if ($is_w) {
    		$db = Yii::app()->lcs_w;
    	}
    	$res = $db->createCommand($sql)->queryRow();
    	if (empty($res)) {
    		return array();
    	} else {
    		return $res;
    	}
    }
    private function doSqlQueryColumn($sql, $is_w=false)
    {
    	$db = Yii::app()->lcs_r;
    	if ($is_w) {
    		$db = Yii::app()->lcs_w;
    	}
    	$res = $db->createCommand($sql)->queryColumn();
    	if (empty($res)) {
    		return array();
    	} else {
    		return $res;
    	}
    }
    private function doSqlExecute($sql)
    {
    	$res = Yii::app()->lcs_w->createCommand($sql)->execute();
    	return $res;
    }
    // 筛选返回支付过的客户
    private function filterUnpaidUser($p_uid, $uids)
    {
    	if (empty($uids)) {
    		return array();
    	} else {
    		$sql = "SELECT DISTINCT uid FROM lcs_orders WHERE p_uid={$p_uid} AND uid IN (".implode(',', $uids).")";
    		return $this->doSqlQueryColumn($sql);
    	}
    }
    // 筛选返回购买过观点包的客户
    private function filterPaidPkgUser($uids)
    {
    	$pkg_ids = array_merge($this->free_pkg_ids, $this->paid_pkg_ids);

    	if (empty($uids) || empty($pkg_ids)) {
    		return array();
    	} else {
    		$sql = "SELECT DISTINCT uid FROM lcs_package_subscription WHERE pkg_id IN (". implode(',', $pkg_ids) .") ANS uid IN (".implode(',', $uids).")";
    		return $this->doSqlQueryColumn($sql);
    	}
    }
    // 筛选返回购买过计划的客户
    private function filterPaidPlanUser($uids)
    {
    	if (empty($uids) || empty($this->pln_ids)) {
    		return array();
    	} else {
    		$sql = "SELECT DISTINCT uid FROM lcs_plan_subscription WHERE pln_id IN (". implode(',', $this->pln_ids) .") AND uid IN (".implode(',', $uids).")";
    		return $this->doSqlQueryColumn($sql);
    	}
    }
    // 获取理财师
    private function getPlanner($time='')
    {
    	if (!empty($time)) {
    		$start = date("Y-m-d H:i:s", strtotime($time) - 90*86400);
    		$sql = "SELECT s_uid FROM lcs_planner WHERE status=0 AND '{$start}'<u_time";
    	} else {
    		$sql = "SELECT s_uid FROM lcs_planner WHERE status=0";
    	}
    	return $this->doSqlQueryColumn($sql);
    }
    // 获取理财师的观点包
    private function getPlannerPkg($p_uid, $price_type=0)
    {
    	if ($price_type === 1) { // 付费观点包
    		$sql = "SELECT id FROM lcs_package WHERE p_uid={$p_uid} AND status=0 AND subscription_price>0";
    	} elseif ($price_type === -1) { // 免费观点包
    		$sql = "SELECT id FROM lcs_package WHERE p_uid={$p_uid} AND status=0 AND subscription_price=0";
    	} else { // 所有观点包
    		$sql = "SELECT id FROM lcs_package WHERE p_uid={$p_uid} AND status=0";
    	}

    	return $this->doSqlQueryColumn($sql);
    }
    // 获取理财师计划
    private function getPlannerPlan($p_uid, $new_plan=0)
    {
    	if ($new_plan === -1) { // 最新一期
    		$sql = "SELECT pln_id FROM lcs_plan_info WHERE p_uid={$p_uid} AND status IN (2,3,4,5,6,7) ORDER by c_time DESC LIMIT 0,1";
    	} elseif ($new_plan === -2) { // 倒数第一期
    		$sql = "SELECT pln_id FROM lcs_plan_info WHERE p_uid={$p_uid} AND status IN (2,3,4,5,6,7) ORDER by c_time DESC LIMIT 1,1";
    	} else { // 全部符合条件的
    		$sql = "SELECT pln_id FROM lcs_plan_info WHERE p_uid={$p_uid} AND status IN (2,3,4,5,6,7) ORDER by c_time DESC";
    	}

    	return $this->doSqlQueryColumn($sql);
    }
    // 获取关注理财师的所有客户
    private function getAttentionUser($p_uid)
    {
    	$sql = "SELECT DISTINCT uid FROM lcs_attention WHERE p_uid={$p_uid} AND '{$this->day_range['start']}' <= c_time AND c_time <= '{$this->day_range['end']}'";
    	return $this->doSqlQueryColumn($sql);
    }
    // 获取向理财师提问的客户
    private function getAskQuestion($p_uid, $is_price=0)
    {
    	$sql = "SELECT DISTINCT uid FROM lcs_ask_question WHERE p_uid={$p_uid} AND is_price={$is_price} AND '{$this->day_range['start']}' <= c_time AND c_time <= '{$this->day_range['end']}'";
    	return $this->doSqlQueryColumn($sql);
    }
    // 获取关注观点包的所有客户
    private function getPkgCollectUser($pkgs)
    {
    	return $this->getCollectUser(4, $pkgs);
    }
    
    // 获取关注计划的的所有客户
    private function getPlanCollectUser($pln_ids)
    {
    	return $this->getCollectUser(3, $pln_ids);
    }
    // 获取关注{观点包 | 计划}的所有客户
    private function getCollectUser($type, $relation_ids)
    {
    	if (empty($relation_ids)) {
    		return array();
    	} else {
    		$relation_ids = (array) $relation_ids;
    	}

    	$sql = "SELECT DISTINCT uid FROM lcs_collect WHERE type={$type} AND relation_id IN (".implode(',', $relation_ids).") AND '{$this->day_range['start']}' <= c_time AND c_time <= '{$this->day_range['end']}'";
    	return $this->doSqlQueryColumn($sql);
    }
    // 获取观点包订阅客户
    private function getPkgPaidUser($pkgs, $continue_paid=0)
    {
    	if (empty($pkgs)) {
    		return array();
    	} else {
    		$pkgs = (array) $pkgs;
    	}

    	if ($continue_paid === 1) { // 订阅中
    		$sql = "SELECT DISTINCT uid FROM lcs_package_subscription WHERE pkg_id IN (". implode(',', $pkgs) .") AND '{$this->day_range['start']}' <= u_time AND u_time <= '{$this->day_range['end']}' AND end_time > u_time";
    	} elseif ($continue_paid === -1) { // 到期未续费
    		$sql = "SELECT DISTINCT uid FROM lcs_package_subscription WHERE pkg_id IN (". implode(',', $pkgs) .") AND '{$this->day_range['start']}' <= end_time AND end_time <= '{$this->day_range['end']}'";
    	} elseif ($continue_paid === -2) { // 过期未续费
    		$sql = "SELECT DISTINCT uid FROM lcs_package_subscription WHERE pkg_id IN (". implode(',', $pkgs) .") AND '{$this->day_range['start']}' <= end_time AND end_time <= '{$this->day_range['end']}'";
    	} else { // 全部
    		$sql = "SELECT DISTINCT uid FROM lcs_package_subscription WHERE pkg_id IN (". implode(',', $pkgs) .") AND '{$this->day_range['start']}' <= c_time AND c_time <= '{$this->day_range['end']}'";
    	}

    	return $this->doSqlQueryColumn($sql);
    }
    // 获取订阅计划的客户
    private function getPlanPaidUser($pln_ids)
    {
    	if (empty($pln_ids)) {
    		return array();
    	} else {
    		$pln_ids = (array) $pln_ids;
    	}

    	$sql = "SELECT DISTINCT uid FROM lcs_plan_subscription WHERE pln_id IN (".implode(',', $pln_ids).") AND '{$this->day_range['start']}' <= c_time AND c_time <= '{$this->day_range['end']}'";
    	return $this->doSqlQueryColumn($sql);
    }
    // 获取计划信息
    private function getPlanInfo($pln_id)
    {
    	$sql = "SELECT pln_id,status,real_end_time,panic_buy_time FROM lcs_plan_info WHERE pln_id={$pln_id}";
    	return $this->doSqlQueryRow($sql);
    }
    // 获取理财师的group信息
    private function getPlannerGrp($p_uid)
    {
    	$sql = "SELECT id AS grp_id, type FROM lcs_planner_group WHERE p_uid={$p_uid}";
    	$res = $this->doSqlQueryAll($sql, true);
    	if (empty($res)) {
    		$this->initSystemDefaultCustomerGrp($p_uid);
    		$res = $this->doSqlQueryAll($sql, true);
    	}

    	$grp_list = array();
    	foreach ($res as $grp_info) {
    		$grp_list[$grp_info['type']] = $grp_info;
    	}
    	return $grp_list;
    }

    // =====================================================================
    // 初始化理财师推送信息
    public function initSystemDefaultPushInfo($p_uid)
    {
    	try{
	        $sql = "INSERT INTO lcs_planner_push_info (p_uid,times,times_ruler,c_time,u_time) 
	                    VALUES ({$p_uid},".CustomerMsgPushTimes::DEFAULT_PUSH_TIMES.",".CustomerMsgPushTimes::DEFAULT_PUSH_RULER.",'2016-05-18 10:13:40','2016-05-18 10:13:40')";
	        $this->doSqlExecute($sql);
        } catch(exception $e) {
            // 已经有数据的情况，不做处理
        }
    }
    // 初始化理财师组
    public function initSystemDefaultCustomerGrp($p_uid)
    {
    	try{
	        $sql = "INSERT INTO lcs_planner_group (p_uid,type,name,image,summary,status,cstm_total_num,c_time,u_time) 
	                    VALUES ({$p_uid},2,'星标客户','','',0,0,'2016-05-18 10:13:40','2016-05-18 10:13:40'),
	                           ({$p_uid},3,'潜在客户','','有待挖掘的非付费客户',0,0,'2016-05-18 10:13:40','2016-05-18 10:13:40'),
	                           ({$p_uid},4,'付费客户','','购买计划、观点包',0,0,'2016-05-18 10:13:40','2016-05-18 10:13:40'),
	                           ({$p_uid},5,'未续费客户','','服务到期未继续购买',0,0,'2016-05-18 10:13:40','2016-05-18 10:13:40'),
	                           ({$p_uid},6,'流失客户','','超过规定周期未续费',0,0,'2016-05-18 10:13:40','2016-05-18 10:13:40')";
	        $this->doSqlExecute($sql);
        } catch(exception $e) {
            // 已经有数据的情况，不做处理
        }
    }
    // 客户信息更新部分
    private function updateCustomerInfo($p_uid, $grp_type, $uids, $tag_info)
    {
    	if (empty($uids)) {
    		return true;
    	} else {
    		$uids = array_unique($uids);
    	}

    	if ($grp_type > 0) {
    		$grp_id = $this->grp_info[$grp_type]['grp_id'];
    	} else {
    		$grp_id = $grp_type;
    		$grp_type = 0;
    	}

    	// 存在客户
    	$sql = "SELECT DISTINCT uid FROM lcs_planner_customer WHERE p_uid={$p_uid} AND uid IN (".implode(',', $uids).")";
    	$uids_exist = $this->doSqlQueryColumn($sql, true);

    	// 不存在客户
    	$uids_unexist = array_diff($uids, $uids_exist);

		// 更新前记录统计信息log，待后期程序处理
		if ($this->is_daily == 1) {
			// log new add uids
			if (!empty($uids_exist)) {
				$sql = "SELECT DISTINCT uid FROM lcs_planner_customer_group WHERE grp_id={$grp_id} AND uid IN (".implode(',', $uids_exist).")";
				$uids_grp_exist = $this->doSqlQueryColumn($sql, true);
				$uids_grp_add = array_diff($uids, $uids_grp_exist);
				$this->logGrpUpdate($p_uid, 0, $grp_id, $uids_grp_add);
				unset($uids_grp_exist);unset($uids_grp_add);
			} else {
				$this->logGrpUpdate($p_uid, 0, $grp_id, $uids);
			}

			// 付费客户组需注意分出有未续费客户组和流失客户组转移的情况log
			if (!empty($uids_exist) && ($grp_id == $this->grp_info[self::$user_grp_type['paid_user']]['grp_id'])) {
				$discontinue_grp_id = $this->grp_info[self::$user_grp_type['discontinue_paid_user']]['grp_id'];
				$sql = "SELECT DISTINCT uid FROM lcs_planner_customer_group WHERE grp_id={$discontinue_grp_id} AND uid IN (". implode(',', $uids_exist) .")";
				$discontinue_uids = $this->doSqlQueryColumn($sql, true);
				$this->logGrpUpdate($p_uid, $discontinue_grp_id, $grp_id, $discontinue_uids);
				unset($discontinue_uids);

				$lost_grp_id = $this->grp_info[self::$user_grp_type['lost_user']]['grp_id'];
				$sql = "SELECT DISTINCT uid FROM lcs_planner_customer_group WHERE grp_id={$lost_grp_id} AND uid IN (". implode(',', $uids_exist) .")";
				$lost_uids = $this->doSqlQueryColumn($sql, true);
				$this->logGrpUpdate($p_uid, $lost_grp_id, $grp_id, $lost_uids);
				unset($lost_uids);
			}
		}
		unset($uids);

    	$curr_time = date("Y-m-d H:i:s");
    	try{
	    	if (!empty($uids_exist)) {
	    		$set = "";
	    		foreach ($tag_info as $kk => $vv) {
	    			$set .= "{$kk}={$vv},";
	    		}
	    		$sql = "UPDATE lcs_planner_customer
	    					SET {$set} u_time='{$curr_time}'
	    					WHERE p_uid={$p_uid} AND uid IN (".implode(',', $uids_exist).")";
	    		$this->doSqlExecute($sql);

	    		$sql = "UPDATE lcs_planner_customer_group
	    					SET grp_id={$grp_id}, type={$grp_type}, u_time='{$curr_time}'
	    					WHERE p_uid={$p_uid} AND type IN (0,3,4,5,6) AND uid IN (".implode(',', $uids_exist).")";
	    		$this->doSqlExecute($sql);
	    	}
	    	if (!empty($uids_unexist)) {
	    		$fields = "";
	    		$values = "";
	    		foreach ($tag_info as $kk => $vv) {
	    			$fields .= "{$kk},";
	    			$values .= "{$vv},";
	    		}
	    		$sql = "INSERT INTO lcs_planner_customer (p_uid,uid,{$fields}c_time,u_time) VALUES ";
		    	foreach ($uids_unexist as $uid) {
		    		$sql .= "({$p_uid}, {$uid}, {$values} '{$curr_time}', '{$curr_time}'),";
		    	}
		    	$sql = rtrim($sql, ',');
		    	$this->doSqlExecute($sql);

		    	$sql = "INSERT INTO lcs_planner_customer_group (grp_id,uid,p_uid,type,c_time,u_time) VALUES ";
		    	foreach ($uids_unexist as $uid) {
		    		$sql .= "({$grp_id}, {$uid}, {$p_uid}, {$grp_type}, '{$curr_time}', '{$curr_time}'),";
		    	}
		    	$sql = rtrim($sql, ',');
		    	$this->doSqlExecute($sql);
	    	}
    	} catch(exception $e) {
            print_r(json_encode($e)."\n\n");exit;
        }

    	return true;
    }
    // 更新客户标签信息
    private function updateCustomerTagInfo($p_uid, $uids, $tag_info)
    {
    	if (empty($uids)) {
    		return true;
    	} else {
    		$uids = array_unique($uids);
    	}

    	// 存在客户
    	$sql = "SELECT DISTINCT uid FROM lcs_planner_customer WHERE p_uid={$p_uid} AND uid IN (".implode(',', $uids).")";
    	$uids_exist = $this->doSqlQueryColumn($sql, true);

    	// 不存在客户
    	$uids_unexist = array_diff($uids, $uids_exist);
		unset($uids);

    	$curr_time = date("Y-m-d H:i:s");
    	try{
	    	if (!empty($uids_exist)) {
	    		$set = "";
	    		foreach ($tag_info as $kk => $vv) {
	    			$set .= "{$kk}={$vv},";
	    		}
	    		$sql = "UPDATE lcs_planner_customer
	    					SET {$set} u_time='{$curr_time}'
	    					WHERE p_uid={$p_uid} AND uid IN (".implode(',', $uids_exist).")";
	    		$this->doSqlExecute($sql);
	    	}
	    	if (!empty($uids_unexist)) {
	    		$fields = "";
	    		$values = "";
	    		foreach ($tag_info as $kk => $vv) {
	    			$fields .= "{$kk},";
	    			$values .= "{$vv},";
	    		}
	    		$sql = "INSERT INTO lcs_planner_customer (p_uid,uid,{$fields}c_time,u_time) VALUES ";
		    	foreach ($uids_unexist as $uid) {
		    		$sql .= "({$p_uid}, {$uid}, {$values} '{$curr_time}', '{$curr_time}'),";
		    	}
		    	$sql = rtrim($sql, ',');
		    	$this->doSqlExecute($sql);
	    	}
    	} catch(exception $e) {
            print_r(json_encode($e)."\n\n");exit;
        }

    	return true;
    }
    
    // =====================================================================
    // 初始化客户组统计信息
    private function initPlannerGrpStat($p_uid, $grp_id)
    {
    	if ($grp_id < 1) {
    		return ;
    	} else {
	    	$curr_time = date("Y-m-d H:i:s");
	    	$sql = "INSERT INTO lcs_planner_group_stat (day_time,p_uid,grp_id,c_time,u_time) 
	                    VALUES ('{$this->day_range['start']}',{$p_uid},{$grp_id},'{$curr_time}','{$curr_time}')";
	        $this->doSqlExecute($sql);
    	}
    }
    // 记录客户组更新信息
    private function logGrpUpdate($p_uid, $f_grp_id, $t_grp_id, $uids)
    {
    	if (empty($uids)) {
    		return ;
    	}

    	try {
	    	$this->initPlannerGrpStat($p_uid, $f_grp_id);
	    	$this->initPlannerGrpStat($p_uid, $t_grp_id);
    	} catch(exception $e) {
            // 表里已经有数据，不做操作
        }

    	$incr_key = MEM_PRE_KEY . "cstmgrpupdate_{$p_uid}_{$t_grp_id}_incr";
    	$actv_key = MEM_PRE_KEY . "cstmgrpupdate_{$p_uid}_{$f_grp_id}_actv";
    	foreach ($uids as $uid) {
    		Yii::app()->redis_w->sAdd($incr_key, $uid);
	    	if (intval($f_grp_id) !== 0) {
	    		Yii::app()->redis_w->sAdd($actv_key, $uid);
	    	}
    	}

    	Yii::app()->redis_w->setTimeout($incr_key, 7200);
    	Yii::app()->redis_w->setTimeout($actv_key, 7200);
    }
    // 统计记录的客户组更新信息
    private function logGrpUpdateStat($p_uid)
    {
    	$curr_time = date("Y-m-d H:i:s");

    	if (!empty($this->grp_info)) {
    		$keys = array(
    			MEM_PRE_KEY . "cstmgrpupdate_{$p_uid}_".$this->grp_info[self::$user_grp_type['potential_user']]['grp_id']."_incr",
    			MEM_PRE_KEY . "cstmgrpupdate_{$p_uid}_".$this->grp_info[self::$user_grp_type['paid_user']]['grp_id']."_incr",
    			MEM_PRE_KEY . "cstmgrpupdate_{$p_uid}_".$this->grp_info[self::$user_grp_type['discontinue_paid_user']]['grp_id']."_actv",
    			MEM_PRE_KEY . "cstmgrpupdate_{$p_uid}_".$this->grp_info[self::$user_grp_type['lost_user']]['grp_id']."_actv",
    		);

    		$redis_key = $this->getRedisGrpStatKey($p_uid, $this->day_range['start']);
    		$sql = "";
    		foreach ($keys as $k) {
    			$tks = explode('_', $k);
    			$t_grp_id = $tks['3'];
    			$t_grp_type = $tks['4'];
    			$t_grp_value = Yii::app()->redis_w->sCard($k);

    			if (intval($t_grp_value) > 0) {
	    			// 红点标记
					$fields_key = "grp_{$t_grp_id}_is_red";
					Yii::app()->redis_w->hset($redis_key, $fields_key, 1);

					// 组统计信息
	    			if ($t_grp_type == "incr") {
	    				$sql .= " UPDATE lcs_planner_group_stat
	    							SET incr_num={$t_grp_value}, u_time='{$curr_time}'
	    							WHERE day_time='{$this->day_range['start']}' AND p_uid={$p_uid} AND grp_id={$t_grp_id};";

	    				$fields_key = "grp_{$t_grp_id}_incr_num";
						Yii::app()->redis_w->hset($redis_key, $fields_key, $t_grp_value);
	    			} elseif ($t_grp_type == "actv") {
	    				$sql .= " UPDATE lcs_planner_group_stat
	    							SET activation_num={$t_grp_value}, u_time='{$curr_time}'
	    							WHERE day_time='{$this->day_range['start']}' AND p_uid={$p_uid} AND grp_id={$t_grp_id};";

	    				$fields_key = "grp_{$t_grp_id}_activation_num";
						Yii::app()->redis_w->hset($redis_key, $fields_key, $t_grp_value);
	    			} else {}
	
    			}
    			
    			Yii::app()->redis_w->delete($k);
    		}
    		if (!empty($sql)) {
    			$this->doSqlExecute($sql);
    		}
    	}
    }


    // =====================================================================
    // 辅助方法部分
    private function convertMonthTime2MonthRange($month_time)
    {
    	$month_range['start'] = date("Y-m-01 00:00:00", strtotime($month_time));

    	$month = date("m", strtotime($month_time));
    	if (in_array($month, array(1,3,5,7,8,10,12))) {
    		$month_range['end'] = date("Y-m-31 23:59:59", strtotime($month_time));
    	} elseif (in_array($month, array(4,6,9,11))) {
    		$month_range['end'] = date("Y-m-30 23:59:59", strtotime($month_time));
    	} else {
    		$year = date("Y", strtotime($month_time));
    		if ($year%4==0 && ($year%100!=0 || $year%400==0)) {
    			$month_range['end'] = date("Y-m-29 23:59:59", strtotime($month_time));
    		} else {
    			$month_range['end'] = date("Y-m-28 23:59:59", strtotime($month_time));
    		}
    	}

    	return $month_range;
    }
    private function memUsagePrint($tag)
    {
    	echo "{$tag}:\t". rand(memory_get_usage()/1048576, 2) ."MB\n";
    }
    private function timeUsagePrint($tag, $point1='', $point2='')
    {
    	echo "{$tag}(s):\t ". $this->elapsedTime($point1, $point2) ."\n";
    }
    private function markTime()
    {
    	return microtime(TRUE);
    }
    private function elapsedTime($point1='', $point2='', $decimals=4)
	{
		if ($point1 === '') {
			return '{elapsed time}';
		}
		if (empty($point1)) {
			return '';
		}
		if (empty($point2)) {
			$point2 = microtime(TRUE);
		}
		return number_format($point2 - $point1, $decimals);
	}

}