<?php

/**
 * 淘股相关数据库操作
 */
class Taogu extends CActiveRecord {

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 淘股股票
     */
    public function tableName(){
        return TABLE_PREFIX.'taogu_symbol';
    }

    /**
     * 淘股记录
     * @return string
     */
    public function recordTableName(){
        return TABLE_PREFIX . 'taogu_record';
    }

    /**
     * 股票当天收盘价
     * @return string
     */
    public function closeTableName(){
        return TABLE_PREFIX . 'symbol_close';
    }
    public function tableNameService()
    {
        return TABLE_PREFIX . 'service';
    }
    public function tableNameProduct()
    {
        return TABLE_PREFIX . 'service_product';
    }
    public function tableNameGroup()
    {
        return TABLE_PREFIX . 'taogu_group';
    }
    public function tableNameSub(){
        return TABLE_PREFIX . 'service_buy';
    }
    public function tableNameList(){
        return TABLE_PREFIX . 'taogu_list';
    }


    /**
     * 更新调入价
     * @param $arr
     * @return mixed
     */
    public function updatePrice($arr){
        if(empty($arr)){
            return;
        }
        $now = date('Y-m-d H:i:s');
        $day = substr($now,0,10);
        $sql = "update ".$this->tableName()." set `deal_price`=:deal_price,u_time='{$now}',stat_day='{$day}' where id=".$arr['id']." and stat_day<'{$day}'";
        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $cmd->bindParam(':deal_price', $arr['new_price'], PDO::PARAM_STR);

        $res = $cmd->execute();
        return $res;
    }

    /**
     * 更新股票状态
     * @param $data
     * @return mixed
     */
    public function updateStatus($data){
        if(empty($data)){
            return;
        }
        $connection = Yii::app()->lcs_w;
        $transaction = $connection->beginTransaction();
        try{
            $now = date('Y-m-d H:i:s');
            if($data['status'] == 0){
                $sql = "update ".$this->tableName()." set `status`=:status,`deal_price`=:deal_price,u_time='{$now}' where id=".$data['id'];
                $cmd = $connection->createCommand($sql);
                $cmd->bindParam(':status', $data['status'], PDO::PARAM_INT);
                $cmd->bindParam(':deal_price', $data['deal_price'], PDO::PARAM_STR);
            }else{
                $sql ="delete from ".$this->tableName()." where id=".$data['id'];
                $cmd = $connection->createCommand($sql);
            }
            $cmd->execute();
            $status = $data['status'] == 0 ? 2 : 3;
            $sql_insert = "insert into ".$this->recordTableName()." (p_uid,symbol,status,deal_price,rate,summary,c_time,u_time) values (:p_uid,:symbol,:status,:deal_price,:rate,:summary,'{$now}','{$now}')";
            $new_cmd = $connection->createCommand($sql_insert);
            $new_cmd->bindParam(':p_uid',$data['p_uid'],PDO::PARAM_INT);
            $new_cmd->bindParam(':symbol',$data['symbol'],PDO::PARAM_STR);
            $new_cmd->bindParam(':status',$status,PDO::PARAM_INT);
            $new_cmd->bindParam(':deal_price',$data['deal_price'],PDO::PARAM_STR);
            $new_cmd->bindParam(':rate',$data['rate'],PDO::PARAM_STR);
            $new_cmd->bindParam(':summary',$data['summary'],PDO::PARAM_STR);
            $new_cmd->execute();

            $transaction->commit();
            return true;
        }catch (Exception $e){
            $transaction->rollBack();
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * 根据股票的持仓状态和股票代码查询股票相关信息
     * @param $status
     * @param $field
     * @param null $in
     * @return mixed
     */
    public function getSymbolByStatus($status,$field,$in=null){
        if($status == 0){
            $str = ' where status=0';
        }else if($status == 1){
            $str = ' where status=1';
        }else if($status == 2){
            $str = ' where status=2';
        }else if($status == 3){
            $str = ' where (status=0 or status=2)';
        }else if($status == 4){
            $str = ' where (status=1 or status=2)';
        }

        $sql = "select ".implode(',',$field)." from ".$this->tableName().$str;
        if(!empty($in)){
            $sql .= " and symbol in (".implode(',',$in).")";
        }
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();

        return $res;
    }

    /**
     * 撤销委托
     * @param $order
     * @return bool
     */
    public function cancelOrder($order){
        $connection = Yii::app()->lcs_w;
        $transaction = $connection->beginTransaction();
        try {
            foreach ($order as $d){
                if($d['status'] == 2){
                    $str = '委托调出';
                    $sql = 'update '.$this->tableName()." set status=0 where id=".$d['id'];
                }else {
                    $str = '委托调入';
                    $sql = 'delete from '.$this->tableName()." where id=".$d['id'];
                }
                $connection->createCommand($sql)->execute();
                $summary[] = array($d['symbol'],$str);
            }
            $now = date('Y-m-d H:i:s');
            $summary_str = json_encode($summary);
            $sql_insert = "insert into ".$this->recordTableName()." (p_uid,status,summary,c_time,u_time) values (:p_uid,5,:summary_str,'{$now}','{$now}')";
            $cmd = $connection->createCommand($sql_insert);
            $cmd->bindParam(':p_uid', $order[0]['p_uid'], PDO::PARAM_INT);
            $cmd->bindParam(':summary_str', $summary_str, PDO::PARAM_STR);
            $cmd->execute();
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * 获取根据持仓状态和股票代码获取理财师最近的一次操作记录
     * @param $p_uid
     * @param $symbol
     * @param $status
     * @return mixed
     */
    public function getSummary($p_uid,$symbol,$status){
        $sql = "select summary from ".$this->recordTableName()." where p_uid=".$p_uid." and status=".$status." and symbol='".$symbol."' order by c_time desc limit 1";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryScalar();

        return $res;
    }

    /**
     * 根据参数判断持仓的where条件
     * @param $status
     * @return string
     */
    public function getWhere($status){
        if($status == 0){
            $str = ' where status=0';
        }else if($status == 1){
            $str = ' where status=1';
        }else if($status == 2){
            $str = ' where status=2';
        }else if($status == 3){
            $str = ' where (status=0 or status=2)';
        }else if($status == 4){
            $str = ' where (status=1 or status=2)';
        }

        return $str;
    }

    /**
     * 根据持仓状态，分页获取股票列表
     * @param $status
     * @param $field
     * @param $p
     * @param $num
     * @return mixed
     */
    public function getSymbolList($status,$field,$p,$num){
        $str = self::getWhere($status);
        $sql = "select ".implode(',',$field)." from ".$this->TableName().$str;

        if($num > 0){
            $offset = ($p-1)*$num;
            $limit = " order by id desc limit {$offset},{$num}";
        }
        $sql .= $limit;

        $cmd = Yii::app()->lcs_r->createCommand($sql);

        if(count($field) > 1){
            return $cmd->queryAll();
        }else{
            return $cmd->queryColumn();
        }
    }

    /**
     * 批量更新股票收盘价
     * @param $symbol_info
     */
    public function updateSymblClose($symbol_info){
        if(empty($symbol_info)){
            return;
        }
        $sql = "replace into ".$this->closeTableName()." (symbol,close_price,c_time,u_time) values ".implode(',',$symbol_info);
        Yii::app()->lcs_w->createCommand($sql)->execute();
    }

    /**
     * 根据股票代码获取收盘价
     * @param $symbol
     */
    public function getClosePrice($symbol){
        if(empty($symbol)){
            return;
        }
        $symbol = (array)$symbol;
        $sql = "select symbol,close_price from ".$this->closeTableName()." where symbol in (".implode(',',$symbol).")";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();

        if(!empty($data)){
            foreach ($data as $item){
                $symbol_info[$item['symbol']] = $item['close_price'];
            }
            return $symbol_info;
        }else{
            return;
        }
    }
    /**
     * 淘股列表
     */
    public function getTaoGuList($page=1,$num=200){
        if ($page <1) {
            $page = 1;
        }
        if ($num < 1){
            $num = 200;
        }
        $db_r = Yii::app()->lcs_standby_r;
        $cnt_sql = "SELECT COUNT(r.id) FROM ".$this->tableNameProduct()." as r left join ".$this->tableNameService()." as s on r.service_id=s.id left join lcs_planner as p on s.p_uid=p.s_uid WHERE r.sp_type=2 and r.audit_status=2 ";
        $cmd_count = $db_r->createCommand($cnt_sql);
        $total = $cmd_count->queryScalar();
        //当前页是否超过总页数   超过后加载最后一页
        $pages = floor(($total+$num-1)/$num);
        $pages = $pages < 1? 1 : $pages;
        $page = $page > $pages ? $pages : $page;
        $offset = intval(($page-1) * $num);
        $limit = intval($num);
        $data = NULL;
        $data['pages']=ceil($total / $num);
        if($offset < $total){
            $sql_data = "SELECT s.id,s.p_uid,p.name,r.u_time,s.service_status,r.sp_status FROM ".$this->tableNameProduct()." as r left join ".$this->tableNameService()." as s on r.service_id=s.id left join lcs_planner as p on s.p_uid=p.s_uid WHERE r.sp_type=2 and r.audit_status=2 ";
            $cmd = $db_r->createCommand($sql_data);
            $cmd->bindParam(':offset',$offset,PDO::PARAM_INT);
            $cmd->bindParam(':limit',$limit,PDO::PARAM_INT);
            $res = $cmd->queryAll();
            if($res){
                $data['data']=$res;
            }
        }
        return $data;
    }
    /**
    * 理财师淘股分组数
    * @param $p_uid
    */
    public function getGroupNum($p_uid){
        if(empty($p_uid)){
            return 0;
        }
        $db_r = Yii::app()->lcs_standby_r;
        $sql='select count(id) from '.$this->tableNameGroup().' where p_uid='.$p_uid;
        $cmd_count = $db_r->createCommand($sql);
        $total = $cmd_count->queryScalar();
        return $total;
    }

    /**
     * 最近5交易日调仓次数
     * @param $p_uid
     * @param $trade_day 交易日日期
     * @param $is_all
     */
    public function getSiloNum($p_uid,$trade_day,$is_all=0){
        if(empty($p_uid)){
            return 0;
        }
        $db_r = Yii::app()->lcs_standby_r;
        if($is_all==1){
            $sql='select count(id) from '.$this->recordTableName().' where p_uid='.$p_uid;
        }else{
            if(empty($trade_day) || empty($trade_day['start_time'])|| empty($trade_day['end_time']) ){
                return 0;
            }
            $end_time=$trade_day['end_time'].' 23:59:59';
            $sql='select count(id) from '.$this->recordTableName().' where p_uid='.$p_uid.' and c_time>="'.$trade_day['start_time'].'" and c_time <="'.$end_time.'"';
        }
        $cmd_count = $db_r->createCommand($sql);
        $total = $cmd_count->queryScalar();
        return $total;
    }
    /**
     * 调入股票数
     * @param $p_uid
     */
    public function getSymbolNum($p_uid){
        if(empty($p_uid)){
            return 0;
        }
        $db_r = Yii::app()->lcs_standby_r;
        $sql='select count(DISTINCT symbol) from '.$this->recordTableName().' where status=2 and p_uid='.$p_uid;
        $cmd_count = $db_r->createCommand($sql);
        $total = $cmd_count->queryScalar();
        return $total;
    }
    /**
     * 最近一次操作记录
     * @param $p_uid
     */
    public function getNewRecord($p_uid){
        if(empty($p_uid)){
            return false;
        }
        $db_r = Yii::app()->lcs_standby_r;
        $sql='select id,c_time,summary,symbol from '.$this->recordTableName().' where p_uid='.$p_uid.' order by c_time desc limit 0,1';
        $cmd_count = $db_r->createCommand($sql);
        $data = $cmd_count->queryRow();
        return $data;
    }

    /**
     * 历史最高调出累计收益
     * @param $p_uid
     */
    public function getMaxIncome($p_uid){
        if(empty($p_uid)){
            return false;
        }
        $db_r = Yii::app()->lcs_standby_r;
        $sql='select id,c_time,summary,symbol,rate from '.$this->recordTableName().' where status=3 and p_uid='.$p_uid.' order by rate desc limit 0,1';
        $cmd_count = $db_r->createCommand($sql);
        $data = $cmd_count->queryRow();
        return $data;
    }
    /**
     * 当前持仓数
     * @param $p_uid
     */
    public function getCurHoldNum($p_uid){
        if(empty($p_uid)){
            return false;
        }
        $db_r = Yii::app()->lcs_standby_r;
        $sql='select count(id) from '.$this->tableName().' where p_uid='.$p_uid.' and status in(0,2)';
        $cmd_count = $db_r->createCommand($sql);
        $data = $cmd_count->queryScalar();
        return $data;
    }
    /**
     * 订阅用户数
     * @param $p_uid
     */
    public function getUserNum($service_id){
        if(empty($service_id)){
            return false;
        }
        $db_r = Yii::app()->lcs_standby_r;
        $time=date('Y-m-d H:i:s');
        $sql='select count(DISTINCT uid) from '.$this->tableNameSub().' where service_id='.$service_id.' and end_time >="'.$time.'"';
        $cmd_count = $db_r->createCommand($sql);
        $data = $cmd_count->queryScalar();
        return $data;
    }
    /**
     * 保存淘股统计信息
     * @param $p_uid
     */
    public function saveTaoGuList($p_uid,$params){
        if(empty($p_uid) || empty($params)){
            return false;
        }
        $db_r = Yii::app()->lcs_standby_r;
        $db_w = Yii::app()->lcs_w;
        $sql='select id,p_uid from '.$this->tableNameList().' where p_uid='.$p_uid;
        $cmd_count = $db_r->createCommand($sql);
        $res = $cmd_count->queryRow();
        $params['u_time']=date('Y-m-d H:i:s');
        if($res){
            $result = $db_w->createCommand()->update($this->tableNameList(), $params, 'p_uid=:p_uid', array(':p_uid'=>$p_uid));
        }else{
            $params['c_time']=date('Y-m-d H:i:s');
            $result = $db_w->createCommand()->insert($this->tableNameList(), $params);
        }

        return $result>=0?true:false;
    }
    /**
     * 当前累计收益
     * @param $p_uid
     */
    public function getCurIncome($p_uid){
        if(empty($p_uid)){
            return 0;
        }
        $db_r = Yii::app()->lcs_standby_r;
        $sql='select id,p_uid,symbol,deal_price from '.$this->tableName().' where p_uid='.$p_uid.' and status in(0,2)';
        $cmd_count = $db_r->createCommand($sql);
        $data = $cmd_count->queryAll();
        $rate=0;
        if(!empty($data)){
            foreach ($data as $key => &$v) {
                $symbolInfo=TaoguService::getNewSymbolPrice($v['symbol']);
                $curr_price=isset($symbolInfo['curr_price'])?$symbolInfo['curr_price']:0;
                $v['rate']=0;
                if($v['deal_price']>0){
                    $v['rate']=round(($curr_price-$v['deal_price'])/$v['deal_price'],4);
                }
            }
            $data=CommonUtils::arrayMultiSort($data, 'rate','desc');
            $rate=$data[0]['rate'];
        }
        return $rate;
    }
    /**
     * 股票持仓天数
     * @param $p_uid
     */
    public function getSymbolDays($p_uid){
        if(empty($p_uid)){
            return 0;
        }
        $db_r = Yii::app()->lcs_standby_r;
        $sql='select id,p_uid,symbol,c_time from '.$this->recordTableName().' where status=2 and p_uid='.$p_uid;
        $cmd_count = $db_r->createCommand($sql);
        $total = $cmd_count->queryAll();
        $day=0;
        if(!empty($total)){
            foreach($total as &$v){
                $sql='select id,p_uid,symbol,c_time from '.$this->recordTableName().' where status=4 and p_uid='.$p_uid.' and c_time>"'.$v['c_time'].'" order by c_time desc limit 0,1';
                $cmd_count = $db_r->createCommand($sql);
                $record = $cmd_count->queryRow();
                if(!empty($record)){
                    $days=ceil((strtotime($record['c_time'])-strtotime($v['c_time']))/86400);
                }else{
                    $days=ceil((time()-strtotime($v['c_time']))/86400);
                }
                $day= $day+$days;
            }
        }
        return $day;
    }

    //更新正在持仓的收益率
    public function updateRecordRate($p_uid,$symbol,$rate){
        $sql = "select id  from " . $this->recordTableName() . " where p_uid='$p_uid' and symbol='$symbol' and status=2 order by c_time desc limit 1";
        try{
            $id_info = Yii::app()->lcs_r->createCommand($sql)->queryRow();
            if($id_info){
                $id = $id_info['id'];
                $sql_up = " update " . $this->recordTableName() . " set rate='$rate' where p_uid='$p_uid' and symbol='$symbol' and status=2 and id='$id'";
                Yii::app()->lcs_w->createCommand($sql_up)->execute();
            }
        }catch (Exception $e){
            Common::model()->saveLog("更新正在持仓的收益率失败".$e->getMessage(),"error","taogu_now_rate");
        }
    }

    //获取正在持仓的股票
    public function getSymbol($status=0){
        $sql = " select p_uid,symbol,deal_price from ". $this->tableName(). " where status='$status'";
        return Yii::app()->lcs_r->createCommand($sql)->queryAll();
    }

}
