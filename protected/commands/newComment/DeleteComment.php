<?php

/**
 * Desc  : 从表中删除说说，并删除缓存
 * Author: meixin
 * Date  : 2015-11-26 16:49:52
 */
class DeleteComment {

	const CRON_NO = 4001; //任务代码

	public function __construct() {

	}

	public function delComment() {
		//从redis pop 数据
		$redis_key = MEM_PRE_KEY . "delete_comment";
		$res_num = 0;
		while ($commentInfo = Yii::app()->redis_w->lPop($redis_key)) {
			var_dump($commentInfo);
			//从分表中删除数据

			$commentInfo = json_decode($commentInfo, true);

			foreach ($commentInfo as $tb_index => $v) {
				$cmn_ids = array();
				$crc32_ids = array();
				$cache_ids = array();
				foreach ($v as $info) {
					$cmn_ids[] = $info['cmn_id'];

					$cache_ids[] = $tb_index . '_' . $info['cmn_id'];
				}
				$comment_list = NewComment::model()->getCommentInfoFromNormal($tb_index, $cmn_ids);				

				$num = NewComment::model()->delCommentFromNormal($tb_index, $cmn_ids);
				#echo 'del--';
				$type_id_arr = array();
				$comment_num = $planner_comment_num = 0;

				foreach ($comment_list as $info) {
					$info['index_id'] = (NewComment::model()->getTableIndex($info['crc32_id'])) . '_' . $info['cmn_id'];
					$res = NewComment::model()->insertCommentToSpecial($info, $tb_type = 2);
					unset($info['index_id']);
					#echo 'insert--';

					$crc32_id = $info['crc32_id'];
					$cmn_id = $info['cmn_id'];
					//在主表中删除
					NewComment::model()->delCommentFromMaster($crc32_id, $cmn_id);
					
					if($info['cmn_type'] == 71){
						//更新圈子goim中的评论信息
						$args = array(
							'circle_id'=>$info['relation_id'],
							'cmn_id'=>$info['cmn_id'],
							'type'=>2
						);
						$url = "http://i.licaishi.sina.com.cn/inner/UpdateGoimCircleInfo";
						$_curl = Yii::app()->curl->setTimeOut(5);
						$args["access_token"] = CommonUtils::buildAccessToken($args);		
						$_curl->post($url, $args);
					} 
					
					//属于计划，观点(包),说说数量按一定条件判断是否减掉
					$old_cmn_type = array(1, 2, 3, 4);
					if (in_array($info['cmn_type'], $old_cmn_type)) {
						if ($info['is_display'] == 1 && $info['reply_id'] == 0) {
							$comment_num = isset($crc32_ids[$crc32_id]['comment_num']) ? $crc32_ids[$crc32_id]['comment_num'] + 1 : 1;
							$crc32_ids[$crc32_id]['comment_num'] = $comment_num;
							$type_id_arr[$crc32_id] = array(
								'cmn_type' => $info['cmn_type'],
								'relation_id' => $info['relation_id'],
								'child_relation_id' => $info['child_relation_id']
							);
						}
						$crc32_ids[$crc32_id]['comment_num'] = $comment_num;
					} else {
						$crc32_ids[$crc32_id]['comment_num'] = isset($crc32_ids[$crc32_id]['comment_num']) ? $crc32_ids[$crc32_id]['comment_num'] + 1 : 1;
					}
					if ($info['u_type'] == 2) {
						$crc32_ids[$crc32_id]['planner_comment_num'] = isset($crc32_ids[$crc32_id]['planner_comment_num']) ? $crc32_ids[$crc32_id]['planner_comment_num'] + 1 : 1;
					}
					//累计主表和分表的reply_num
					$root_reply_id = $info['root_reply_id'];
					if ($root_reply_id !== 0 && $info['is_display'] == 1) {

						$up_root_num = isset($crc32_ids[$crc32_id]['root_id'][$root_reply_id]) ? $crc32_ids[$crc32_id]['root_id'][$root_reply_id] + 1 : 1;

						$crc32_ids[$crc32_id]['root_id'][$root_reply_id] = $up_root_num;
					}
				}
				//减说总数表
				#echo "upindex++";
				foreach ($crc32_ids as $id => $val) {
					$num = isset($val['comment_num']) ? $val['comment_num'] : 0;
					$p_num = isset($val['planner_comment_num']) ? $val['planner_comment_num'] : 0;
					NewComment::model()->updatetbIndexNum($id, 'del', $num, $p_num);
					//修改不同模块下 info表里的 comment_num , 找到cmn_type类型
					if (isset($type_id_arr[$id])) {
						$type_rid = $type_id_arr[$id];
						$cmn_type = $type_rid['cmn_type'];
						$rid = $type_rid['relation_id'];
						if ($cmn_type == 1) {
							//plan::up
							Plan::model()->updateNumber($rid, 'comment_count', 'delete', $num);
						} elseif ($cmn_type == 2) {
							//package::up
							Package::model()->updateNumber($rid, 'comment_num', 'delete', $num);
							if (!empty($type_rid['child_relation_id'])) {
								View::model()->updateNumber($rid, 'comment_num', 'delete', $num);
							}
						} elseif ($cmn_type == 3 || $cmn_type == 4) {
							//topic::up
							Topic::model()->updateNumber($rid, 'comment_num', 'delete', $num);
						}
					}
					if (isset($val['root_id'])) {
						//更新主表和分表的reply_num
						$root_num_arr = $val['root_id'];
						foreach ($root_num_arr as $root_id => $up_num) {
							//update master set reply_num = $root_num where crc32_id =$id and cmn_id = $root_id
							Newcomment::model()->updateCommentMasterInc($id, $root_id, 'reply_num', $up_num);
							//根据crc32算分表index
							//update comment_index set reply_num = $root_num where cmn_id = $root_id
							$tb_index = $crc32_id % Newcomment::COMMENT_TABLE_NUMS;
							Newcomment::model()->updateCommentInc($tb_index, $root_id, 'reply_num', $up_num);
						}
					}

					#echo 'num--';
				}
				CommonUtils::saveDateFile(self::CRON_NO, date("Y-m-d H:i:s") . "\t" . json_encode($cache_ids) . "\n");
				$cache_res = CacheUtils::delNewComment($cache_ids);
				#echo 'cache--';
				if (!$cache_res) {
					CommonUtils::saveDateFile(self::CRON_NO, date("Y-m-d H:i:s") . "\t清除缓存失败\n" . json_encode($commentInfo) . "\n");
				}
				$res_num += $num;
			}
		}

		return $res_num;
	}

}
