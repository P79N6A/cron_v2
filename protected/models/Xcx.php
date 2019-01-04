<?php
/**
 * Created by PhpStorm.
 * User: pcy
 * Date: 18-11-1
 * Time: 下午2:54
 */

class Xcx extends CActiveRecord
{

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    //小程序推送记录表
    public function tableRecordName()
    {
        return "lcs_xcx_push_record";
    }

    public function tableInfoName(){
        return 'lcs_xcx_user_info';
    }


    //获取48小时未活跃的用户进行推送
    public function notActive48ByPuid($p_uid,$type){
        if($type == 1 && empty($p_uid)){
            return false;
        }
        try{
            $now = date("Y-m-d H:i:s");
            $now_time = date("Y-m-d H:i:s",strtotime("-10 minute"));
            $time = date("Y-m-d H:i:s",strtotime("+7 day",strtotime($now_time)));
            $two_time =   date("Y-m-d H:i:s",strtotime("-2 day",strtotime($now)));
            if($type == 1){
                $sql = "select `open_id`,`uid` from ". $this->tableInfoName() ." M where M.p_uid='$p_uid' and M.push_times<2 and last_up_time<'$two_time'";
            }else{
                $sql = "select `open_id`,`uid` from ". $this->tableInfoName() ." M where M.p_uid='' and M.push_times<2 ";
            }
            $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            if(!empty($res)){
                foreach ($res as $k=>$v){
                        if($v['uid']){
                            $uid = $v['uid'];
                            $sql_time = " select form_end_time as time from " . $this->tableRecordName(). " where uid='$uid' order by form_end_time desc limit 1";
                            $time_end = Yii::app()->lcs_r->createCommand($sql_time)->queryRow();
                            if($time < $time_end['time'] && $type ==1){
                                unset($res[$k]);
                                continue;
                            }else{
                                $sql = "select form_id from " . $this->tableRecordName() . " where uid='$uid' and form_end_time>'$now' order by form_end_time asc limit 1";
                            }
                            $form_id = Yii::app()->lcs_r->createCommand($sql)->queryRow();
                            if(isset($form_id['form_id']) && empty($form_id['form_id'])){
                                $open_id = $v['open_id'];
                                $sql = "select form_id from " . $this->tableRecordName() . " where open_id='$open_id' and form_end_time>'$now' order by form_end_time asc limit 1";
                                $form_id = Yii::app()->lcs_r->createCommand($sql)->queryRow();
                                $id = $form_id['form_id'];
                                $sql_del = "delete from " .$this->tableRecordName(). " where open_id='$open_id' and form_id='$id'";
                                Yii::app()->lcs_w->createCommand($sql_del)->execute();
                            }
                        }else{
                            $open_id = $v['open_id'];
                            $sql_time = " select form_end_time as time from " . $this->tableRecordName(). " where open_id='$open_id' order by form_end_time desc limit 1";
                            $time_end = Yii::app()->lcs_r->createCommand($sql_time)->queryRow();
                            if($time < $time_end['time'] && $type ==1) {
                                unset($res[$k]);
                                continue;
                            }else{
                                $sql = "select form_id from " . $this->tableRecordName() . " where open_id='$open_id' and form_end_time>'$now' order by form_end_time asc limit 1";
                            }
                            $form_id = Yii::app()->lcs_r->createCommand($sql)->queryRow();
                        }
                        $res[$k]['form_id'] = $form_id['form_id'];
                    }
            }
            return $res;
        }catch (Exception $e){
            Common::model()->saveLog('小程序查询48小时未活跃的用户:'.$e->getMessage(),'error','select_xcx_not_active');
            return false;
        }
    }

    //根据用户uid获取
    public function getFormOrPayIdByUid($uid){
        if(empty($uid))
            return false;
        $now = date("Y-m-d H:i:s");
        try{
            $sql = "select open_id,uid,(select form_id from ". $this->tableRecordName(). " where uid='$uid' and form_end_time>'$now' order by form_end_time asc limit 1) as form_id from " . $this->tableInfoName(). " where uid='$uid'";
            $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            if(!isset($res[0]['form_id'])){
                $sql = "select open_id,uid,(select form_id from ". $this->tableRecordName(). " where open_id=M.open_id and form_end_time>'$now' order by form_end_time asc limit 1) as form_id from " . $this->tableInfoName(). "  M where uid='$uid'";
                $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
                if($res){
                    foreach ($res as $k){
                        $form_id = $k['form_id'];
                        $open_id = $k['open_id'];
                        $sql_del = "delete from " .$this->tableRecordName(). " where open_id='$open_id' and form_id='$form_id'";
                        Yii::app()->lcs_w->createCommand($sql_del)->execute();
                    }
                }
            }
        }catch (Exception $e){
            Common::model()->saveLog('小程序删除Id失败:'.$e->getMessage(),'error','delete_xcx_id');
            return false;
        }
        return $res;
    }

    public function delRecordById($uid,$Id,$type=1){
        if(empty($uid) || empty($Id))
            return false;
        if($type == 1){
            $sql = "delete from " .$this->tableRecordName(). " where uid='$uid' and form_id='$Id'";
            $sql_up = "update " . $this->tableInfoName(). " set push_times=push_times+1 where uid='$uid'";
        }elseif ($type == 2){
            $sql = "delete from " .$this->tableRecordName(). " where open_id='$uid' and form_id='$Id'";
            $sql_up = "update " . $this->tableInfoName(). " set push_times=push_times+1 where open_id='$uid'";
        }else if($type == 3){
            $sql = "delete from " .$this->tableRecordName(). " where uid='$uid' and pay_id='$Id'";
            $sql_up = "update " . $this->tableInfoName(). " set push_times=push_times+1 where uid='$uid'";
        }else{
            $sql = "delete from " .$this->tableRecordName(). " where open_id='$uid' and pay_id='$Id'";
            $sql_up = "update " . $this->tableInfoName(). " set push_times=push_times+1 where open_id='$uid'";
        }
        try{
            $res = Yii::app()->lcs_w->createCommand($sql)->execute();
            Yii::app()->lcs_w->createCommand($sql_up)->execute();
            return $res;
        }catch (Exception $e){
            Common::model()->saveLog('小程序删除Id失败:'.$e->getMessage(),'error','delete_xcx_id');
            return false;
        }
    }
}
