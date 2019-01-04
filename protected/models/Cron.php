<?php

class Cron extends CActiveRecord
{

    private static $_model=null;

    /**
     * 初始化方法
     * @param system $className
     * @return multitype:|unknown
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

	/**
	 * 任务表
	 * @return string
	 */
	public function tableName()
	{
		return "lcs_cron";
	}

    /**
     * 任务日志表
     * @return string
     */
    public function tableNameLog()
    {
        return "lcs_cron_log";
    }

    /**
     * 清理定时任务日志
     * @param $cron_no
     * @param $level
     * @param $start_time
     * @param $end_time
     */
    public function removeCronLog($end_time,$cron_no='',$level=''){
        $cdn='';
        if(!empty($cron_no)){
            $cdn .=' and cron_no=:cron_no';
        }
        if(!empty($level)){
            $cdn .=' and level=:level';
        }
        $sql = 'delete from '.$this->tableNameLog().' where c_time<:end_time'.$cdn.';';
        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $cmd->bindParam(':end_time',$end_time, PDO::PARAM_STR);
        if(!empty($cron_no)){
            $cmd->bindParam(':cron_no',$cron_no, PDO::PARAM_INT);
        }
        if(!empty($level)){
            $cmd->bindParam(':level',$level, PDO::PARAM_STR);
        }
        return $cmd->execute();

    }

    /**
     * 保存日志
     * @param $cron_no
     * @param $level
     * @param $msg
     * @return int
     */
    public function saveCronLog($cron_no,$level,$msg){
        $data['cron_no']=$cron_no;
        $data['level']=$level;
        $data['message']='[IP:'.CommonUtils::getServerIp().']'.$msg;
        $data['c_time']=date(DATE_RFC3339,time());
        $index = "cronlog".date("Ym");
        CommonUtils::esdata($index,"cronlog",json_encode($data,true));
        /*$res = Yii::app()->lcs_w->createCommand()->insert($this->tableNameLog(), $data);
        if($res==1){
            $id = Yii::app()->lcs_w->getLastInsertID();
            return empty($id) ? 1 : $id;
        }else{
            return $res;
        }*/
    }

    /**
     * 根据任务级别查询日志
     * @param $level
     * @param $start_time
     * @param $end_time
     */
    public function getCronLogByLevel($level, $start_time, $end_time=''){
        $cdn = '';
        if(!empty($end_time)){
            $cdn .= ' and c_time<:end_time';
        }

        $sql = 'select id, cron_no, level, message, c_time from '.$this->tableNameLog().' where c_time>=:start_time ';
        $sql .= 'and level=:level'.$cdn.';';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':start_time',$start_time, PDO::PARAM_STR);
        $cmd->bindParam(':level',$level, PDO::PARAM_STR);
        if(!empty($end_time)){
            $cmd->bindParam(':end_time',$end_time, PDO::PARAM_STR);
        }
        return $cmd->queryAll();

    }

	/**
	 * 修改记录
	 * @param unknown $columns
	 * @param string $conditions
	 * @param unknown $params
	 */
	public function updateCron($columns, $conditions='', $params=array()){
		return Yii::app()->lcs_w->createCommand()->update($this->tableName(),$columns,$conditions,$params);
		
	}


    /**
     * 获取计划信息
     * @param $id
     * @param null $fields
     * @return mixed
     */
    public function getCronById($id, $fields=null){
        $select='id,cron_no,cron_name,category,env,notice,status,start_time,end_time,space_time,notice_time,c_time,u_time';
        if(!empty($fields)){
            $select = is_array($fields)?implode(',',$fields): $fields;
        }
        $sql = 'select '.$select.' from '.$this->tableName().' where id=:id;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':id',$id, PDO::PARAM_INT);

        return $cmd->queryRow();
    }


    public function getCronList($fields=null){
        $select='id,cron_no,cron_name,category,env,notice,status,start_time,end_time,space_time,notice_time,c_time,u_time';
        if(!empty($fields)){
            $select = is_array($fields)?implode(',',$fields): $fields;
        }
        $sql = 'select '.$select.' from '.$this->tableName().' where status=0;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        return $cmd->queryAll();
    }


}
