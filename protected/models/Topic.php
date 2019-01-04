<?php /**
 * 话题
 * User: zwg
 * Date: 2015/7/17
 * Time: 18:06
 */

class Topic extends CActiveRecord {


    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName(){
        return 'lcs_hot_topic';
    }

    public function tableNameExt(){
        return 'lcs_hot_topic_ext';
    }


    /**
     * 获取话题
     * @param $type  1-url 2-搜索词 3-话题
     * @param null $ind_id
     * @param int $limit
     * @return mixed
     */
    public function getTopicByType($type, $ind_id=null, $limit=10,$fields=''){
        $select = 'id,title';
        if(!empty($fields)){
            $select = is_array($fields)?implode(',',$fields):$fields;
        }
        $cdn = '';
        if(!empty($type)){
            if(is_array($type)){
                $cdn .= ' and type in ('.implode(',',$type).')';
            }else{
                $cdn .= ' and type='.intval($type);
            }
        }
        if(!empty($ind_id)){
            $cdn .= ' and ind_id=:ind_id';
        }
        $sql = "select ".$select." from ".$this->tableName()
            . " where status=1 ".$cdn." order by order_no desc limit 0,:limit;";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':limit', $limit, PDO::PARAM_INT);
        if(!empty($ind_id)) {
            $cmd->bindParam(':ind_id', $ind_id, PDO::PARAM_INT);
        }

        return $cmd->queryAll();
    }


    /*
	 * 按ID	获取多个话题信息
	* */
    public function getTopicInfoIds($ids)
    {
        $res = array();
        $no_mc_ids = (array)$ids;

        $sql = "SELECT title,id,ind_id,type,ext,comment_num,parise_num,c_time,u_time FROM ".$this->tableName()." WHERE id in(".implode(',', $no_mc_ids).") and (status=1 or status=-1);"; //update by zwg 20151231   and status=1
        $res_data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if(!empty($res_data))
        {
            foreach ($res_data as $v)
            {
                $res[$v['id']] = $v;
            }
        }
        return $res;

    }

    /**
     * 更新数量
     * @param $topic_id
     * @param string $field
     * @param string $oper
     * @param int $num
     * @return mixed
     */
    public function updateNumber($topic_id, $field = 'sub_num', $oper = "add", $num = 1) {
        $topic_id = intval($topic_id);
        $num = intval($num);
        $sql = "update " . $this->tableName() . " set $field=" . ($oper == 'add' ? "$field+$num" : "$field-$num") . " where id=$topic_id";
        return Yii::app()->lcs_w->createCommand($sql)->execute();
    }


    /**
     * 保存热点相关记录
     *  @param  int $topic_id   话题id
     *  @param  int $type 类型id，1：观点，2：问答，3：计划，4：微观点
     *  @param  int $rs_id 相关id
     */
    public function saveTopicRelation($topic_id,$type,$relation_id){
        $sql = "insert into ".$this->tableNameExt()." (hot_topic_id,rs_id,type,expired_time,c_time,u_time)  values(:hot_topic_id,:rs_id,:type,:expired_time,:c_time,:u_time)";
        $year_late = date("Y-m-d H:i:s",strtotime("+1 year",time()));
        $now = date("Y-m-d H:i:s");
        $cmd = Yii::app()->lcs_w->createCommand($sql);
        $cmd->bindParam(':hot_topic_id', $topic_id, PDO::PARAM_STR);
        $cmd->bindParam(':rs_id', $relation_id, PDO::PARAM_STR);
        $cmd->bindParam(':type', $type, PDO::PARAM_STR);
        $cmd->bindParam(':expired_time', $year_late, PDO::PARAM_STR);
        $cmd->bindParam(':c_time', $now, PDO::PARAM_STR);
        $cmd->bindParam(':u_time', $now, PDO::PARAM_STR);
        $cmd->execute();
    }

}
