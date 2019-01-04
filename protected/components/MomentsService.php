<?php
/**
 * Moments Service
 * add by zhihao6 2017/02/13
 */

class MomentsService
{
    const MOMENTS_CMN_TYPE = 81; // moments预留版本 对应说说的类型（以用户为主体）
    const MOMENTSV1_CMN_TYPE = 82; // moments第一版(以理财师为主体) 对应说说的类型
    const MOMENTS_RELATION_ID = 0; // moments 对应说说的类型的relation_id

    private $crc32_id;
    private $cmn_tb_index;

    public function __construct()
    {
        $cmn_type = self::MOMENTSV1_CMN_TYPE;
        $relation_id = self::MOMENTS_RELATION_ID;

        $tb_index_info = NewComment::model()->getTbIndex($cmn_type, $relation_id);
        $this->crc32_id = $tb_index_info['crc32_id'];
        $this->cmn_tb_index = $tb_index_info['tb_index'];
    }

    // =====================================================================
    // 用户关注、购买的情况
    // =====================================================================
    
    // 观点列表生产用户moments
    public function batchSaveUserViewCmn($uid, $view_type, $view_list)
    {
        $cmn_type = self::MOMENTS_CMN_TYPE;
        $relation_id = self::MOMENTS_RELATION_ID;
        $content = ($view_type == 1) ? 'vip动态' : '公共动态';
        $is_anonymous = ($view_type == 1) ? 1 : 0;
        $valus = "(cmn_type,relation_id,crc32_id,u_type,uid,content,is_display,is_anonymous,discussion_type,discussion_id,c_time,u_time) VALUES ";
        foreach ($view_list as $view) {
            $valus .= "({$cmn_type},{$relation_id},{$this->crc32_id},1,{$uid},'{$content}',1,{$is_anonymous},2,{$view['id']},'{$view['p_time']}','{$view['p_time']}'),";
        }
        $valus = trim($valus, ',');

        $db = Yii::app()->lcs_comment_w;
        $db->active = false;
        $db->active = true;
        $transaction = $db->beginTransaction();
        try {
            // master表
            $sql = "INSERT INTO lcs_comment_master {$valus}";
            print_r("{$sql}\n\n");
            $res = $db->createCommand($sql)->execute();

            // 分表
            $sql = "INSERT INTO lcs_comment_{$this->cmn_tb_index} {$valus}";
            print_r("{$sql}\n\n");
            $res = $db->createCommand($sql)->execute();
            
            // index表
            $num = count($view_list);
            $u_time = date('Y-m-d H:i:s');
            $sql = "UPDATE lcs_comment_index_num SET comment_num=comment_num+{$num}, u_time='{$u_time}' WHERE crc32_id={$this->crc32_id}";
            print_r("{$sql}\n\n");
            $res = $db->createCommand($sql)->execute();

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollback();
        }
    }

    // 删除观点列表对应的用户moments
    public function batchDeleteUserViewCmn($uid, $view_type, $view_list)
    {
        $cmn_type = self::MOMENTS_CMN_TYPE;
        $relation_id = self::MOMENTS_RELATION_ID;
        $is_anonymous = ($view_type == 1) ? 1 : 0;

        $view_ids = [];
        foreach ($view_list as $view) {
            $view_ids[] = $view['id'];
        }
        $where = "cmn_type={$cmn_type} and relation_id={$relation_id} and u_type=1 and uid={$uid} and discussion_type=2 and discussion_id IN (".implode(",", $view_ids).")";

        $db = Yii::app()->lcs_comment_w;
        $db->active = false;
        $db->active = true;
        $transaction = $db->beginTransaction();
        try {
            // master表
            $sql = "DELETE FROM lcs_comment_master WHERE {$where}";
            print_r("{$sql}\n\n");
            $res = $db->createCommand($sql)->execute();
            if ($res === 0) {
                throw new Exception("没有待删除的观点moments");
            }

            // 分表
            $sql = "DELETE FROM lcs_comment_{$this->cmn_tb_index}  WHERE {$where}";
            print_r("{$sql}\n\n");
            $res = $db->createCommand($sql)->execute();
            if ($res === 0) {
                throw new Exception("没有待删除的观点moments");
            }
            
            // index表
            $num = count($view_list);
            $u_time = date('Y-m-d H:i:s');
            $sql = "UPDATE lcs_comment_index_num SET comment_num=comment_num-{$num}, u_time='{$u_time}' WHERE crc32_id={$this->crc32_id}";
            print_r("{$sql}\n\n");
            $res = $db->createCommand($sql)->execute();

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollback();
        }
    }

    // =====================================================================
    // 理财师发的情况
    // =====================================================================

    // 免费观点生成moments
    public function freeViewMoments($view_id, $view_info=array())
    {
        if (empty($view_info)) {
            $view_map = View::model()->getViewById($view_id);
            $view_info = isset($view_map[$view_id]) ? $view_map[$view_id] : null;
        }
        if (empty($view_info)) {
            return ;
        }

        $pkg_map = Package::model()->getPackagesById($view_info['pkg_id'], false);
        if (isset($pkg_map[$view_info['pkg_id']]) && ($pkg_map[$view_info['pkg_id']]['subscription_price'] > 0)) {
            return ;
        }

        $attention_uids = Planner::model()->getAttentionUser($view_info['p_uid']);
        $attention_uids_arr = array_chunk($attention_uids, 500);
        foreach ($attention_uids_arr as $uids) {
            $this->batchSaveUserCmn(
                array_unique($uids),
                $this->cmn_tb_index,
                self::MOMENTS_CMN_TYPE,
                self::MOMENTS_RELATION_ID,
                $this->crc32_id,
                0,
                2,
                $view_id,
                $view_info['p_time']
            );
        }
    }

    // 付费观点生产moments
    public function chargeViewMoments($view_id, $view_info=array())
    {
        if (empty($view_info)) {
            $view_map = View::model()->getViewById($view_id);
            $view_info = isset($view_map[$view_id]) ? $view_map[$view_id] : null;
        }
        if (empty($view_info)) {
            return ;
        }

        $pkg_map = Package::model()->getPackagesById($view_info['pkg_id'], false);
        if (isset($pkg_map[$view_info['pkg_id']]) && ($pkg_map[$view_info['pkg_id']]['subscription_price'] > 0)) {
            $sub_uids = Package::model()->getSubscriptionUid($view_info['pkg_id']);
            $sub_uids_arr = array_chunk($sub_uids, 500);
            foreach ($sub_uids_arr as $uids) {
                $this->batchSaveUserCmn(
                    array_unique($uids),
                    $this->cmn_tb_index,
                    self::MOMENTS_CMN_TYPE,
                    self::MOMENTS_RELATION_ID,
                    $this->crc32_id,
                    1,
                    2,
                    $view_id,
                    $view_info['p_time']
                );
            }
        }
    }

    // 计划交易生产moments
    public function planTransMoments($trans_id, $trans_info=array())
    {
        if (empty($trans_info)) {
            $trans_map = PlanTransactions::model()->getTransListByTransIds($trans_id);
            $trans_info = isset($trans_map[$trans_id]) ? $trans_map[$trans_id] : null;
        }
        if (empty($trans_info)) {
            return ;
        }

        $sub_uids = Plan::model()->getSubPlanUids($trans_info['pln_id']);
        $sub_udis_arr = array_chunk($sub_uids, 500);
        foreach ($sub_udis_arr as $uids) {
            $this->batchSaveUserCmn(
                array_unique($uids),
                $this->cmn_tb_index,
                self::MOMENTS_CMN_TYPE,
                self::MOMENTS_RELATION_ID,
                $this->crc32_id,
                1,
                7,
                $trans_id,
                $trans_info['c_time']
            );
        }
    }


    // 生产一批用户的moments
    public function batchSaveUserCmn($uids, $cmn_tb_index, $cmn_type, $relation_id, $crc32_id, $is_anonymous, $discussion_type, $discussion_id, $c_time, $u_type = 1)
    {
        $uids = (array) $uids;
        $content = ($is_anonymous == 1) ? 'vip动态' : '公共动态';
        $head = "(cmn_type,relation_id,crc32_id,u_type,uid,content,is_display,is_anonymous,discussion_type,discussion_id,c_time,u_time) VALUES ";
        $value_arr = [];
        foreach ($uids as $uid) {
            //已经存在的则直接跳过，避免重复创建
            $is_exist = NewComment::model()->checkExist($uid, $u_type, $crc32_id, $is_anonymous, $discussion_type, $discussion_id);
            if ($is_exist > 0) {
                //throw new Exception("$discussion_id 已经存在");
                continue;
            }

            $value_arr[] = "({$cmn_type},{$relation_id},{$crc32_id},{$u_type},{$uid},'{$content}',1,{$is_anonymous},{$discussion_type},{$discussion_id},'{$c_time}','{$c_time}')";
        }

        if (empty($value_arr)) {
            return ;
        }
        $valus = $head . join(',', $value_arr);

        $db = Yii::app()->lcs_comment_w;
        $db->active = false;
        $db->active = true;
        $transaction = $db->beginTransaction();
        try {
            // master表
            $sql = "INSERT INTO lcs_comment_master {$valus}";
            print_r("{$sql}\n\n");
            $res = $db->createCommand($sql)->execute();

            // 分表
            $sql = "INSERT INTO lcs_comment_{$cmn_tb_index} {$valus}";
            print_r("{$sql}\n\n");
            $res = $db->createCommand($sql)->execute();
            
            // index表
            $num = count($value_arr);
            $u_time = date('Y-m-d H:i:s');
            $sql = "UPDATE lcs_comment_index_num SET comment_num=comment_num+{$num}, u_time='{$u_time}' WHERE crc32_id=" .$crc32_id;
            print_r("{$sql}\n\n");
            $res = $db->createCommand($sql)->execute();

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollback();
        }
    }

    /**
     * 排除已入库的，并批量写入动态流
     * @param $data
     * @return mixed
     */
    public function batchSaveMoment($data) {
        $insert_data = array();
        foreach ($data as $item) {
            $is_exist = Moments::model()->isExistMoments($item['uid'], $item['discussion_type'], $item['discussion_id']);
            //仅写入不存在的动态，避免后期维护时候重复添加
            if (!empty($is_exist)) {
                $insert_data[] = $item;
            }
        }

        $res = Moments::model()->batchAddMoments($insert_data);
        //若写入成功，返回写入数量
        return $res > 0 ? count($insert_data) : $res;
    }

    /**
     * 推送到理财师动态生成队列
     * @param $discussion_type
     * @param $discussion_id
     */
    public static function pushMomentQueue($discussion_type, $discussion_id)
    {
        $msg_data = array(
            'type'            => 'MomentsProducer',
            'discussion_type' => $discussion_type,
            'discussion_id'   => $discussion_id,
        );

        $redis_key = 'lcs_fast_message_queue';
        Yii::app()->redis_w->rPush($redis_key, json_encode($msg_data, JSON_UNESCAPED_UNICODE));
    }
}
