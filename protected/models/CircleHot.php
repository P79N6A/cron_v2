<?php
/**
 * 圈子热度表
 *
 */

class CircleHot extends CActiveRecord
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return TABLE_PREFIX .'circle_hot';
    }

    public function tableNameRewardOrder()
    {
        return TABLE_PREFIX .'circle_order_reward';
    }

    /**
     * 组建where查询条件
     * @param  array $filters 过滤字段列表
     * @return string          组建好的where条件
     */
    public function buildAndWhere($filters)
    {
        $where = ' where 1 ';

        if (!empty($filters)) {
            foreach ($filters as $f => $v) {
                if (is_int($v)) {
                    $where .= " and {$f}={$v}";
                } elseif (is_array($v)) {
                    if (is_int($v['0'])) {
                        $where .= " and {$f} in (". implode(",", $v) .")";
                    } else {
                        $where .= " and {$f} in ('". implode("','", $v) ."')";
                    }
                } else {
                    $where .= " and {$f}='{$v}'";
                }
            }
        }

        return $where;
    }
    /**
     * 新增热度数据
     * 
     */
    public function saveCircleHot($data=array(),$is_free=0){
        if(!empty($data)){
            //添加热度数据
            $hot = array(
                "uid" => $data['uid'],
                "hot" => $data['hot'],
                "circle_id" => $data['circle_id'],
                "g_id" => $data['g_id'],
                "c_time" => date("Y-m-d H:i:s",time()),
                "u_time" => date("Y-m-d H:i:s",time())
            );
            //是否为免费礼物
            $hot['is_free'] = $is_free;
            return Yii::app()->lcs_w->createCommand()->insert($this->tableName(),$hot);
        }
        return 0;
    }
    /**
     * 粉丝度榜单
     * 
     */
    public function getHotList($start_time,$end_time,$circle_id){
        if(empty($start_time) || empty($end_time)){
            $sql = "select id,uid,hot,circle_id,c_time,sum(hot) as sum from lcs_circle_hot where circle_id=".$circle_id." group by uid order by sum desc;";
        }else{
            // $sql = "select id,uid,hot,circle_id,c_time,sum(hot) as sum from (select * from lcs_circle_hot where circle_id=".$circle_id." and c_time>'".$start_time."' and c_time<'".$end_time."')H group by uid order by sum desc;";
            $sql = "select id,uid,hot,circle_id,c_time,sum(hot) as sum from lcs_circle_hot where circle_id=".$circle_id." and c_time>'".$start_time."' and c_time<'".$end_time."'  group by uid order by sum desc;";
        }
        $CircleHotList = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $CircleHotList;
    }
     /**
      * 直播间热度 
      * 
      */
    public function getCircleHot($circle_id=array()){
        $circle_ids = implode(',',$circle_id);
        if(!empty($circle_id)){
            $sql = "select h.id,uid,hot,circle_id,uid,(sum(hot)+c.heat) as sum,h.c_time from lcs_circle_hot as h LEFT JOIN lcs_circle as c ON h.circle_id=c.id where circle_id in (".$circle_ids.") group by circle_id;";
            // $sql = "select id,uid,hot,circle_id,c_time,sum(hot) as sum from ".$this->tableName()." where circle_id in (".$circle_ids.") group by circle_id";
            
        }else{
            $sql = "select h.id,uid,hot,circle_id,uid,(sum(hot)+c.heat) as sum,h.c_time from lcs_circle_hot as h LEFT JOIN lcs_circle as c ON h.circle_id=c.id group by circle_id;";
            //$sql = "select id,uid,hot,circle_id,c_time,sum(hot) as sum from ".$this->tableName()." group by circle_id";
        }
        $circleHot = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $circleHot;
    }
    /**
     * 直播间热度排行
     * 
     */
    public function getCircleHotList($start_time,$end_time){
        $sql = "select id,circle_id,p_uid,title,summary,image,(`current_heat`+`sum`) as hot from lcs_circle as c left join (select circle_id,c_time,sum(hot) as sum from lcs_circle_hot where c_time>'".$start_time."' and c_time<'".$end_time."'  group by circle_id) h on c.id=h.circle_id where type=0 order by hot desc limit 21;";
        $CircleHotList = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        return $CircleHotList;
    }
    /**
     * 用户距离上次送免费礼物的时间
     *
     */
    public function getUserLastSend($circle_id,$uid,$g_id){
        $sql = "select * from ".$this->tableName()." where circle_id=:circle_id and uid=:uid and g_id=:g_id and is_free=1 order by id desc limit 1;";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':circle_id',$circle_id,PDO::PARAM_INT);
        $cmd->bindParam(':uid',$uid,PDO::PARAM_INT);
        $cmd->bindParam(':g_id',$g_id,PDO::PARAM_INT);
        return $cmd->queryRow();
    }
    //保存礼物订单信息
    public function saveRewardOrder($uid,$circle_id,$order_no,$g_id,$g_amount){
         //订单关联信息
         $reward = array(
            'uid'=>$uid,
            'circle_id'=>$circle_id,
            'order_no'=>$order_no,
            'g_id'=>$g_id,
            'g_amount'=>$g_amount,
            'c_time'=>date("Y-m-d H:i:s",time()),
         );
         return Yii::app()->lcs_w->createCommand()->insert($this->tableNameRewardOrder(),$reward);
    }
    //根据订单号获取礼物信息
    public function getOrdernoRewardInfo($order_no){
        $sql = "select uid,circle_id,order_no,g_id,g_amount from ".$this->tableNameRewardOrder()." where order_no=:order_no order by c_time desc limit 1;";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':order_no',$order_no,PDO::PARAM_INT);
        return $cmd->queryRow();
    }
}
