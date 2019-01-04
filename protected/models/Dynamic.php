<?php
/**
 * 动态相关
 */
class Dynamic extends CActiveRecord {
    
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
   
    //动态列表
    public function tableName(){
    	return TABLE_PREFIX .'dynamic';
    }

    /**
     * 根据数组id查询相关记录
     */
    public function getDynamicByIds($ids){
        $sql = "select id,content,p_uid,imgurl,radio_url,clicknums,praisenums,status,c_time,u_time from ".$this->tableName()." where id in (".implode(',',$ids).")";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        $result = array();
        if($data){
            foreach($data as $item){
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }

    /**
     * 获取动态信息
     * @param   int $type 1 两个小时内 0 两天内
     */
    public  function  getTwoDayDynamic($type=0){
        if($type==1){
            $time1=date('Y-m-d H:i:s',strtotime('-2 hours'));
            $time=date('Y-m-d H:i:s');
        }else{
            $time1=date('Y-m-d H:i:s',strtotime('-2 day'));
            $time=date('Y-m-d H:i:s',strtotime('-2 hours'));
        }
        $data=array();
        $sql_count="SELECT count(id) from ".$this->tableName()." where status=0 and c_time>='$time1' and c_time<='$time'";
        $data['total']=Yii::app()->lcs_standby_r->createCommand($sql_count)->queryScalar();
        $sql="SELECT id as dynamic_id,praisenums from ".$this->tableName()." where status=0 and c_time>='$time1' and c_time<='$time'";
        $data['data'] = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        return $data;
    }

    /**
     * 设置dynamic中的某个值
     * @param   int $id 主键
     * @param   str $op add增加,minus减
     * @param   int $val
     */
    public function setDynamicValue($id,$column,$op="add",$val=1){
        if($op=="add"){
            $sql = "update ".$this->tableName()." set $column=$column+1 where id='$id'";
        }else{
            $sql = "update ".$this->tableName()." set $column=$column-1 where id='$id' and $column>0";
        }

        $res = Yii::app()->lcs_w->createCommand($sql)->execute();
        return $res;
    }

    /**
     * 修改动态content
     * @param $params
     * @return bool
     */
    public function update($params)
    {
        $connection = Yii::app()->lcs_w;
        $updateArr['content'] = $params['content'];
        $updateArr['u_time'] = date("Y-m-d H:i:s");
        return $connection->createCommand()->update($this->tableName(), $updateArr, 'id='.$params['id']);
    }

    /**
     * 获取动态视频
     * @param $p_uid
     * @param $lastId
     * @param int $pageSize
     * @param $isVipService
     * @return mixed
     */
    public function getVideoList($time1)
    {
        $lcsPlannerTbName = Planner::model()->tableName();
        $where = "a.video_id!=''  and a.status=0 and return_status=1 and a.is_vip_service=0";
        if (!empty($time1)){

            $where .= " and a.c_time>='$time1'";
        }
        $db_r = Yii::app()->lcs_r;
        $sql = "select a.video_id as vid,a.c_time as publishedAt,a.imgurl as cover,b.s_uid as id,b.name as nickname,b.image as avatar,b.summary as description,a.content as title from " . $this->tableName() .
            " a left join $lcsPlannerTbName b on a.p_uid=b.s_uid  where $where";
        $cmd = $db_r->createCommand($sql);
        return $cmd->queryAll();


    }
    /**
     * 查询动态视频阿里云回调出问题
     */
    public function getVideoImagesAliyun()
    {
        $sql_count="SELECT video_id,id from ".$this->tableName()." where return_status=1 and imgurl='https://' and video_id!=''";

        $data = Yii::app()->lcs_r->createCommand($sql_count)->queryAll();
        return $data;
    }
    /**
     * 修改动态images
     * @param $params
     * @return bool
     */
    public function updateImgurl($params)
    {
        $connection = Yii::app()->lcs_w;
        $updateArr['imgurl'] = $params['imgurl'];
        $updateArr['u_time'] = date("Y-m-d H:i:s");
        return $connection->createCommand()->update($this->tableName(), $updateArr, 'id='.$params['id']);
    }
}
