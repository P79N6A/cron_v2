<?php

/**
 * 直播组件
 * mark： 注意 cronv2 admin2 NewLiveUtils 保持一致
 * Created by PhpStorm.
 * User: ff
 * Date: 16-8-11
 * Time: 下午2:11
 */
class NewLiveUtils
{
    const APP_ID = 3;
    const APP_KEY = 'gm743IiAQTSp7flh';

    const ZB_URL = 'http://zhibo.video.sina.com.cn'; // 外网
    //const ZB_URL = 'http://zhibo.intra.video.sina.com.cn'; // 内网

    /**
     * 获取毫秒时间戳
     * @return float
     */
    public static function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    /**
     * 生成 Authorization
     * @param $url
     * @param $reqTime
     * @return string
     */
    private static function createAuth($url, $reqTime)
    {
        return strtoupper(md5($url.$reqTime.self::APP_KEY));
    }

    /**
     * 生成 Authorization2
     * @param $user
     * @param $reqTime
     * @return string
     */
    private static function createAuth2($user, $reqTime)
    {
        return strtoupper(md5($user.self::APP_KEY.$reqTime));
    }

    /**
     * 生成主参数
     * @return array
     */
    private static function getParams()
    {
        $user = array('s_uid' => '2118352025', 'uid' => '13115905 ', 'v_uid' => 50);
        $appOs = Yii::app()->request->getParam('appOs', 'windows');//App运行平台，可选值：android，ios，windows
        $devId = $appOs == 'windows' ? $user['uid'] : Yii::app()->request->getParam('devId', '');

        return array(
            'AppId' => self::APP_ID, // App标识
            'AppVer' => Yii::app()->request->getParam('AppVer', 'v1.0'), // App版本号，string最大长度为8字节,自定义
            'AppOS' => $appOs, // App运行平台，可选值：android，ios，windows
            'DevId' => $devId, // 设备唯一标识，string最大长度为64字节,DevId如果是移动app就是设备的唯一id,pc可以一个用户传一个固定值就可以
            'User' => $user['v_uid'], // 用户id，通过用户授权接口获得
            'ReqTime' => self::getMillisecond(), // 请求时间，格式为毫秒数，例如：1462255351714
        );
    }

    /**
     * 创建直播方法封装
     * @param $url /program/create
     * @param $params
     * @param string $type post get
     * @return string
     */
    private static function curlRequest($url, $params, $type = 'post')
    {
        try {
            $head = array(
                'Authorization' => self::createAuth($url, $params['ReqTime']),
                'Authorization2' => self::createAuth2($params['User'], $params['ReqTime']),
            );
            $curl = Yii::app()->curl->setHeaders($head)->setTimeOut(3);

            return $type == 'post' ? $curl->post(self::ZB_URL.$url, $params) : $curl->get(self::ZB_URL.$url.'?'.http_build_query($params));
        } catch (Exception $e) {
            return json_encode(array('code' => -100));
        }
    }

    /**
     * 创建节目
     * http://wiki.intra.sina.com.cn/pages/viewpage.action?pageId=96011008
     * @param $data
     * @return mixed
     */
    public static function createProgram($data)
    {
        $params = array(
            'Title' => !empty($data['title']) ? $data['title'] : '',        // 标题 Y
            'Start' => !empty($data['start_time']) ? $data['start_time'] : '',   // 开始时间 N
            //'Poster' => $data['picture'],           // 封面图 N ，大小不超过1MB
        );
        $params = array_merge(self::getParams(), $params);

        $result = self::curlRequest('/program/create', $params);
        return !empty($result) ? json_decode($result, true) : false;
    }

    /**
     * 开始直播
     * http://wiki.intra.sina.com.cn/pages/viewpage.action?pageId=96011010
     * @param $data
     * @return bool|mixed
     */
    public static function startProgram($data)
    {
        $params = array(
            'ProgramId' => $data['program_id'], // 节目id Y
        );
        $params = array_merge(self::getParams(), $params);

        $result = NewLiveUtils::curlRequest('/program/start', $params);
        return !empty($result) ? json_decode($result, true) : false;
    }

    /**
     * 创建直播流
     *
     * 重复调用生成同一直播流
     * http://wiki.intra.sina.com.cn/pages/viewpage.action?pageId=97322610
     * @param $data
     * @return mixed
     */
    public static function createStream($data)
    {
        $params = array(
            'ProgramId' => $data['program_id'], // 节目id Y
            'Definition' => 'fhd', // 清晰度，可选值:sd-标清，hd-高清，fhd-超高清 Y
            'Profile' => $data['description'] ?: '直播流', // 流简介（机位描述），10个字以内 Y
            'Device' => 'phone',// 推流设备，phone-手机，xiaoyi-小蚁，qingpai-轻拍 Y
            //'SessionId' => 123, // 上报日志的session id Y
        );
        $params = array_merge(self::getParams(), $params);

        $result = self::curlRequest('/stream/create', $params);
        return !empty($result) ? json_decode($result, true) : false;
    }

    /**
     * 获取直播观看统计
     * @param $data
     * @return bool|int
     */
    public static function getStats($data)
    {
        if (empty($data['program_id'])) {
            return false;
        }

        $result = self::curlRequest('/stat/public/program/'.intval($data['program_id']), self::getParams());
        if (empty($result)) {
            return false;
        }

        $result = json_decode($result, true);
        if (!empty($result['data']['stats']['total'])) {
            return $result['data']['stats']['total'];
        }

        return 0;
    }

    /**
     * 关闭直播
     * @param $data
     * @return bool|mixed
     */
    public static function closeLive($data)
    {
        $params = array(
            'ProgramId' => $data['program_id'], // 节目id Y
        );
        $params = array_merge(self::getParams(), $params);

        $result = self::curlRequest('/program/complete', $params);
        return !empty($result) ? json_decode($result, true) : false;
    }

    /**
     * 延长直播
     * @param $data
     * @return bool|mixed
     */
    public static function prolongLive($data)
    {
        $params = array(
            'ProgramId' => $data['program_id'], // Y 节目id
            'Time'      => $data['time'], // Y 延长的时间，以秒记
        );
        $params = array_merge(self::getParams(), $params);

        $result = self::curlRequest('/program/prolong', $params);
        return !empty($result) ? json_decode($result, true) : false;
    }

}
