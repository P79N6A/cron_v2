<?php

/**
 * 理财师服务平台消息处理方法
 */
class SpCommonHandler
{
    public function __construct()
    {
    }

    public function addToPushQueue($msg_data)
    {
    }

    //检查必须参数  否则返回exception
    public function checkRequireParam($params, $fields)
    {
        if (empty($params)) {
            throw new Exception('参数为空');
        }
        if (!empty($fields)) {
            foreach ($fields as $field) {
                if (!isset($params[$field])) {
                    throw new Exception('缺少参数：'.$field);
                }
            }
        }
    }
}
