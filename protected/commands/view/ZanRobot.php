<?php
/**
 * 点赞机器人
 */
class ZanRobot{
    const CRON_NO = 14020; //任务代码

    public function process(){
        //两个小时内点赞
        $views=View::model()->getTwoDayView(1);
        $dynamic=Dynamic::model()->getTwoDayDynamic(1);
        $datas = array_merge($views['data'],$dynamic['data']);
        $num1 =$dynamic['total'];
        $num =$views['total'];
        $rand=ceil(($num+$num1)/4);
        echo'前两个小时：'.$rand.'---';
        $this->handle($rand,$datas);
        //两天内点赞
        $views=View::model()->getTwoDayView();
        $dynamic=Dynamic::model()->getTwoDayDynamic();
        $datas = array_merge($views['data'],$dynamic['data']);
        $num1 =$dynamic['total'];
        $num =$views['total'];
        $rand=ceil(($num+$num1)/28);
        echo'两天内：'.$rand.'---';
        $this->handle($rand,$datas);
    }
    private  function  handle($rand,$datas){
        if(!empty($datas)){
            $data=array_rand($datas,$rand);
            if(is_array($data)){
                foreach($data as $v){
                    $param=array();
                    if(isset($datas[$v]['id'])){
                        echo 'view:'.$datas[$v]['id'].'--';
                        $params['praise_num']=$datas[$v]['praise_num']+1;
                        View::model()->updateViewInfo($params,$datas[$v]['id']);
                        $redis_key = MEM_PRE_KEY . "view_praisenum_" .$datas[$v]['id'];
                        Yii::app()->redis_w->incrBy($redis_key, 1);
                        $param['op']='praise_view';
                        $param['r_id']=$datas[$v]['id'];
                        View::model()->savelog($param);
                    }else{
                        echo 'dynamic:'.$datas[$v]['dynamic_id'].'--';
                        $dynamic_key = MEM_PRE_KEY."dynamic_".$datas[$v]['dynamic_id'];
                        Dynamic::model()->setDynamicValue($datas[$v]['dynamic_id'],"praisenums","add",1);
                        $num = Yii::app()->redis_r->get($dynamic_key);
                        $num+=1;
                        Yii::app()->redis_w->set($dynamic_key,$num);
                        $param['op']='praise_dynamic';
                        $param['r_id']=$datas[$v]['dynamic_id'];
                        View::model()->savelog($param);
                    }
                }
                echo "\n";
            }else{
                $param=array();
                if(isset($datas[$data]['id'])){
                    echo 'view:'.$datas[$data]['id'].'--';
                    $params['praise_num']=$datas[$data]['praise_num']+1;
                    View::model()->updateViewInfo($params,$datas[$data]['id']);
                    $redis_key = MEM_PRE_KEY . "view_praisenum_" .$datas[$data]['id'];
                    Yii::app()->redis_w->incrBy($redis_key, 1);
                    $param['op']='praise_view';
                    $param['r_id']=$datas[$data]['id'];
                    View::model()->savelog($param);

                }else{
                    echo 'dynamic:'.$datas[$data]['dynamic_id'].'--';
                    $dynamic_key = MEM_PRE_KEY."dynamic_".$datas[$data]['dynamic_id'];
                    Dynamic::model()->setDynamicValue($datas[$data]['dynamic_id'],"praisenums","add",1);
                    $num = Yii::app()->redis_r->get($dynamic_key);
                    $num+=1;
                    Yii::app()->redis_w->set($dynamic_key,$num);
                    $param['op']='praise_dynamic';
                    $param['r_id']=$datas[$data]['dynamic_id'];
                    View::model()->savelog($param);
                }
            }
        }
    }
}