<?php

/**
 * 大赛处理服务
 */
class NewMatchService {
    //获取战队的用户uids
    public static function getPlannerCorpsUids($planner_id){
        $data = Match::model()->getPlannerCorpsUids($planner_id);
        foreach ($data as $key=>$value){
            $uids[] = $value['uid'];
        }
        return $uids;
    }
    //获取用户战队的信息
    public static function getUserCorpsInfo($users_map)
    {
        if(!$users_map)
            return false;
        foreach ($users_map as $key => $value) {
            $uids[] = $key;
        }
        $data = Match::model()->getCorpsInfoByUids($uids);
        foreach ($data as $key => $value) {
            $corps[$value['uid']] = $value;
        }
        return $corps;
    }
    //获取赛事报道
    public static function getMatchEventReport($fr_web="",$page=1,$num=15){
        $returnData = Match::model()->getEventReport($fr_web,$page,$num);
        $data = $returnData["data"];
        if(!empty($data)){
            foreach ($data as $key=>$value){
                if($value['is_top'] == 1){
                    array_unshift($data,$value);
                    unset($data[$key+1]);
                }
                $v_ids[] = $value['v_id'];
            }
            $views = View::model()->getViewById($v_ids);
            foreach ($data as $key=>$value){
                $data[$key] = $views[$value['v_id']];
            }
            $returnData['data'] = array_values($data);
            return $returnData;
        }else {
            return array();
        }
    }
}
