<?php
/**
 * 课程
 */
class Course extends CActiveRecord {
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
   
    //课程表
    public function tableName(){
    	return TABLE_PREFIX .'course';
    }

    //课程订阅表
    public function tableNameSub(){
    	return TABLE_PREFIX .'course_subscription';
    }

    /**
     * 获取某个课程的全部订阅用户
     */
    public function getUidByCourseSubscription($course_id){
        if(empty($course_id)){
            return array();
        }
        $now = date("Y-m-d H:i:s");
        $sql = "select distinct uid from ".$this->tableNameSub()." where course_id='".$course_id."' and class_id=0 and status=0  and end_time>='".$now."'";
        $course_uid = Yii::app()->lcs_r->createCommand($sql)->queryAll($sql);

        $result = array();
        if($course_uid){
            foreach($course_uid as $item){
                $result[] = $item['uid'];
            }
        }
        return $result;
    }

    /**
     * 根据课程id获取课程详情数据
     * @param   int $course_id 课程id
     * @return  array   课程详情
     */
    public function getCourseById($course_id){
        $sql = "select id,type,title,subscription_price from ".$this->tableName()." where id=:id";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(":id",$course_id,PDO::PARAM_INT);
        $data = $cmd->queryAll();
        return $data;
    }
}
