<?php
/**
 * Created by PhpStorm.
 * User: PanChaoYi
 * Date: 2017/10/23
 * Time: 17:15
 */
class VoteCount{

    const CRON_NO = 13202; //任务代码

    public function __construct(){

    }

    public function run(){
        $data = ActionVote::model()->VoteCount();
        ActionVote::model()->VoteRecord($data);
    }

}
