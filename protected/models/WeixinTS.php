<?php

/**
 * Class 微信第三方系统model
 */
class WeixinTS extends CActiveRecord
{


    private $remove_max_limit = 50000;//50000

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }


    public function tableNameWxtsMsgLog()
    {
        return "lcs_wxts_msg_log";
    }

    public function tableNameWxtsMsgTpl()
    {
        return "lcs_wxts_msg_tpl";
    }

    public function tableNameWxtsInfo()
    {
        return "lcs_wxts_info";
    }


    /**
     * 获取消息类型的模板ID
     * @param $app_id
     * @param $lcs_msg_type
     * @return mixed
     */
    public function getMsgTemplateId($app_id,$lcs_msg_type){
        $sql = "SELECT tpl_id FROM ".$this->tableNameWxtsMsgLog()." WHERE app_id=:app_id and lcs_msg_type=:lcs_msg_type;";
        $cmd = Yii::app()->lcs_comment_r->createCommand($sql);
        $cmd->bindParam(':app_id',$app_id, PDO::PARAM_STR);
        $cmd->bindParam(':lcs_msg_type',$lcs_msg_type, PDO::PARAM_STR);
        return $cmd->queryScalar();
    }




    /**
     * 保存微信消息结果
     * @param $data
     * @return mixed
     */
    public function saveWxtsMsgLog($data){
        return Yii::app()->lcs_comment_w->createCommand()->insert($this->tableNameWxtsMsgLog(), $data);
    }

    /**
     * 修改微信消息结果
     * @param $id
     * @param $data
     * @return mixed
     */
    public function updateWxtsMsgLog($id, $data){
        return Yii::app()->lcs_comment_w->createCommand()->update($this->tableNameWxtsMsgLog(),$data,'id=:id',array(':id'=>$id));
    }

    /**
     * 获取微信通知消息发送结果信息
     * @param int $send_status
     * @param string $s_time
     * @return array
     */
    public function getWxtsMsgLogBySendStatus($send_status=-1,$s_time=''){
        $sql = "SELECT id, wx_uid, message_content,wx_message_error FROM ".$this->tableNameWxtsMsgLog()." WHERE c_time>:s_time and send_status=:send_status;";
        $cmd = Yii::app()->lcs_comment_r->createCommand($sql);
        $cmd->bindParam(':s_time',$s_time, PDO::PARAM_STR);
        $cmd->bindParam(':send_status',$send_status, PDO::PARAM_INT);
        return $cmd->queryAll();
    }



    /**
     * 清理微信发送消息通知的结果日志
     * @param $end_time
     * @return int
     */
    public function removeWxtsMsgLog($end_time){
        $sql = 'delete from '.$this->tableNameWxtsMsgLog().' where c_time<:end_time limit :limit;';
        $cmd = Yii::app()->lcs_comment_w->createCommand($sql);
        $cmd->bindParam(':end_time',$end_time, PDO::PARAM_STR);
        $cmd->bindParam(':limit',$this->remove_max_limit, PDO::PARAM_INT);
        $total = 0;
        do{
            $records = $cmd->execute();
            $total +=$records;
        }while($records>=$this->remove_max_limit);

        return $total;

    }

    /**
     * 获取绑定的微信公众号列表
     *
     * @return mixed
     */
    public function getWxInfoList()
    {
        $sql = 'select app_id from ' . $this->tableNameWxtsInfo();
        $cmd = Yii::app()->lcs_comment_r->createCommand($sql);
        $res = $cmd->queryAll();

        return $res;
    }

    /**
     * 更新公众号资料
     *
     * @param $app_id
     * @param $data
     * @return bool
     */
    public function updateWxtsInfoByAppId($app_id, $data)
    {
        $res = Yii::app()->lcs_comment_w->createCommand()->update($this->tableNameWxtsInfo(), $data, 'app_id=:app_id', array(':app_id' => $app_id));

        return $res;
    }



}