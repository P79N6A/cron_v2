<?php

/**
 * 理财师基本信息数据库访问类
 * User: zwg
 * Date: 2015/5/18
 * Time: 18:06
 */
class Planner extends CActiveRecord {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'lcs_planner';
    }

    public function tableNameAttention() {
        return 'lcs_attention';
    }

    //违规记录表b
    public function tableNameViolation() {
        return 'lcs_planner_violation';
    }

    public function tableNameEvaluation() {
        return 'lcs_planner_evaluation';
    }

    //理财师问答
    public function tableNameAsk() {
        return TABLE_PREFIX . 'ask_planner';
    }

    //理财师冻结表
    public function tableNameFreeze() {
        return 'lcs_planner_freeze';
    }

    //投顾大赛理财师表
    public function tableNameMatch() {
        return 'lcs_planner_match';
    }

    public function tableNameExt() {
        return 'lcs_planner_ext';
    }

    public function tableCompany() {
        return 'lcs_company';
    }

    public function tableNamePostion() {
        return 'lcs_position';
    }

    public function tableNameView() {
        return 'lcs_view';
    }

    public function tableNameAnswer() {
        return 'lcs_ask_answer';
    }

    public function tableNamePlan() {
        return 'lcs_plan_info';
    }

    public function tableNameCircle() {
        return 'lcs_comment_master';
    }

    public function tableNameOrder() {
    	return 'lcs_orders';
    }

    public function savePlanner($data){
    	$db_w = Yii::app()->lcs_w;
    	return $db_w->createCommand()->insert($this->tableName(), $data);
    }
    /** 获取理财师的违规记录
     * @param $p_uid
     * @param $start_date
     * @param $end_date
     */
    public function getPlannerViolation($p_uid, $start_date, $end_date) {
        $cdn = '';
        if (!empty($p_uid)) {
            $cdn .= ' AND p_uid=:p_uid';
        }
        if (!empty($start_date)) {
            $cdn .= ' AND c_time>=:start_date';
        }
        if (!empty($end_date)) {
            $cdn .= ' AND c_time<:end_date';
        }

        $sql = 'SELECT p_uid,increase FROM ' . $this->tableNameViolation() . ' WHERE 1=1 ' . $cdn . ';';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        if (!empty($p_uid)) {
            $cmd->bindParam(':p_uid', $p_uid, PDO::PARAM_INT);
        }
        if (!empty($start_date)) {
            $cmd->bindParam(':start_date', $start_date, PDO::PARAM_STR);
        }
        if (!empty($end_date)) {
            $cmd->bindParam(':end_date', $end_date, PDO::PARAM_STR);
        }
        return $cmd->queryAll();
    }

    public function updatePlannerExt($data, $fields) {
        if (empty($data)) {
            return 0;
        }
        $fields = (array) $fields;
        $sql_ins = "insert into " . $this->tableNameExt() . " (s_uid," . implode(',', $fields) . ",c_time, u_time) values";
        $sql_on_duplicate = " ON DUPLICATE KEY UPDATE ";
        foreach ($fields as $field) {
            $sql_on_duplicate .= "`" . $field . "`=VALUES(" . $field . "), ";
        }
        $sql_on_duplicate .= "`u_time`=VALUES(u_time);";

        $sql_values = "";
        $num = 0;
        $records = 0;
        $cur_time = date('Y-m-d H:i:s');
        foreach ($data as $p_uid => $val) {
            $num ++;
            $sql_values .= "({$p_uid},";
            foreach ($fields as $field) {
                $sql_values .= (isset($val[$field]) ? $val[$field] : 0) . ",";
            }
            $sql_values .= "'{$cur_time}','{$cur_time}'),";

            if ($num >= 100) {
                $sql_insert = $sql_ins . substr($sql_values, 0, -1) . $sql_on_duplicate;
                $records += Yii::app()->lcs_w->createCommand($sql_insert)->execute();
                $num = 0;
                $sql_values = '';
            }
        }

        if ($num > 0) {
            $sql_insert = $sql_ins . substr($sql_values, 0, -1) . $sql_on_duplicate;
            $records += Yii::app()->lcs_w->createCommand($sql_insert)->execute();
        }

        return $records;
    }

    /**
     * 保存或更新理财师的评价信息
     * @param $stat_date
     * @param $data
     * @param $fields
     * @return int
     */
    public function saveOrUpdateEvaluation($stat_date, $data, $fields) {
        if (empty($data)) {
            return 0;
        }
        $fields = (array) $fields;
        $sql_ins = "insert into " . $this->tableNameEvaluation() . " (p_uid,stat_date," . implode(',', $fields) . ",c_time, u_time) values";
        $sql_on_duplicate = " ON DUPLICATE KEY UPDATE ";
        foreach ($fields as $field) {
            $sql_on_duplicate .= "`" . $field . "`=VALUES(" . $field . "), ";
        }
        $sql_on_duplicate .= "`u_time`=VALUES(u_time);";

        $sql_values = "";
        $num = 0;
        $records = 0;
        $cur_time = date('Y-m-d H:i:s');
        foreach ($data as $p_uid => $val) {
            $num ++;
            $sql_values .= "({$p_uid},'{$stat_date}',";
            foreach ($fields as $field) {
                $sql_values .= (isset($val[$field]) ? $val[$field] : 0) . ",";
            }
            $sql_values .= "'{$cur_time}','{$cur_time}'),";

            if ($num >= 100) {
                $sql_insert = $sql_ins . substr($sql_values, 0, -1) . $sql_on_duplicate;
                $records += Yii::app()->lcs_w->createCommand($sql_insert)->execute();
                $num = 0;
                $sql_values = '';
            }
        }

        if ($num > 0) {
            $sql_insert = $sql_ins . substr($sql_values, 0, -1) . $sql_on_duplicate;
            $records += Yii::app()->lcs_w->createCommand($sql_insert)->execute();
        }

        return $records;
    }

    /**
     * @param $start_date
     * @param $end_date
     */
    public function getPlannerIDOfEvaluationByStatDate($start_date, $end_date) {
        $sql = 'SELECT DISTINCT(p_uid) FROM ' . $this->tableNameEvaluation() . ' where stat_date>=:start_date and stat_date<:end_date;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':start_date', $start_date, PDO::PARAM_STR);
        $cmd->bindParam(':end_date', $end_date, PDO::PARAM_STR);
        return $cmd->queryAll();
    }

    /**
     * 获取理财师的评价信息
     * @param $stat_date
     * @param $fields
     * @return mixed
     */
    public function getPlannerEvaluationByStatDate($start_date, $end_date, $p_uids, $fields) {
        $select = 'id';
        if (!empty($fields)) {
            $select = is_array($fields) ? implode(',', $fields) : $fields;
        }
        $cdn = '';
        if (!empty($p_uids)) {
            $p_uids = (array) $p_uids;
            $cdn = ' and p_uid in (' . implode(',', $p_uids) . ')';
        }
        $sql = 'select ' . $select . ' from ' . $this->tableNameEvaluation() . ' where stat_date>=:start_date AND stat_date<:end_date ' . $cdn . ';';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':start_date', $start_date, PDO::PARAM_STR);
        $cmd->bindParam(':end_date', $end_date, PDO::PARAM_STR);
        return $cmd->queryAll();
    }

    /**
     * 统计设置为私人理财师的用户数量
     * @return mixed
     */
    public function getAttentionUserCount($end_date = '') {
        $cdn = '';
        if (!empty($end_date)) {
            $cdn .= ' AND c_time<:end_date';
        }
        $sql = 'SELECT p_uid, COUNT(uid) as num FROM lcs_attention where 1=1 ' . $cdn . ' group by p_uid;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        if (!empty($end_date)) {
            $cmd->bindParam(':end_date', $end_date, PDO::PARAM_STR);
        }
        return $cmd->queryAll();
    }

    /**
     * 查询理财师关注用户
     * @return mixed
     */
    public function getAttentionUser($p_uid) {
        $sql = "SELECT uid FROM lcs_attention where p_uid=:p_uid";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':p_uid', $p_uid, PDO::PARAM_INT);
        return $cmd->queryColumn();
    }

    /**
     * 根据理财师id 获取昵称
     *
     * @param unknown_type $p_uid
     */
    public function getPlannerById($p_uids) {
        $p_uids = (array) $p_uids;
        $return = array();

        if (empty($p_uids)) {
            return $p_uids;
        }

        $sql = "select s_uid, s_uid as p_uid,name,image,phone,c_time,u_time,company_id,position_id,department from " . $this->tableName() . " where s_uid in(";
        $sql .= implode(',', $p_uids);
        $sql .= ')';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $planners = $cmd->queryAll();

        foreach ($planners as $val) {
            $return["$val[p_uid]"] = $val;
        }

        return $return;
    }

    /**
     * 根据公司id 获取公司信息
     *
     * @param unknown_type $p_uid
     */
    public function getCompanyById($c_ids) {
        $return = array();

        if (empty($c_ids)) {
            return $c_ids;
        }
        $c_ids = (array) $c_ids;

        $sql = "select id, id as company_id,name from " . $this->tableCompany() . " where id in(";
        $sql .= implode(',', $c_ids);
        $sql .= ')';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $companys = $cmd->queryAll();
        foreach ($companys as $val) {
            $return["$val[company_id]"] = $val;
        }

        return $return;
    }

    /**
     * 解冻理财师
     * @param unknown $s_uid
     * @return boolean
     */
    public function unfreezePlanner($s_uid) {
        $db_w = Yii::app()->lcs_w;
        $u_time = date(DATE_ISO8601);

        if (!is_array($s_uid)) {
            $s_uid = (array) $s_uid;
        }

        $transaction = $db_w->beginTransaction();

        try {
            $cmd = $db_w->createCommand('UPDATE ' . $this->tableName() . ' SET status=0, u_time=:u_time WHERE s_uid in (' . implode(',', $s_uid) . ');');
            $cmd->bindParam(':u_time', $u_time, PDO::PARAM_INT);
            $res = $cmd->execute();
            $db_w->createCommand('UPDATE ' . $this->tableNameFreeze() . ' SET `type`=3 WHERE p_uid in (' . implode(',', $s_uid) . ') AND `type`=1')->execute();

            // 提交事务
            $transaction->commit();
        } catch (Exception $e) {
            error_log($e);
            $transaction->rollBack();
            return false;
        }
        return $res;
    }

    /**
     * 取所有理财师的电话号码
     * @return array
     */
    public function getPlannerPhoneList() {
        $sql = "select phone from " . $this->tableName() . " where cert_id=1 and phone!=''";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $phone_list = $cmd->queryColumn();
        return $phone_list;
    }

    /**
     * 取所有发过观点的，或者回答过问题的理财师的电话号码
     * @return array
     */
    public function getActivePlannerPhoneList($offset, $limit) {
        $offset = (int) $offset;
        $limit = (int) $limit;

        $sql_ask = "select s_uid from " . $this->tableNameAsk();
        $cmd = Yii::app()->lcs_r->createCommand($sql_ask);
        $ask_p_uids = $cmd->queryColumn();

        $sql = "select phone from " . $this->tableName()
            . " where phone!='' and (view_num>0 or s_uid in (" . implode(',', $ask_p_uids) . ")) "
            . " order by s_uid asc limit $offset, $limit;";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $phone_list = $cmd->queryColumn();

        return $phone_list;
    }

    /**
     * 取投顾大赛的p_uid
     * @return unknown
     */
    public function getTouguPuids() {
        $db_r = Yii::app()->lcs_r;
        $sql = 'select p_uid from ' . $this->tableNameMatch() . ' where status=0';
        $data = $db_r->createCommand($sql)->queryColumn();
        return $data;
    }

    /**
     * 取投顾大赛的理财师电话号码
     * @return array
     */
    public function getTougudasaiPhoneList($offset, $limit) {
        $p_uids = $this->getTouguPuids();
        $offset = (int) $offset;
        $limit = (int) $limit;
        $sql = "select phone from " . $this->tableName()
            . " where phone!='' "
            . " and s_uid in (" . implode(',', $p_uids) . ") "
            . " order by s_uid asc limit $offset, $limit;";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $phone_list = $cmd->queryColumn();
        return $phone_list;
    }

    /**
     * 工具公司ID获取理财师
     * @param unknown $company_id
     * @param string $fileds
     */
    public function getPlannerByCommpanyId($company_id) {
        $sql = 'select s_uid, name, phone,c_time,u_time from lcs_planner where company_id=' . $company_id . ';';

        $db_r = Yii::app()->lcs_r;
        $cmd = $db_r->createCommand($sql);
        $cmd->bindParam(':company_id', $company_id, PDO::PARAM_INT);
        return $cmd->queryAll();
    }

    /**
     * 更新理财师信息
     *
     * @param unknown_type $p_uid
     * @param unknown_type $data
     */
    public function updatePlannerInfo($p_uid, $data) {
        //更改理财师的观点总阅读数, 注意有的需要更新u_time 有的不需要，
        $allow_fields = array('view_num');

        $p_info = array();
        foreach ($data as $key => $val) {
            if (in_array($key, $allow_fields)) {
                $p_info["$key"] = $val;
            }
        }
        if (sizeof($p_info) > 0) {
            $set = '';
            foreach ($p_info as $key => $val) {
                $set = "$key='$val',";
            }
            $set = substr($set, 0, -1);
            $sql = "update lcs_planner set $set where s_uid='$p_uid' limit 1";
            $cmd = Yii::app()->lcs_w->createCommand($sql);
            $cmd = $cmd->execute();
            //需要删除缓存的还需要删除下缓存
            return $cmd;
        }
        return false;
    }

    public function getPlannerRecommandList($day = 30, $limit = 20) {
        $day = intval($day) > 0 ? intval($day) : 30;
        $limit = intval($limit) > 0 ? intval($limit) : 20;

        $_date = new DateTime();
        $_date->sub(new DateInterval("P" . $day . "D"));
        $date = $_date->format("Y-m-d");

        $sql = "SELECT `p_uid`,count(id) as num FROM " . $this->tableNameAttention() . " WHERE `c_time`>='" . $date . "' GROUP BY `p_uid` ORDER BY `num` DESC,`id` LIMIT " . $limit;
        $result = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $result;
    }

    public function updateCommentNum($p_uid, $data) {
        if ($p_uid <= 0 || empty($data)) {
            return false;
        }
        return Yii::app()->lcs_w->createCommand()->update($this->tableNameExt(), $data, "s_uid='" . $p_uid . "'");
    }

    /**
     * 获取正在特惠的理财师id列表
     * @return type
     */
    public function getAskOpenPlanner() {
        $condition = ' is_open=1 ';
        $sql = "SELECT `s_uid` FROM " . $this->tableNameAsk() . ' WHERE ' . $condition;
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }

    /**
     * 批量更新问答理财师信息
     * @param type $p_uids
     * @param type $data
     * @return boolean
     */
    public function closeAskPlanner($p_uids = array(), $data = array()) {
        if (empty($p_uids) || empty($data)) {
            return FALSE;
        }
        return Yii::app()->lcs_w->createCommand()->update($this->tableNameAsk(), $data, "s_uid IN (" . implode(',', $p_uids) . ")");
    }

    /*
     * 获取满足以下条件的理财师s_uid
     * 理财师已完成计划总数>=2
     * 历史年华收益>0%
     * 未来30天有待运行或者运行中的计划
     */

    public function getBasicPlannerSuid($type = 1) {
        $condition = "";
        if ($type == 1) {
            $condition = ' WHERE pln_num>=2 and pln_year_rate>0 ';
        } else if ($type == 2) {
            $condition = ' WHERE pln_num=1 and pln_year_rate>0 ';
        } else if ($type == 3) {
            $condition = ' WHERE grade_plan>0 and pln_num>=2 and pln_year_rate>0.0000 LIMIT 50 ';
        }
        $sql = "SELECT s_uid FROM " . $this->tableNameExt(). $condition;
        $s_uid = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        //先获取满足理财师已完成计划总数>=2，历史年华收益>0%的理财师
        ///然后获取未来30天有待运行或者运行中的计划
        $history_30_day = date("Y-m-d", strtotime(' -30 day', time()));
        if (count($s_uid) > 0) {
            $s_uid_str = "";
            foreach ($s_uid as $index => $item) {
                $s_uid_str.=$item['s_uid'];
                if ($index < count($s_uid) - 1) {
                    $s_uid_str.=",";
                }
            }
            $tableName_PlanInfo = Plan::model()->tableName();
            $sql = "SELECT distinct p_uid FROM " . $tableName_PlanInfo . " WHERE p_uid in (" . $s_uid_str . ") and ((status >=4 and real_end_time >'" . $history_30_day . "') or status=3 )";
            $p_uid = Yii::app()->lcs_r->createCommand($sql)->queryAll();

            return $p_uid;
        } else {
            return false;
        }
    }

    ///获取该理财师下的所有已完成计划的基础信息
    public function getPlannerPlanInfos($p_uids) {
        if (is_array($p_uids)&&count($p_uids)>0) {
            $result = Array();
            $tableName_PlanInfo = Plan::model()->tableName();
            $sql = "select pln_id,p_uid,max_profit,min_profit,curr_ror,hs300,prev_ror,curr_ror from " . $tableName_PlanInfo . " where p_uid in (" . implode(',', $p_uids) . ") and status>=4";
            $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            foreach ($res as $item) {
                if (!isset($result[$item['p_uid']])) {
                    $result[$item['p_uid']] = Array();
                }
                $result[$item['p_uid']][$item['pln_id']]['pln_id'] = $item['pln_id'];
                $result[$item['p_uid']][$item['pln_id']]['max_profit'] = $item['max_profit'];
                $result[$item['p_uid']][$item['pln_id']]['min_profit'] = $item['min_profit'];
                $result[$item['p_uid']][$item['pln_id']]['curr_ror'] = $item['curr_ror'];
                $result[$item['p_uid']][$item['pln_id']]['hs300'] = $item['hs300'];
                $result[$item['p_uid']][$item['pln_id']]['prev_ror'] = $item['prev_ror'];
            }
            return $result;
        } else {
            return false;
        }
    }

    /*
     * 获取该理财师下的扩展信息
     */

    public function getPlannerExtInfo($p_uids) {
        if (is_array($p_uids) && count($p_uids) > 0) {
            $result = Array();
            $sql = "select s_uid,pln_num,pln_loss_num,pln_buy_num,pln_sell_num,pln_success_rate,pln_year_rate,influence,grade_plan,grade_pkg_auto,grade_plan_status,grade_pkg,grade_plan_auto,grade_pkg_status from " . $this->tableNameExt() . " where s_uid in (" . implode(',', $p_uids) . ")";
            $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            foreach ($res as $item) {
                if (!isset($result[$item['s_uid']])) {
                    $result[$item['s_uid']] = Array();
                }
                $result[$item['s_uid']]['pln_num'] = $item['pln_num'];
                $result[$item['s_uid']]['pln_loss_num'] = $item['pln_loss_num'];
                $result[$item['s_uid']]['pln_buy_num'] = $item['pln_buy_num'];
                $result[$item['s_uid']]['pln_sell_num'] = $item['pln_sell_num'];
                $result[$item['s_uid']]['pln_success_rate'] = $item['pln_success_rate'];
                $result[$item['s_uid']]['pln_year_rate'] = $item['pln_year_rate'];
                $result[$item['s_uid']]['grade_plan'] = $item['grade_plan'];
                $result[$item['s_uid']]['grade_pkg_auto'] = $item['grade_pkg_auto'];
                $result[$item['s_uid']]['grade_plan_status'] = $item['grade_plan_status'];
                $result[$item['s_uid']]['grade_pkg'] = $item['grade_pkg'];
                $result[$item['s_uid']]['grade_plan_auto'] = $item['grade_plan_auto'];
                $result[$item['s_uid']]['grade_pkg_status'] = $item['grade_pkg_status'];
                $result[$item['s_uid']]['influence'] = $item['influence'];
            }
            return $result;
        } else {
            return false;
        }
    }

    ///获取理财师的职位信息
    public function getPositions($p_uids) {
        if (is_array($p_uids)) {
            $result = Array();
            $sql = "select s_uid,position_id from " . $this->tableName() . " where s_uid in (" . implode(',', $p_uids) . ")";
            $allpositions = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            $position_ids = Array();
            foreach ($allpositions as $item) {
                $result[$item['s_uid']] = $item['position_id'];
                $position_ids[] = $item['position_id'];
            }
            $sql = "select id,name from " . $this->tableNamePostion() . " where id in (" . implode(',', $position_ids) . ")";
            $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            $positions = Array();
            foreach ($res as $item) {
                $positions[$item['id']] = $item['name'];
            }
            foreach ($result as $index => $item) {
                $result[$index] = $positions[$item];
            }
            return $result;
        }
    }

    /**
     * 设置新的50名理财师.
     * @param type $data
     * lcs_p_top50_update_time理财师排行榜更新时间
     *    rank分类：最高盈利榜1 风险控制榜2 逆势赚钱榜3 交易胜率榜4 稳定业绩榜5 最佳选股榜6 
     *    rank_change记录名次与上次相比的变化，0无变化，n>0上升n名，n<0下降n名
     *    is_new是否新进前50名,true新进，false非新进
     *    总交易数               总胜率                计划总数            计划成功率                  亏损控制        跑赢大盘        成功率      最佳选股
     * total_trade_num//total_success_rate_trade//total_pln_num//total_success_rate_plan_new//loss_control//compare_market//win_loss//best_choice
     */
    public function setTop50PlannerToRedis($data, $rank_type = '1') {
        $redis_w = Yii::app()->redis_w;
        $redis_w->set("lcs_p_top50_" . $rank_type, json_encode($data));
    }

    /**
     * 先获取当前的50名的理财师，存放在redis中的是字符串
     */
    public function getTop50PlannerFromRedis($rank_type = '1') {
        $res_str = Yii::app()->redis_r->get("lcs_p_top50_" . $rank_type);
        return json_decode($res_str, true);
    }

    /**
     * 获取非投顾大赛的理财师编号
     */
    public function getPlannerWithOutTouGu() {
        $sql = "select distinct(p_uid) from lcs_plan_info where  pln_id>28340 and status in (4,5,7)";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $data = $cmd->queryColumn();
        return $data;
    }

    /**
     * 获取理财师名片折线图数据
     * @return type
     */
    public function getPlannerCardDataFromRedis($p_uid) {
        $res_str = Yii::app()->redis_r->hget("lcs_p_plannerCardChartData", $p_uid);
        return json_decode($res_str, true);
    }

    /**
     * 设置理财师名片折线图数据
     * @param type $data
     */
    public function setPlannerCardDataToRedis($p_uid, $data) {
        $res = Yii::app()->redis_w->hset("lcs_p_plannerCardChartData_" . $p_uid, "result", json_encode($data));
    }

    /**
     * 根据理财师的编号获取所有的交易日
     * @param type $p_uid
     */
    public function getPlannerTradeDate($p_uid) {
        $res = Array();
        ///先取redis中的交易日，
        $all_trade_date = Yii::app()->redis_r->hget("lcs_p_plannerCardChartData_" . $p_uid, "trade_date");
        $all_trade_date=NULL;
        if (!empty($all_trade_date)) {
            ///如果有则检查今天是否是交易日，是则添加
            $all_trade_date = json_decode($all_trade_date, true);
            if ($all_trade_date[count($all_trade_date) - 1] != date("Y-m-d") && $this->plannerTodayIfTrade($p_uid)) {
                $all_trade_date[] = date("Y-m-d", time());
                Yii::app()->redis_w->hset("lcs_p_plannerCardChartData_" . $p_uid, "trade_date", json_encode($all_trade_date));
            }
            $res = $all_trade_date;
        } else {
            ///如果没有则计算该理财师的交易日
            $res = $this->calculatePlannerTradeDate($p_uid);
            Yii::app()->redis_w->hset("lcs_p_plannerCardChartData_" . $p_uid, "trade_date", json_encode($res));
        }
        return $res;
    }

    /**
     * 计算理财师的交易日
     * @param type $p_uid
     */
    private function calculatePlannerTradeDate($p_uid) {
        $res = Array();
        $plans = Plan::model()->getPlanInfoByPlanner($p_uid, "pln_id,start_date,end_date,real_end_time,status", array(3, 4, 5, 6, 7),1);
        if (count($plans) > 0) {
            foreach ($plans as $item) {
                if ($item['status'] >= 4) {
                    $start_date = $item['start_date'];
                    $end_date = $item['real_end_time'] != "0000-00-00 00:00:00" ? $item['real_end_time'] : $item['end_date'];
                } else {
                    $start_date = $item['start_date'];
                    $end_date = date("Y-m-d", time());
                }
                $start = $start_date;
                $end = date("Y-m-d", strtotime($end_date));
                $result = Common::getMarketDayAsArray($start, $end);

                while ($start <= $end) {
                    if (isset($result[$start])) {
                        $res[] = $start;
                    }
                    $start = date("Y-m-d", strtotime(' +1 day', strtotime($start)));
                }
            }
        }
        $res = array_unique($res);
        sort($res);
        return $res;
    }

    /**
     * 理财师今天是否交易
     * @param type $p_uid
     */
    private function plannerTodayIfTrade($p_uid) {
        ///该理财师今天是否交易,判断当前是否有正在运行中的计划即可
        $sql = "select count(*) from lcs_plan_info where p_uid=" . $p_uid . " and status=3";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $data = $cmd->queryScalar();
        if ($data > 0) {
            return True;
        } else {
            return False;
        }
    }

    /**
     * 获取理财师某一天的所有计划的年化收益率
     * @param type $p_uid
     * @param type $day
     */
    private function getPlannerSumCurr_ror($p_uid, $start, $end, $is_today = 0) {
        if ($is_today == 1) {
            $total_ror = (float) Plan::model()->getCurrRor($p_uid) + (float) Plan::model()->getSumCurrRor($p_uid, $end);
        } else {
            $total_ror = (float) Plan::model()->getCurrRor($p_uid, $end) + (float) Plan::model()->getSumCurrRor($p_uid, $end);
        }
        return $total_ror;
    }

    /**
     * 获取该理财师某一天的总收益
     * @param type $p_uid
     * @param type $start
     * @param type $end
     * @return type
     */
    public function getPlannerSumROR($p_uid, $start, $end) {
        $res = 0;
        if ($end == date("Y-m-d", time())) {
            $res = $this->getPlannerSumCurr_ror($p_uid, $start, $end, 1);
        } else {
            $res = $this->getPlannerSumCurr_ror($p_uid, $start, $end, 0);
        }
        return $res;
    }

    /**
     * 获取某一个理财师的当天数据，先从redis中取，如果有则直接用，
     * @param type $p_uid
     * @param type $start
     * @param type $end
     */
    public function getDataByDate($p_uid, $start, $end) {
        ///$data = Yii::app()->redis_r->hget("lcs_p_plannerCardChartData_" . $p_uid, $end);
        $data=NULL;
        if (empty($data)) {
            $temp['date'] = $end;
            $temp['profit'] = Planner::model()->getPlannerSumROR($p_uid, $start, $temp['date']);
            $temp['hs300'] = Plan::model()->getHs300FromDaily_k($start, $temp['date']);
            ///Yii::app()->redis_w->hset("lcs_p_plannerCardChartData_" . $p_uid, $end, json_encode($temp));
            return $temp;
        } else {
            return json_decode($data, true);
        }
    }

    /**
     * 根据ID和排序规则返回排序后的理财师id
     * @param $ids
     * @param string $order
     * @return bool
     */
    public function getPlannerExtOrderByIds($ids, $order = '')
    {
        if (empty($ids)) {
            return false;
        } else {
            $ids_cdn = ' s_uid in ('.join(',', $ids).')';
        }
        $sql = "select s_uid from ".$this->tableNameExt()." where $ids_cdn {$order}";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $res = $cmd->queryAll();
        return $res;
    }
    
    /**
     * 获得最近某些天内更新过观点的理财师id
     */
    protected function getLastViewPlanner($day)
    {
	$time = date('Y-m-d H:i:s',strtotime("-$day day"));
	$sql = "select distinct p_uid from ".$this->tableNameView()." where p_time > '$time'";	
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $data = $cmd->queryAll();
	$res = array();
	foreach($data as $v){
	    $res[] = $v['p_uid'];
	}
	return $res;
    } 

    /**
     * 获得最近某些天内有回答过问题的理财师id
     */
    protected function getLastAnswerPlanner($day)
    {
	$time = date('Y-m-d H:i:s',strtotime("-$day day"));
	$sql = "select distinct p_uid from ".$this->tableNameAnswer()." where c_time > '$time'";	
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $data = $cmd->queryAll();
	$res = array();
	foreach($data as $v){
	    $res[] = $v['p_uid'];
	}
	return $res;
    } 
    
    /**
     * 获得最近某些天内有更新计划的理财师id
     */
    protected function getLastPlanPlanner($day)
    {
	$time = date('Y-m-d H:i:s',strtotime("-$day day"));
	$sql = "select distinct p_uid from ".$this->tableNamePlan()." where operate_time > '$time' or c_time > '$time'";	
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $data = $cmd->queryAll();
	$res = array();
	foreach($data as $v){
	    $res[] = $v['p_uid'];
	}
	return $res;
    }
    
    /**
     * 获得最近某些天内有更新圈子发言的理财师id
     */
    protected function getLastCirclePlanner($day)
    {
	$time = date('Y-m-d H:i:s',strtotime("-$day day"));
	$sql = "select distinct uid from ".$this->tableNameCircle()." where u_type = 2 and c_time > '$time'";	
        $cmd = Yii::app()->lcs_comment_r->createCommand($sql);
        $data = $cmd->queryAll();
	$res = array();
	foreach($data as $v){
	    $res[] = $v['uid'];
	}
	return $res;
    }
    
    /**
     * 获得当前活跃的理财师id（某些天内更新圈子，回答，观点，计划）
     */
    protected function getActivePlanner($day)
    {
	$uid1 = $this->getLastViewPlanner($day);	
	$uid2 = $this->getLastAnswerPlanner($day);	
	$uid3 = $this->getLastCirclePlanner($day);	
	$uid4 = $this->getLastPlanPlanner($day);
	
	$active = array_unique(array_merge($uid1,$uid2,$uid3,$uid4));
	return $active;	
    }

    protected function getPlannerInfo($arr_puid)
    {
	if(!$arr_puid)
	    return array();
	$query_str = implode(',',$arr_puid);
	//$sql = "select s_uid,name,summary,department,image from ".$this->tableName()." where s_uid in ($query_str) order by field(s_uid,$query_str)";
        $sql = "select a.s_uid,a.name,a.summary,a.image,a.is_show,b.name company from ".$this->tableName()." a inner join ".$this->tableCompany().
               " b on a.company_id = b.id where a.s_uid in ($query_str) order by field(s_uid,$query_str)";
	$cmd = Yii::app()->lcs_r->createCommand($sql);
	$res = $cmd->queryAll();
	foreach($res as &$v){
            if(!$v['is_show']){
		$v['company'] = '新浪认证';
	    }
	    $v['attention'] = $this->getPlannerAcction($v['s_uid']);
	}
	return $res;
    }

    protected function getPlannerAcction($p_uid)
    {
	$sql = "select count(*) from ".$this->tableNameAttention()." where p_uid = $p_uid";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
	return $cmd->queryScalar();
    }
 
    public function filtePlanner($p_uids)
    {
	$sql = 'select relation_id from lcs_page_cfg where area_code=122 and status = 0';
	$cmd = Yii::app()->lcs_r->createCommand($sql);
        $data = $cmd->queryAll();
        $res = array();
        foreach($data as $v){
            $res[] = $v['relation_id'];
        }
	$res[] = 2813700891;//养基金
        if($p_uids == 'plan')
	    return $res;
        return array_diff($p_uids,$res);	
    }
   
    /**
     * 观点热
     */
    public function getPlannerView($day,$num)
    {
	$p_uids = $this->getLastViewPlanner($day);
	$p_uids = $this->filtePlanner($p_uids);
	if(!$p_uids)
	    return array();
	$query_str = implode(',',$p_uids);
	$sql = "select sum(view_num) sum_view,p_uid from ".$this->tableNameView()." where p_uid in($query_str) group by p_uid order by sum_view desc limit $num";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $data = $cmd->queryAll();
	$arr_puid = array();
	foreach($data as $k=>$v){
	    $arr_puid[] = $v['p_uid'];
        }
	$res = $this->getPlannerInfo($arr_puid);
	return $res;
    }

    /**
     * 回答快
     */
    public function getPlannerAnswer($day,$num)
    {
	$p_uids = $this->getLastAnswerPlanner($day);
	$p_uids = $this->filtePlanner($p_uids);
	if(!$p_uids)
	    return array();
	$query_str = implode(',',$p_uids);
	$sql = "select s_uid,resp_time_num from ".$this->tableNameAsk()." where q_num > 3 and resp_time_num > 0 and s_uid in($query_str) order by resp_time_num asc limit $num";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $data = $cmd->queryAll();
	$arr_puid = $arr_resp = array();
	foreach($data as $k=>$v){
	    $arr_puid[] = $v['s_uid'];
	    $arr_resp[$v['s_uid']] = $v['resp_time_num'];
        }
	$res = $this->getPlannerInfo($arr_puid);
	foreach($res as &$v){
	    $v['responseLength'] = (int)$arr_resp[$v['s_uid']];
	}
	return $res;
    }

    /**
     * 计划赚
     */
    public function getPlannerPlan($day,$num)
    {
	$p_uids = $this->filtePlanner('plan');
	$str_puids = $p_uids?implode(',',$p_uids):0;
	$sql = "select sum(curr_ror) as ror,p_uid from ".$this->tableNamePlan()." where status = 3 and p_uid not in ($str_puids) group by p_uid order by ror desc limit $num";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $data = $cmd->queryAll();
	$arr_puid = $arr_ror = array();
	foreach($data as $v){
	    if($v['ror'] > 0){
	    	$arr_puid[] = $v['p_uid'];
		$arr_ror[$v['p_uid']] = $v['ror'];
	    }
	}
	$res = $this->getPlannerInfo($arr_puid);
	foreach($res as &$v){
	    $v['currentEarn'] = (float)$arr_ror[$v['s_uid']]; 
	}
	return $res;
    }

    /**
     * 粉丝多
     */
    public function getPlannerFans($day,$num)
    {
	$p_uids = $this->getActivePlanner($day);
	$p_uids = $this->filtePlanner($p_uids);
	if(!$p_uids)
	    return array();
	$query_str = implode(',',$p_uids);
	$sql = "select count(*) guanzhu,p_uid from ".$this->tableNameAttention()." where p_uid in($query_str) group by p_uid order by guanzhu desc limit $num";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $data = $cmd->queryAll();
	$arr_puid = array();
	foreach($data as $k=>$v){
	    $arr_puid[] = $v['p_uid'];
        }
	$res = $this->getPlannerInfo($arr_puid);
	return $res;
    }

    /**
     * 同城
     */
    public function getPlannerCity($day,$num)
    {
	$p_uids =  $this->getActivePlanner($day);
	$p_uids = $this->filtePlanner($p_uids);
	if(!$p_uids)
	    return array();
	$query_str = implode(',',$p_uids);
	//$sql = "select province,s_uid,name,summary,department,image from ".$this->tableName()." where s_uid in($query_str)";
        $sql = "select a.province,a.s_uid,a.name,a.summary,a.image,a.is_show,b.name company from ".$this->tableName()." a inner join ".$this->tableCompany().               
		       " b on a.company_id = b.id where a.s_uid in ($query_str)";
	$cmd = Yii::app()->lcs_r->createCommand($sql);
        $res = $cmd->queryAll();
	$sort = array();
	foreach($res as &$v){
            if(!$v['is_show']){
                $v['company'] = '新浪认证';
            }
	    $v['attention'] = $this->getPlannerAcction($v['s_uid']);
	    $sort[] = $v['attention'];
	}
	array_multisort($sort,SORT_DESC,SORT_NUMERIC,$res);
        return $res;
    }

    /**
     * vip服务订阅人数多
     */
    public function getPlannerService($day,$num)
    {
        $sql = "select count(*) total_sub,p_uid from ".$this->tableNameOrder()." where type=111 and status=2  group by p_uid order by total_sub desc limit $num";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $data = $cmd->queryAll();
        $arr_puid = array();
        foreach($data as $k=>$v){
            $arr_puid[] = $v['p_uid'];
        }
        $res = $this->getPlannerInfo($arr_puid);
        return $res;
    }
    
   /*
     * 关注理财师的用户uid
     */
    public function getPlannerUids($p_uid){
        $sql = "select uid from ".$this->tableNameAttention()." where p_uid = $p_uid";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        return $cmd->queryAll();
    }
    /**
	 * 根据理财师id 获取理财师信息
	 *
	 * @param unknown_type $p_uid
	 * @param $info_trim  1基础     2公司和职位   4省市   8认证名称    16能力圈信息  32 评级  64观点统计信息
	 */
	public function getPlannerByIdsNew($p_uids, $info_trim = 1) {

		$mem_xkey = 'lcs_getPlannerByIdsNew|' . (is_array($p_uids) ? implode('.', $p_uids) : $p_uids) . '|' . $info_trim;
		$val = Yii::app()->cache->get($mem_xkey);
		if (!empty($val))
			return $val;

		$return = array();

		if (empty($p_uids)) {
			return $p_uids;
		}
		$mem_pre_key = MEM_PRE_KEY . "p_";
		$p_uids = array_unique(array_filter((array) $p_uids));
		//从缓存获取数据
		$mult_key = array();
		foreach ($p_uids as $val) {
			$mult_key[] = $mem_pre_key . intval($val);
		}
		$cache = Yii::app()->cache->mget($mult_key);
		$no_cache_id = array();
		$cache_data = array();
		foreach ($cache as $key => $val) {
			$v_key = str_replace($mem_pre_key, '', $key);  // 从缓存的key中截取 p_uid
			if ($this->isUseCache && !empty($val) && $val !== false && isset($val['bimage'])) {
				$cache_data[] = $val;
			} else {
				$no_cache_id[] = $v_key;
			}
		}
		unset($cache);
		// 未缓存的，从数据库中读取，然后合并到缓存数组中。
		if (!empty($no_cache_id)) {
			$sql = "select s_uid,s_uid as p_uid,w_uid,auth_id,group_id,name,real_name,image,bimage,province as province_id,city as city_id,company_id,department,ind_id,cert_id,cert_number,summary,position_id,location,tags,view_num,status,u_time,partner_id,job_no,is_show from " . $this->tableName() . " where status!=-1 and s_uid in(";
			$sql .= implode(',', $no_cache_id);
			$sql .= ')';
			$cmd = Yii::app()->lcs_r->createCommand($sql);
			$planners = $cmd->queryAll();

			if (is_array($planners) && sizeof($planners) > 0) {
				foreach ($planners as $vals) {
					//cache缓存 缓存一周
					Yii::app()->cache->set($mem_pre_key . $vals['s_uid'], $vals, 36000);
				}
				$cache_data = array_merge($cache_data, $planners);  // 把从数据库读取的数据合并到缓存数组中。
			}
		}
		//公司名称   职位
		$company_id = array();
		$position_id = array();
		$city_id = array();
		foreach ($cache_data as $val) {
			$company_id[] = $val['company_id'];
			$position_id[] = $val['position_id'];
			if (!empty($val['province_id'])) {
				$city_id[] = $val['province_id'];
			}
			if (!empty($val['city_id'])) {
				$city_id[] = $val['city_id'];
			}
		}
		$company = array();
		$position = array();
		if (($info_trim & 2) == 2) {
			$company = Common::model()->getCompany($company_id);
			$position = Common::model()->getPosition($position_id);
		}

		$cities = array();
		if (($info_trim & 4) == 4) {
			$city = Common::model()->getRegion(array_unique($city_id)); //获取涉及的全部城市（省份）
			foreach ($city as $ct) { //city id 做索引
				$cities[$ct['id']] = $ct;
			}
		}

		$certification = array();
		if (($info_trim & 8) == 8) {
			$certification = Common::model()->getCertification();  // 取所有的资格名称
		}

		$planner_ablitys = array();
		if (($info_trim & 16) == 16) {
			$planner_ablitys = $this->getPlannerAbilityInfoByIds($p_uids);
		}

		$planner_exts = array();
		if (($info_trim & 32) == 32) {
			$planner_exts = PlannerExt::model()->getPlannerExtById($p_uids);
		}

		//观点或观点包统计数据
		$pkgs = array();
		if (($info_trim & 64) == 64 && !empty($p_uids)) {
			foreach ($p_uids as $p_uid) {
				$all_package = Package::model()->getPackageByPlanner($p_uid);
				$pkg_info = array('view_num' => 0, 'sub_num' => 0, 'collect_num' => 0, 'comment_num' => 0); //'praise_num'=>0,'against_num'=>0,
				if (!empty($all_package)) {
					foreach ($all_package as $v) {
						$pkg_info['view_num'] += intval($v['view_num']);
						$pkg_info['sub_num'] += intval($v['sub_num']);
						$pkg_info['collect_num'] += intval($v['collect_num']);
						$pkg_info['comment_num'] += intval($v['comment_num']);
					}
				}

				$pkgs[$p_uid] = $pkg_info;
			}
		}

		// 理财师直播
		$planner_live_map = Video::model()->getLiveInfoBySuids($p_uids);
		foreach ($cache_data as $vals) {
			$return[$vals['s_uid']] = $vals;
			if (isset($company[$vals['company_id']])) {
				if (isset($vals['is_show']) && $vals['is_show'] == 0) {
					$return[$vals['s_uid']]['company'] = "新浪认证";
					$return[$vals['s_uid']]['company_name'] = "新浪认证";
				} else {
					$return[$vals['s_uid']]['company'] = $company[$vals['company_id']]['name'];
					$return[$vals['s_uid']]['company_name'] = $company[$vals['company_id']]['name'];
				}
			} else {
				$return[$vals['s_uid']]['company'] = "";
				$return[$vals['s_uid']]['company_name'] = "";
			}
			$return[$vals['s_uid']]['position'] = $position[$vals['position_id']]['name'];
			$return[$vals['s_uid']]['province'] = $cities[$vals['province_id']]['name'];
			$return[$vals['s_uid']]['city'] = $cities[$vals['city_id']]['name'];
			$return[$vals['s_uid']]['position_name'] = $position[$vals['position_id']]['name'];
			$return[$vals['s_uid']]['certification'] = $certification[$vals['cert_id']]['name'];
			//update by zwg 20140822   问答的能力圈标签 替换理财师注册时填写的标签    @see http://issue.internal.sina.com.cn/browse/CSTECHDEV-205
			if ($planner_ablitys && isset($planner_ablitys[$vals['s_uid']])) {
				$ablity_tags = $this->getUnRepeatAblityTags($planner_ablitys[$vals['s_uid']]['ability_tags']);
				if (empty($ablity_tags)) {
					$return[$vals['s_uid']]['tags'] = '';
				} else {
					$return[$vals['s_uid']]['tags'] = implode(',', $ablity_tags);
				}
				$p_ind_name = '';
				if (is_array($planner_ablitys[$vals['s_uid']]['ability_industrys'])) {
					foreach ($planner_ablitys[$vals['s_uid']]['ability_industrys'] as $ind) {
						$p_ind_name .= $ind['name'] . ',';
					}
				}
				$return[$vals['s_uid']]['ind_name'] = substr($p_ind_name, 0, -1);
			}

			$grade_info = null;
			$plan_info = null;
			if (!empty($planner_exts) && isset($planner_exts[$vals['s_uid']])) {
				$px = $planner_exts[$vals['s_uid']];
				//评级信息
				$grade_info = array();
				$grade_info['grade_plan'] = isset($px['grade_plan']) ? ($px['grade_plan_auto'] == 1 && $px['grade_plan'] > "3" ? "3" : $px['grade_plan']) : '0';
				$grade_info['grade_plan'] = strval($grade_info['grade_plan']);
				$grade_info['grade_plan_status'] = isset($px['grade_plan_status']) ? $px['grade_plan_status'] : '0';
				$grade_info['grade_pkg'] = isset($px['grade_pkg']) ? (($px['grade_pkg_auto'] == 1 && $px['grade_pkg'] > 3 ? "3" : $px['grade_pkg'])) : '0';
				$grade_info['grade_pkg'] = strval($grade_info['grade_pkg']);
				$grade_info['grade_pkg_status'] = isset($px['grade_pkg_status']) ? $px['grade_pkg_status'] : '0';
				//星级百分比数据
				$plan_grade_percent = MEM_PRE_KEY . 'plan_grade_percent';
				$plan_grade_percent_title = Yii::app()->redis_r->hget($plan_grade_percent, $vals['s_uid']);
				$grade_info['plan_grade_percent_title'] = $plan_grade_percent_title ? '实战力' . $grade_info['grade_plan'] . '星' . '，' . $plan_grade_percent_title : '';

				$pkg_grade_percent = MEM_PRE_KEY . 'pkg_grade_percent';
				$pkg_grade_percent_title = Yii::app()->redis_r->hget($pkg_grade_percent, $vals['s_uid']);
				$grade_info['pkg_grade_percent_title'] = $pkg_grade_percent_title ? '分析力' . $grade_info['grade_pkg'] . '星' . '，' . $pkg_grade_percent_title : '';

				//计划信息
				$plan_info = array();
				$plan_info['pln_num'] = $px['pln_num'];
				$plan_info['total_num'] = Plan::model()->getPlanNumByPuid($vals['s_uid'], array(2, 3, 4, 5, 6, 7)); //总计划数
				$plan_info['pln_success_rate'] = $px['pln_success_rate'];
				$plan_info['pln_year_rate'] = $px['pln_year_rate'];
				$plan_info['pln_profit_num'] = $px['pln_profit_num'];
				$plan_info['pln_total_profit'] = $px['pln_total_profit'];
				$plan_info['pln_max_ror'] = $px['pln_max_ror'];
			}
			$return[$vals['s_uid']]['grade_info'] = $grade_info;
			$return[$vals['s_uid']]['plan_stat_info'] = $plan_info;
			//影响力
			$return[$vals['s_uid']]['influence'] = $planner_exts[$vals['s_uid']]['influence'];
			//活跃度
			$return[$vals['s_uid']]['activity'] = $planner_exts[$vals['s_uid']]['activity'];
			//观点或观点包统计数据
			if (!empty($pkgs) && isset($pkgs[$vals['s_uid']])) {
				$return[$vals['s_uid']]['view_stat_info'] = $pkgs[$vals['s_uid']];
			}
			//是否在线
			$return[$vals['s_uid']]['is_online'] = $return[$vals['s_uid']]['online'] = time() - strtotime($vals['u_time']) < self::ONLINE_TIME ? 1 : 0;

			// 是否正在直播
			$tmp_live_info = !empty($planner_live_map[$vals['s_uid']]) ? array_shift($planner_live_map[$vals['s_uid']]) : null;
			$return[$vals['s_uid']]['is_live'] = !empty($tmp_live_info) && ($tmp_live_info['is_online'] == 1) ? 1 : 0;

			// 免费圈子信息
			$return[$vals['s_uid']]['free_circle_info'] = Circle::model()->getCircleInfoNew(["p_uid" => $vals['s_uid'], "type" => 0]);
		}
		unset($cache_data);

		$val = Yii::app()->cache->set($mem_xkey, $return, 1);
		return $return;
    }
    	/**
	 * 去掉重复的能力标签
	 * @param unknown $ability_tags
	 * @return multitype:
	 */
	private function getUnRepeatAblityTags($ability_tags) {
		//能力标签去重
		$tags = array();
		if (!empty($ability_tags) && count($ability_tags) > 0) {

			foreach ($ability_tags as $item) {
				$tags[$item['name']] = $item['name'];
			}
		}
		return array_values($tags);
	}
    	/**
	 * 获取理财师的问题能力信息
	 *
	 * @param number $uid
	 */
	public function getPlannerAbilityInfoByIds($p_uids) {
		$result = array();
		if (empty($p_uids)) {
			return $result;
		}
		$p_uids = (array) $p_uids;
		$leave_ids = array();
		//先从缓存获取信息
		$mem_pre_key_p = MEM_PRE_KEY . 'ask_' . 'p_ability_';
		$cache = Yii::app()->cache;
		foreach ($p_uids as $p_uid) {
			$p_ability_info = $cache->get($mem_pre_key_p . $p_uid);
			if ($p_ability_info) {
				$result[$p_uid] = $p_ability_info;
			} else {
				$leave_ids[] = $p_uid;
			}
		}

		if (count($leave_ids) > 0) {
			foreach ($leave_ids as $p_uid) {
				$p_ability_info = $this->_getPlannerAbilityInfo($p_uid);
				$result[$p_uid] = $p_ability_info;
				Yii::app()->cache->set($mem_pre_key_p . $p_uid, $p_ability_info, 259200); //缓存3天
			}
		}
		return $result;
    }
    /**
	 * 获取一个理财师的能力信息 没有缓存
	 * @param unknown $p_uid
	 * @return multitype:multitype:multitype:unknown
	 */
	private function _getPlannerAbilityInfo($p_uid) {
		$res = array();
		if ($p_uid > 0) {
			$sql = 'select id, p_uid, ind_id, parent_id, name, assent_num from ' . $this->tableNameAbility() . ' where  p_uid=:p_uid and status=0 order by id asc;';
			$cmd = Yii::app()->lcs_r->createCommand($sql);
			$cmd->bindParam(':p_uid', $p_uid, PDO::PARAM_INT);
			$data = $cmd->queryAll();

			if (!empty($data)) {
				$industrys = array();
				$tags = array();
				foreach ($data as $ability) {
					if ($ability['parent_id'] == 0) {
						$industrys[] = array('id' => $ability['id'], 'ind_id' => $ability['ind_id'], 'name' => $ability['name']);
					} else {
						$tags[] = array('id' => $ability['id'], 'ind_id' => $ability['ind_id'], 'name' => $ability['name']);
					}
				}

				$res['ability_industrys'] = $industrys;
				$res['ability_tags'] = $tags;
			}
		}
		return $res;
	}
}












