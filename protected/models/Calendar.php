<?php
class Calendar extends CActiveRecord{
	
	public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    
    //计划表
    public function tableName(){
        return TABLE_PREFIX .'calendar';
    }
    
    //数据库 读
    private function getDBR(){
        return Yii::app()->lcs_r;

    }
    
    public function isTradeDay($date=''){
    	
    	if(empty($date))
    	{
    		$date = date('Y-m-d');
    	}
    	
    	$sql = "select cal_date from ".$this->tableName()." where cal_date='$date'";
    	return   $this->getDBR()->createCommand($sql)->queryRow();
    }
    
    /*
	 * 获取上一个交易日
	* Author:yangmao
	* Date:2014-11-22
	* */
	public function getLastMarketDate($day)
	{

		return  $this->getDBR()->createCommand("select cal_date as day from lcs_calendar where cal_date<'".$day."' order by cal_date desc limit 1")->queryscalar();
	}

    //获取下一个交易日
    public function getNextMarketDate($day){
        return  $this->getDBR()->createCommand("select cal_date as day from lcs_calendar where cal_date>'".$day."' order by cal_date asc limit 1")->queryscalar();
    }
    /**
     * 统计一段时间内的交易天数
     * @param type $start
     * @param type $end
     */
    public function getTotalTradeDay($start,$end){
        $sql = "select count(*) as total from {$this->tableName()} where cal_date>='{$start}' and cal_date<='{$end}'";        
        return $this->getDBR()->createCommand($sql)->queryscalar();        
    }
    /**
     * 根据日期获取最近5个交易日
     * @$Date string
     */
    public function getNearCalendar($date,$is_today=0){
        $where = $is_today == 0 ? " cal_date<='$date' ":" cal_date<'$date' ";
        $sql = "select cal_date from ".$this->tableName()."  where $where order by cal_date desc limit 0,5";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $arr =  $cmd->queryAll();
        $data=array();
        if(!empty($arr)){
            foreach ($arr as $key =>$v){
                if($key==0){
                    $data['end_time']=$v['cal_date'];
                }
                $data['start_time']=$v['cal_date'];
            }
        }
        return $data;
    }
}