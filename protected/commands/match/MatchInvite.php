<?php
/**
 * 大赛推送
 */
class MatchInvite
{

    //任务代码
    const CRON_NO_REINVITERANK='20180627';

    /**
     * 获取邀请榜入榜信息
    */
    public function getinvitenum(){
        $invites = array();
        $offset = 0;
        while(1){
            $invite = array();
            $invite = Match::model()->getInviteNum($offset,100);
            if(empty($invite)){
                break;
            }
            $offset = ($offset+1)*100;
            $invites = array_merge($invites,$invite);
        }
        $addinvite = $this->addTopRank();
        if(!empty($addinvite)){
            $invites = array_merge($invites,$addinvite);
        }
        $num = array();
        $Id = array();
        foreach ($invites as $k => $v){
            $num[$k] = $v['num'];
            $Id[$k] = $v['parent_id'];
        }
        array_multisort($num,SORT_DESC,$Id,SORT_DESC,$invites);
        return $invites;
    }

    /**
     * 手动修改邀请榜参数录取
    */
    private function addTopRank(){
        return ;
        $rank = [
            [
                'num' => '1000',
                'parent_id'=> '1714',
            ],
            [
                'num' => '1001',
                'parent_id'=> '1713',
            ],
        ];
        return $rank;
    }

    /**
     * 重新记录邀请榜到redis中
    */
    public function makeinvite($invites){
        $redis = Yii::app()->redis_w;
        $key = "lcs_baiwangushen_reward";
        $redis->delete($key);
        foreach ($invites as $v){
           $redis->zadd($key,$v['num'],$v['parent_id']);
        }
        echo "更新邀请榜成功！\r\n";
    }
}