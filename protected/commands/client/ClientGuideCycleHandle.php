<?php
/**
 * @description 万能引导周期更新
 * @author      shixi_danxian
 * @date        2016/4/5
 */

class ClientGuideCycleHandle
{
    const CRON_NO = 1401;

    public function __construct()
    {

    }

    /**
     * @description 更新周期时间
     */
    public function updateCycleTime()
    {
        //获取已过期、但重复周期未完成数据
        $data       = ClientGuide::model()->getGuideOutOfDate();
        $success_id = array();
        $error_id   = array();
        if (!empty($data))
        {
            foreach ($data as $v)
            {
                $new_time = $this->getNextTime($v['s_time'], $v['e_time']);
                //更新开始时间和结束时间
                $res      = ClientGuide::model()->updateGuideData($v['id'], $new_time['s_time'], $new_time['e_time']);
                if (!$res)
                {
                    $error_id[$v['id']]   =  array(
                        'old_time' => array(
                            's_time' => $v['s_time'],
                            'e_time' => $v['e_time']
                        ),
                        'new_time' => array(
                            's_time' => $new_time['s_time'],
                            'e_time' => $new_time['e_time']
                        ),
                    );
                }
                else
                {
                    //删除该引导对应的已读记录，重新记录阅读状态
                    $del_msg  = ClientGuide::model()->deleteReadLog($v['id']);
                    $success_id[$v['id']]   =  array(
                        'old_time' => array(
                            's_time' => $v['s_time'],
                            'e_time' => $v['e_time']
                        ),
                        'new_time' => array(
                            's_time' => $new_time['s_time'],
                            'e_time' => $new_time['e_time']
                        ),
                        'del_read_log' => intval($del_msg),
                    );
                }
            }
        }

        if (!empty($success_id) || !empty($error_id))
        {
            $msg = "\n成功：".json_encode($success_id)."\n失败：".json_encode($error_id);
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, "万能引导周期更新：".$msg);
        }

    }

    /**
     * @description 获得下次运行的开始和结束时间
     * @param $stime 本次开始时间 Y-m-d H:i:s格式
     * @param $etime 本次结束时间
     *
     * @return array 下次运行的开始和结束时间
     */
    private function getNextTime($stime, $etime)
    {
        $s_time  = explode(' ', $stime);
        $e_time  = explode(' ', $etime);
        //新的开始时间为：原结束时间日期部分+1天，时分部分为原开始时间
        $new_start_date = date('Y-m-d', strtotime($e_time[0]) + 24*3600);
        $new_start_hour = $s_time[1];
        $new_start_time = $new_start_date.' '.$new_start_hour;
        //新一期结束时间：原结束日期+周期天数，时分不变
        //一个周期间隔的天数
        $cycle_time     = (strtotime($e_time[0].' 00:00:00') - strtotime($s_time[0].' 00:00:00'));
        $new_end_time   = date('Y-m-d H:i:s', strtotime($etime) + $cycle_time + 24*3600);

        $new = array(
            's_time' => $new_start_time,
            'e_time' => $new_end_time,
        );
        return $new;
    }
}