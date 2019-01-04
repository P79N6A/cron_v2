<?php

class PlannerExt extends CActiveRecord {
	
	
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
	
	public function tableName(){
		return TABLE_PREFIX .'planner_ext';
	}


    /**
     * 获取理财师计划年收益率最多的排序
     * @param int $num
     * @return array
     */
    public function getPlannerPlanYearRateTop($num=50){
        $mc_key = MEM_PRE_KEY . 'p_plan_year_rate_50';
        $cache = Yii::app()->cache->get($mc_key);
        if($cache === false) {
            $sql = "select s_uid,pln_success_rate,pln_year_rate from ".$this->tableName()." where pln_num>1 order by pln_year_rate desc limit 0,50;";
            $cmd = Yii::app()->lcs_r->createCommand($sql);
            $res = $cmd->queryAll();

            if($res) {
                Yii::app()->cache->set($mc_key, $res, 86400); //一天
                $cache = $res;
            }
        }

        if(!empty($cache) && $num<50 && $num>0){
            $cache = array_slice($cache,0,$num);
        }
        return $cache;
    }
	
	
	/**
	 * 根据理财师额外信息获取列表数据
	 *
	 */
	public function getPlannerExtList($page,$num,$field,$order='desc',$where=''){
		
		$field = strtolower($field);
		$order = strtolower($order);
		$allow_field = array('pln_year_rate','pln_p_comment_num','pln_total_profit');
		if(!in_array($field,$allow_field,true) || !in_array($order,array('asc','desc'),true)){
			return array();
		}
		$start = CommonUtils::fomatPageParam($page,$num);
		
		$sql_count = "select count(s_uid) from ".$this->tableName();
		$count = Yii::app()->lcs_r->createCommand($sql_count)->queryScalar();
		
		$where = "where b.partner_id=0";
		if($field == 'pln_total_profit'){//按年化收益排序 把只有一个计划的去掉 去掉郑芳芳
			$where .= " and a.pln_num>1 and a.s_uid not in (1655008812,1789578644) ";
			//新的逻辑去掉没有预运行和正在运行的理财师
			$sql = "select M.s_uid,M.p_uid,M.pln_num,M.pln_loss_num,M.pln_buy_num,M.pln_sell_num,M.pln_success_rate,M.pln_year_rate,M.pln_u_comment_num,M.pln_p_comment_num,M.pln_total_profit,M.pln_profit_num from (select b.s_uid,b.s_uid as p_uid,a.pln_num,a.pln_loss_num,a.pln_buy_num,a.pln_sell_num,a.pln_success_rate,a.pln_year_rate,a.pln_u_comment_num,a.pln_p_comment_num,a.pln_total_profit,a.pln_profit_num from ". $this->tableName() ." a,lcs_planner b where a.s_uid=b.s_uid and b.partner_id=0 and a.pln_num>1 and a.s_uid not in (1655008812,1789578644)) M,(select count(*) as total,c.p_uid from lcs_plan_info c where c.p_uid in (select b.s_uid as p_uid from ". $this->tableName()  . " a,lcs_planner b where a.s_uid=b.s_uid and b.partner_id=0 and a.pln_num>1 and a.s_uid not in (1655008812,1789578644)) and c.status in (2,3) group by c.p_uid) N where M.p_uid=N.p_uid and N.total>0 order by M.{$field} {$order} limit $start,$num";
		}else{
        		$sql = "select b.s_uid,b.s_uid as p_uid,pln_num,pln_loss_num,pln_buy_num,pln_sell_num,pln_success_rate,pln_year_rate,pln_u_comment_num,pln_p_comment_num,pln_total_profit,pln_profit_num from ".$this->tableName()." a left join lcs_planner b on a.s_uid=b.s_uid  $where order by $field $order limit $start,$num";                        
		}
		
		$data = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		$return_data =  CommonUtils::getPage($data,$page,$num,$count);      
		return $return_data;
		
	}
	
	/**
	 * 根据理财师id获取理财师扩展表详情
	 *
	 */
	public function getPlannerExtById($p_uids){	
		$p_uids = (array)$p_uids;
		if(empty($p_uids)){
			return array();
		}
		foreach ($p_uids as $key=>$val){
			$p_uids["$key"] = intval($val);
		}
		
		$sql = "select s_uid as p_uid,pln_profit_num,pln_total_profit,pln_max_ror,pln_num,pln_success_rate,pln_year_rate,pln_u_comment_num,pln_p_comment_num,activity,influence from ".$this->tableName()." where s_uid in (".implode(',',$p_uids).")";
		
		$res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		$return_data = array();
		if(!empty($res)){
			foreach ($res as $val){
				$return_data["$val[p_uid]"] = $val;
			}
		}
		return $return_data;
	}

    public function getPlannerExtByIds($p_uids){
        $p_uids = (array)$p_uids;
        if(empty($p_uids)){
            return array();
        }
        $mem_pre_key = MEM_PRE_KEY . "p_ext_";
        $p_uids = array_unique(array_filter((array) $p_uids));
        //从缓存获取数据
        $mult_key = array();
        foreach ($p_uids as $val) {
            $mult_key[] = $mem_pre_key . intval($val);
        }
        $cache = Yii::app()->cache->mget($mult_key);

        $no_cache_id = array();
        $return_data = array();
        foreach ($cache as $key => $val) {
            $v_key = str_replace($mem_pre_key, '', $key);  // 从缓存的key中截取 p_uid
            //TODO
            //$val=false;
            if ($val !== false) {
                $return_data[$v_key] = $val;
            } else {
                $no_cache_id[] = intval($v_key);
            }
        }
        unset($cache);
        if(!empty($no_cache_id)){
            //TODO
            $sql = "select s_uid as p_uid,pln_profit_num,pln_total_profit,pln_max_ror,pln_num,pln_success_rate,pln_year_rate,pln_u_comment_num,pln_p_comment_num,activity,influence, grade_plan,grade_plan_auto, grade_plan_status,grade_pkg,grade_pkg_auto,grade_pkg_status from ".$this->tableName()." where s_uid in (".implode(',',$no_cache_id).")";

            $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            if(!empty($res)){
                foreach ($res as $vals) {

                    //add by weiguang3 自动评级超过3的显示3
                    $vals['grade_plan'] = isset($vals['grade_plan'])?($vals['grade_plan_auto']==1&&$vals['grade_plan']>3?3:$vals['grade_plan']):0;
                    $vals['grade_pkg'] = isset($vals['grade_pkg'])?(($vals['grade_pkg_auto']==1&&$vals['grade_pkg']>3?3:$vals['grade_pkg'])):0;


                    //cache缓存 缓存一周
                    Yii::app()->cache->set($mem_pre_key . $vals['p_uid'], $vals, 3600);
                    $return_data[$vals['p_uid']]=$vals;
                }
            }
        }

        return $return_data;
    }


    /**
     * 获取理财师的观点数量
     *
     */
    public static function getPlannerViewNumber($p_uids){
        $result = array();
        $sql = "select p_uid,count(*) as total from lcs_view where p_uid in (".implode(',',$p_uids).") and status=0 group by p_uid";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if(!empty($res)){
            foreach($res as $item){
                $result[$item['p_uid']] = $item['total'];
            }
        }
        return $result;
    }

    /**
     * 获取理财师的问答数量数量
     *
     */
    public static function getPlannerAskNumber($p_uids){
        $result = array();
        $sql = "select p_uid,count(*) as total from lcs_ask_answer where p_uid in (".implode(',',$p_uids).") group by p_uid";
        $res = Yii::app()->lcs_r->createCommand($sql)->queryAll();
        if(!empty($res)){
            foreach($res as $item){
                $result[$item['p_uid']] = $item['total'];
            }
        }
        return $result;
    }
}
