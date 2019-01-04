<?php
/**
 * 视频相关
 * User: haohao
 * Date: 15-12-01
 * Time: 上午9:02
 */

class Video extends CActiveRecord {

    public function getDbConnection($table_key = 'lcs_w') {

        return Yii::app() -> $table_key;
    }

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 视频用户是否在线
     * @see CActiveRecord::tableName()
     */
    public function tableName(){
        return TABLE_PREFIX.'video_user';
    }

    /**
     * 视频在线人数统计
     * @return string
     */
    private function tableNameOption(){
        return TABLE_PREFIX.'video_user_stat';
    }

    /*
	  * 查询一分钟内在线人数
	  * @param str date 开始统计时间
	  */
    public function getLastMinVideoUserNumber($date=''){

        !empty($date) or $date=date('Y-m-d H:i:s',strtotime('-5 seconds'));

        $sql = "select count(1) count,video_id from ".$this->tableName()." where u_time>=:date group by video_id";

        $cmd = $this->getDbConnection('lcs_w')->createCommand($sql);
        $cmd->bindParam(':date',$date,PDO::PARAM_STR);

        $result = $cmd->queryAll();
        $ids = array();
        foreach($result as $k=>$v){
            if($v['count']>0){
                $ids[$v['video_id']] = $v['count'];
            }
        }

        return $ids;
    }
    /*
     * 添加单个视频在线人数
     */
    public function addVideoUserStat($data){
        if(empty($data)){
            return ;
        }
        $res = $this->getDbConnection('lcs_w')->createCommand()->insert($this->tableNameOption(),$data);
        return $res;
    }
    /**
     * 根据理财师s_uid获取直播信息
     * @param  array $s_uids 理财师s_uid
     * @return array        直播信息
     */
    public function getLiveInfoBySuids($s_uids, $type=0, $order="is_online desc,is_recommend desc,u_time desc")
    {
        if (empty($s_uids)) {
            return array();
        } else {
            $s_uids = (array) $s_uids;
        }

        $fields = "id,is_online,liveid,image,title,description,s_uid,start_time,end_time,type,rtmp_url,play_url,c_time";
        if (in_array($type, array(1,2,3,4,5))) {
            $where = "s_uid in (:s_uid) and type={$type} and is_display=1";
        } else {
            $where = "s_uid in (:s_uid) and is_display=1";
        }

        $sql = "select {$fields} from ".$this->tableNameVideo()." where {$where} order by {$order}";
        $cmd = $this->getDbConnection()->createCommand($sql);
        $s_uid_str = implode(",", $s_uids);
        $cmd->bindParam(':s_uid',$s_uid_str,PDO::PARAM_STR);
        $video = $cmd->queryAll();

        $return = array();
        if (!empty($video)) {
            foreach ($video as $row) {
                $return[$row['s_uid']][] = $row;
            }
        }
        return $return;
    }
}