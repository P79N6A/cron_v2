<?php
/**
 * 临时的数据统计
 */

class TestStatCommand extends LcsConsoleCommand
{
    public function actionCircleActivePlanner($day="")
    {
        if (empty($day)) {
            $start_time = date("Y-m-d 00:00:00", time()-86400);
            $end_time = date("Y-m-d 23:59:59", time()-86400);
        } else {
            $start_time = date("Y-m-d 00:00:00", strtotime($day));
            $end_time = date("Y-m-d 23:59:59", strtotime($day));
        }

        $sql = "select id from lcs_circle where partner_id=18;";
        $cirle_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        if (empty($cirle_ids)) exit("nothing\n");
        
        $sql = "select uid from lcs_comment_master where cmn_type=71 and relation_id in (".implode(",", $cirle_ids).") and u_type=2 and '{$start_time}' <= c_time and c_time <= '{$end_time}' group by uid;";
        $p_uids = Yii::app()->lcs_comment_r->createCommand($sql)->queryColumn();
        if (empty($p_uids)) exit("nothing\n");
        
        $sql = "SELECT partner_id,s_uid,real_name,phone,department,location
                FROM lcs_planner
                WHERE s_uid IN (".implode(",", $p_uids).")";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        
        print_r("机构标示=微博id=姓名=手机号=部门=省市\n");
        foreach ($res as $row) {
            print_r("{$row['partner_id']}={$row['s_uid']}={$row['real_name']}={$row['phone']}={$row['department']}={$row['location']}\n");
        }
    }

    // 凌晨跑前一天数据及截止前一天结束的总数据
    public function actionXindaDataStatMail()
    {
        $data['投顾数据'] = $this->xinDaPlannerStat();
        $data['订单数据'] = $this->xindaOrderStat();
        $data['用户数据'] = $this->partnerUserStat();

        $mail_title = "信达数据统计-" . date("Y-m-d",strtotime("-1 day"));
        $mail_content = "";
        foreach ($data as $onecase_title => $onecase) {
            $mail_content .= "<br><hr><br>";
            $mail_content .= "<table border='1'><caption>{$onecase_title}</caption>";
            foreach ($onecase as $caption => $rows) {
                $mail_content .= "<tr><th rowspan='2'>{$caption}</th>";
                $field_name_arr = array_keys($rows);
                foreach ($field_name_arr as $field_name) {
                    $mail_content .= "<th>{$field_name}</th>";
                }
                $mail_content .= "</tr>";

                $mail_content .= "<tr>";
                $field_value_arr = array_values($rows);
                foreach ($field_value_arr as $field_value) {
                    $mail_content .= "<td>{$field_value}</td>";
                }
                $mail_content .= "</tr>";
            }
            $mail_content .= "</table>";
        }
        $mail_to = [
            'hanxue3@staff.sina.com.cn',
            'zhaochen2@staff.sina.com.cn',
            'zhihao6@staff.sina.com.cn',
            'danxian@staff.sina.com.cn',
            'hailin3@staff.sina.com.cn',
            // 'tianlingb@cindasc.com',
            // 'zhangyarui@cindasc.com',
            // 'gaoyiwei@cindasc.com',
        ];
        $sendMail = new NewSendMail($mail_title,$mail_content,$mail_to);
    }


    private function xinDaPlannerStat($day="")
    {
        if (empty($day)) {
            $start_time = date("Y-m-d 00:00:00", time()-86400);
            $end_time = date("Y-m-d 23:59:59", time()-86400);
        } else {
            $start_time = date("Y-m-d 00:00:00", strtotime($day));
            $end_time = date("Y-m-d 23:59:59", strtotime($day));
        }

        // 日投顾免费观点发布数
        $sql = "select count(1) as count from lcs_view where pkg_id in (select id from lcs_package where partner_id=18 and subscription_price=0) and '{$start_time}' <= p_time and p_time <= '{$end_time}';";
        $planner['当日']['日投顾免费观点发布数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        // 日发布免费观点投顾数
        $sql = "select count(1) as count from (select p_uid from lcs_view where pkg_id in (select id from lcs_package where partner_id=18 and subscription_price=0) and '{$start_time}' <= p_time and p_time <= '{$end_time}' group by p_uid) t;";
        $planner['当日']['日发布免费观点投顾数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();

        // 日投顾付费观点发布数
        $sql = "select count(1) as count from lcs_view where pkg_id in (select id from lcs_package where partner_id=18 and subscription_price>0) and '{$start_time}' <= p_time and p_time <= '{$end_time}';";
        $planner['当日']['日投顾付费观点发布数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        // 日发布付费观点投顾数
        $sql = "select count(1) as count from (select p_uid from lcs_view where pkg_id in (select id from lcs_package where partner_id=18 and subscription_price>0) and '{$start_time}' <= p_time and p_time <= '{$end_time}' group by p_uid) t;";
        $planner['当日']['日发布付费观点投顾数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();

        // 圈子id限制
        $sql = "select id from lcs_circle where partner_id=18;";
        $cirle_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        // 日投顾在圈子互动数
        $sql = "select count(1) as count from lcs_comment_master where cmn_type=71 and relation_id in (".implode(",", $cirle_ids).") and u_type=2 and '{$start_time}' <= c_time and c_time <= '{$end_time}';";
        $planner['当日']['日投顾在圈子互动数'] = Yii::app()->lcs_comment_r->createCommand($sql)->queryScalar();
        // 日圈子互动投顾数
        $sql = "select count(1) as count from (select uid from lcs_comment_master where cmn_type=71 and relation_id in (".implode(",", $cirle_ids).") and u_type=2 and '{$start_time}' <= c_time and c_time <= '{$end_time}' group by uid) t;";
        $planner['当日']['日圈子互动投顾数'] = Yii::app()->lcs_comment_r->createCommand($sql)->queryScalar();

        // 日投顾回答免费问题数
        $sql = "select count(1) as count from lcs_ask_question where p_uid!=0 and status in (3,4,5) and is_price=0 and partner_id=18 and '{$start_time}' <= c_time and c_time <= '{$end_time}';";
        $planner['当日']['日投顾回答免费问题数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        // 日回答免费问题投顾数
        $sql = "select count(1) as count from (select p_uid from lcs_ask_question where p_uid!=0 and status in (3,4,5) and is_price=0 and partner_id=18 and '{$start_time}' <= c_time and c_time <= '{$end_time}' group by p_uid) t;";
        $planner['当日']['日回答免费问题投顾数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();

        // 日投顾回答付费问题数
        $sql = "select count(1) as count from lcs_ask_question where p_uid!=0 and status in (3,4,5) and is_price=1 and partner_id=18 and '{$start_time}' <= c_time and c_time <= '{$end_time}';";
        $planner['当日']['日投顾回答付费问题数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        // 日回答付费问题投顾数
        $sql = "select count(1) as count from (select p_uid from lcs_ask_question where p_uid!=0 and status in (3,4,5) and is_price=1 and partner_id=18 and '{$start_time}' <= c_time and c_time <= '{$end_time}' group by p_uid) t;";
        $planner['当日']['日回答付费问题投顾数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();


        // 累计入驻投顾数，免费观点总数，付费观点总数，付费锦囊总数，回答免费问题总数，回答付费问题总数，计划总数，运行中计划总数，成功计划总数，未成功未止损计划总数，止损计划总数
        // 备注：计划统计需要扣除大赛计划
        $sql = "select count(1) as count from lcs_planner where partner_id=18;";
        $planner['总计']['累计入驻投顾数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $sql = "select sum(view_num) as count from lcs_package where subscription_price=0 and partner_id=18;";
        $planner['总计']['免费观点总数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $sql = "select sum(view_num) as count from lcs_package where subscription_price>0 and partner_id=18;";
        $planner['总计']['付费观点总数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $sql = "select count(1) as count from lcs_package where subscription_price>0 and partner_id=18;";
        $planner['总计']['付费锦囊总数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $sql = "select count(1) as count from lcs_ask_question where p_uid!=0 and status in (3,4,5) and is_price=0 and partner_id=18;";
        $planner['总计']['回答免费问题总数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $sql = "select count(1) as count from lcs_ask_question where p_uid!=0 and status in (3,4,5) and is_price=1 and partner_id=18;";
        $planner['总计']['回答付费问题总数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $sql = "select count(1) as count from lcs_plan_info where partner_id=18 and match_id=0;";
        $planner['总计']['计划总数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $sql = "select count(1) as count from lcs_plan_info where partner_id=18 and match_id=0 and status=3;";
        $planner['总计']['运行中计划总数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $sql = "select count(1) as count from lcs_plan_info where partner_id=18 and match_id=0 and status=4;";
        $planner['总计']['成功计划总数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $sql = "select count(1) as count from lcs_plan_info where partner_id=18 and match_id=0 and status=5;";
        $planner['总计']['未成功未止损计划总数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $sql = "select count(1) as count from lcs_plan_info where partner_id=18 and match_id=0 and status=6;";
        $planner['总计']['止损计划总数'] = Yii::app()->lcs_r->createCommand($sql)->queryScalar();

        print_r("信达投顾数据统计（{$start_time} <---> {$end_time}）:\n");
        print_r($planner);

        return $planner;
    }

    private function xindaOrderStat()
    {
        Yii::import('application.commands.order.OrderDailyStat');

        $t = new OrderDailyStat();
        $order['当日'] = $t->all(date('Y-m-d',strtotime('-1 days')));
        $order['总计'] = $t->all();

        print_r("信达订单数据统计:\n");
        print_r($order);

        return $order;
    }


    ///初始化理财师行为统计
    public function actioninitPlannerActive(){
            Common::model()->initActive();
    }

    private function partnerUserStat($day="", $partner_id=18) {
        Yii::import('application.commands.stat.StatPartnerUserCount');
        $stat_obj = new StatPartnerUserCount();
        $partner_id = (int)$partner_id;
        $partner_info = Partner::model()->getPartnerByAppId($partner_id);
        if (empty($partner_info)) {
            echo "机构{$partner_id}不存在\n";
            exit();
        }
        $appid = $partner_info[$partner_id]['app_key'];

        if (empty($day)) {
            $start_time = date("Y-m-d 00:00:00", time()-86400);
            $end_time = date("Y-m-d 23:59:59", time()-86400);
        } else {
            $start_time = date("Y-m-d 00:00:00", strtotime($day));
            $end_time = date("Y-m-d 23:59:59", strtotime($day));
        }

        $day_stat = array();
        //日活跃付费用户数（每日付费的用户数量）
        $sql = "select count(DISTINCT uid) as count from lcs_orders where partner_id={$partner_id} and status>1 and pay_time>'{$start_time}' and pay_time<'{$end_time}';";
        $count_daily_paid_user = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $day_stat['日活跃付费用户数'] = $count_daily_paid_user;

        //日活跃新增付费用户数:以前没付过费的老用户+今天刚付费的新用户，即所有以前未付费过的用户
        $count_daily_new_paid_user = $stat_obj->getActivePaidUserCount($start_time, $end_time, $partner_id) ;
        $day_stat['日活跃新增付费用户数'] = $count_daily_new_paid_user;

        //日活跃免费用户数
        $new_free_uids = $stat_obj->getNewFreeUser($start_time, $end_time, $appid);
        $count_daily_new_free_user = count($new_free_uids);
        $day_stat['日活跃免费用户数'] = $count_daily_new_free_user;

        //日新注册用户数
        $count_daily_new_user = count($stat_obj->_new_partner_user);
        $day_stat['日新注册用户数'] = $count_daily_new_user;

        //日身份认证用户数
        $sql = "select count(*) from lcs_user_cert where c_time>'{$start_time}' and c_time<'{$end_time}' and exta like '%{$appid}%';";
        $count_daily_cert = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $day_stat['日身份认证用户数'] = $count_daily_cert;

        //风险测评用户数
        $sql = "select count(DISTINCT uid) from lcs_user_risk_test_log where c_time>'{$start_time}' and c_time<'{$end_time}' and paper_id='{$appid}';";
        $count_daily_riskt_test_user = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $day_stat['日风险测评用户数'] = $count_daily_riskt_test_user;


        //日活跃圈子数
        $daily_active_circle_ids = $stat_obj->getActiveCircleIds($partner_id, $start_time, $end_time);
        $count_daily_active_circles = count($daily_active_circle_ids);
        $day_stat['日活跃圈子数'] = $count_daily_active_circles;

        //日圈子互动用户数
        $daily_active_circle_users = $stat_obj->getActiveCircleUsers($stat_obj->_partner_circle_ids, $start_time, $end_time);
        $count_daily_active_circle_users = count($daily_active_circle_users);
        $day_stat['日圈子互动用户数'] = $count_daily_active_circle_users;

        //日圈子用户互动数
        $count_daily_active_circle_comments = $stat_obj->getActiveCircleCommentsCount($stat_obj->_partner_circle_ids, $start_time, $end_time);
        $day_stat['日圈子用户互动数'] = $count_daily_active_circle_comments;

        //日观点免费解锁数
        $count_daily_free_view_unlock = $stat_obj->getUnclockViewCount(1, $partner_id, $start_time, $end_time);
        $day_stat['日观点免费解锁数'] = $count_daily_free_view_unlock;

        //日观点付费解锁数
        $count_daily_paid_view_unlock = $stat_obj->getUnclockViewCount(0, $partner_id, $start_time, $end_time);
        $day_stat['日观点付费解锁数'] = $count_daily_paid_view_unlock;

        //日活跃付费锦囊服务用户数
        $count_daily_pkg_sub = $stat_obj->getPkgSubNum($partner_id, $start_time, $end_time);
        $day_stat['日活跃付费锦囊服务用户数'] = $count_daily_pkg_sub;

        //日免费提问数
        $count_daily_free_ask = $stat_obj->getAskNum($partner_id, 0, $start_time, $end_time);
        $day_stat['日免费提问数'] = $count_daily_free_ask;

        //日付费提问数
        $count_daily_paid_ask = $stat_obj->getAskNum($partner_id, 1, $start_time, $end_time);
        $day_stat['日付费提问数'] = $count_daily_paid_ask;

        //日问答免费解锁数
        $count_daily_free_answer = $stat_obj->getAnswerNum($partner_id, 0, $start_time, $end_time);
        $day_stat['日问答免费解锁数'] = $count_daily_free_answer;

        //日问答付费解锁数
        $count_daily_charge_answer = $stat_obj->getAnswerNum($partner_id, 1, $start_time, $end_time);
        $day_stat['日问答付费解锁数'] = $count_daily_charge_answer;

        //日活跃计划服务用户数
        $count_daily_plan_sub = $stat_obj->getPlanSubUserNum($partner_id, $start_time);
        $day_stat['日活跃计划服务用户数'] = $count_daily_plan_sub;

        //日大赛活跃服务用户数
        $count_daily_plan_match_sub = $stat_obj->getPlanMatchSubUserNum($partner_id, $start_time);
        $day_stat['日大赛活跃服务用户数'] = $count_daily_plan_match_sub;

        //累计数据统计

        //print_r("累计访问用户数:");
        $total_stat = array();

        $sql = "select count(1) from lcs_user_channel where channel_id='{$appid}' and channel=1";
        $count_total_user =  Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $total_stat['累计注册用户数'] = $count_total_user;

        $sql = "select count(DISTINCT uid) from lcs_orders where partner_id='{$partner_id}'";
        $count_total_charge_user =  Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $total_stat['累计付费用户数'] = $count_total_charge_user;

        $sql = "select count(*) from lcs_user_cert where exta like '%{$appid}%';";
        $count_total_cert = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $total_stat['累计身份认证用户数'] = $count_total_cert;

        $sql = "select count(DISTINCT uid) from lcs_user_risk_test_log where paper_id='{$appid}';";
        $count_total_riskt_test_user = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $total_stat['累计风险测评用户数'] = $count_total_riskt_test_user;

        $total_active_circle = $stat_obj->getActiveCircleIds($partner_id);
        $count_total_active_circle = count($total_active_circle);
        $total_stat['累计活跃圈子数'] = $count_total_active_circle;

        $count_total_active_circle_comment = $stat_obj->getActiveCircleCommentsCount($stat_obj->_partner_circle_ids);
        $total_stat['累计圈子互动数'] = $count_total_active_circle_comment;

        $total_active_circle_users = $stat_obj->getActiveCircleUsers($stat_obj->_partner_circle_ids);
        $count_total_active_circle_users = count($total_active_circle_users);
        $total_stat['累计圈子互动用户数'] = $count_total_active_circle_users;

        $total_count_free_view_unlock = $stat_obj->getUnclockViewCount(1, $partner_id);
        $total_stat['累计观点免费解锁数'] = $total_count_free_view_unlock;

        $total_count_charge_view_unlock = $stat_obj->getUnclockViewCount(0, $partner_id);
        $total_stat['累计观点付费解锁数'] = $total_count_charge_view_unlock;

        $sql = "select COUNT(1) from lcs_package where partner_id='{$partner_id}';";
        $total_count_pkg = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $total_stat['累计锦囊数'] = $total_count_pkg;

        $total_count_pkg_sub = $stat_obj->getPkgSubNum($partner_id);
        $total_stat['累计锦囊服务用户数'] = $total_count_pkg_sub;

        $sql = "select COUNT(1) from lcs_plan_info where partner_id='{$partner_id}' ;";
        $total_count_pln = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
        $total_stat['累计计划数'] = $total_count_pln;

        $total_count_pln_sub = $stat_obj->getPlanSubUserNum($partner_id);
        $total_stat['累计计划服务用户数'] = $total_count_pln_sub;

        $total_count_charge_ask = $stat_obj->getAskNum($partner_id, 1);
        $total_stat['累计付费提问数'] = $total_count_charge_ask;

        $total_count_free_ask = $stat_obj->getAskNum($partner_id, 0);
        $total_stat['累计免费提问数'] = $total_count_free_ask;

        $total_count_free_answer = $stat_obj->getAnswerNum($partner_id, 0);
        $total_stat['累计问答免费解锁数'] = $total_count_free_answer;

        $total_count_charge_answer = $stat_obj->getAnswerNum($partner_id, 1);
        $total_stat['累计问答付费解锁数'] = $total_count_charge_answer;

        $total_count_pln_match_sub = $stat_obj->getPlanMatchSubUserNum($partner_id);
        $total_count_pln_match = count($stat_obj->_partner_pln_match_ids);
        $total_stat['累计大赛计划数'] = $total_count_pln_match;

        $total_stat['累计大赛计划订阅用户数'] = $total_count_pln_match_sub;

        $user_stat = array(
            '单日' => $day_stat,
            '总计' => $total_stat,
        );
        print_r("\n信达用户数据统计:\n");
        print_r($user_stat);

        return $user_stat;
    }


}
