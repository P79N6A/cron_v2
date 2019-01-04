<?php
/**
 * 礼物道具
 * Author: lixiang23
 * Date: 2017-02-23
 */
class Gift extends CActiveRecord {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return TABLE_PREFIX . 'gift';
    }

    ///根据类型获取礼物道具列表
    public function getGiftListByType($type,$page=1,$num=20){
        try{
            $db_r = Yii::app()->lcs_r;
            $skip_num = ($page - 1)*$num;
            $skip_num = $skip_num >=0?$skip_num:0;
            $sql = "select id,title,summary,image,image_left,image_right,image_gif,gif_time,type,partner_id,price,status from ".$this->tableName()." where status=0 and type=".$type." limit ".$skip_num.",".$num;
            $cmd = $db_r->createCommand($sql);
            $res = $cmd->queryAll();
            if(!empty($res)){
                return $res;
            }else{
                return null;
            }
        }catch(Exception $e){
            return null;
        }
    }

    ///根据id获取礼物道具详情
    public function getGiftInfoByIds($ids){
        try{
            $db_r = Yii::app()->lcs_r;
            $sql = "select id,title,summary,image,image_left,image_right,image_gif,gif_time,type,partner_id,price,status from ".$this->tableName()." where status=0 ";
            if(is_array($ids)){
                $sql = $sql . " and id in (".implode(',',$ids).")";
            }else{
                $sql = $sql . "and id =".$ids;
            }

            $cmd = $db_r->createCommand($sql);
            $res = $cmd->queryAll();
            $result = array();
            if(!empty($res)){
                foreach($res as $item){
                    $result[$item['id']] = $item;
                }
            }
            return $result;
        }catch(Exception $e){
            return array();
        }
    }

    ///获取圈子的收礼统计
    //$circle_id 圈子id
    public function getCircleGiftSum($circle_id){
        $db_r = Yii::app()->lcs_r;
        $gift_list = $this->getGiftListByType(10);
        $result = array();
        $yesterday = date("Y-m-d 00:00:00",strtotime("-1 day"));
        $today = date("Y-m-d 00:00:00",time());
        foreach($gift_list as $item){
            $temp_gift = array();
            $temp_gift['name']=$item['title'];
            $temp_gift['image']=$item['image'];
            $temp_gift['total']=0;
            $temp_gift['yesterday']=0;
            $sql = "select count(*) from lcs_orders where relation_id=$circle_id and type=80 and amount=".$item['id']." and status=2 and pay_time>='$yesterday' and pay_time<='$today'";
            $temp_gift['yesterday'] = $db_r->createCommand($sql)->queryScalar();
            $sql = "select count(*) from lcs_orders where relation_id=$circle_id and type=80 and amount=".$item['id']." and status=2";
            $temp_gift['total'] = $db_r->createCommand($sql)->queryScalar();
            $result[] = $temp_gift;
        }
        return $result;
    }

    ///根据订单编号获取打赏送礼的评论格式信息
    public function getGiftCmntInfoByOrderNo($order_no){
        $order_info = Orders::model()->getOrdersInfo($order_no);
        if(!empty($order_info) && isset($order_info['type']) && $order_info['type']>=80 && $order_info['type']<=89){
            $circle_id = isset($order_info['relation_id'])?$order_info['relation_id']:0;
            $uid = isset($order_info['uid'])?$order_info['uid']:0;
            $gift_id = isset($order_info['amount'])?$order_info['amount']:0;
            if(!empty($circle_id) && !empty($uid) && !empty($gift_id)){
                $filters = array("circle_id"=>$circle_id,"discussion_type"=>"8","discussion_id"=>$gift_id,"uid"=>$order_info['uid']);
                $data = CircleCommentService::getCircleCommentListPage($filters, $orders=["c_time" => "desc"]);
                if(isset($data['data'])){
                    return $data['data'][0];
                }
            }
        }
        return null;
    }
}
?>
