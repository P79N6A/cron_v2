<?php

defined('YII_DEBUG') or define('YII_DEBUG', true);

// change the following paths if necessary
require_once('yii/1.1.14/yii.php');
//测试
//require_once('/usr/home/framework/yii.php');
//$config='config/main.php';
$config = dirname(__FILE__) . '/config/dev.php';

// creating and running console application
Yii::createConsoleApplication($config)->run();
