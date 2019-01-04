<?php
/**
 * 理财师聚合页后台定时任务
 * 该脚本通过一系列排序逻辑将数据推入redis
 * 前台接口直接读取redis数据即可
 * @author zhuoyuan
 */
class PlannerList
{
    const CRON_NO = 1099;
    const QUERY_DAY = 30; //理财师活跃的周期
    const QUERY_NUM = 30; //理财师展示个数
    private $redis_w;
    private $type;

    public function __construct()
    {
	$this->redis_w = Yii::app()->redis_w;
	$this->type = array(
        	            'view'=>2,
        	            'answer'=>3,
        	            'plan'=>4,
        	            'fans'=>5,
        	            'city'=>6,//只会返回活跃的理财师id,同城信息交由前台
        	            'service'=>7,//vip服务
			    );
    }

    public function plannerList()
    {
	foreach($this->type as $type=>$c){
	    $redis_key = MEM_PRE_KEY.'planner_list_'.$c;
	    $method = 'getPlanner'.ucfirst($type);
	    $result = call_user_func(array(Planner::model(),$method),self::QUERY_DAY,self::QUERY_NUM);
	    $this->redis_w->set($redis_key,serialize($result));
	}
    }
}
