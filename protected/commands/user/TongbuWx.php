<?php
/**
 * 保存用户信息到es
 */
class TongbuWx
{

	//任务代码
	const CRON_NO=14020 ;
	/**
	 * 入口
	 */
	public function process(){		
		$count = 0;
		$testData = array();
		try {
			for ($i=9; $i <= 9; $i++) {
				$tableName = "lcs_user_".$i;
				echo $tableName."\r\n";
				$sql = "SELECT `uid`,`s_uid`,`wx_public_uid` From $tableName WHERE wx_public_uid!=''";
				echo $sql."\r\n";
				$data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
				foreach ($data as $key => $value) {
					$sql = "SELECT * from lcs_message_channel_user WHERE uid=".$value['uid']." and channel_type=1 and c_time>='2018-05-18 11:30:00'";
					echo $sql."\r\n";
					$datas = Yii::app()->lcs_r->createCommand($sql)->queryAll();
					$status = false;
					if(!empty($datas)){
						foreach ($datas as $key => $v) {
							//同步时间
							//删除对应的channel_id
							$sql = "DELETE FROM lcs_message_channel_user WHERE id=".$v['id']." and channel_type=1;";
							echo $sql."\r\n";
							$db_w = Yii::app()->lcs_w;
							$r_cmd = $db_w->createCommand($sql);
							$r_count = $r_cmd->execute();
						}
						$status = true;
					}
					if($status){
						$sql = "select * from lcs_message_channel_user where channel_id='".$value['wx_public_uid']."';";
						$da = Yii::app()->lcs_r->createCommand($sql)->queryAll();
						echo "sql:".$sql."\r\n";
						if(!empty($da)){
							echo "跳过同步\r\n";
							continue;
						}

						$sql = "INSERT INTO lcs_message_channel_user (`channel_type`,`channel_id`,`u_type`,`s_uid`,`uid`,`c_time`,`u_time`) VALUES ('1','".$value['wx_public_uid']."','1','".$value['s_uid']."','".$value['uid']."',now(),now());";
						$db_w = Yii::app()->lcs_w;
						$r_cmd = $db_w->createCommand($sql);
						$r_count = $r_cmd->execute();
						if($r_count){
							echo $sql."\r\n";
							$count++;
							$testData[$value['uid']]['wx_public_uid'] = $value['wx_public_uid'];
							$testData[$value['uid']]['s_uid'] = $value['s_uid'];
							$testData[$value['uid']]['uid'] = $value['uid'];
							
						}else{
							continue;
						}
					}
				}
			}
			echo "num:".$count."\r\n";
			var_dump($testData);
		} catch (Exception $e){
			echo $e->getMessage();
		}
	}
}
