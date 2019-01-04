<?php
define('TABLE_PREFIX', 'licaishi.lcs_');
define('NEW_COMMENT_TABLE_PREFIX', 'licaishi_comment.lcs_comment_');
//memcached 
define("MEM_PRE_KEY", 'lcs_');
define("BASE_PATH", dirname(__FILE__) . DIRECTORY_SEPARATOR . '..');
define("LOG_PATH", BASE_PATH.DIRECTORY_SEPARATOR.'../log');
define("DATA_PATH", BASE_PATH.DIRECTORY_SEPARATOR.'../data');
define('LCS_WEB_URL','http://licaishi.sina.com.cn');
define('LCS_WEB_INNER_URL','http://i.licaishi.sina.com.cn');
//运行服务器绑定host到10.13.3.25
define('ES_URL','http://es.sina.com.cn');
// This is the configuration for yiic console application.
// Any writable CConsoleApplication properties can be configured here.
return array(
    'import' => array(
        'application.models.*',
        'application.components.*',
        'application.extensions.*',
        'application.extensions.PHPExcel.*',
        'application.extensions.PHPMailer.*'
    ),
    'name' => 'Licaishi Console Application',
    'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
    // preloading 'log' component
    'preload' => array('log'),

    // application components
    'components' => array(
        //中转库
        'lcs_hub' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=m3312i.apollo.grid.sina.com.cn;port=3312;dbnam=licaishi_hub',
            'username' => 'licaishi_hub',
            'password' => 'dba92666e2b89d9',
            'charset' => 'utf8'
        ),
        //理财师数据库
		'lcs_standby_r' => array(
            'class'=>'system.db.CDbConnection',
            'connectionString' => 'mysql:host=s3312c.mars.grid.sina.com.cn;port=3312;dbname=licaishi',
            'username'=>'licaishi_r',
            'password'=>'662b97dab80cab1',
            'charset'=>'utf8',
            "enableProfiling"=>true
        ),
        'lcs_r' => array(
            'class'=>'system.db.CDbConnection',
            'connectionString' => 'mysql:host=s3312i.mars.grid.sina.com.cn;port=3312;dbname=licaishi',
            'username'=>'licaishi_r',
            'password'=>'662b97dab80cab1',
            'charset'=>'utf8',
            "enableProfiling"=>true
        ),
        'lcs_w' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=m3312i.mars.grid.sina.com.cn;port=3312;dbname=licaishi',
            'username' => 'licaishi',
            'password' => 'a222541420a50a5',
            'charset' => 'utf8',
            "enableProfiling" => true
        ),
        'licaishi_r' => array(
            'class'=>'system.db.CDbConnection',
            'connectionString' => 'mysql:host=s3312i.mars.grid.sina.com.cn;port=3312;dbname=licaishi',
            'username'=>'licaishi_r',
            'password'=>'662b97dab80cab1',
            'charset'=>'utf8',
            "enableProfiling"=>true
        ),
        'licaishi_w' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=m3312i.mars.grid.sina.com.cn;port=3312;dbname=licaishi',
            'username' => 'licaishi',
            'password' => 'a222541420a50a5',
            'charset' => 'utf8',
            "enableProfiling" => true
        ),
        //理财师数据库
        'lcs_comment_r' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=s3314i.apollo.grid.sina.com.cn;port=3314;dbname=licaishi_comment',
            'username' => 'lcs_comment_r',
            'password' => '3c05068bb4a5cd6',
            'charset' => 'utf8',
        ),
        'lcs_comment_w' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=m3314i.apollo.grid.sina.com.cn;port=3314;dbname=licaishi_comment',
            'username' => 'lcs_comment',
            'password' => '86b7a555610c9c9',
            'charset' => 'utf8',
        ),
        //理财师数据库
        'licaishi_comment_r' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=s3314i.apollo.grid.sina.com.cn;port=3314;dbname=licaishi_comment',
            'username' => 'lcs_comment_r',
            'password' => '3c05068bb4a5cd6',
            'charset' => 'utf8',
        ),
        'licaishi_comment_w' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=m3314i.apollo.grid.sina.com.cn;port=3314;dbname=licaishi_comment',
            'username' => 'lcs_comment',
            'password' => '86b7a555610c9c9',
            'charset' => 'utf8',
        ),
        'xincai_w' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=m8324i.mars.grid.sina.com.cn;port=8324;dbname=xincai_trade',
            'username' => 'xinlicai_trade',
            'password' => 'b6897de4de9eba11',
            'charset' => 'utf8',
            'enableProfiling' => YII_DEBUG,
            'enableParamLogging' => YII_DEBUG,
        ),
        'xincai_r' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=s8324i.mars.grid.sina.com.cn;port=8324;dbname=xincai_trade',
            'username' => 'xincai_trade_r',
            'password' => 'c0f0bcfebb5830a',
            'charset' => 'utf8',
            'enableProfiling' => YII_DEBUG,
            'enableParamLogging' => YII_DEBUG,
        ),
        // licaishi_stat read and write config
        'stat_r' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=192.168.48.225;port=3306;dbname=licaishi_stat',
            'username' => 'licaishi',
            'password' => '123456',
            'charset' => 'utf8',
        ),
        'stat_w' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=192.168.48.225;port=3306;dbname=licaishi_stat',
            'username' => 'licaishi',
            'password' => '123456',
            'charset' => 'utf8',
        ),
		// licaishi_account read and write config
        'account_r' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=192.168.48.225;port=3306;dbname=licaishi_account',
            'username' => 'licaishi',
            'password' => '123456',
            'charset' => 'utf8',
        ),
        'account_w' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=192.168.48.225;port=3306;dbname=licaishi_account',
            'username' => 'licaishi',
            'password' => '123456',
            'charset' => 'utf8',
        ),

        'jiaoyi_r' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=192.168.48.225;port=3306;dbname=stp',
            'username' => 'root',
            'password' => '123456q',
            'charset' => 'utf8',
        ),
        'quotes_r' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=s3326i.apollo.grid.sina.com.cn;port=3326;dbname=quotes_db',
            'username' => 'quotes_DB_lcs_r',
            'password' => '67fda229162bd0e',
            'charset' => 'gbk',
        ),
	//投资易数据库
        'tzy_r' => array(
            'class'=>'system.db.CDbConnection',
            'connectionString' => 'mysql:host=s3441c.hebe.grid.sina.com.cn;port=3441;dbname=finanalysis',
            'username'=>'touzhiyi_r',
            'password'=>'1a37c6a25ec51a8',
            'charset'=>'utf8',
            "enableProfiling"=>true
        ),
        'tzy_w' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=m3441i.hebe.grid.sina.com.cn;port=3441;dbname=finanalysis',
            'username' => 'touzhiyi',
            'password' => '1e20cf85928f9c8',
            'charset' => 'utf8',
            "enableProfiling" => true
        ),
/*  该配置在10.39.32.56上使用
        'fcdb_r' => array(
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=s3329i.mars.grid.sina.com.cn:3329;port=3329;dbname=fcdb',
            'username' => 'fcdb_DB_lcs_r',
            'password' => '48e96f18b96dc15',
            'charset' => 'gbk',
        ),
*/
        'fcdb_r' => array( ///在192.168.48.225上使用
            'class' => 'system.db.CDbConnection',
            'connectionString' => 'mysql:host=s3329i.atlas.grid.sina.com.cn:3329;port=3329;dbname=fcdb',
            'username' => 'fcdb_DB_lcs_r',
            'password' => '48e96f18b96dc15',
            'charset' => 'gbk',
        ),
        //redis config
        "redis_w" => array(
            "class" => "ext.CRedis",
            "options" => array("hostname" => "rm7158.eos.grid.sina.com.cn", "port" => 7158)
        ),
        "redis_r" => array(
            "class" => "ext.CRedis",
            'options' => array("hostname" => "rs7158.hebe.grid.sina.com.cn", "port" => 7158)
        ),
        //memcache config
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
        'curl' => array(
            'class' => 'ext.Curl',
            'options' => array(''),
        ),
        'mailer' => array(
            'class' => 'ext.PHPMailer.EMailer',
            'options' => array(''),
        ),
        'sinaStorageService'=>array(
            'class'=>'ext.s3.SinaStorageService',
            'project'=>'s3.licaishi.sina.com.cn',
            'access_key'=>'SINA000000000CANGSHI',
            'secret_key'=>'MYGoUkcut1hrDhKY38wiGJ2FRmyxfONJgzQmuMcE'
        ),
        'log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                //错误日志
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning, info',
                    //'categories' => '*',
                    'logPath'=> LOG_PATH,
                    'logFile' => "system_".date("Ymd").".log",
                )
            ),
        ),
    )
);
