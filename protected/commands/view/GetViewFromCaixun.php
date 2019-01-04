<?php
/**
 * 将观点从上海财讯处同步到本地
 *
 */
class GetViewFromCaixun{
	const CRON_NO = 1209; //任务代码
	
	public function __construct(){
	
	}

    public function process(){
        $start = time();
        $end = time()+60;
        while($start<$end){
            $this->processMsg();
            sleep(1);
            $start = time();
        }
    }    

    /**
     * 具体处理消息
     */
    public function processMsg() {
        try{
            $time_redis = Yii::app()->redis_r->get(MEM_PRE_KEY."getviewcaixun_time");
            if(empty($time_redis)){
                ///获取未推送的观点
                $time = strtotime("-5 minute",time());
                ///毫秒级别
                $time = $time."000";
            }else{
                $time = $time_redis;
            }
            $data = ViewService::getViewFromCX($time);
            if(isset($data['currentTime'])){
                $time = $data['currentTime'];
            }
            if(!empty($data) && !empty($data['datas'])){
                foreach($data['datas'] as $item){
                    $this->saveOrUpdateView($item);
                }
            }
            Yii::app()->redis_w->set(MEM_PRE_KEY."getviewcaixun_time",$time);
        }catch(Exception $e){
            Common::model()->saveLog("保存财讯日志错误".$e->getMessage(),"error","caixun_view");
            exit;
        }
    }

    /**
     * 保存观点
     *
     * @param   array   $view 观点详情
     */
    private function saveOrUpdateView($view){
        $caixun_id = 0;
        $v_id = 0;
        if(isset($view['id'])){
            $caixun_id = $view['id'];
            $view['old_id'] = $view['id'];
        }

        if(isset($view['oid'])){
            $v_id = $view['oid'];
            $view['old_vid'] = $view['oid'];
        }

        unset($view['id']);
        unset($view['oid']);

        $old_view = ViewCaixun::model()->getViewByOldId($caixun_id,"id");
	
        ///如果观点id不空且是理财师来源，则需要更新原始观点内容
        if(!empty($v_id) && $view['sourceCode']==2){
	        var_dump("更新本地观点",$view);
            $this->updateView($view);
            ///删除缓存
	        $url = "http://licaishi.sina.com.cn/cacheApi/DelView";
	        Yii::app()->curl->post($url,array("v_id"=>$v_id));
        }

        if(empty($old_view)){
            ///新增财讯观点
	        var_dump("新增财讯观点",$view);
            $res = ViewCaixun::model()->addView($view);
        }else{
            ///更新财讯观点
	        var_dump("更新财讯观点",$view);
            $res = ViewCaixun::model()->updateView($view);
        }

        ///保存专题内容
        if(isset($view['subjectIds'])){
            ViewCaixun::model()->updateViewThemeRelation($caixun_id,$v_id,$view['subjectIds']);
        }
    }

    /**
     * 更新理财师原有id
     */
    private function updateView($view){
        $update_info = array();
        //更新观点的内容
        if(isset($view['freeContent'])){
            $update_info['content'] = $view['freeContent'];
        }

        //更新付费内容，仅限付费观点
        if(isset($view['payContent']) && $view['fee']>0){
            $update_info['content_pay'] = $view['payContent'];
        }

        //更新理由
        if(isset($view['remark'])){
            $update_info['remark'] = $view['remark'];
        }
        //更新标题
        if(isset($view['title'])){
            $update_info['title'] = $view['title'];
        }
        //更新是否
        if(isset($view['recommend']))
        {
            if($view['recommend'] === '0')
            {
		$page_tag = 1;//page的标志,用来分辨是否是真的是财讯推荐或者要置顶的
                $page_cfg_info = array();
                $page_cfg_info['title'] = $view['title'];
                $page_cfg_info['sequence'] = 0;
                $page_cfg_info['status'] = 0;
                $page_cfg_info['c_time'] = date('Y-m-d H:i:s', substr($view['recommendTime'], 0, 10));
                //推荐
                ViewCaixun::model()->addPageCfgInfo($view['old_vid'], $page_cfg_info, $page_tag);
            }
            else if($view['recommend'] === '1')
            {
                //推荐且置顶
                //置顶
		$page_tag = 1;//page的标志
                $page_cfg_info = array();
                $page_cfg_info['title'] = $view['title'];
                $page_cfg_info['sequence'] = 1;
                $page_cfg_info['status'] = 0;
                $page_cfg_info['c_time'] = date('Y-m-d H:i:s', substr($view['recommendTime'], 0, 10));
                ViewCaixun::model()->addPageCfgInfo($view['old_vid'], $page_cfg_info, $page_tag);
            }
            else if($view['recommend'] === ''){
                //取消推荐
		$page_tag = 0;//page的标志
                $page_cfg_info = array();
                $page_cfg_info['title'] = $view['title'];
                $page_cfg_info['sequence'] = 0;
                $page_cfg_info['status'] = -1;
                $page_cfg_info['c_time'] = date('Y-m-d H:i:s', substr($view['recommendTime'], 0, 10));
                ViewCaixun::model()->addPageCfgInfo($view['old_vid'], $page_cfg_info, $page_tag);
            }
        }
	
        //更新观点的状态,不发送状态lixiang
        if(isset($view['status'])){
            $update_info['caixun_status'] = $view['status'];
	      /*  $view_list = View::model()->getViewById($view['old_vid']);
	        if(isset($view_list[$view['old_vid']])){
		        $p_uid = isset($view_list[$view['old_vid']]['p_uid'])?$view_list[$view['old_vid']]['p_uid']:0;
		        if(!empty($p_uid)){
            	    $this->sendMessage($view['status'],$p_uid,$view['title']);
		        }
	        }*/
        }
	
        //更新观点的相关股票
        if(isset($view['stockCodes'])){
            $this->updateRelateStock($view['old_vid'],$view['stockCodes'],$view['freeContent']);
        }
        View::model()->updateViewInfo($update_info,$view['old_vid']);
    }

    /**
    * 根据审核状态给理财师发送短信
    * @param    int $status
    * @param    int $p_uid
    * @param    str $view_title
    */
    private function sendMessage($status,$p_uid,$view_title){
        $sub_title = mb_substr($view_title,0,10,'utf-8');
        if($status==1){
            $content = "观点<".$sub_title.">审核不通过,如有疑惑，联系021-36129996";
        }elseif($status==3){
            $content = "观点<".$sub_title.">审核下架,如有疑惑，联系021-36129996";
        }else{
            return;
        }

        $planner_list = Planner::model()->getPlannerById($p_uid);
        if(isset($planner_list[$p_uid])){
            $phone = $planner_list[$p_uid]['phone'];
            if($phone!=""){
                $content = iconv("UTF-8", "GB2312//IGNORE", $content);
                CommonUtils::sendSms($phone,urlencode($content));
            }
        } 
    }

    /**
    * 更新观点相关的股票
    */
    private function updateRelateStock($v_id,$stocks,$freeContent = ''){
	//为指数对应的股票fix
	if(!empty($freeContent)){
	    $symbol_list = ViewService::getRelateStock(array("content"=>$freeContent,"ind_id"=>1));
	    foreach($symbol_list as $symbol){
		$stocks[] = $symbol;
	    }
	    $stocks = array_unique($stocks);
	}

        ///先删除观点相关股票，然后添加进去
        Symbol::model()->deleteSymbolByTypeId("view",$v_id);
        $now = date("Y-m-d H:i:s",time());
        
        foreach($stocks as $code){
            Symbol::model()->addSymbolRelation("('1','stock_cn','$code','$v_id','$now','$now')");
        }
    }
}
