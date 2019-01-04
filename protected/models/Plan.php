<?php

/**
 * 计划相关信息数据库访问类
 */
class Plan extends CActiveRecord {

    const PLAN_STATUS_DRAFT = 1; //待发布
    const PLAN_STATUS_PENDING = 2; //待运行
    const PLAN_STATUS_ACTIVE = 3; //运行中
    const PLAN_STATUS_SUCCESS = 4; //运行成功
    const PLAN_STATUS_FAIL = 5; //运行失败
    const PLAN_STATUS_STOPLOSS_FREEZE = 6; //止损冻结
    const PLAN_STATUS_EXPIRE_FREEZE = 7; //到期冻结
    const TRANSACTION_COST_COMMISSION = 0.0003; //佣金比例

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    //计划表
    public function tableName() {
        return TABLE_PREFIX . 'plan_info';
    }

    //调仓明细表
    public function tableNameTransactions() {
        return TABLE_PREFIX . 'plan_transactions';
    }

    //订阅表
    public function tableNameSubscription() {
        return TABLE_PREFIX . 'plan_subscription';
    }

    public function tableNamePlanAssess() {
        return 'lcs_plan_assess';
    }

    //持仓表
    public function tableNameAsset() {
        return TABLE_PREFIX . 'plan_asset';
    }

    //数据库 读
    private function getDBR() {
        return Yii::app()->lcs_r;
    }

    //数据库 写
    private function getDBW() {
        return Yii::app()->lcs_w;
    }

    ///历史股票开收盘记录
    public function tableNameDaily() {
        return TABLE_PREFIX . "daily_k";
    }

    //计划收益历史表
    public function tableNameHistory() {
        return TABLE_PREFIX . 'plan_profit_stats_history';
    }
    public function savePlanInfo($data){
    	$db_w = Yii::app()->lcs_w;
    	$db_w->createCommand()->insert($this->tableName(), $data);
        return Yii::app()->lcs_w->getLastInsertID();
    }

    /**
     * 查询交易动态
     * @param $tran_id
     * @param $is_r 是否是从读库获取数据
     * @return mixed
     */
    public function getPlanTransactionByIds($tran_id,$is_r=true) {
        $tran_id = (array) $tran_id;
        $sql = "select id,pln_id,symbol,type,deal_price,deal_amount,hold_avg_cost,profit,wgt_before,wgt_after,reason,c_time,transaction_cost from " . $this->tableNameTransactions() . " where id in(" . implode(',', $tran_id) . ");";
        $db = $is_r?Yii::app()->lcs_r:Yii::app()->lcs_w;
        $list = $db->createCommand($sql)->queryAll();
        $result = array();
        if (!empty($list)) {
            foreach ($list as $item) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }

    public function updPlanInfo($pln_id, $pln_array) {

        $pln_id = intval($pln_id);
        $now = date('Y-m-d H:i:s');
        $sql = "update " . $this->tableName() . " set u_time='$now',";
        if (isset($pln_array['warrant_value'])) {
            $sql .= "warrant_value=warrant_value+$pln_array[warrant_value],";
        }
        if (isset($pln_array['available_value'])) {
            $sql .= "available_value=available_value+$pln_array[available_value],";
        }
        if (isset($pln_array['weight'])) {
            $sql .= "weight='$pln_array[weight]',";
        }
        if (isset($pln_array['operate_time'])) {
            $sql .= "operate_time='$pln_array[operate_time]',";
        }

        $sql = substr($sql, 0, -1) . " where pln_id=$pln_id";


        $cmd = Yii::app()->lcs_w->createCommand($sql);
        return $cmd->execute();
    }

    /**
     * 获取计划详情
     * @param $ids
     * @param null $fields
     * @return mixed
     */
    public function getPlanInfoByIds($ids, $fields = null, $db = 'r') {

        $select = 'pln_id,p_uid,name,number,summary,image,ind_id,subscription_price,target_ror,performance_promise,invest_days,subscription_count,reader_count,universe_type,stop_loss,time_left,status,curr_ror,max_profit,min_profit,hs300,init_value,user_values,available_value,warrant_value,market_value,start_date,end_date,status,c_time,min_follower_amt,max_follower_amt,hs300,operate_time,real_end_time,weight,comment_count,history_year_ror,run_days,history_success_ratio,freeze_time,privilege_price,audit_reason';
        if (!empty($fields)) {
            $select = is_array($fields) ? implode(',', $fields) : $fields;
        }
        $ids = (array) $ids;
        $db_link = $db == 'r' ? Yii::app()->lcs_r : Yii::app()->lcs_w;
        $sql = 'select ' . $select . ' from ' . $this->tableName() . ' where pln_id in (' . implode(',', $ids) . ');';
        $cmd = $db_link->createCommand($sql);
        $res = $cmd->queryAll();
        $pln_infos = array();
        if (!empty($res)) {
            foreach ($res as $val) {
                $pln_infos["$val[pln_id]"] = $val;
            }
        }
        return $pln_infos;
    }

    /**
     * 获取指定日期开始的计划
     * @param $btime
     * @return mixed
     */
    public function getPLanStartList($btime = "") {
        if (empty($btime)) {
            return array();
        }
        return Yii::app()->lcs_r->createCommand("select pln_id,p_uid,name,number from " . $this->tableName() . " where status =3 and start_date='$btime'")->queryAll();
    }

    /**
     * 获取指定日期结束的计划
     * @param $etime
     * @return mixed
     */
    public function getPLanEndList($etime = "") {
        if (empty($etime)) {
            return array();
        }
        return Yii::app()->lcs_r->createCommand("select pln_id,p_uid,name,number from " . $this->tableName() . " where status >3 and real_end_time>='$etime'")->queryAll();
    }

    /*
     * 获取计划的最近一条收益
     * Author:yangmao
     * Date:2014-11-22
     * */

    public function getPlanHistoryProfit($pln_id_array = array(), $profit_date) {
        $plan_history = Yii::app()->lcs_r->createCommand("select total_profit,pln_id from lcs_plan_profit_stats_history where pln_id in(" . implode(",", $pln_id_array) . ") and profit_date='" . $profit_date . "'")->queryAll();
        $plan_history_re = array();
        if (!empty($plan_history)) {
            foreach ($plan_history as $v) {
                $plan_history_re[$v['pln_id']] = $v['total_profit'];
            }
        }
        foreach ($pln_id_array as $v) {
            if (!array_key_exists($v, $plan_history_re)) {
                $plan_history_re[$v] = 0;
            }
        }
        return $plan_history_re;
    }

    /**
     * 获取计划历史收益
     * @param $pln_ids
     * @param string $date\
     */
    public function getPlanProfit($pln_ids, $date = '') {
        $pln_ids = (array) $pln_ids;
        $date = empty($date) ? date("Y-m-d") : $date;

        $result = array();
        $profit = Yii::app()->lcs_r->createCommand("select pln_id,day_profit from lcs_plan_profit_stats where pln_id in(" . implode(",", $pln_ids) . ") and profit_date='$date'")->queryAll();

        if (!empty($profit)) {
            foreach ($profit as $val) {
                $result[$val['pln_id']] = $val;
            }
        }

        return $result;
    }

    /**
     * 获取观察计划的用户id
     * @param $pln_id
     */
    public function getAttenPlanUids($pln_id = array()) {
        $pln_id = (array) $pln_id;
        return Yii::app()->lcs_r->createCommand("select uid from lcs_collect where relation_id in (" . implode(',', $pln_id) . ") and type=3")->queryColumn();
    }

    /**
     * 添加观察提醒（每天收盘）
     * @param array $pln_ids
     */
    public function addAttenMessage($pln_ids = array()) {
        $pln_ids = (array) $pln_ids;
        if (!empty($pln_ids)) {
            foreach ($pln_ids as $pln_id) {
                $plan_info = $this->getPlanInfoByIds($pln_id);

                if (!empty($plan_info)) {
                    $plan_info = $plan_info["$pln_id"];

                    $prev_weight = 0; //上一个交易日仓位
                    $buy_count = 0; //建仓数
                    $sell_count = 0; //平仓数
                    //当日收益
                    $profit = $this->getPlanProfit($pln_id);
                    $profit = isset($profit[$pln_id]) ? $profit[$pln_id] : array();

                    //当日交易记录
                    $transactions = PlanTransactions::model()->getPlanTransactionsByPlnID($pln_id, "type,wgt_before", date("Y-m-d"), date("Y-m-d"));

                    if (!empty($transactions)) {
                        foreach ($transactions as $k => $t) {

                            if ($t['type'] == 1) {//建仓
                                $buy_count ++;
                            } else {//平仓
                                $sell_count ++;
                            }
                        }
                    }

                    //add by zwg 20151104 查找上一个交易日的仓位  就是当前交易日前的最后一笔交易的仓位
                    $transactions = PlanTransactions::model()->getPlanTransactionsOfEndDate($pln_id, "wgt_after", date("Y-m-d"), 1);
                    if (!empty($transactions)) {
                        foreach ($transactions as $k => $t) {
                            $prev_weight = $t['wgt_after'];
                        }
                    }

                    //理财师
                    $planner_info = Planner::model()->getPlannerById(array(intval($plan_info['p_uid'])));
                    $planner_info = isset($planner_info[intval($plan_info['p_uid'])]) ? $planner_info[intval($plan_info['p_uid'])] : array();

                    $uids = $this->getAttenPlanUids($pln_id);
                    //个性化 去掉关闭提醒的uid
                    $uids = MessageUserClose::model()->filterCloseUids($uids, 1, 10, 1);

                    if (!empty($uids)) {
                        $uids = array_unique($uids);

                        foreach ($uids as $uid) {

                            $table_name = "lcs_message_".($uid%10);
                            Yii::app()->lcs_w->createCommand()->insert($table_name, array(
                                'uid' => $uid,
                                'u_type' => 1,
                                'type' => 10,
                                'relation_id' => $pln_id,
                                'content' => json_encode(array(
                                    array('value' => '您观察的计划' . date("m月d日") . '最新收益出炉，', 'class' => '', 'link' => ''),
                                    array('value' => "立即查看收益详情。", 'class' => '', 'link' => '/plan/' . $pln_id)
                                    ), JSON_UNESCAPED_UNICODE),
                                'content_client' => json_encode(array(
                                    'type' => 1,
                                    'today_ror' => isset($profit['day_profit']) ? sprintf("%.2f", $profit['day_profit'] * 100) : 0,
                                    'curr_ror' => sprintf("%.2f", $plan_info['curr_ror'] * 100),
                                    'target_ror' => sprintf("%.2f", $plan_info['target_ror'] * 100),
                                    'prev_weight' => sprintf("%.2f", $prev_weight * 100),
                                    'curr_weight' => sprintf("%.2f", $plan_info['weight'] * 100),
                                    'buy_count' => $buy_count,
                                    'sell_count' => $sell_count,
                                    'plan_name' => $plan_info['name'],
                                    'p_uid' => $plan_info['p_uid'],
                                    'planner_name' => isset($planner_info['name']) ? $planner_info['name'] : '',
                                    'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                                    'company' => isset($planner_info['company']) ? $planner_info['company'] : ''
                                    ), JSON_UNESCAPED_UNICODE),
                                'link_url' => '/plan/' . $pln_id,
                                'c_time' => date("Y-m-d H:i:s"),
                                'u_time' => date("Y-m-d H:i:s")
                            ));
                        }
                    }
                }
            }
        }
    }

    /**
     * 获取理财师计划操作的数量
     * @param string $p_uid
     * @param null $start_date
     * @param null $end_date
     * @return mixed
     */
    public function getPlanTransactionCount($pln_id = '', $start_time = '', $end_time = '') {
        $cdn = '';
        if (!empty($pln_id)) {
            $cdn .= ' AND pln_id=:pln_id';
        }
        if (!empty($start_time)) {
            $cdn .= ' AND c_time>=:start_time';
        }
        if (!empty($end_time)) {
            $cdn .= ' AND c_time<:end_time';
        }
        $sql = 'SELECT pln_id, COUNT(pln_id) AS num FROM ' . $this->tableNameTransactions() . ' WHERE 1=1 ' . $cdn . ' AND STATUS=1 GROUP BY pln_id;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        if (!empty($pln_id)) {
            $cmd->bindParam(':pln_id', $pln_id, PDO::PARAM_INT);
        }
        if (!empty($start_time)) {
            $cmd->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        }
        if (!empty($end_time)) {
            $cmd->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        }
        return $cmd->queryAll();
    }

    /**
     * 获取指定时间段内的交易动态
     * @param $s_time
     * @param $e_time
     * @return mixed
     */
    public function getPlanTransactionList($s_time, $e_time) {
        $db_r = Yii::app()->lcs_r;
        $sql = "select id,pln_id,c_time from ". $this->tableNameTransactions() ." where c_time>='". $s_time ."' and c_time<='". $e_time ."' ";
        $cmd =  $db_r->createCommand($sql);
        return $cmd->queryAll();
    }

    public function getPlanInfoById($pln_id) {
        $sql = "select pln_id,name,number,ind_id,p_uid,weight,target_ror,stop_loss,curr_ror,max_profit,min_profit,init_value,available_value,warrant_value,min_follower_amt,max_follower_amt,start_date,end_date,status,operate_time from " . $this->tableName() . " where pln_id=$pln_id";
        $info = Yii::app()->lcs_r->createCommand($sql)->queryRow();
        //处理数据
        if (!empty($info)) {
            $info['name'] .= ($info['number'] > 9 ? $info['number'] : "0" . $info['number']) . "期";
        }
        return $info;
    }

    /**
     * 获取订阅计划的用户id
     * @param $pln_id
     * @return mixed
     */
    public function getSubPlanUids($pln_id) {
        $pln_id = intval($pln_id);
        return Yii::app()->lcs_r->createCommand("select uid from " . $this->tableNameSubscription() . " where pln_id=$pln_id and status>0")->queryColumn();
    }

    /**
     * 获取该理财师下的所有计划id
     * @param type $p_uids
     */
    public function getPlannerPlanIds($p_uids, $filter = " status>=4 ") {
        if (is_array($p_uids)&&count($p_uids)>0) {
            $result = Array();
            $tableName_PlanInfo = Plan::model()->tableName();
            $sql = "select pln_id,p_uid from " . $tableName_PlanInfo . " where p_uid in (" . implode(',', $p_uids) . ") and " . $filter;
            $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            foreach ($res as $item) {
                if (!isset($result[$item['p_uid']])) {
                    $result[$item['p_uid']] = Array();
                }
                $result[$item['p_uid']][] = $item['pln_id'];
            }
            return $result;
        } else {
            return false;
        }
    }

    /*
     * $planner_plan中记录了该名理财师下的所有已完成计划的id
     */

    public function getPlannerAssessInfo($planner_plan) {
        if (is_array($planner_plan)&&count($planner_plan)>0) {
            $result = Array();
            foreach ($planner_plan as $index => $item) {
                $result[$index] = Array();
                if (count($item) > 0) {
                    $sql = "select pln_id,profit_num,loss_num,avg_profit,avg_loss from " . $this->tableNamePlanAssess() . " where pln_id in (" . implode(',', $item) . ")";
                    $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
                    foreach ($res as $temp) {
                        $result[$index][$temp['pln_id']] = $temp;
                    }
                }
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 获取该理财师下的所有已完成计划的股票统计
     * @param type $planner_plan中记录了该名理财师下的所有已完成计划的id
     */
    public function getPlannerAsset($planner_plan) {
        if (is_array($planner_plan)&&count($planner_plan)>0) {
            $result = Array();
            foreach ($planner_plan as $index => $item) {
                $result[$index] = Array();
                if (count($item) > 0) {
                    $sql = "select pln_id,symbol,profit from " . $this->tableNameAsset() . " where pln_id in (" . implode(',', $item) . ")";
                    $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
                    foreach ($res as $temp) {
                        $result[$index][$temp['pln_id']] = $temp;
                    }
                }
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 获取改理财师的最新的计划
     * @param type $p_uids理财师编号数组
     */
    public function getPlannersNewPlan($p_uids) {
        if (is_array($p_uids) && count($p_uids) > 0) {
            $result = Array();
            $sql = "select pln_id,p_uid,name,number,subscription_price,status,curr_ror,target_ror,invest_days,run_days,stop_loss from " . $this->tableName() . " where p_uid in (" . implode(',', $p_uids) . ") and status in (2,3)";
            $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            foreach ($res as $item) {
                if (!isset($result[$item['p_uid']])) {
                    $result[$item['p_uid']] = Array();
                }
                $temp = Array();
                $temp['pln_id'] = $item['pln_id'];
                $temp['name'] = $item['name'];
                $temp['number'] = $item['number'];
                if ((int) $item['number'] < 10) {
                    $temp['name'].="0" . $item['number'] . "期";
                } else {
                    $temp['name'].=$item['number'] . "期";
                }
                $temp['subscription_price'] = $item['subscription_price'];
                $temp['status'] = $item['status'];
                $temp['curr_ror'] = $item['curr_ror'];
                $temp['target_ror'] = $item['target_ror'];
                $temp['invest_days'] = $item['invest_days'];
                $temp['run_days'] = $item['run_days'];
                $temp['stop_loss'] = $item['stop_loss'];
                $result[$item['p_uid']][] = $temp;
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 获取具有理财师影响力的计划
     *  1. 正在运行的计划  status=3
     *  2. 抢购日期内的计划 status=2
     *  3. 计划结束小于指定天数的计划
     * @param $end_date  计划结束时间
     */
    public function getPlanInfoOfInfluence($start_date, $end_date) {
        $sql = 'SELECT pln_id,p_uid,subscription_count,reader_count,status,real_end_time FROM ' . $this->tableName() . ' WHERE (STATUS in (2,3) AND (start_date<=:start_date or panic_buy_time<=:panic_buy_time) ) OR (STATUS IN (4,5,6,7) AND start_date<=:start_date1 AND real_end_time>=:end_date)';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':start_date', $start_date, PDO::PARAM_STR);
        $cmd->bindParam(':panic_buy_time', $start_date, PDO::PARAM_STR);
        $cmd->bindParam(':start_date1', $start_date, PDO::PARAM_STR);
        $cmd->bindParam(':end_date', $end_date, PDO::PARAM_STR);
        return $cmd->queryAll();
    }

    /**
     * 根据计划ID获取计划的购买信息
     * @param $ids
     * @param null $fields
     * @return mixed
     */
    public function getPlanSubInfoByPlanIds($ids, $fields = null) {
        $select = 'pln_id';
        if (!empty($fields)) {
            $select = is_array($fields) ? implode(',', $fields) : $fields;
        }
        $ids = (array) $ids;
        $sql = 'select ' . $select . ' from ' . $this->tableNameSubscription() . ' where pln_id in (' . implode(',', $ids) . ') and status in (1,2);';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        return $cmd->queryAll();
    }

    /**
     * 获取用户订阅计划失效的信息
     * @param string $time
     * @param null $fields
     */
    public function getPlanSubExpireList($time='', $fields = null){
        if(empty($time)){
            $time=date('Y-m-d H:i:s');
        }
        if(empty($fields)){
            $fields='id,uid,pln_id';
        }else if(is_array($fields)){
            $fields = implode(',',$fields);
        }
        $sql = "SELECT ".$fields." FROM ".$this->tableNameSubscription()." WHERE STATUS=1 and expire_time<>'00000-00-00 00:00:00' AND expire_time<:time;";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':time', $time, PDO::PARAM_STR);
        return $cmd->queryAll();
    }

    /**
     * 更新计划订阅表
     * @param $columns
     * @param string $conditions
     * @param array $params
     * @return mixed
     */
    public function updatePlanSubscription($columns, $conditions='', $params=array()){
        return $this->getDBW()->createCommand()->update($this->tableNameSubscription(),$columns,$conditions,$params);

    }

    /**
     * 通过状态获取计划列表
     * @param int $status
     * @return mixed
     */
    public function getPlanListByStatus($status = self::PLAN_STATUS_ACTIVE, $date = '') {
        $status = intval($status);
        $where = "status=$status";
        if (!empty($date)) {
            $where = "(status=$status and end_date>='" . date("Y-m-d") . "' and end_date<'" . $date . "') or (status=" . self::PLAN_STATUS_SUCCESS . " and real_end_time>='" . date("Y-m-d 00:00:00") . "' and real_end_time<='" . date("Y-m-d 23:59:59") . "')";
        }
        $sql = "select pln_id,name,number,ind_id,p_uid,weight,target_ror,stop_loss,curr_ror,max_profit,min_profit,
    			init_value,available_value,warrant_value,min_follower_amt,max_follower_amt,start_date,end_date,status,operate_time
    		    from " . $this->tableName() . " where $where";

        $plans = $this->getDBW()->createCommand($sql)->queryAll();
        return $plans;
    }
    
    /**
     * 根据状态获取计划编号数组
     * @param int $status 计划运行状态
     */
    public function getPlanIdListByStatus($status=self::PLAN_STATUS_ACTIVE){
        $status=intval($status);
        $sql=" select pln_id from ".$this->tableName()." where status=".$status;
        $pln_ids=$this->getDBR()->createCommand($sql)->queryAll();
        return $pln_ids;
    }
    

    /**
     * 获取计划持仓市值
     * @param $pln_id
     * @return array(
     *      mark_value  市值
     *      assets      持仓信息（包含最新价格）
     *
     * )
     */
    public function getPlanAssetMarkValue($pln_id) {
        $pln_id = intval($pln_id);
        $return = array(
            'mark_value' => 0, //市值
            'assets' => array() //持仓信息
        );

        //上证指数（确保上证指数的更新时间是30秒之内，否则退出）
        $sz = $_sz = Yii::app()->curl->get("http://hq.sinajs.cn/format=text&rm=" . time() . "&list=sh000001");
        try {
            $sz = explode('=', $sz);
            $sz[1] = explode(',', $sz[1]);
            $sz_time = $sz[1][30] . " " . $sz[1][31];
            if (abs(strtotime($sz_time) - time()) > 30) {
                echo date("Y-m-d H:i:s") . "(" . mb_convert_encoding($_sz, "UTF-8", "GBK") . ")(上证指数)行情串接口过期！\n";
                $return['mark_value'] = -1;
                return $return;
            }
        } catch (Exception $e) {
            echo date("Y-m-d H:i:s") . "(上证指数)行情串接口调用失败！\n";
            $return['mark_value'] = -1;
            return $return;
        }

        //获取当前持仓信息
        $assets = $this->getPlanAsset($pln_id);

        if (!empty($assets)) {
            $symbols = array();
            array_walk($assets, function($val) use(&$symbols) {
                array_push($symbols, $val['symbol']);
            });


            //通过股票代码获取最新信息
            $stocks = Yii::app()->curl->get("http://hq.sinajs.cn/format=text&rm=" . time() . "&list=" . strtolower(implode(",", $symbols)));
            if (!empty($stocks)) {
                $stocks = array_filter(explode("\n", $stocks));
                /*
                  $stocks 格式：
                  Array
                  (
                  [0] => sz000404=华意压缩,6.59,6.60,6.58,6.63,6.55,6.58,6.59,4052703,26682842
                  .52,11899,6.58,105200,6.57,300600,6.56,106300,6.55,92500,6.54,185021,6.59,266009
                  ,6.60,145550,6.61,148100,6.62,197980,6.63,2014-11-20,14:43:18,00
                  ) */
                //获得最新价
                $new_value = array();
                foreach ($stocks as $key => $val) {
                    $val = explode('=', $val);
                    if (isset($val[1]) && !empty($val[1])) {
                        $val[1] = explode(',', $val[1]);
                        if (count($val[1]) >= 33) {//判断行情串长度
                            $new_price = $val[1][3] > 0 ? $val[1][3] : $val[1][2]; //最新价

                            if ($new_price <= 0) {//停牌股票并且最新价为0时，读日K表
                                echo date("Y-m-d H:i:s") . "(" . mb_convert_encoding($val[0], "UTF-8", "GBK") . ")（" . $pln_id . "）停牌股票最新价为0，从日K表中读取\n";
                                $_price = $this->getDailyK($val[0], date("Y-m-d"));
                                if (isset($_price[$val[0]]) && $_price[$val[0]] > 0) {
                                    $new_price = $_price[$val[0]];
                                } else {
                                    echo date("Y-m-d H:i:s") . "（" . $pln_id . "）停牌股票最新价为0，从日K表中读取最新价失败！\n";
                                    print_r($val);
                                    $return['mark_value'] = -1;
                                    return $return;
                                }
                            }

                            $new_value[$val[0]]['stock_name'] = $val[1][0]; //$new_value['sz000404']['stock_name']=华意压缩
                            $new_value[$val[0]]['price'] = $new_price; //$new_value['sz000404']['price']=6.59
                            $new_value[$val[0]]['deal_time'] = $val[1][30] . " " . $val[1][31]; //$new_value['sz000404']['deal_time']=2014-11-11 12:12:12
                            $new_value[$val[0]]['is_sell'] = ($val[1][32] == '00' && $new_price > sprintf("%.2f", $val[1][2] * 0.9)) ? 1 : 0; // 是否可卖 (满足 非停牌 并且 非跌停)
                        } else {
                            echo date("Y-m-d H:i:s") . "(" . mb_convert_encoding($stocks[$key], "UTF-8", "GBK") . ")（" . $pln_id . "）行情串数据长度错误！\n";
                            $return['mark_value'] = -1;
                            return $return;
                        }
                    } else { //获取不到行情串（从日K表中取最近的价格）
                        echo date("Y-m-d H:i:s") . "(" . mb_convert_encoding($val[0], "UTF-8", "GBK") . ")（" . $pln_id . "）获取行情串失败，从日K表中读取最新价\n";
                        $_price = $this->getDailyK($val[0], date("Y-m-d"));
                        if (isset($_price[$val[0]])) {
                            $new_value[$val[0]]['stock_name'] = $val[0];
                            $new_value[$val[0]]['price'] = $_price[$val[0]];
                            $new_value[$val[0]]['deal_time'] = date("Y-m-d H:i:s");
                            $new_value[$val[0]]['is_sell'] = 0;
                        } else {
                            echo date("Y-m-d H:i:s") . "（" . $pln_id . "）获取行情串失败，从数据库中读取最价失败！\n";
                            print_r($val);
                            $return['mark_value'] = -1;
                            return $return;
                        }
                    }
                }

                //计算持仓市值
                foreach ($assets as $key => $asset_info) {
                    if (isset($new_value[$asset_info['symbol']])) {
                        $return['mark_value'] += $asset_info['amount'] * $new_value[$asset_info['symbol']]['price'];

                        $assets[$key]['stock_name'] = $new_value[$asset_info['symbol']]['stock_name'];
                        $assets[$key]['new_price'] = $new_value[$asset_info['symbol']]['price'];
                        $assets[$key]['deal_time'] = $new_value[$asset_info['symbol']]['deal_time'];
                        $assets[$key]['is_sell'] = $new_value[$asset_info['symbol']]['is_sell'];
                    } else {
                        echo date("Y-m-d H:i:s") . "（" . $pln_id . "）系统错误！\n";
                        $return['mark_value'] = -1;
                        return $return;
                    }
                }

                $return['assets'] = $assets;
            } else {//请求接口失败的操作
                echo date("Y-m-d H:i:s") . "（" . $pln_id . "）行情串接口调用失败！\n";
                $return['mark_value'] = -1;
            }
        }

        return $return;
    }

    /**
     * 撤销计划订单
     * @param $pln_id
     * @return mixed
     */
    public function RevokePlanOrder($pln_id) {
        $pln_id = intval($pln_id);
        $result = array(
            'counter' => 0,
            'revoke_sell' => array()//股票代码(key)=>数量(value)
        );
        $counter = 0;
        //当前订单列表
        $sql = "select id,pln_id,ind_id,symbol,type,order_id,order_price,deal_amount,order_amount,deal_time,reason,status,is_handled 
    			from " . $this->tableNameOrder() . " where pln_id=$pln_id and type in (1,2) and status=1";
        $orders = $this->getDBR()->createCommand($sql)->queryAll();

        //更新
        $this->getDBW()->createCommand()->update($this->tableNameOrder(), array(
            'status' => 4
            ), "pln_id=$pln_id and type in (1,2) and status=1");

        if (!empty($orders)) {
            foreach ($orders as $key => $val) {
                //记录返回的撤销卖出订单的股票信息
                if ($val['type'] == 2) {
                    if (isset($result['revoke_sell'][$val['symbol']])) {
                        $result['revoke_sell'][$val['symbol']] += $val['order_amount'];
                    } else {
                        $result['revoke_sell'][$val['symbol']] = $val['order_amount'];
                    }
                }

                $val['order_id'] = $val['id'];
                $val['type'] = $val['type'] == 1 ? 3 : 4;
                $val['c_time'] = date("Y-m-d H:i:s");
                $val['u_time'] = date("Y-m-d H:i:s");

                unset($val['id']);
                $counter += $this->getDBW()->createCommand()->insert($this->tableNameOrder(), $val);
            }
        }
        if ($counter == count($orders)) {
            $result['counter'] = 1;
        }
        return $result;
    }

    /**
     * 平仓
     * @param $pln_id
     * @return mixed
     */
    public function assetUnwinding($pln_id, $assets) {
        $pln_id = intval($pln_id);
        $counter = 0;
        foreach ($assets as $val) {
            $sql = "update " . $this->tableNameAsset() . " set amount=amount-" . $val['available_sell_amount'] . ",available_sell_amount=0,u_time='" . date("Y-m-d H:i:s") . "' 
    		        where pln_id=$pln_id and symbol='" . $val['symbol'] . "' and amount>0";
            $counter += $this->getDBW()->createCommand($sql)->execute();
        }
        return $counter;
    }

    /**
     * 保存交易动态(增加提醒)
     * @param $plan_info
     * @param $assets 持仓信息
     * @return int
     */
    public function savePlanTransaction($plan_info, $assets, $type) {
        $counter = 0;
        $asset_mark_value = 0;

        //先计算持仓市值
        foreach ($assets as $key => $val) {
            $asset_mark_value += $val['hold_avg_cost'] * $val['amount'];
        }
        //总资产
        $plan_mark_value = $asset_mark_value + $plan_info['available_value'] + $plan_info['warrant_value'];

        //操作原因
        $reason = "因计划止损，系统强制平仓";
        if ($type == 'expire' || $type == 'expire_freeze') {
            $reason = "因计划到期，系统强制平仓";
        }

        //可用资金(可用+冻结)
        $available_value = $plan_info['available_value'] + $plan_info['warrant_value'];

        foreach ($assets as $key => $val) {
            //只处理可卖的股票
            if ($val['is_sell'] == 1 && $val['available_sell_amount'] > 0) {
                //手续费
                $transaction_cost = $this->getTransactionCost($val['symbol'], $val['new_price'], $val['available_sell_amount']);
                //盈利
                $profit = ($val['new_price'] - $val['hold_avg_cost']) * $val['available_sell_amount'] - $transaction_cost;
                //交易动态
                $rows = $this->getDBW()->createCommand()->insert($this->tableNameTransactions(), array(
                    'pln_id' => $plan_info['pln_id'],
                    'symbol' => $val['symbol'],
                    'ind_id' => $plan_info['ind_id'],
                    'type' => 2,
                    'deal_price' => $val['new_price'],
                    'deal_amount' => $val['available_sell_amount'],
                    'hold_avg_cost' => $val['hold_avg_cost'],
                    'status' => 3,
                    'profit' => $profit,
                    'transaction_cost' => $transaction_cost,
                    'wgt_before' => $asset_mark_value / $plan_mark_value,
                    'wgt_after' => ($asset_mark_value - $val['hold_avg_cost'] * $val['available_sell_amount']) / ($plan_mark_value + $profit),
                    'reason' => $reason,
                    'c_time' => $val['deal_time'],
                    'u_time' => date("Y-m-d H:i:s")
                ));
                $counter += $rows;
                //持仓市值
                $asset_mark_value = $asset_mark_value - $val['hold_avg_cost'] * $val['available_sell_amount'];
                //总资产
                $plan_mark_value += $profit;

                //可用资金
                $available_value += ($val['new_price'] * $val['available_sell_amount'] - $transaction_cost);
                //插入对账单表
                $this->getDBW()->createCommand()->insert($this->tableNameStatement(), array(
                    'pln_id' => $plan_info['pln_id'],
                    'symbol' => $val['symbol'],
                    'deal_price' => $val['new_price'],
                    'statement_type' => 3,
                    'rest_fund' => $available_value,
                    'change_fund' => $val['new_price'] * $val['available_sell_amount'] - $transaction_cost,
                    'deal_time' => date("Y-m-d H:i:s"),
                    'stamp_tax' => $val['new_price'] * $val['available_sell_amount'] * 0.001,
                    'commission' => ($val['new_price'] * $val['available_sell_amount'] * self::TRANSACTION_COST_COMMISSION) < 5 ? 5 : ($val['new_price'] * $val['available_sell_amount'] * self::TRANSACTION_COST_COMMISSION),
                    'transfer_fee' => substr($val['symbol'], 0, 2) == 'sh' ? ceil($val['available_sell_amount'] / 1000) : 0,
                    'deal_amount' => $val['available_sell_amount'],
                    'remark' => $reason,
                    'c_time' => date("Y-m-d H:i:s"),
                    'u_time' => date("Y-m-d H:i:s")
                ));
            } elseif ($val['is_sell'] == 1 && $val['available_sell_amount'] <= 0) {
                $counter ++;
            }
        }
        return $counter;
    }

    /**
     * 更新计划信息（发送收益提醒）
     * @param $pln_id
     * @param $plan_info
     * @return mixed
     */
    public function updatePlanInfo($pln_id, $plan_name, $target_ror, $plan_info) {
        $counter = $this->getDBW()->createCommand()->update($this->tableName(), $plan_info, "pln_id=$pln_id");
        if ($counter > 0 && ($plan_info['status'] == self::PLAN_STATUS_SUCCESS || $plan_info['status'] == self::PLAN_STATUS_FAIL)) {
            //成功或失败计划发送收益提醒（观察和购买的用户）
            $sub_uids = $this->getSubPlanUids($pln_id); //购买用户
            //个性化 去掉关闭提醒的uid
            $sub_uids = MessageUserClose::model()->filterCloseUids($sub_uids, 1, 5, 1);

            $atten_uids = $this->getAttenPlanUids($pln_id); //观察用户
            //个性化 去掉关闭提醒的uid
            $atten_uids = MessageUserClose::model()->filterCloseUids($atten_uids, 1, 10, 1);

            $plan_result = $plan_info['status'] == self::PLAN_STATUS_SUCCESS ? "已达成" : "未达成";

            //计划其它信息
            $_plan_info = $this->getPlanInfoByFields($pln_id, 'p_uid,subscription_price,stop_loss');

            //理财师信息
            $planner_info = Planner::model()->getPlannerById(array(intval($_plan_info['p_uid'])));
            $planner_info = isset($planner_info[intval($_plan_info['p_uid'])]) ? $planner_info[intval($_plan_info['p_uid'])] : array();

            if (!empty($sub_uids)) {

                foreach ($sub_uids as $uid) {

                    $push_data = array(
                        "type" => "planStatus",
                        'uid' => $uid,
                        'plan_name' => $plan_name,
                        'pln_id' => $pln_id,
                        'plan_result' => $plan_result,
                        'curr_ror' => $plan_info['curr_ror'],
                        'target_ror' => $target_ror,
                        'status' => $plan_info['status'],
                        'min_profit' => $plan_info['min_profit'],
                        'p_uid' => $_plan_info['p_uid'],
                        'planner_name' => isset($planner_info['name']) ? $planner_info['name'] : '',
                        'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                        'company' => isset($planner_info['company']) ? $planner_info['company'] : ''
                    );
                    Yii::app()->redis_w->rPush("lcs_common_message_queue", json_encode($push_data));
                }
            }

            if (!empty($atten_uids)) {
                foreach ($atten_uids as $uid) {
                    $msg_data = array(
                        'uid' => $uid,
                        'u_type' => 1,
                        'type' => 10,
                        'relation_id' => $pln_id,
                        'content' => json_encode(array(
                            array('value' => '您观察的计划', 'class' => '', 'link' => ''),
                            array('value' => "《" . $plan_name . "》", 'class' => '', 'link' => "/plan/" . $pln_id),
                            array('value' => $plan_result . "目标,实际收益" . sprintf("%.2f", $plan_info['curr_ror'] * 100) . "%，目标收益" . sprintf("%.2f", $target_ror * 100) . "%。", 'class' => '', 'link' => '')
                            ), JSON_UNESCAPED_UNICODE),
                        'content_client' => json_encode(array(
                            'type' => 2,
                            'pln_id' => $pln_id,
                            'plan_name' => $plan_name,
                            'status' => $plan_info['status'],
                            'target_ror' => sprintf("%.2f", $target_ror * 100),
                            'curr_ror' => sprintf("%.2f", $plan_info['curr_ror'] * 100),
                            'min_profit' => $plan_info['min_profit'],
                            'stop_loss' => isset($_plan_info['stop_loss']) ? $_plan_info['stop_loss'] : 0,
                            'p_uid' => $_plan_info['p_uid'],
                            'planner_name' => isset($planner_info['name']) ? $planner_info['name'] : '',
                            'planner_image' => isset($planner_info['image']) ? $planner_info['image'] : '',
                            'company' => isset($planner_info['company']) ? $planner_info['company'] : ''
                            ), JSON_UNESCAPED_UNICODE),
                        'link_url' => "/plan/" . $pln_id,
                        'c_time' => date("Y-m-d H:i:s"),
                        'u_time' => date("Y-m-d H:i:s")
                    );
                    //提醒添加成功，加入redis队列
                    //if($this->getDBW()->createCommand()->insert("lcs_message",$msg_data)){
                    /* 暂时先不发
                      $msg_id = $this->getDBW()->getLastInsertID("lcs_message");
                      $msg_data['id'] = $msg_id;
                      $msg_data['content'] = json_decode($msg_data['content'],true);

                      Message::model()->addMessageQueue($msg_data);
                     */
                    //}
                }
            }
        }

        return $counter;
    }

    public function getPlanInfoByFields($pln_id, $fields) {
        return $this->getDBR()->createCommand("select $fields from " . $this->tableName() . " where pln_id=$pln_id")->queryRow();
    }

    /**
     * 统计计划赚钱数（理财师、平台总数）
     * @param $pln_id
     * @param $plan_id
     * @param $cur_ror
     */
    public function statPlanMakeMoneyTotal($pln_id, $p_uid, $init_value, $cur_ror) {
        $pln_id = intval($pln_id);
        $p_uid = $p_uid;
        $init_value = intval($init_value);
        $cur_ror = floatval($cur_ror);

        $sql = "select init_money from " . $this->tableNameSubscription() . " where status>0 and pln_id=$pln_id";

        $moneys = $this->getDBR()->createCommand($sql)->queryColumn();

        if (!empty($moneys)) {
            $total = 0;
            foreach ($moneys as $key => $val) {
                $total += ($val > 0 ? $val : $init_value);
            }

            $total = ceil($total * $cur_ror);
            if ($total > 0) {
                //理财师
                Yii::app()->redis_w->hIncrBy("lcs_planner_ext_info_" . $p_uid, "make_money", $total);
                //平台总数
                Yii::app()->redis_w->incrBy("lcs_plan_make_money_total", $total);
            }
        }
    }

    /**
     * 获取理财师创建的计划
     * @param $p_uid  理财师ID
     * @param array $status 状态
     */
    public function getPlanInfoByPlanner($p_uid, $fields = "pln_id,name,number,subscription_price,status", $status = array(2, 3), $if_tougu = 0) {
        if ($if_tougu == 1) {
            $sql = "select " . $fields . " from  " . $this->tableName() . "  where pln_id>28340 and p_uid=:p_uid and status in (" . implode(',', $status) . ") order by u_time desc;";
        } else {
            $sql = "select " . $fields . " from  " . $this->tableName() . "  where p_uid=:p_uid and status in (" . implode(',', $status) . ") order by u_time desc;";
        }
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(":p_uid", $p_uid, PDO::PARAM_INT);
        $data = $cmd->queryAll();
        return $data;
    }

    /**
     * 获取理财师的某个时间点所有已完成计划的总收益率,去掉投顾大赛计划
     * @param type $p_uid 理财师编号
     * @param type $day 时间点
     * @return type
     */
    public function getSumCurrRor($p_uid, $day) {
        $day = date("Y-m-d 23:59:59", strtotime($day));
        $sql = "select sum(curr_ror) from " . $this->tableName() . " where pln_id>28340 and p_uid=" . $p_uid . " and (status>=4 and real_end_time<='" . $day . "')";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        return $cmd->queryScalar();
    }

    /**
     * 获取理财师的某个时间点当前运行中的计划的总收益,去掉投顾大赛计划
     * 该时间点可能是当前时间:取lcs_plan_info中处于运行状态的计划的sum(curr_ror)
     * 可能是历史时间
     * @param type $p_uid
     * @param type $day
     */
    public function getCurrRor($p_uid, $day = "") {
        if ($day == "") { ///当前时间
            $sql = "select sum(curr_ror) from " . $this->tableName() . " where pln_id>28340 and p_uid=" . $p_uid . " and status=3";
            $cmd = Yii::app()->lcs_r->createCommand($sql);
            $data = $cmd->queryScalar();
            $data = $data != NULL ? $data : "0";
            return $data;
        } else {
            ///先找到该理财师在某个时间点的时候仍然运行的计划
            $full_day = date("Y-m-d 23:59:59", strtotime($day));
            $sql = "select pln_id from " . $this->tableName() . " where pln_id>28340 and p_uid=" . $p_uid . " and start_date<='" . $day . "' and ((real_end_time>'" . $full_day . "' and status>=4) or (end_date>'" . $day . "' and status=3))";
            $cmd = Yii::app()->lcs_r->createCommand($sql);
            $data = $cmd->queryColumn();
            if (count($data) > 0) {
                $sql = "select sum(total_profit) from " . $this->tableNameHistory() . " where pln_id in (" . implode(',', $data) . ")" . " and profit_date='" . $day . "'";
                $cmd = Yii::app()->lcs_r->createCommand($sql);
                $data = $cmd->queryScalar();
                return $data;
            } else {
                return 0;
            }
        }
    }

    /**
     * 获取某一个时间段的同期股指
     * @param type $start
     * @param type $end
     */
    public function getHs300FromDaily_k($start, $end) {
        if ($start <= $end) {
            $start = $this->getNearestDay($start);
            $end = $this->getNearestDay($end);
            $sql = "select open from " . $this->tableNameDaily() . " where day='" . $start . "' and symbol='sh000300'";
            $open_price = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
            $sql = "select close from " . $this->tableNameDaily() . " where day='" . $end . "' and symbol='sh000300'";
            $close_price = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
            $res = ($close_price - $open_price) / $open_price;
            return $res;
        } else {
            return 0;
        }
    }

    /**
     * 获取最近交易日
     * @param type $day
     */
    private function getNearestDay($day) {
        $sql="select max(day) as day from ".$this->tableNameDaily()." where symbol='sh000300' and day<='".$day."'";
        $cmd=Yii::app()->lcs_r->createCommand($sql);
        $nearest_day = $cmd->queryScalar();
        return $nearest_day;
    }

    /**
     * 更新数量
     * @param $pln_id
     * @param string $field
     * @param string $oper
     * @param int $num
     * @return mixed    
     */
    public function updateNumber($pln_id, $field = 'subscription_count', $oper = "add", $num = 1) {
        $pln_id = intval($pln_id);
        $num = intval($num);
        $sql = "update " . $this->tableName() . " set $field=" . ($oper == 'add' ? "$field+$num" : "$field-$num") . " where pln_id=$pln_id";
        return $this->getDBW()->createCommand($sql)->execute();
    }
    /**
     * 获取计划历史收益
     * @param type $pln_ids
     * @param type $date
     * @return boolean
     */
    public function getPlanProfitHistory($pln_ids,$date){
        if(empty($date) || empty($pln_ids) || !is_array($pln_ids)){
            return FALSE;
        }        
        $result = array();
        $sql = "select pln_id,total_profit from {$this->tableNameHistory()} where pln_id in (".implode(",",$pln_ids).") and profit_date ='{$date}'";        
        $list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if(empty($list)){
            return $result;
        }
        foreach ($list as $item){
            $result[$item['pln_id']] = $item;
        }
        return $result;
    }
    
    
    /**
     * 获取平台体验卡pln_id 
     */
    public function getPlatformTykPlnIds(){
        $db_r = Yii::app()->lcs_r;
        $sql = "select pln_id from {$this->tableName()} where status = 3 and curr_ror >= 0.05 and (TO_DAYS(end_date)-TO_DAYS(STR_TO_DATE(now(),'%Y-%m-%d'))) >=7 and (target_ror-curr_ror) >= 0.02";
        return $db_r->createCommand($sql)->queryColumn();
    }

    /**
     * 通过计划ID获取计划详情
     * @param $pln_id string
     * @return array
     * */
    public function getPlanDetail($pln_id)
    {
        if (empty($pln_id))
            return array();

        $sql = "select pln_id, p_uid, name, number, weight, invest_days, summary, image, ind_id, curr_draft_step, subscription_price, target_ror, hs300, performance_promise, subscription_count, reader_count, universe_type,
				stop_loss, time_left, curr_ror, init_value, operate_time, user_values, available_value, warrant_value, market_value, start_date, end_date, status, max_follower_amt, min_follower_amt,audit_reason,privilege_price,real_end_time,opt_style,is_trans_unlock from  " . $this->tableName() . "  where pln_id=$pln_id";
        $arr = Yii::app()->lcs_r->createCommand($sql)->queryRow();
        return $arr;
    }
}
