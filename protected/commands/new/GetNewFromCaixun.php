<?php
/**
 * 将观点从上海财讯处同步到本地
 *
 */
class GetNewFromCaixun{
	const CRON_NO = 1601; //任务代码
	private $sourceCodesList = array(3=>'新闻', 5=>'新浪港美股', 6=>'格隆汇新闻');
	
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
            $time_redis = Yii::app()->redis_r->get(MEM_PRE_KEY."getnewcaixun_time");
            if(empty($time_redis)){
                ///获取未推送的观点
                $time = strtotime("-5 minute",time());
                ///毫秒级别
                $time = $time."000";
            }else{
                $time = $time_redis;
            }
            $data = NewService::getNewFromCX($time);

            if(isset($data['currentTime'])){
                $time = $data['currentTime'];
            }
            if(!empty($data) && !empty($data['datas'])){
                foreach($data['datas'] as $item){
                    $this->saveOrUpdateNew($item);
                }
            }
            Yii::app()->redis_w->set(MEM_PRE_KEY."getnewcaixun_time",$time);
        }catch(Exception $e){
		    var_dump($e->getMessage());
            Common::model()->saveLog("保存财讯新闻日志错误".$e->getMessage(),"error","caixun_new");
        }
    }

    /**
     * 保存新闻
     *
     * @param   array   $view 新闻详情
     */
    private function saveOrUpdateNew($new){
        
	$caixun_id = 0;
        $n_id = 0;
        if(isset($new['id'])){
            $caixun_id = $new['id'];
            $new['n_id'] = $new['id'];
        }

        unset($new['id']);
        unset($new['oid']);
	
        $old_new = NewCaixun::model()->getNewByOldId($caixun_id,"id");//var_dump($old_new);die;
        if(empty($old_new)){
            //新增财讯新闻
	    var_dump("新增财讯新闻",$new);
            $res = NewCaixun::model()->addNew($new);
	    $new_id = $res;//新增的新闻id
        }else{
	    $new_id = $old_new;//如果已经存在
	    $new['id'] = $new_id;
            //更新财讯新闻
	    var_dump("更新财讯新闻",$new);
            $res = NewCaixun::model()->updateNew($new);
        }

	//如果新闻相关的股票有的话就新增对应关系
	if(!empty($new['stockCodes'])){
	    $params = array('new_id'=>$new_id, 'stockCodes'=>$new['stockCodes'], 'status'=>$new['status'], 'showTime'=>$new['showTime']);
	    $this->updateRelateStock($params);
	}
    }


    /**
    * 更新新闻相关的股票
    */
    private function updateRelateStock($params){
	$new_id = $params['new_id'];
	$stocks = $params['stockCodes'];
	$status = $params['status']==2 ? '0' : '-1';
	$showTime = date('Y-m-d H:i:s', substr($params['showTime'],0,10));
        ///先删除新闻相关股票，然后添加进去
        NewCaixun::model()->deleteSymbolByNewId($new_id);
        $now = date("Y-m-d H:i:s",time());
       	$stocks = array_unique($stocks); 
       
	foreach($stocks as $code){
            NewCaixun::model()->addSymbolNewRelation("('$new_id','stock_cn','$code','$status','$showTime','$now','$now')");
        }
	
    }
}
