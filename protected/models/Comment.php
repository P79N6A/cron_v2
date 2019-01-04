<?php
/**
 * 说说基本信息数据库访问类
 * User: zwg
 * Date: 2015/5/18
 * Time: 18:06
 */

class Comment extends CActiveRecord {
    const INDEX_NAME = 'checkcirclecomment';
    public $url;
    public function __construct(){
        if(defined('ENV') && ENV == 'dev'){
            $this->url='http://192.168.48.224:9200/';
        }else{
            if(time()%2==0){
                try{
                    $url = "http://47.104.254.17:9200/";
                    $res = Yii::app()->curl->setTimeOut(1)->get($url);
                    if(!empty($res)){
                        $this->url = $url;
                        return;
                    }
                }catch(Exception $e){
                }

                try{
                    $url = "http://47.104.129.89:9200/";
                    $res = Yii::app()->curl->setTimeOut(1)->get($url);
                    if(!empty($res)){
                        $this->url = $url;
                    }
                }catch(Exception $e){
                }
            }else{
                try{
                    $url = "http://47.104.129.89:9200/";
                    $res = Yii::app()->curl->setTimeOut(1)->get($url);
                    if(!empty($res)){
                        $this->url = $url;
                    }
                }catch(Exception $e){
                }

                try{
                    $url = "http://47.104.254.17:9200/";
                    $res = Yii::app()->curl->setTimeOut(1)->get($url);
                    if(!empty($res)){
                        $this->url = $url;
                        return;
                    }
                }catch(Exception $e){
                }

            }
        }
    }
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName(){
        return 'lcs_comment';
    }


    private $is_new_comment = true;

    const COMMENT_TABLE_NUMS = 256; // 大家说分表个数

    private $default_comment_fields = "cmn_id,cmn_type,relation_id,crc32_id,u_type,uid,content,is_display,is_anonymous,discussion_type,discussion_id,c_time";

    public function tableNameMaster(){
        return 'lcs_comment_master';
    }
    public function tableNameComment($table_index){
        return 'lcs_comment_' . $table_index;
    }
    public function tableNameMedia(){
        return 'lcs_comment_media';
    }

    /** 新说说迁移此方法已经不能在使用
    public function getCommentInfoByID($ids){
        $ids = (array)$ids;
        $result = array();

        $sql = "select cmn_id,cmn_type,relation_id,u_type,uid,content,replay_id,parent_relation_id from ".$this->tableName()." where cmn_id in (".implode(',',$ids).")";
        $data = Yii::app()->lcs_r->createCommand($sql)->queryAll();

        if(!empty($data)){
            foreach($data as $val){
                $result[$val['cmn_id']] = $val;
            }
        }

        return $result;
    }*/

    public function getCommontInfo($cmn_type, $relation_id, $cmn_id) {
        $crc32_id = CommonUtils::getCRC32($cmn_type . '_' . $relation_id);
        $cmn_tb_index = $crc32_id % self::COMMENT_TABLE_NUMS;

        $db_r = Yii::app()->lcs_comment_r;
        if (!$db_r->active) {
            $db_r->setActive(true);
        }

        $sql = "select {$this->default_comment_fields}
                from {$this->tableNameComment($cmn_tb_index)}
                where cmn_id=:cmn_id";
        $cmd = $db_r->createCommand($sql);
        $cmd->bindParam(':cmn_id', $cmn_id, PDO::PARAM_INT);
        $cmn_res = $cmd->queryRow();
        if (empty($cmn_res)) {
            return [];
        }

        $sql = "select type,url
                from {$this->tableNameMedia()}
                where crc32_id=:crc32_id and cmn_id=:cmn_id";
        $cmd = $db_r->createCommand($sql);
        $cmd->bindParam(':crc32_id', $crc32_id, PDO::PARAM_INT);
        $cmd->bindParam(':cmn_id', $cmn_id, PDO::PARAM_INT);
        $media_res = $cmd->queryAll();
        if (!empty($media_res)) {
            $cmn_res['media_list'] = $media_res;
        }

        return $cmn_res;
    }

    /**
     * 根据类型获取说说信息
     * @param $start_time
     * @param $end_time
     */
    public function getCommentUidCountOfRelation_id($cmn_type, $relation_ids, $u_type, $start_time, $end_time){
        $cdn = '';
        if(!empty($relation_ids)){
            $relation_ids = (array)$relation_ids;
            $cdn = 'AND relation_id in ('.implode(',',$relation_ids).')';
        }
        $db_r = null;
        $sql = 'SELECT relation_id, count(DISTINCT(uid)) as num FROM '.$this->tableName().' WHERE cmn_type=:cmn_type '.$cdn.' AND u_type=:u_type AND c_time>=:start_time AND c_time<:end_time and status=0 group by relation_id;';
        if($this->is_new_comment){
            $db_r = Yii::app()->lcs_comment_r;
            $sql = 'SELECT relation_id, count(DISTINCT(uid)) as num FROM '.$this->tableNameMaster().' WHERE cmn_type=:cmn_type '.$cdn.' AND u_type=:u_type AND c_time>=:start_time AND c_time<:end_time group by relation_id;';
        }else{
            $db_r = Yii::app()->lcs_r;
        }

        $cmd = $db_r->createCommand($sql);

        $cmd->bindParam(':cmn_type', $cmn_type, PDO::PARAM_INT);
        $cmd->bindParam(':u_type', $u_type, PDO::PARAM_INT);
        $cmd->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        $cmd->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        return $cmd->queryAll();
    }


    /**
     * 根据类型获取说说信息
     * @param $start_time
     * @param $end_time
     */
    public function getCommentByType($cmn_type, $relation_ids, $u_type, $start_time, $end_time, $fields){
        $select = 'cmn_id';
        if(!empty($fields)){
            $select = is_array($fields)?implode(',',$fields): $fields;
        }
        $cdn = '';
        if(!empty($relation_ids)){
            $relation_ids = (array)$relation_ids;
            $cdn = 'AND relation_id in ('.implode(',',$relation_ids).')';
        }

        $db_r = null;
        $sql = 'SELECT '.$select.' FROM '.$this->tableName().' WHERE cmn_type=:cmn_type '.$cdn.' AND u_type=:u_type AND c_time>=:start_time AND c_time<:end_time and status=0;';
        if($this->is_new_comment){
            $db_r = Yii::app()->lcs_comment_r;
            $select = 'uid,relation_id, relation_id as parent_relation_id';
            $sql = 'SELECT '.$select.' FROM '.$this->tableNameMaster().' WHERE cmn_type=:cmn_type '.$cdn.' AND u_type=:u_type AND c_time>=:start_time AND c_time<:end_time;';
        }else{
            $db_r = Yii::app()->lcs_r;
        }


        $cmd = $db_r->createCommand($sql);
        $cmd->bindParam(':cmn_type', $cmn_type, PDO::PARAM_INT);
        $cmd->bindParam(':u_type', $u_type, PDO::PARAM_INT);
        $cmd->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        $cmd->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        return $cmd->queryAll();
    }


    /**
     * 获取理财师发布说说的数量
     * @param string $p_uid
     * @param null $start_date
     * @param null $end_date
     * @return mixed
     */
    public function getCommentCount($p_uid='', $start_time='', $end_time=''){
        $cdn = '';
        if(!empty($p_uid)){
            $cdn .= ' AND uid=:uid';
        }
        if(!empty($start_time)){
            $cdn .= ' AND c_time>=:start_time';
        }
        if(!empty($end_time)){
            $cdn .= ' AND c_time<:end_time';
        }

        $db_r = null;
        $sql = 'SELECT uid,count(uid) as num FROM '.$this->tableName().' WHERE u_type=2 '.$cdn.' AND status=0 GROUP BY uid;';
        if($this->is_new_comment){
            $db_r = Yii::app()->lcs_comment_r;
            $sql = 'SELECT uid,count(uid) as num FROM '.$this->tableNameMaster().' WHERE u_type=2 '.$cdn.' GROUP BY uid;';
        }else{
            $db_r = Yii::app()->lcs_r;
        }


        $cmd = $db_r->createCommand($sql);
        if(!empty($p_uid)){
            $cmd->bindParam(':uid', $p_uid, PDO::PARAM_INT);
        }
        if(!empty($start_time)){
            $cmd->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        }
        if(!empty($end_time)){
            $cmd->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        }
        return $cmd->queryAll();
    }
    //获取圈子说说信息
    public function getCircleCommentInfo($ids=array()){
        if(empty($ids)){
            return false;
        }
        $ids=(array)$ids;    
        $db_r = Yii::app()->lcs_comment_r;
        if (!$db_r->active) {
            $db_r->setActive(true);
        }
        $sql = 'SELECT id,cmn_id,cmn_type,relation_id,crc32_id,u_type,uid,content,is_anonymous,is_good,discussion_id,is_essences,c_time
                from '. $this->tableNameMaster().' where u_type=1 and id in('.implode(',',$ids).')';
        $cmd = $db_r->createCommand($sql);
        $cmn_res = $cmd->queryAll();
        return $cmn_res;
    }
    public function getCircleCommentAll(){
        $db_r = Yii::app()->lcs_comment_r;
        $datetime=date('Y-m-d H:i:s',strtotime("-15 day"));
        $sql = 'SELECT id,cmn_id,cmn_type,relation_id,crc32_id,u_type,uid,content,is_anonymous,is_good,discussion_id,is_essences,c_time
                from '. $this->tableNameMaster().' where u_type=1 and cmn_type=71 and c_time>="'.$datetime.'"';
        $cmd = $db_r->createCommand($sql);
        $cmn_res = $cmd->queryAll();
        return $cmn_res;
    }
}
