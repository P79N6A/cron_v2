<?php
/**
 * Created by PhpStorm.
 * User: PanChaoYi
 * Date: 2017/9/6
 * Time: 16:31
 */

class Stock extends CActiveRecord{
    public static function model($className = __CLASS__){
        return parent::model($className = __CLASS__);
    }

    public function baseTableName(){
        return MEM_PRE_KEY.'stock_base';
    }

    public function groupTableName(){
        return MEM_PRE_KEY.'user_stock_group';
    }

    public function tableName(){
        return MEM_PRE_KEY.'user_stock';
    }

    public function tableStockPlate(){
        return MEM_PRE_KEY.'stock_plate';
    }

    public function tableStockPlateSymbol(){
        return MEM_PRE_KEY.'stock_plate_symbol';
    }

    public function tableStockPlateRelation(){
        return MEM_PRE_KEY.'stock_plate_relation';
    }

    public function pushUserStock(){
        $key = MEM_PRE_KEY.'push_user_stock';
        $date = Yii::app()->redis_r->get($key);
        if(empty($date)){
            $date = date('Y-m-d H:i:s');
            Yii::app()->redis_w->set($key,$date);
            $where = ".modify_time <= '".$date."' ";
        }else{
            $where = ".modify_time >= '".$date."' ";
        }
        $sql = "SELECT M2.uid,GROUP_CONCAT(DISTINCT M3.symbol) AS symbols FROM ";
        $sql .= " (SELECT M.uid,M1.id FROM ".$this->groupTableName()." M INNER JOIN ".$this->groupTableName()." M1 ON M.uid=M1.uid WHERE M.uid<>0 AND M".$where.")M2  ";
        $sql .= " LEFT JOIN ".$this->tableName()." M3 ON M3.gid=M2.id  GROUP BY M2.uid";
        $user_info = Yii::app()->lcs_r->createcommand($sql)->queryAll();

        $user_ei = [];
        foreach ($user_info as $key=>$val){
            $symbol_arr = explode(',',$val['symbols']);
            $ei_arr = $this->getSymbolName($symbol_arr);
            $user_ei[intval($val['uid'])] = $ei_arr;
        }

        return $user_ei;
    }

    /*
     * 根据股票代码获取内码
     * @param $symbols
     * @return array
     */
    private function getSymbolName($symbols)
    {
        $R = [];
        if (empty($symbols))
            return $R;

        //从缓存中查找名称
        $un_find = [];

        foreach ($symbols as $item=>$val) {
            $name_json = Yii::app()->redis_r->get(MEM_PRE_KEY . 'cache_stock_name_' . $val);
            if ($name_json == '') {
                $un_find[] = $val;
                continue;
            }
            $name_data = json_decode($name_json, true);
            if ($name_json && isset($name_data[0])) {
                $R[] = intval($name_data[0]['Ei']);
            } else
                $un_find[] = $val;
        }

        if (empty($un_find))
            return $R;


        //从数据库中查找名称
        $sql_name = "select `Ei`,`code`,`symbol`,`name`,`pinyin` from " . $this->baseTableName() . " where `symbol` in ('" . implode("','", $un_find) . "')";
        $names = Yii::app()->lcs_r->createCommand($sql_name)->queryAll();

        if (empty($names))
            return $R;
        foreach ($names as $v=>$val) {
            $R[] = intval($val['Ei']);
            //Yii::app()->redis_w->hset(MEM_PRE_KEY . 'cache_stock_name', $v['symbol'], json_encode($val));
        }
        return $R;
    }

    /**
     * 保存股票板块
     * @param   string  $type   板块类型
     * @param   array   $info   股票板块数组数据
     */
    public function SaveStockPlate($type,$info){
        try{
            $sql = "insert into ".$this->tableStockPlate()." (type,ei,scode,sname,status,c_time,u_time) values(:type,:ei,:scode,:sname,:status,:c_time,:u_time)";
            $cmd = Yii::app()->lcs_w->createCommand($sql);
            $now = date("Y-m-d H:i:s");
            $status = 1;
            $cmd->bindParam(":type",$type,PDO::PARAM_STR);
            $cmd->bindParam(":ei",$info['Ei'],PDO::PARAM_STR);
            $cmd->bindParam(":scode",$info['SCode'],PDO::PARAM_STR);
            $cmd->bindParam(":sname",$info['SName'],PDO::PARAM_STR);
            $cmd->bindParam(":status",$status,PDO::PARAM_STR);
            $cmd->bindParam(":c_time",$now,PDO::PARAM_STR);
            $cmd->bindParam(":u_time",$now,PDO::PARAM_STR);
            $cmd->execute();
        }catch(Exception $e){
            var_dump($e->getMessage());
        }
    }

    /**
      * 保存板块相关股票
      */
    public function SaveStockSymbol($pei,$info){
        try{
            $sql = "insert into ".$this->tableStockPlateSymbol()." (p_ei,ei,scode,sname,status,c_time,u_time) values(:p_ei,:ei,:scode,:sname,:status,:c_time,:u_time)";
            $cmd = Yii::app()->lcs_w->createCommand($sql);
            $now = date("Y-m-d H:i:s");
            $status = 1;
            $cmd->bindParam(":p_ei",$pei,PDO::PARAM_STR);
            $cmd->bindParam(":ei",$info['Ei'],PDO::PARAM_STR);
            $cmd->bindParam(":scode",$info['SCode'],PDO::PARAM_STR);
            $cmd->bindParam(":sname",$info['SName'],PDO::PARAM_STR);
            $cmd->bindParam(":status",$status,PDO::PARAM_STR);
            $cmd->bindParam(":c_time",$now,PDO::PARAM_STR);
            $cmd->bindParam(":u_time",$now,PDO::PARAM_STR);
            $cmd->execute();
        }catch(Exception $e){
            var_dump($e->getMessage());
        }
    }

    /**
      * 删除股票板块相关数据
      */
    public function DeleteStockPlate(){
        $sql = "delete from ".$this->tableStockPlate()." where status=0";
        Yii::app()->lcs_w->createCommand($sql)->execute();

        $sql = "delete from ".$this->tableStockPlateSymbol()." where status=0;";
        Yii::app()->lcs_w->createCommand($sql)->execute();

        $sql = "update ".$this->tableStockPlate()." set status=0";
        Yii::app()->lcs_w->createCommand($sql)->execute();

        $sql = "update ".$this->tableStockPlateSymbol()." set status=0";
        Yii::app()->lcs_w->createCommand($sql)->execute();
    }

    /**
     * 分页获取板块数据
     */
    public function getStockPlateByPage($start,$limit){
        $sql = "select id,type,ei,scode,sname,c_time,u_time from ".$this->tableStockPlate()." where id>=:id and status=1 limit :limit";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(":id",$start,PDO::PARAM_STR);
        $cmd->bindParam(":limit",$limit,PDO::PARAM_INT);
        $data = $cmd->queryAll();
        return $data;
    }

    /**
      * 添加板块相关数据
      * @param  int $plate_ei   板块内码
      * @param  int $plate_scode    板块代码
      * @param  int $type   相关类型１热点
      * @param  int $relation_id 相关id
      */
    public function addStockPlateRelation($plate_ei,$plate_scode,$type,$relation_id){
        $sql = "insert into ".$this->tableStockPlateRelation()." (ei,scode,type,relation_id,c_time,u_time) values(:ei,:scode,:type,:relation_id,:c_time,:u_time)";
        $now = date("Y-m-d H:i:s");
        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $cmd->bindParam(":ei",$plate_ei,PDO::PARAM_STR);
        $cmd->bindParam(":scode",$plate_scode,PDO::PARAM_STR);
        $cmd->bindParam(":type",$type,PDO::PARAM_STR);
        $cmd->bindParam(":relation_id",$relation_id,PDO::PARAM_STR);
        $cmd->bindParam(":c_time",$now,PDO::PARAM_STR);
        $cmd->bindParam(":u_time",$now,PDO::PARAM_STR);
        $cmd->execute();
    }

    /**
     * 获取股票板块
     */
    public function getAllStockPlate(){
        $sql = "select type,ei,scode,sname from ".$this->tableStockPlate();
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $result = array();
        if($data){
            foreach($data as $item){
                $result[$item['sname']] = $item;
            }
        }
        return $result;
    }

    public function getStockName($symbol){
        $sql = "select name,code,pinyin from ".$this->baseTableName()." where symbol='{$symbol}'";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryRow();

        return $data;
    }

    public function getStockSymbols($symbol){
        $sql = "select name,symbol,pinyin from ".$this->baseTableName()." where code in (".implode(',',$symbol).")";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();

        return $data;
    }
}
