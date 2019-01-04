<?php

class NewSendMail 
{
	/*
	 * 统一发件人发送邮件
	 * @param title 邮件标题
	 * @param msg 邮件正文 html or txt
	 * @param tos 收件人列表，数组 $tos = array('11@sina.cn','22@sina.cn');
	 * @param attachs 附件列表， 数组 , $attachs = array('/usr/xx.txt', '/usr/yy.txt');
	 */
	public function __construct($title, $msg, $tos=array(), $attachs=array())
	{
		$mailer_obj = Yii::app()->mailer;
		$mailer_obj->IsSMTP();
		$mailer_obj->IsHTML();
		$mailer_obj->SMTPAuth = true;
		// $mailer_obj->Host = 'smtp.sina.cn';
		// $mailer_obj->Username = "iamapc@sina.cn";
		// $mailer_obj->Password = "000000";
		// $mailer_obj->From = 'iamapc@sina.cn';
		// 上面的账号不能用了，换为自己的
		$mailer_obj->Host = 'mail.staff.sina.com.cn';
		$mailer_obj->Username = "xxx@staff.sina.com.cn"; 
		$mailer_obj->Password = "xxxx";
		$mailer_obj->From = 'xxx@staff.sina.com.cn';

		// $mailer_obj->SMTPDebug = true;

		//设置收件人
		if (is_array($tos) && count($tos) > 0)
		{
			foreach($tos as $to)
				$mailer_obj->AddAddress($to);
		}
		//设置附件
		if (is_array($attachs) && count($attachs) > 0)
		{
			foreach($attachs as $attach)
				$mailer_obj->AddAttachment($attach, '');
		}
		$mailer_obj->FromName = 'LCS_Mailer';
		$mailer_obj->CharSet = 'UTF-8';
		$mailer_obj->Subject = $title; //主题
		$mailer_obj->Body = $msg; //正文 html or txt
		$mailer_obj->Send();
	}
}
