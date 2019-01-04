<?php

define('ENV', 'dev');
//配置开发环境的差异设置
return CMap::mergeArray(
		require(dirname(__FILE__) . '/main.php'), array(
		'components' => array(
			'lcs_standby_r' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=licaishi',
				'username' => 'licaishi',
				'password' => '123456',
				'charset' => 'utf8',
			),
			//理财师数据库
			'lcs_r' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=licaishi',
				'username' => 'licaishi',
				'password' => '123456',
				'charset' => 'utf8',
			),
			'licaishi_r' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=licaishi',
				'username' => 'licaishi',
				'password' => '123456',
				'charset' => 'utf8',
			),
			'lcs_standby_r' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=licaishi',
				'username' => 'licaishi',
				'password' => '123456',
				'charset' => 'utf8',
			),
			'lcs_w' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=licaishi',
				'username' => 'licaishi',
				'password' => '123456',
				'charset' => 'utf8',
			),
			'licaishi_w' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=licaishi',
				'username' => 'licaishi',
				'password' => '123456',
				'charset' => 'utf8',
			),
			//理财师数据库
			'lcs_comment_r' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=licaishi_comment',
				'username' => 'licaishi',
				'password' => '123456',
				'charset' => 'utf8',
			),
			'lcs_comment_w' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=licaishi_comment',
				'username' => 'licaishi',
				'password' => '123456',
				'charset' => 'utf8',
			),
			'licaishi_comment_r' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=licaishi_comment',
				'username' => 'licaishi',
				'password' => '123456',
				'charset' => 'utf8',
			),
			'licaishi_comment_w' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=licaishi_comment',
				'username' => 'licaishi',
				'password' => '123456',
				'charset' => 'utf8',
			),
			'xincai_r' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=xincai_trade',
				'username' => 'root',
				'password' => '123456q',
				'charset' => 'utf8',
				'enableProfiling' => YII_DEBUG,
				'enableParamLogging' => YII_DEBUG,
			),
			'xincai_w' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=xincai_trade',
				'username' => 'root',
				'password' => '123456q',
				'charset' => 'utf8',
				'enableProfiling' => YII_DEBUG,
				'enableParamLogging' => YII_DEBUG,
			),
			// licaishi_account read and write config
			'account_r' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=licaishi_account',
				'username' => 'licaishi',
				'password' => '123456',
				'charset' => 'utf8',
			),
			'account_w' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=licaishi_account',
				'username' => 'licaishi',
				'password' => '123456',
				'charset' => 'utf8',
			),
			//投资易数据库
			'tzy_r' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=finanalysis',
				'username' => 'finanalysis',
				'password' => '123456',
				'charset' => 'utf8',
			),
			'tzy_w' => array(
				'class' => 'system.db.CDbConnection',
				'connectionString' => 'mysql:host=192.168.48.224;port=3306;dbname=finanalysis',
				'username' => 'finanalysis',
				'password' => '123456',
				'charset' => 'utf8',
			),
			//redis config
			"redis_w" => array(
				"class" => "ext.CRedis",
				"options" => array("hostname" => "192.168.48.224", "port" => 6379)
			),
			"redis_r" => array(
				"class" => "ext.CRedis",
				"options" => array("hostname" => "192.168.48.224", "port" => 6379)
			),
			'cache' => array(
				'class' => 'system.caching.CMemCache',
				'servers' => array(
					array('host' => '192.168.48.224', 'port' => 11211, 'weight' => 50),
					array('host' => '192.168.48.224', 'port' => 11211, 'weight' => 50),
				),
				'keyPrefix' => '',
				'hashKey' => false,
				'serializer' => false
			),
		)
		)
);
