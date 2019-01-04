<?php
/**
 * create by zhihao6 2016/12/06
 *
 * 用户等级评估，根据用户的消费情况，评估用户所属等级
 */

class RankingEvaluate
{
    const CRON_NO = 1501; //任务代码

    public $level_info = [
        // "0"  => ["level" => 0, "name" => "草根", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/01481168660.jpg", "pay_min" => 0, "pay_max" => 0], // 默认值，不用处理
        "1"  => ["level" => 1, "name" => "温饱", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/11481168811.jpg", "pay_min" => 0, "pay_max" => 100],
        "2"  => ["level" => 2, "name" => "小康", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/21481168853.jpg", "pay_min" => 100, "pay_max" => 500],
        "3"  => ["level" => 3, "name" => "小资", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/31481168873.jpg", "pay_min" => 500, "pay_max" => 2000],
        "4"  => ["level" => 4, "name" => "小富", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/41481168895.jpg", "pay_min" => 2000, "pay_max" => 5000],
        "5"  => ["level" => 5, "name" => "地主", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/51481168918.jpg", "pay_min" => 5000, "pay_max" => 10000],
        "6"  => ["level" => 6, "name" => "土豪", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/61481168942.jpg", "pay_min" => 10000, "pay_max" => 20000],
        "7"  => ["level" => 7, "name" => "富豪", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/71481168958.jpg", "pay_min" => 20000, "pay_max" => 50000],
        "8"  => ["level" => 8, "name" => "大富豪", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/81481168983.jpg", "pay_min" => 50000, "pay_max" => 100000],
        "9"  => ["level" => 9, "name" => "首富", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/91481169008.jpg", "pay_min" => 100000, "pay_max" => 2000000],
        "10" => ["level" => 10, "name" => "财神爷", "image" => "http://www.sinaimg.cn/cj/licaishi/avatar/50/A1481169229.jpg", "pay_min" => 2000000, "pay_max" => -1],
    ];

    private $evaluate_limit = 10000; //一次评估的单位数
    private $next_eva_index = []; // 下一个评估单位的起始位
    private $eva_order_end_time = ''; // 评估的订单截至时间

    private $inner_level_users = [
        "3" => [2700739381], // 马玉婷
        "4" => [2557854840], // 石丽欢
        "5" => [2376780683,5943646817], // 孟谦、曹凯
        "6" => [3730907694], // 陈冬银
        "7" => [3730035794,5614257744,3120069830], // 陈冬银、俞晓茁、王燕玲
        "8" => [2403237201], // 李昭琛
    ];

    /**
     * 每日对前一天新增订单的用户重新评估
     *
     *  1. 取昨日新订单用户
     *  2. 获取这些用户中等级为[1...10]的剩余用户
     *  3. 更新用户等级信息
     *  4. 记录日志
     * 
     * @param  integer $level 评估等级，0评估所有等级
     * @return boolean         true
     */
    public function evaluateDaily($level=0)
    {
        if (empty($level)) {
            $level_info = $this->level_info;
        } else {
            $level_info = array(array("level" => $level));
        }

        $this->eva_order_end_time = date("Y-m-d 23:59:59", time() - 86400);
        // $this->eva_order_end_time = "2016-12-05 23:59:59";

        $re_eva_users = $this->getReEvaUser();
        if (empty($re_eva_users)) {
            print_r("nothing need to do\n");
            return true;
        }

        foreach ($level_info as $lvi) {
            while ($res_us = $this->getRestReEvaUser($re_eva_users, $lvi['level'])) {
                $this->rankingUpdate($res_us, $lvi['level']);
                $this->rankingLog(date("Y-m-d", strtotime($this->eva_order_end_time)), $lvi['level'], count($res_us));
            }
        }

        $this->updateInnerUsersLevel();

        return true;
    }

    public function updateInnerUsersLevel()
    {
        $s_uids = [];
        foreach ($this->inner_level_users as $ss) {
            $s_uids = array_merge($s_uids, $ss); 
        }
        $uids_map = User::model()->getUidBySuids($s_uids);

        $level_uids = [];
        foreach ($this->inner_level_users as $level => $ss) {
            $paid = $this->level_info[$level]['pay_min'] + 10;
            foreach ($ss as $s) {
                if (isset($uids_map[$s])) {
                    $level_uids[$level][] = ["uid" => $uids_map[$s], "total_paid" => $paid];
                }
            }
            if (isset($level_uids[$level])) {
print_r("update inner user level:\n");
                $this->rankingUpdate($level_uids[$level], $level);
            }
        }
    }

    /**
     * 初始评估，截至前一天
     *
     *  1. 取等级为[1...10]的剩余未评估用户
     *  2. 更新用户等级信息
     *  3. 记录日志
     * 
     * @param  integer $level   评估等级，0评估所有等级
     * @param  integer $is_init 1初始化数据 0每次定时任务
     * @return boolean           true
     */
    public function evaluateInit($level=0)
    {
        if (empty($level)) {
            $level_info = $this->level_info;
        } else {
            $level_info = array(array("level" => $level));
        }

        $this->eva_order_end_time = date("Y-m-d 23:59:59", time() - 86400);

        foreach ($level_info as $lvi) {
            while ($res_us = $this->getRestUser($lvi['level'])) {
                $this->rankingUpdate($res_us, $lvi['level']);
                $this->rankingLog(date("Y-m-d", strtotime($this->eva_order_end_time)), $lvi['level'], count($res_us));
            }
        }

        return true;
    }

    // 需重新评估用户
    private function getReEvaUser()
    {
        $start_time = date("Y-m-d 00:00:00", strtotime($this->eva_order_end_time));

        $sql = "
            SELECT uid
            FROM lcs_orders 
            WHERE '{$start_time}' <= pay_time AND pay_time <= '{$this->eva_order_end_time}' 
        ";
        $re_eva_users = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        if (empty($re_eva_users)) {
            return [];
        } else {
            return array_unique($re_eva_users);
        }
    }

    // 
    private function getRestReEvaUser($users, $level)
    {
        $this->next_eva_index[$level] = isset($this->next_eva_index[$level]) ? $this->next_eva_index[$level] : 0;
print_r("next start:\t{$this->next_eva_index[$level]}\n");

        $pay_min = $this->level_info[$level]['pay_min'];
        $pay_max = $this->level_info[$level]['pay_max'];

        if ($pay_max === 0) {
            $sql = "
                SELECT uid, SUM(price) as total_paid 
                FROM lcs_orders 
                WHERE uid > {$this->next_eva_index[$level]} AND status = 2 
                    AND uid in (" . implode(",", $users) . ") 
                    AND pay_time <= '{$this->eva_order_end_time}' 
                GROUP BY uid 
                    HAVING total_paid = 0
                ORDER BY uid asc 
                LIMIT {$this->evaluate_limit}
            ";
        } elseif ($pay_max === -1) {
            $sql = "
                SELECT uid, SUM(price) as total_paid 
                FROM lcs_orders 
                WHERE uid > {$this->next_eva_index[$level]} AND status = 2 
                    AND uid in (" . implode(",", $users) . ") 
                    AND pay_time <= '{$this->eva_order_end_time}' 
                GROUP BY uid 
                    HAVING {$pay_min} < total_paid
                ORDER BY uid asc 
                LIMIT {$this->evaluate_limit}
            ";
        } else {
            $sql = "
                SELECT uid, SUM(price) as total_paid 
                FROM lcs_orders 
                WHERE uid > {$this->next_eva_index[$level]} AND status = 2 
                    AND uid in (" . implode(",", $users) . ") 
                    AND pay_time <= '{$this->eva_order_end_time}' 
                GROUP BY uid 
                    HAVING {$pay_min} < total_paid AND total_paid <= {$pay_max} 
                ORDER BY uid asc 
                LIMIT {$this->evaluate_limit}
            ";
        }
print_r("{$sql}\n");
        $rest_us = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if (empty($rest_us)) {
            return [];
        } else {
            $total = count($rest_us);
            $this->next_eva_index[$level] = $rest_us[$total-1]['uid'];
            return $rest_us;
        }
    }

    // 获取指定等级的、订单时间截至昨天的、剩余用户数
    private function getRestUser($level)
    {
        $this->next_eva_index[$level] = isset($this->next_eva_index[$level]) ? $this->next_eva_index[$level] : 0;
print_r("next start:\t{$this->next_eva_index[$level]}\n");

        $pay_min = $this->level_info[$level]['pay_min'];
        $pay_max = $this->level_info[$level]['pay_max'];

        if ($pay_max === 0) {
            $sql = "
                SELECT uid, SUM(price) as total_paid 
                FROM lcs_orders  o
                    JOIN lcs_user_index u ON o.uid=u.id
                WHERE uid > {$this->next_eva_index[$level]} AND status = 2 
                    AND pay_time <= '{$this->eva_order_end_time}' 
                GROUP BY uid 
                    HAVING total_paid = 0
                ORDER BY uid asc 
                LIMIT {$this->evaluate_limit}
            ";
        } elseif ($pay_max === -1) {
            $sql = "
                SELECT uid, SUM(price) as total_paid 
                FROM lcs_orders  o
                    JOIN lcs_user_index u ON o.uid=u.id
                WHERE uid > {$this->next_eva_index[$level]} AND status = 2 
                    AND pay_time <= '{$this->eva_order_end_time}' 
                GROUP BY uid 
                    HAVING {$pay_min} < total_paid
                ORDER BY uid asc 
                LIMIT {$this->evaluate_limit}
            ";
        } else {
            $sql = "
                SELECT uid, SUM(price) as total_paid 
                FROM lcs_orders  o
                    JOIN lcs_user_index u ON o.uid=u.id
                WHERE uid > {$this->next_eva_index[$level]} AND status = 2 
                    AND pay_time <= '{$this->eva_order_end_time}' 
                GROUP BY uid 
                    HAVING {$pay_min} < total_paid AND total_paid <= {$pay_max} 
                ORDER BY uid asc 
                LIMIT {$this->evaluate_limit}
            ";
        }
print_r("{$sql}\n");
        $rest_us = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if (empty($rest_us)) {
            return false;
        } else {
            $total = count($rest_us);
            $this->next_eva_index[$level] = $rest_us[$total-1]['uid'];
            return $rest_us;
        }
    }

    // 保存用户等级信息
    private function rankingUpdate($us, $level)
    {
        if (empty($us)) return true;

        $uids_table = [];
        foreach ($us as $u) {
            $table = substr($u['uid'], -1);
            $uids_table[$table][] = $u['uid'];
        }

        foreach ($uids_table as $table => $uids) {
            $uids_arr = array_chunk($uids, 500);
            foreach($uids_arr as $_uids) {
                $sql = "UPDATE lcs_user_{$table} SET ranking_lv={$level} WHERE uid IN (" . implode(",", $_uids) . ")";
print_r("\t\t{$sql}\n");
                Yii::app()->lcs_w->createCommand($sql)->execute();
            }
        }

    }

    // 记录等级更新历史
    private function rankingLog($day_time, $level, $incr_num)
    {
        $curr_time = date("Y-m-d H:i:s");

        $sql = "SELECT id FROM lcs_user_ranking_level_history WHERE level={$level} AND day_time = '{$day_time}'";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryRow();
        if (empty($res)) { // insert
            $sql = "
                INSERT INTO lcs_user_ranking_level_history 
                    (level,day_time,incr_num,c_time,u_time) 
                VALUES
                    ({$level},'{$day_time}',{$incr_num},'{$curr_time}','{$curr_time}')";
print_r("\t\t{$sql}\n");
            Yii::app()->lcs_w->createCommand($sql)->execute();
        } else { // update
            $sql = "
                UPDATE lcs_user_ranking_level_history 
                SET incr_num=incr_num+{$incr_num},u_time='{$day_time}' 
                WHERE level={$level} and day_time='{$day_time}'";
print_r("\t\t{$sql}\n");
            Yii::app()->lcs_w->createCommand($sql)->execute();
        }
    }
}
