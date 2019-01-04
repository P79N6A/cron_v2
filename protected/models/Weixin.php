<?php

/**
 * 微信方面的model
 */
class Weixin extends CActiveRecord
{

    private static $_model=null;

    private $remove_max_limit = 50000;//50000

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }


    public function tableNameWxMsgResult()
    {
        return "lcs_wx_msg_result";
    }

    public function tableNameWxMessage()
    {
        return "lcs_wx_message";
    }

    public function tableNameWxReply()
    {
        return "lcs_wx_reply";
    }

    public function tableNameMessageChannelUser(){
        return 'lcs_message_channel_user';
    }


    /**
     * 删除渠道用户
     * @param unknown $wx_uid
     */
    public function deleteChannelUserByWxUid($wx_uid){

        $sql = "delete from ".$this->tableNameMessageChannelUser()." where channel_type=1 and channel_id=:channel_id;";
        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $cmd->bindParam(':channel_id', $wx_uid, PDO::PARAM_STR);
        return $cmd->execute();
    }




    /**
     * 保存微信消息结果
     * @param $data
     * @return mixed
     */
    public function saveWxMsgResult($data){
        return Yii::app()->lcs_w->createCommand()->insert($this->tableNameWxMsgResult(), $data);
    }

    /**
     * 修改微信消息结果
     * @param $id
     * @param $data
     * @return mixed
     */
    public function updateWxMsgResult($id, $data){
        return Yii::app()->lcs_w->createCommand()->update($this->tableNameWxMsgResult(),$data,'id=:id',array(':id'=>$id));
    }

    /**
     * 获取微信通知消息发送结果信息
     * @param int $send_status
     * @param string $s_time
     * @return array
     */
    public function getWxMsgResultBySendStatus($send_status=-1,$s_time=''){
        $sql = "SELECT id, wx_uid, message_id, message_content,wx_message_error FROM ".$this->tableNameWxMsgResult()." WHERE c_time>:s_time and send_status=:send_status;";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':s_time',$s_time, PDO::PARAM_STR);
        $cmd->bindParam(':send_status',$send_status, PDO::PARAM_INT);
        return $cmd->queryAll();
    }



    /**
     * 清理微信发送消息通知的结果日志
     * @param $end_time
     * @return int
     */
    public function removeWxMsgResult($end_time){
        $sql = 'delete from '.$this->tableNameWxMsgResult().' where c_time<:end_time limit :limit;';
        $cmd = Yii::app()->lcs_w->createCommand($sql);
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
     * 清理微信消息数据表
     * @param $end_time
     * @return int
     */
    public function removeWxMessage($end_time){
        $sql = 'delete from '.$this->tableNameWxMessage().' where c_time<:end_time limit :limit;';
        $cmd = Yii::app()->lcs_w->createCommand($sql);
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
     * 清理微信消息回复数据表
     * @param $end_time
     */
    public function removeWxReply($end_time){
        $sql = 'delete from '.$this->tableNameWxReply().' where c_time<:end_time limit :limit;';
        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $cmd->bindParam(':end_time',$end_time, PDO::PARAM_STR);
        $cmd->bindParam(':limit',$this->remove_max_limit, PDO::PARAM_INT);
        $total = 0;
        do{
            $records = $cmd->execute();
            $total +=$records;
        }while($records>=$this->remove_max_limit);

        return $total;

    }




}