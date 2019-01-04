<?php
/**
 * 投教课程
 */
class CoursePackage extends CActiveRecord {
     
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
   
    //课程订阅表
    public function tableNameSub(){
    	return TABLE_PREFIX .'set_subscription';
    }

    public function getCoursePackageSubUser($set_id){
        $now = date("Y-m-d H:i:s",time());
        $sql = "select uid from ".$this->tableNameSub()." where setid='$set_id' and end_time>='$now'";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $res = array();
        if($data){
            foreach($data as $item){
                $res[] = $item['uid'];
            }
        }
        return $res;
    }
    public function getSubscriptionUids(){
        $time=date('Y-m-d H:i:s');
        $sql='select end_time,settype,uid,p_uid,setid from '.$this->tableNameSub().' where end_time>"'.$time.'" order by end_time asc ';
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $res=array();
        if(!empty($data)){
            foreach ($data as $v){
              array_push($res,$v['uid']);
            }
        }
        return $res;
    }
}
