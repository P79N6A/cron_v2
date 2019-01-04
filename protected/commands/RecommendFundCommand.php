<?php

/*
 * Function:理财基金推荐表  
 * Desc: 初始化基金推荐表中两个展示收益字段 
 * Author: meixin@staff.sina.com.cn
 * Date: 2016/04/15
 */

class RecommendFundCommand extends LcsConsoleCommand {

    public function init(){
		Yii::import('application.commands.recommendFund.*');
	}

    
    public function actionShowIncome(){
        $o = new ShowIncome();
        $o->income();
    }

}

