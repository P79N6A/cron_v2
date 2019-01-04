<?php
/**
 * moments 模块
 *
 * add by zhihao6 2017/03/02
 *
 * + 用户关注理财师后，根据理财师免费观点（免费观点包）生产用户的moments
 * + 用户取消关注理财师后，删除已经根据理财师免费观点（免费观点包）生产的用户moments
 */

class MomentsPlannerAttentionMessageHandler
{
    private $commonHandler = null;

    public function __construct()
    {
        $this->commonHandler=new CommonHandler();
    }

    public function run($msg)
    {
        $log_data = array('status'=>1,'result'=>'ok','ext_data'=>'');
        try {
            $this->commonHandler->checkRequireParam($msg, array('uid', 'p_uid', 'attention'));

            switch (intval($msg['attention'])) {
                case 0: // 取消关注理财师
                    $this->inattentionPlanner($msg['uid'], $msg['p_uid']);
                    break;
                case 1: // 关注理财师
                    $this->attentionPlanner($msg['uid'], $msg['p_uid']);
                    break;
                default:
                    throw new Exception("未知关注理财师状态:{$msg['attention']}");
                    break;
            }
        }catch(Exception $e){
            $log_data['status']=-1;
            $log_data['result']=$e->getMessage();
        }

        //记录队列处理结果
        if(isset($msg['queue_log_id']) && !empty($msg['queue_log_id'])){
            $log_data['ext_data'] = is_array($log_data['ext_data']) ? json_encode($log_data['ext_data']) : $log_data['ext_data'];
            Message::model()->updateMessageQueueLog($log_data, $msg['queue_log_id']);
        }
    }

    // 用户关注理财师，生产moments
    // 1. 取理财师免费观点（观点包）信息
    // 2. 生产moments
    private function attentionPlanner($uid, $p_uid)
    {
        $mom_s = new MomentsService();

        $pkg_ids = Package::model()->getPackageIdsByPuid($p_uid, -1);
        $view_info_map = View::model()->getViewInfoMapByPkgids($pkg_ids);
        if (!empty($view_info_map)) {
            foreach ($view_info_map as $views) {
                $mom_s->batchSaveUserViewCmn($uid, 0, $views);
            }
        }
    }

    // 用户取消关注理财师，删除moments
    // 1. 取理财师免费观点（观点包）信息
    // 2. 删除对应的moments
    private function inattentionPlanner($uid, $p_uid)
    {
        $mom_s = new MomentsService();

        $pkg_ids = Package::model()->getPackageIdsByPuid($p_uid, -1);
        $view_info_map = View::model()->getViewInfoMapByPkgids($pkg_ids);
        if (!empty($view_info_map)) {
            foreach ($view_info_map as $views) {
                $mom_s->batchDeleteUserViewCmn($uid, 0, $views);
            }
        }
    }
}
