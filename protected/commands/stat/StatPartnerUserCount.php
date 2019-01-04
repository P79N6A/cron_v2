<?php

/**
 * 机构用户数据统计
 */
class StatPartnerUserCount
{
    //日新增机构用户数
    public $_new_partner_user = array();
    //当前机构下的圈子
    public $_partner_circle_ids = array();
    //机构下的大赛计划
    public $_partner_pln_match_ids = array();


    /**
     * 日活跃新增付费用户数
     * @param $start_time
     * @param $end_time
     * @param $partner_id
     * @return int
     */
    public function getActivePaidUserCount($start_time, $end_time, $partner_id) {
        //1.今日付费uid
        $sql = "select DISTINCT uid from lcs_orders where partner_id={$partner_id} and status>1 and pay_time>'{$start_time}' and pay_time<'{$end_time}'";
        $today_paid_uid = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        if (empty($today_paid_uid)) {
            return 0;
        }
        //2.找到1中此前付过费uid
        $sql = "select DISTINCT uid from lcs_orders where partner_id={$partner_id} and status>1 and pay_time<'{$start_time}' and uid in(".join(',', $today_paid_uid).")";
        $before_paid_uid = Yii::app()->lcs_r->createCommand($sql)->queryColumn();

        return count($today_paid_uid) - count($before_paid_uid);
    }

    /**
     * 指定时间内注册但未付费用户
     * @param $start_time
     * @param $end_time
     * @return array
     */
    public function getNewFreeUser($start_time, $end_time, $appid) {
        $new_uids = $this->getNewPartnerUser($start_time, $end_time, $appid);
        $this->_new_partner_user = $new_uids;
        if (empty($new_uids)) {
            return array();
        }

        $sql = "select DISTINCT uid from lcs_orders where u_time>'{$start_time}' and u_time<'{$end_time}' and status<2 and uid not in(".join(',', $new_uids).")";
        $unpaid_uid = Yii::app()->lcs_r->createCommand($sql)->queryAll();

        return $unpaid_uid;
    }

    /**
     * 获取指定时间段新增的机构用户
     * @param $start_time
     * @param $end_time
     * @param $appid
     * @return array
     */
    public function getNewPartnerUser($start_time, $end_time, $appid) {
        $all_new_uids = $this->getNewUser($start_time, $end_time);
        if (empty($all_new_uids)) {
            return array();
        }
        $all_new_uids = join(',', $all_new_uids);
        $sql = "select uid from lcs_user_channel where uid in({$all_new_uids}) and channel_id='{$appid}' and channel=1";
        return Yii::app()->lcs_r->createCommand($sql)->queryColumn();
    }

    /**
     * 获取指定时间内注册的用户
     * @param $start_time
     * @param $end_time
     * @return array
     */
    public function getNewUser($start_time, $end_time) {
        $all_uids = array();
        for ($i = 0; $i < 10; $i++) {
            $sql = "select uid from lcs_user_" . strval($i) . " where c_time>='" . $start_time . "' and c_time<='".$end_time."' and status=0";
            $res = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
            if (!empty($res)) {
                $all_uids = array_merge($all_uids, $res);
            }
        }

        return $all_uids;
    }

    /**
     * 活跃的圈子数(分别有理财师和用户发说说)
     * @param $start_time
     * @param $end_time
     * @param $partner_id
     *
     * @return array
     */
    public function getActiveCircleIds($partner_id, $start_time="", $end_time="") {
        $circle_ids = $this->getPartnerCircleIds($partner_id);
        $this->_partner_circle_ids = $circle_ids;
        if (empty($circle_ids)) {
            return array();
        }

        $time_cdn = "";
        if (!empty($start_time) || !empty($end_time)) {
            $time_cdn = " and c_time>='{$start_time}' and c_time<'{$end_time}'";
        }
        $sql = "select distinct(relation_id) from lcs_comment_master where cmn_type=71 {$time_cdn} and relation_id in(".join(',', $circle_ids).")";
        Yii::app()->lcs_comment_r->active = false;
        Yii::app()->lcs_comment_r->active = true;
        $res = Yii::app()->lcs_comment_r->createCommand($sql)->queryColumn();

        return $res;
    }

    /**
     * 获取当前机构下的圈子
     * @param $partner_id
     * @return mixed
     */
    public function getPartnerCircleIds($partner_id) {
        $sql = "select id from lcs_circle where partner_id='{$partner_id}'  and status=0;";
        $circle_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();

        return $circle_ids;
    }

    /**
     * 活跃的圈子用户数
     * @param $start_time
     * @param $end_time
     * @param $partner_circle_ids
     * @return array
     */
    public function getActiveCircleUsers($partner_circle_ids, $start_time="", $end_time="") {
        if (empty($partner_circle_ids)) {
            return array();
        }

        $time_cdn = "";
        if (!empty($start_time) || !empty($end_time)) {
            $time_cdn = " and c_time>='{$start_time}' and c_time<'{$end_time}'";
        }

        $sql = "select distinct(uid) from lcs_comment_master where cmn_type=71 {$time_cdn} and relation_id in(".join(',', $partner_circle_ids).")";
        Yii::app()->lcs_comment_r->active = false;
        Yii::app()->lcs_comment_r->active = true;
        $active_users = Yii::app()->lcs_comment_r->createCommand($sql)->queryAll();

        return $active_users;
    }

    /**
     * 圈子用户互动数
     * @param $start_time
     * @param $end_time
     * @param $partner_circle_ids
     * @return array
     */
    public function getActiveCircleCommentsCount($partner_circle_ids, $start_time="", $end_time="") {
        if (empty($partner_circle_ids)) {
            return 0;
        }

        $time_cdn = "";
        if (!empty($start_time) || !empty($end_time)) {
            $time_cdn = " and c_time>='{$start_time}' and c_time<'{$end_time}'";
        }

        $sql = "select count(1) from lcs_comment_master where cmn_type=71 {$time_cdn} and relation_id in(".join(',', $partner_circle_ids).")";
        Yii::app()->lcs_comment_r->active = false;
        Yii::app()->lcs_comment_r->active = true;
        $count = Yii::app()->lcs_comment_r->createCommand($sql)->queryScalar();

        return $count;
    }

    /**
     * 获取机构观点解锁数
     * @param $is_free 1免费观点，0收费观点
     * @param $start_time
     * @param $end_time
     * @param $partner_id
     * @return array
     */
    public function getUnclockViewCount($is_free, $partner_id, $start_time="", $end_time="") {
        if ($is_free == 1) {
            $sub_price_cdn = " and subscription_price=0.00 ";
        } else {
            $sub_price_cdn = " and subscription_price>0.00 ";
        }
        $sql = "select id from lcs_view where partner_id='{$partner_id}' {$sub_price_cdn};";
        $partner_view_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        if (empty($partner_view_ids)) {
            return array();
        }

        $time_cdn = "";
        if (!empty($start_time) || !empty($end_time)) {
            $time_cdn = " and c_time>='{$start_time}' and c_time<'{$end_time}'";
        }

        $sql = "select count(1) from lcs_view_subscription where status=0 {$time_cdn}  and v_id in(".join(',', $partner_view_ids).")";
        $unlock_num = Yii::app()->lcs_r->createCommand($sql)->queryScalar();

        return $unlock_num;
    }

    /**
     * 获取机构下锦囊总服务用户数
     * @param $partner_id
     * @param string $start_time
     * @param string $end_time
     * @return array
     */
    public function getPkgSubNum($partner_id, $start_time="", $end_time="") {
        $sql = "select id from lcs_package where partner_id='{$partner_id}';";
        $partner_pkg_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        if (empty($partner_pkg_ids)) {
            return 0;
        }

        $time_cdn = "";
        if (!empty($start_time) || !empty($end_time)) {
            $time_cdn = " and (end_time>='{$start_time}' or end_time='0000-00-00 00:00:00')";
        }
        $sql = "select count(1) from lcs_package_subscription where  pkg_id in(".join(',', $partner_pkg_ids).") {$time_cdn}";
        $sub_num = Yii::app()->lcs_r->createCommand($sql)->queryScalar();

        return $sub_num;
    }

    /**
     * 日免费提问数
     * @param $partner_id
     * @param string $start_time
     * @param string $end_time
     * @return mixed
     */
    public function getAskNum($partner_id, $is_price, $start_time="", $end_time="") {
        $time_cdn = "";
        if (!empty($start_time) || !empty($end_time)) {
            $time_cdn = " and c_time>='{$start_time}' and c_time<='{$end_time}'";
        }
        $sql = "select count(1) from lcs_ask_question where partner_id='{$partner_id}' and is_price='{$is_price}' {$time_cdn};";
        $ask_num = Yii::app()->lcs_r->createCommand($sql)->queryScalar();

        return $ask_num;
    }

    /**
     * 日问答免费/付费解锁数
     * @param $partner_id
     * @param $is_price
     * @param string $start_time
     * @param string $end_time
     * @return int
     */
    public function getAnswerNum($partner_id, $is_price, $start_time="", $end_time="") {
        $time_cdn = "";
        if (!empty($start_time) || !empty($end_time)) {
            $time_cdn = " and c_time>='{$start_time}' and c_time<='{$end_time}'";
        }
        //取当日所有回答关联的提问id
        $sql = "select q_id from lcs_ask_answer where 1 {$time_cdn};";
        $q_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        if (empty($q_ids)) {
            return 0;
        }
        //按是否收费筛选出机构的提问id
        $sql = "select id from lcs_ask_question where partner_id='{$partner_id}' and is_price='{$is_price}' and id in(".join(',', $q_ids).");";
        $partner_q_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        if (empty($partner_q_ids)) {
            return 0;
        }

        $sql = "select count(1) from lcs_ask_answer where q_id in (".join(',', $partner_q_ids).") {$time_cdn};";
        $count = Yii::app()->lcs_r->createCommand($sql)->queryScalar();

        return $count;
    }

    /**
     * 在计划服务期内的用户人数
     * @param $partner_id
     * @return mixed
     */
    public function getPlanSubUserNum($partner_id, $start_time="") {
        $sql = "select pln_id from lcs_plan_info where partner_id='{$partner_id}' ;";
        $partner_pln_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        if (empty($partner_pln_ids)) {
            return 0;
        }

        $time_cdn = "";
        if (!empty($start_time)) {
            $time_cdn = " and (expire_time>='{$start_time}' or expire_time='0000-00-00 00:00:00')";
        }
        $sql = "select count(1) from lcs_plan_subscription where status>0 and pln_id in(".join(',', $partner_pln_ids).") {$time_cdn}";

        $count = Yii::app()->lcs_r->createCommand($sql)->queryScalar();

        return $count;
    }

    /**
     * 机构大赛计划订阅用户数
     * @param $partner_id
     * @param $start_time
     * @return int
     */
    public function getPlanMatchSubUserNum($partner_id, $start_time="") {
        //取大赛表中的机构大赛计划
        $sql = "select DISTINCT A.pln_id from lcs_planner_match A left join lcs_plan_info B on A.pln_id=B.pln_id where B.partner_id='{$partner_id}';";
        $partner_match_pln_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
        $this->_partner_pln_match_ids = $partner_match_pln_ids;
        if (empty($partner_match_pln_ids)) {
            return 0;
        }

        $time_cdn = "";
        if (!empty($start_time)) {
            $time_cdn = " and (expire_time>='{$start_time}' or expire_time='0000-00-00 00:00:00')";
        }
        $sql = "select count(1) from lcs_plan_subscription where status>0 and pln_id in(".join(',', $partner_match_pln_ids).") {$time_cdn}";
        $count = Yii::app()->lcs_r->createCommand($sql)->queryScalar();

        return $count;
    }
}