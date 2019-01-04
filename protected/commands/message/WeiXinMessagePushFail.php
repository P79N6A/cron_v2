<?php
/**
 * 微信发送模板消息失败 再次发送处理
 * User: zwg
 * Date: 2015/11/30
 * Time: 14:32
 */

class WeiXinMessagePushFail {

    const CRON_NO = 1306; //任务代码
    public function __construct(){
    }


    public function check(){
        $time = date("Y-m-d H:i:s",time()-60);
        $msg = Weixin::model()->getWxMsgResultBySendStatus(-1,$time);
        if(!empty($msg)){
            $weiXinMsg = new WeiXinMessagePushQueue();
            foreach($msg as & $val){
                $wx_message_error = !empty($val['wx_message_error'])?json_decode($val['wx_message_error'],true):array();
                //
                if(isset($wx_message_error['errcode'])&&$wx_message_error['errcode']=='43004'){
                    //更新消息状态不在重发
                    Weixin::model()->updateWxMsgResult($val['id'],array('send_status'=>2));
                    //用户不在关注理财师公众号，解除绑定关系
                    Weixin::model()->deleteChannelUserByWxUid($val['wx_uid']);
                    continue;
                }

                $counter = Yii::app()->lcs_r->createCommand("SELECT count(wx_uid) FROM lcs_wx_msg_result WHERE wx_uid='".$val['wx_uid']."' AND message_id=".$val['message_id'].";")->queryScalar();
                if(intval($counter) >= 7){
                    Weixin::model()->updateWxMsgResult($val['id'],array('send_status'=>2));

                }else{
                    $weiXinMsg->sendMessage($val['message_content']);
                    Weixin::model()->updateWxMsgResult($val['id'],array('send_status'=>1));
                }
            }
        }
    }



}