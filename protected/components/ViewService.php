<?php

/**
 * 观点相关
 *
 */
class ViewService{
    /**
     * 从财讯获取专题列表
     * @param   int $lastTime   上次更新时间
     */
    public static function getViewZhuanti($lastTime){
        if(defined("ENV") && ENV == "dev"){
            $url = "http://116.236.205.27:1380/information/api/info/1/subject/list?lastTime=$lastTime&pageNo=1";
        }else{
            $url = "http://article.caixun99.com/api/info/1/subject/list?lastTime=$lastTime&pageNo=1";
        }
        $headers = array(
            "content-type = application/x-www-form-urlencoded",
            "Accept:application/json"
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        try{
            $result = curl_exec($ch);
		    var_dump($result);
            $result = json_decode($result,true);
            if(isset($result['code']) && $result['code']==1){
                return $result;
            }
            Common::model()->saveLog("从新财讯获取专题出错,".json_encode($result),"info","get_caixun_view_zhuanti");
            return false;
        }catch(Exception $e){
            Common::model()->saveLog("从新财讯获取专题出错,".$e->getMessage(),"error","get_caixun_view_zhuanti");
            return false;
        }
    }

    /**
     * 将观点推送道财讯
     * @param   int $v_id   观点id
     */
    public static function pushViewToCX($v_id){
        ViewService::pushViewToCMS($v_id);
        ViewService::pushViewToBAIDAO($v_id);
        if(defined("ENV") && ENV == "dev"){
	    $url = "http://test-message.caixun99.com/api/spider/1/test/article/receive";
	    //$url = "http://116.236.205.27:1380/spider/api/spider/1/licaishi/article/receive";
        }else{
            $url = "http://message.caixun99.com/api/spider/1/licaishi/article/receive";
        }
        $param = ViewService::getViewByIdCaixunFormat($v_id);
        if(empty($param)){
            return false;
        }
        #$data = http_build_query($param);
        $data = "";
        foreach($param as $k=>$v){
            $data = $data.$k."=".urlencode($v)."&";
        }
        $headers = array(
            "content-type = application/x-www-form-urlencoded",
            "Accept:application/json"
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        //财讯224服务挂掉了，为防止测试环境重复推送，故在测试环境期间做此修改
//        $result = curl_exec($ch);
//        return true;

        try{
            $result = curl_exec($ch);
		    var_dump($result);
            $result = json_decode($result,true);
            if(isset($result['code']) && $result['code']==1){
               return true;
            }
            Common::model()->saveLog("推送数据到新财讯,".json_encode($result),"info","push_caixun_view");
            return false;
        }catch(Exception $e){
            var_dump($e->getMessage());
            Common::model()->saveLog("推送数据到新财讯错误,".$e->getMessage(),"error","push_caixun_view");
            return false;
        }
    }
    /**
     * 将观点推送CMS
     * @param   int $v_id   观点id
     */
    public static function pushViewToCMS($v_id){
        if(defined("ENV") && ENV == "dev"){
            $url="http://kong-http.api-zq-dev.baidao.com/gmg-app/v1/articles";
        }else{
            $url="https://xlggapi.caixun99.com/gmg-app/v1/articles";
        }
        $param = ViewService::getViewByIdCmsFormat($v_id);
        if(empty($param)){
            return false;
        }
        $param=json_encode($param);
        echo $param;
        $headers = array(
            'Content-Type: application/json',
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        try{
            $result = curl_exec($ch);
            var_dump($result);
            $result = json_decode($result,true);
            if(isset($result['code']) && $result['code']==0){
                Common::model()->saveLog("推送数据到CMS成功,".json_encode($result).'---'.$param,"success","push_cms_view");
                return;
            }
            Common::model()->saveLog("推送数据到CMS失败,".$param,"error","push_cms_view");
            return;
        }catch(Exception $e){
            var_dump($e->getMessage());
            Common::model()->saveLog("推送数据到CMS失败,".$e->getMessage(),"error","push_cms_view");
            return false;
        }
    }

    /**
     * 推送文章到baidao
     * @param $v_id 观点id
     * @return bool
     */
    public static function pushViewToBAIDAO($v_id){
        if(defined("ENV") && ENV == "dev"){
            $url = "https://test-baidu-ai.baidao.com/viewpoint";
        }else{
            $url = "https://test-baidu-ai.baidao.com/viewpoint";
        }
        $param = ViewService::getViewByIdBaiDaoFormat($v_id);
        if(empty($param)){
            return false;
        }
        #$data = http_build_query($param);
        $data = "";
        foreach($param as $k=>$v){
            $data = $data.$k."=".urlencode($v)."&";
        }
        $headers = array(
            "content-type = application/x-www-form-urlencoded",
            "Accept:application/json"
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        try{
            $result = curl_exec($ch);
            var_dump($result);
            $result = json_decode($result,true);
            if(isset($result['code']) && $result['code']==1){
                return true;
            }
            Common::model()->saveLog("推送数据到baidao成功,".json_encode($result),"info","push_baidao_view");
            return false;
        }catch(Exception $e){
            var_dump($e->getMessage());
            Common::model()->saveLog("推送数据到baidao错误,".$e->getMessage(),"error","push_baidao_view");
            return false;
        }
    }
    /**
     * 推送给新财讯的观点格式
     *  @param  int $v_id   观点id
     */
    public static function getViewByIdCaixunFormat($v_id){
        try{
            $result = array();
            $view_list = View::model()->getViewById($v_id);
            if(!empty($view_list) && isset($view_list[$v_id])){
                $view_info = $view_list[$v_id];
            }else{
                return false;
            }
            if(!empty($view_info)){
                $content_info = View::model()->getViewContentById($v_id);
                $result['itemId'] = intval($view_info['ind_id']);
                $result['itemName'] = View::model()->getNameByIndId($result['itemId']);
                $result['id'] = $view_info['id'];
                $result['title'] = $view_info['title'];
                $result['authorId'] = $view_info['p_uid'];
                $planner = Planner::model()->getPlannerById($view_info['p_uid']);
                if(!empty($planner) && isset($planner[$view_info['p_uid']])){
                    $result['authorName'] = $planner[$view_info['p_uid']]['name'];
                }else{
                    $result['authorName'] = "";
                }
                $result['summary'] = $view_info['summary'];
                $result['fee'] = $view_info['subscription_price'];
                $result['freeContent'] = $content_info['content'];
                $result['feeContent'] = $content_info['content_pay'];
                $symbol_list = ViewService::getRelateStock(array("content"=>$content_info['content'],"ind_id"=>$view_info['ind_id']));
                $result['stocks'] = implode(',',$symbol_list);
                $result['showTime'] = $view_info['p_time'];
            }
            
            return $result;
        }catch(Exception $e){
            var_dump($e->getMessage());
        }
    }

    /**
    * 推送给CMS的观点格式
    *  @param  int $v_id   观点id
    */
    public static function getViewByIdCmsFormat($v_id){
        $result = array();
        $view_list = View::model()->getViewById($v_id);
        if(!empty($view_list) && isset($view_list[$v_id])){
            $view_info = $view_list[$v_id];
        }else{
            return false;
        }
        if(!empty($view_info) && $view_info['ind_id']==1){
            $extra=View::model()->getViewExtraById($v_id);
            $result['authorId'] = $view_info['p_uid'];
            if(defined("ENV") && ENV == "dev"){
                if($view_info['ind_id']==1) {
                    $result['categoryIds'] = 211;
                }
//                }else if($view_info['ind_id']==2){
//                    $result['categoryIds'] = 215;
//                }else if($view_info['ind_id']==3){
//                    $result['categoryIds'] = 216;
//                }else if($view_info['ind_id']==4){
//                    $result['categoryIds'] = 217;
//                }else if($view_info['ind_id']==5){
//                    $result['categoryIds'] = 218;
//                }else if($view_info['ind_id']==6){
//                    $result['categoryIds'] = 219;
//                }else if($view_info['ind_id']==7){
//                    $result['categoryIds'] = 220;
//                }else if($view_info['ind_id']==8){
//                    $result['categoryIds'] = 221;
//                }
                $result['platformId']=107;
                if($view_info['p_uid']=='1451326947'){
                    $result['authorId'] = 898;
                }else if($view_info['p_uid']=='6150188584'){
                    $result['authorId'] = 896;
                }else if($view_info['p_uid']=='2730594637' ||$view_info['p_uid']=='6567967440'){
                    $result['authorId'] = 897;
                }
            }else{
                if($view_info['ind_id']==1){
                    $result['categoryIds'] = 71;
                }
                $result['platformId']=29;
                if($view_info['p_uid']=='1451326947'){
                    $result['authorId'] = 343;
                }else if($view_info['p_uid']=='6150188584'){
                    $result['authorId'] = 344;
                }else if($view_info['p_uid']=='2730594637' ||$view_info['p_uid']=='6567967440'){
                    $result['authorId'] = 345;
                }
            }
            $result['description'] = $view_info['summary'];
            $result['title'] = $view_info['title'];
            $result['cover'] = !empty($view_info['image'])?'http://s3.licaishi.sina.com.cn/720/'.$view_info['image']:'';
            $result['content'] = $view_info['content'];
            $result['tags'] = $view_info['tags'];
            $result['click_num'] = $view_info['view_num'];
            $result['fake_click_num'] = $view_info['view_num']-$view_info['real_view_num'];
            $p_time = strtotime($view_info['p_time']);
            $result['publishTime']=str_pad($p_time,13,"0",STR_PAD_RIGHT);
            $result['extra']=json_encode($extra);
        }
        return $result;

    }

    /**
     * 推送给baidao的数据格式
     * @param $v_id 观点id
     * @return array
     */
    public static function getViewByIdBaiDaoFormat($v_id){
        try{
            $result = array();
            $view_list = View::model()->getViewById($v_id);
            if(!empty($view_list) && isset($view_list[$v_id])){
                $view_info = $view_list[$v_id];
            }else{
                return false;
            }
            if(!empty($view_info)){
                $content_info = View::model()->getViewContentById($v_id);
                $result['id'] = $view_info['id'];
                $result['title'] = $view_info['title'];
                $result['authorId'] = $view_info['p_uid'];
                $planner = Planner::model()->getPlannerById($view_info['p_uid']);
                if(!empty($planner) && isset($planner[$view_info['p_uid']])){
                    $result['authorName'] = $planner[$view_info['p_uid']]['name'];
                }else{
                    $result['authorName'] = "";
                }
                $result['summary'] = $view_info['summary'];
                $result['fee'] = $view_info['subscription_price'];
                $result['freeContent'] = $content_info['content'];
                $result['feeContent'] = $content_info['content_pay'];
                $symbol_list = ViewService::getRelateStock(array("content"=>$content_info['content'],"ind_id"=>$view_info['ind_id']));
                $result['stocks'] = implode(',',$symbol_list);
                $result['showTime'] = $view_info['p_time'];
                //观点分类ID及名称
//                $result['itemId'] = intval($view_info['ind_id']);
//                $result['itemName'] = View::model()->getNameByIndId($result['itemId']);
            }

            return $result;
        }catch(Exception $e){
            var_dump($e->getMessage());
        }
    }
    /**
     * 从财讯处同步观点
     * @param   int $time  最新更新的一次时间
     */
    public static function getViewFromCX($time){
        if(defined("ENV") && ENV == "dev"){
            $url = "http://116.236.205.27:1380/information/api/info/1/articles?lastArticleTime=$time";
        }else{
            $url = "http://article.caixun99.com/api/info/1/articles?lastArticleTime=$time";
        }
        $headers = array(
            #"content-type = application/x-www-form-urlencoded",
            "Accept:application/json"
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        try{
            $result = curl_exec($ch);
            $result = json_decode($result,true);
            var_dump($result);
            if(isset($result['code']) && $result['code']==1){
                return $result;
            }
            Common::model()->saveLog("从新财讯同步观点失败:".json_encode($result),"info","get_caixun_view");
            return false;
        }catch(Exception $e){
            Common::model()->saveLog("从新财讯同步观点异常:".$e->getMessage(),"error","get_caixun_view");
            return false;
        }
    }

    /**
     * 获取观点相关的股票内容
     */
    public static function getRelateStock($views){
	$symbol = array();
	$preg_content = $views['content'];
	$preg_symbols = SymbolService::getPregSymbols($preg_content,$views['ind_id']);
	if(!empty($preg_symbols)){
		foreach ($preg_symbols as $preg){
		    $symbol[] = $preg[1];
		}
	}
        return $symbol;
    }
}
