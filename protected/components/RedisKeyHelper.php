<?php
/**
 * redis key helper类
 * add by zhihao6 2016/12/29
 * 
 */

class RedisKeyHelper
{
    public static $redis_key_tpl = [
        // 圈子模块
        "10001" => "circle_#circle_id#_u_#uid#_alive", // string - 圈子用户的最近活动时间
        "10002" => "circle_#circle_id#_#day_time#_usernum", // string - 圈子的用户数（设置过期时间2天）。示例 circle_24210_20170110_usernum
        "10003" => "circle_#circle_id#_#day_time#_commentnum", // string - 圈子的评论数（设置过期时间2天）。示例 circle_24210_20170110_commentnum
        // 添加其他模块
        // ...
    ];

    public static function buildKey($type, $replace_data)
    {
        $tpl = MEM_PRE_KEY . self::$redis_key_tpl[$type];

        foreach ($replace_data as $find => $replace) {
            $tpl = str_replace("#{$find}#", $replace, $tpl);
        }

        return $tpl;
    }
}
