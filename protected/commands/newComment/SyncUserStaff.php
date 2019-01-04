<?php

/**
 * Desc  : 同步理财小妹
 */
class SyncUserStaff {    

    public function __construct() {
    }
    
    public function sync(){
		$sql = "select uid,staff_uid,c_time,u_time from lcs_user_freeze where type =3 and status = 0";
		$res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		print_r($res);	
		foreach($res as $info){
			$res = Yii::app()->lcs_comment_w->createCommand()->insert('lcs_comment_staff' , $info);
			var_dump($res);

		}
    }
    
}
