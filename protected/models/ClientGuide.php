<?php
/**
 * @description:    客户端引导数据对象
 * @author:         shixi_danxian
 * @date:           2016/4/5
 */

class ClientGuide extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return TABLE_PREFIX.'client_guide';
    }

    public function tableNameReadLog()
    {
        return TABLE_PREFIX.'client_guide_read_log';
    }

    /**
     * 获取已经过期的、未删除、周期未完成的所有引导数据
     * @return mixed
     */
    public function getGuideOutOfDate()
    {
        $db_r   = Yii::app()->lcs_r;
        $sql    = "select id,s_time,e_time from ".$this->tableName()." where status=0 and cycle_total>1 and cycle_current<cycle_total and e_time<:now";
        $cmd    = $db_r->createCommand($sql);
        $now    = date('Y-m-d H:i:s');
        $cmd->bindParam(':now', $now);
        $result = $cmd->queryAll();
        return $result;
    }

    /**
     * 更新引导数据
     * @param $id
     * @param $s_time
     * @param $e_time
     *
     * @return mixed
     */
    public function updateGuideData($id, $s_time, $e_time)
    {
        $db_w = Yii::app()->lcs_w;
        $sql  = "update ".$this->tableName()." set cycle_current=cycle_current+1,s_time=:s_time,e_time=:e_time,u_time=:u_time where id=:id";
        $cmd  = $db_w->createCommand($sql);
        $now  = date('Y-m-d H:i:s');
        $cmd->bindParam(':id', $id);
        $cmd->bindParam(':s_time', $s_time);
        $cmd->bindParam(':e_time', $e_time);
        $cmd->bindParam(':u_time', $now);
        $res = $cmd->execute();
        return $res;
    }

    /**
     * 删除引导数据对应的已读日志
     * @param $g_id
     *
     * @return mixed
     */
    public function deleteReadLog($g_id)
    {
        $db_w = Yii::app()->lcs_w;
        $sql  = "delete from ".$this->tableNameReadLog()." where g_id=:id";
        $cmd  = $db_w->createCommand($sql);
        $cmd->bindParam(':id', $g_id);
        $res = $cmd->execute();
        return $res;
    }

}