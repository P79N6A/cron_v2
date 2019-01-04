<?php
/**
 * 观点基本信息数据库访问类
 * User: zwg
 * Date: 2015/5/18
 * Time: 18:06
 */

class View extends CActiveRecord {


    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName(){
        return 'lcs_view';
    }

    public function tableNameSubscription(){
        return 'lcs_view_subscription';
    }
    
    public function tableNameSdata(){
        return 'lcs_view_sdata';
    }

    public function tableNameIndustry(){
        return 'lcs_industry';
    }

    public function tableNameLog(){
        return 'lcs_visit_log';
    }


    // 根据指定的观点包id集合获取观点集合map
    public function getViewInfoMapByPkgids($pkg_ids) {
        if (empty($pkg_ids)) {
            return null;
        } else {
            $pkg_ids = (array) $pkg_ids;
        }
        $sql = "select id,pkg_id,p_time from {$this->tableName()} where pkg_id in (".implode(",", $pkg_ids).") and status=0";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if (!empty($res)) {
            $return = [];
            foreach ($res as $row) {
                $return[$row['pkg_id']][] = $row;
            }
            return $return;
        } else {
            return null;
        }
    }


    /**
     * 获取用户订阅理财师观点信息 参与理财师影响力计算
     * @param string $start_time
     * @param string $end_time
     */
    public function getViewSubscriptionOfInfluence($start_time, $end_time){
        $sql = 'SELECT v.`id`,v.`p_uid`,vs.`uid`,vs.`subscription_price` FROM lcs_view v LEFT JOIN lcs_view_subscription vs ON v.`id`=vs.`v_id` WHERE vs.c_time>=:start_time AND vs.c_time<:end_time AND vs.`status`=0;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        $cmd->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        return $cmd->queryAll();
    }


    /**
     * 获取用户发布观点的数量
     * @param string $p_uid
     * @param null $start_date
     * @param null $end_date
     * @return mixed
     */
    public function getViewCount($p_uid='', $start_time='', $end_time=''){
        $cdn = '';
        if(!empty($p_uid)){
            $cdn .= ' AND p_uid=:p_uid';
        }
        if(!empty($start_time)){
            $cdn .= ' AND p_time>=:start_time';
        }
        if(!empty($end_time)){
            $cdn .= ' AND p_time<:end_time';
        }
        $sql = 'SELECT p_uid,count(p_uid) as num FROM '.$this->tableName().' WHERE 1=1 '.$cdn.' AND status=0 GROUP BY p_uid;';
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        if(!empty($p_uid)){
            $cmd->bindParam(':p_uid', $p_uid, PDO::PARAM_INT);
        }
        if(!empty($start_time)){
            $cmd->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        }
        if(!empty($end_time)){
            $cmd->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        }
        return $cmd->queryAll();
    }


    /**
     * 根据文章id获取内容
     *
     * @param unknown_type $id
     */
    public function getViewContentById($id){

        $id = intval($id);
        $sql = "SELECT id,id as v_id,content,content_pay FROM ".$this->tableName()." WHERE id='$id'";
        $return_data =  Yii::app()->lcs_standby_r->createCommand($sql)->queryRow();

        return $return_data;
    }
    
    /**
     * 根据文章所属分类号ind_id获取分类名称
     *
     * @param unknown_type $ind_id
     */
    public function getNameByIndId($ind_id){
        $ind_id = intval($ind_id);//var_dump($ind_id);
        $sql = "SELECT name FROM ".$this->tableNameIndustry()." WHERE id='$ind_id'";
        $return_data =  Yii::app()->lcs_r->createCommand($sql)->queryRow();
        if(!$return_data)
        {
            return '';
        }
        return $return_data['name'];
    }

    /**
     * 根据观点id获取观点
     *
     * @param unknown_type $ids
     */
    public function getViewById( $ids=array(),$is_r=true){
        $ids = (array)$ids;
        if(empty($ids)){
            return array();
        }
        $ids = array_unique($ids);
        $return = array();
        $sql = "SELECT id,id as v_id,p_uid,ind_id,pkg_id,title,summary,content,content_pay,tags,view_num,real_view_num,p_time,image,p_time,subscription_price,type FROM ".$this->tableName()." WHERE id IN(";
        foreach ($ids as $val){
            $sql .= intval($val).',';
        }

        $sql = substr($sql,0,-1).')';
        $db=null;
        if($is_r){
            $db= Yii::app()->lcs_standby_r;
            ///关闭连接，重新链接
            if(empty($db->active)){
                $db->active=true;
            }
        }else{
            $db= Yii::app()->lcs_w;
        }
        $cmd =  $db->createCommand($sql);
        $views = $cmd->queryAll();

        if(is_array($views) && sizeof($views)>0){
            foreach ($views as $vals){
                $return[$vals['id']] = $vals;
            }
        }
        return $return;
    }


    /**
     * 删除观点缓存
     *
     * @param unknown_type $v_id
     * @param unknown_type $num
     */
    public function delViewMc($v_id){

        $key = MEM_PRE_KEY."v_".intval($v_id);
        Yii::app()->cache->delete($key);
        return ;
    }

    /**
     * 根据id获取文章点击数
     * 返回数组
     * @param unknown_type $ids
     */
    public function getViewClick($ids=array()){
        if(empty($ids)){
            return array();
        }
        $ids = (array)$ids;

        $key = array();
        foreach ($ids as $val){
            $key[] = MEM_PRE_KEY.'v_c_'.$val;
        }
        $res = Yii::app()->redis_r->mget($key);

        $return = array();
        foreach ($ids as $key=>$val){
            $return[$val] = $res[$key];
        }
        return $return;
    }

    /**
     * get id from lcs_view by p_time between a time range.
     */
    public function getViewIdList($s_time, $e_time = '') {
        $db_r = Yii::app()->lcs_r;
        if($e_time == '') {
            $sql = "select id,pkg_id,ind_id,p_uid,quote_id,title from ". $this->tableName() ." where p_time>='". $s_time ."' ";
        }else{
            $sql = "select id,pkg_id,ind_id,p_uid,quote_id,title from ". $this->tableName() ." where p_time>='". $s_time ."' and p_time<='". $e_time ."' ";
        }
        $cmd =  $db_r->createCommand($sql);
        $v_ids = $cmd->queryAll();

        return $v_ids;
    }
    
    /**
     * 从redis中读出需要同步到搜索部门的观点数据
     */
    public function getSyncViewListFromRedis(){
        try{
            $redis_w = Yii::app()->redis_w;
            $db_r = Yii::app()->lcs_r;
            $data=Array();
            while($temp=$redis_w->lpop("lcs_Sync_search_list")){
                array_push($data,$temp);
            }
            if(count($data)>0){
                $sql="select id,ind_id,title,tags,content,content_pay,p_time,status from ".$this->tableName()." where id in (".  implode(',', $data).")";
                $cmd = $db_r->createCommand($sql);
                $res = $cmd->queryAll();
                return $res;
            }else{
                return False;
            }
        } catch (Exception $ex) {
            return False;
        }
    }

    /**
     * get quote url from lcs_quote by quote_id.
     */
    public function getQuoteList($quote_ids) {
        if(!is_array($quote_ids)) {
            $quote_ids = (array)$quote_ids;
        }
        $db_r = Yii::app()->lcs_r;
        $sql_quote = 'select id, s_url from lcs_quote where id in ('. implode(',', $quote_ids) .')';
        $cmd =  $db_r->createCommand($sql_quote);
        $quote_list = $cmd->queryAll();
        $quote_rs = array();
        if($quote_list) {
            foreach($quote_list as $quote_row) {
                $quote_rs[$quote_row['id']] = $quote_row['s_url'];
            }
        }
        return $quote_rs;
    }

    /**
     * 获取理财师的所有观点的点击数
     *
     * @param unknown_type $p_uid
     */
    public function getAllClicksByPlanner($p_uid){

        $p_uid = intval($p_uid);
        $sql = "select sum(view_num)  from lcs_view where p_uid='$p_uid'";
        return Yii::app()->lcs_r->createCommand($sql)->queryScalar();
    }

    /**
     * 获取所有发过观点的理财师uid
     *
     * @return unknown
     */
    public function getAllPlannerByView(){
        $sql = "select distinct(p_uid)  from lcs_view ";
        return Yii::app()->lcs_r->createCommand($sql)->queryColumn();
    }

    /**
     * 获取指定时间内观点抱内观点的数量
     */
    public function getViewCountInPkg($pkg_id, $start_time = null, $end_time = null)
    {
        $where = 'pkg_id=:pkg_id';
        if ($start_time) {
            $where .= ' AND p_time>=:s_time';
        }
        if ($end_time) {
            $where .= ' AND p_time<=:e_time';
        }
        $sql = "SELECT COUNT(*) AS total FROM " . $this->tableName() . " WHERE {$where}";
        $command = Yii::app()->lcs_r->createCommand($sql);
        $command->bindParam(':pkg_id', $pkg_id, PDO::PARAM_INT);
        $start_time && $command->bindParam(':s_time', $start_time, PDO::PARAM_STR);
        $end_time && $command->bindParam('e_time', $end_time, PDO::PARAM_STR);
        return $command->queryScalar();
    }
    /**
     * //观点相关数 和 最后时间
     * @param type $begin_time 开始统计时间
     * @return type
     */
    public function getViewTagSdata($begin_time){
        $view_count_sql = "SELECT tag_id,COUNT(tag_id) AS `count`,MAX(c_time) AS c_time FROM ".$this->tableNameSdata()." WHERE c_time>'{$begin_time}' GROUP BY tag_id";
        $view_count_data = Yii::app()->lcs_r->createCommand($view_count_sql)->queryAll();
        $result = array();
        if(!empty($view_count_data)){
            foreach ($view_count_data as $item){
                $result[$item['tag_id']] = array(
                    'count'=>$item['count'],
                    'c_time'=>$item['c_time']
                );
            }
        }
        return $result;
    }

    /**
     * 统计理财师回答问题数排行
     */
    public function getPlannerRankByViewNum($ind_id = NULL)
    {
        $where = '';
        if ($ind_id !== NULL & in_array($ind_id, range(1, 8))) {
            $where .= " a.ind_id={$ind_id} AND ";
        }
        $start_date = date('Y-m-d 00:00:00',  strtotime("-30 days"));
        $end_date = date('Y-m-d 00:00:00');
        $sql = 'SELECT a.`p_uid`, COUNT(*) AS total FROM '.$this->tableName(). " AS a WHERE {$where} a.`c_time`>=:start_time AND a.`c_time`<:end_time GROUP BY a.`p_uid` ORDER BY total DESC LIMIT 10";

        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':start_time', $start_date, PDO::PARAM_STR);
        $cmd->bindParam(':end_time', $end_date, PDO::PARAM_STR);

        $list = $cmd->queryAll();
        return $list;
    }

    /*
     * 1400
     * 获取理财师30天内发布基金类观点阅读量最多的一条
     */
    public function getMostViewNumof30Days($p_uids=array(),$ind_id=2){
        if(empty($p_uids)){
            return;
        }
        $p_uids = (array)$p_uids;
        $where = '';
        if ($ind_id !== NULL & in_array($ind_id, range(1, 8))) {
            $where .= " a.ind_id={$ind_id} AND a.`status`=0 AND ";
        }
        $start_date = date('Y-m-d 00:00:00',  strtotime("-30 days"));
        $end_date = date('Y-m-d 00:00:00');
        $sql = "select p_uid,v_id,view_num from (SELECT p_uid,id v_id,view_num FROM `lcs_view` a where {$where} a.`c_time`>=:start_time AND a.`c_time`<:end_time and p_uid in (".implode(',',$p_uids).") order by a.view_num desc) t group by p_uid";
        $cmd = Yii::app()->lcs_r->createCommand($sql);
        $cmd->bindParam(':start_time', $start_date, PDO::PARAM_STR);
        $cmd->bindParam(':end_time', $end_date, PDO::PARAM_STR);
        
        $list = $cmd->queryAll();
        return $list;
    }


    /**
     * 更新数量
     * @param $v_id
     * @param string $field
     * @param string $oper
     * @param int $num
     * @return mixed
     */
    public function updateNumber($v_id, $field = 'sub_num', $oper = "add", $num = 1) {
        $v_id = intval($v_id);
        $num = intval($num);
        $field = !empty($field) ? $field : "sub_num";
        $sql = "update " . $this->tableName() . " set $field=" . ($oper == 'add' ? "$field+$num" : "$field-$num") . " where id=$v_id";
        return Yii::app()->lcs_w->createCommand($sql)->execute();

    }

    /**
     * 获取未推送的观点id
     */
    public function getViewNotPush($num=1){
        $time = date("Y-m-d H:i:s",strtotime("-1 day"));
        $sql = "select id from lcs_view where status=0 and is_push=0 and p_time>='$time' order by id desc limit $num";
        $data = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        return $data;
    }

    /**
    * 更新观点
    */
    public function updateViewInfo($view_info,$id){
        $sql = "update ".$this->tableName()." set ";
        foreach($view_info as $key=>$data){
            $sql = $sql ." $key=:$key ,";
        }
        $sql = rtrim($sql,',');
        $sql = $sql." where id='$id'";
        $cmd = Yii::app()->lcs_w->createCommand($sql);
        foreach($view_info as $key=>$data){
            $cmd->bindParam(":$key", $view_info[$key], PDO::PARAM_STR);
        }
        $cmd->execute();
        $this->delViewMc($id);        
    }

    /**
     * 获取发布的观点信息
     *@param   int $type 1 两个小时内 0 两天内
     */
    public  function  getTwoDayView($type=0){
        if($type==1){
            $time1=date('Y-m-d H:i:s',strtotime('-2 hours'));
            $time=date('Y-m-d H:i:s');
        }else{
            $time1=date('Y-m-d H:i:s',strtotime('-2 day'));
            $time=date('Y-m-d H:i:s',strtotime('-2 hours'));
        }
        $data=array();
        $sql_count="SELECT count(id) from ".$this->tableName()." where status=0 and p_time>='$time1' and p_time<='$time'";
        $data['total']=Yii::app()->lcs_standby_r->createCommand($sql_count)->queryScalar();
        $sql="SELECT id,praise_num from ".$this->tableName()." where status=0 and p_time>='$time1' and p_time<='$time'";
        $data['data'] = Yii::app()->lcs_standby_r->createCommand($sql)->queryAll();
        return $data;
    }

    /**
     * 点赞保存日志
     * @param $data
     * @return mixed
     */
    public function savelog($data){
        if(empty($data)){
            return false;
        }
        $data['c_time']=date("Y-m-d H:i:s");
        $data['u_time']=date("Y-m-d H:i:s");
        return Yii::app()->lcs_w->createCommand()->insert($this->tableNameLog(), $data);
    }

    /**
     * 根据文章id获取内容
     *
     * @param unknown_type $id
     */
    public function getViewExtraById($id){

        $id = intval($id);
        $sql = "SELECT pkg_id,p_uid,content_pay,quote_id,quote_title,praise_num,sub_num,collect_num,comment_num,real_view_num,share_wb,share_wm,price,status,source,subscription_price,against_num,type,media_url,media_image,media_data,partner_id,recommend,isblack,remark,media2_type,media2_url,media2_image,media2_data,c_time,u_time FROM ".$this->tableName()." WHERE id='$id'";
        $return_data =  Yii::app()->lcs_standby_r->createCommand($sql)->queryRow();

        return $return_data;
    }

    /**
     * 根据观点包获取最近一分钟发布的观点
     * @param $pkg_id
     */
    public function getOneMinuteView($pkg_id,$p_time){
        $sql = "select id,title,content from ".$this->tableName()." where pkg_id=:pkg_id and status=0 and p_time>:p_time";
        $command = Yii::app()->lcs_r->createCommand($sql);
        $command->bindParam(':pkg_id', $pkg_id, PDO::PARAM_INT);
        $command->bindParam(':p_time', $p_time, PDO::PARAM_STR);
        return $command->queryAll();
    }
    /**
     * 查询动态视频阿里云回调出问题
     */
    public function getVideoImagesAliyun()
    {
        $sql_count="SELECT video_id,id from ".$this->tableName()." where return_status=1 and cover_images='https://' and video_id!=''";

        $data = Yii::app()->lcs_r->createCommand($sql_count)->queryAll();
        return $data;
    }

    /**
     * 获取观点视频
     * @param $p_uid
     * @param $lastId
     * @param int $pageSize
     * @return mixed
     */
    public function getVideoList($time1)
    {
        $time1='';
        $lcsPlannerTbName = Planner::model()->tableName();
        $where = "a.video_id!=''  and a.status =0 and return_status=1 and a.subscription_price=0";
        if (!empty($time1)){

            $where .= " and a.c_time>='$time1'";
        }
        $db_r = Yii::app()->lcs_r;
        $sql = "select a.video_id as vid,a.c_time as publishedAt,a.cover_images as cover,b.s_uid as id,b.name as nickname,b.image as avatar,b.summary as description,a.title from " . $this->tableName() .
            " a left join $lcsPlannerTbName b on a.p_uid=b.s_uid  where $where";
        $cmd = $db_r->createCommand($sql);
        return $cmd->queryAll();
    }

    /**
     * 修改动态images
     * @param $params
     * @return bool
     */
    public function updateImgurl($params)
    {
        $connection = Yii::app()->lcs_w;
        $updateArr['cover_images'] = $params['imgurl'];
        $updateArr['u_time'] = date("Y-m-d H:i:s");
        return $connection->createCommand()->update($this->tableName(), $updateArr, 'id='.$params['id']);
    }

}
