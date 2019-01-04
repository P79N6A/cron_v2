<?php 
/**
 * 配置中心
 */
class Config
{
    private static $config = [
		//sc_phone（aes加密）
		'_aesKey' => [
			'dev' => 'aaaaaaabbbbcccc1',
			'pro' => 'yuDA4rnmzNlm9zYo'
		],
		//接口地址
		'crmUrl'=>[
			'dev' => 'http://101.37.25.13:8210/',
			'pro' => 'https://poc.yintech.net/',
		],
		//签名字符串
		'signature'=>[
			'dev' => '32b76827-ba4f-4089-845f-059a3bc64ce5',
			'pro' => '',
		],
		//事业部编码
		'buCode'=>[
			'dev' => 'LCS',
			'pro' => 'LCS',
		],
    ];
    /**
     * 获取配置信息
     * 
     */
    public static function getConfig($key)
	{
		if (!isset(self::$config[$key]))
			return null;
		if (defined('ENV') && ENV == 'dev')
			return self::$config[$key]['dev'];
		else
			return self::$config[$key]['pro'];
	}
}
