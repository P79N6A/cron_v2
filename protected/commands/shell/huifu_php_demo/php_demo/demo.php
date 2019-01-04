<?php
require_once dirname(__FILE__) . '/service.php';

/**
 * P7带原文消息签名
 * @param array $order 订单参数
 * @return json 签名后的base64编码
 */
function sign($order)
{
    $cert_base64 = '';
    $key_index = '';
    $pfx_path = dirname(__FILE__).'/ccc.pfx';
    $fp = fopen($pfx_path, 'rb');
    $pfx_content = fread($fp, filesize($pfx_path));
    fclose($fp);
    $pfx_base64 = base64_encode($pfx_content);
    $order_base64 = base64_encode(base64_encode(json_encode($order)));
    try {
        $result = lajpCall("cfca.sadk.api.CertKit::getCertFromPFX", $pfx_base64, '111111');
        $result = json_decode($result);
        $cert_base64 = $result->Base64CertString;
    } catch (Exception $e) {
        echo $e;
        // Log::info($e);
    }
    // 密钥索引
    $kit_result = lajpCall("cfca.sadk.api.KeyKit::getPrivateKeyIndexFromPFX", $pfx_base64, '111111');
    $key_index = json_decode($kit_result)->privateKeyIndex;
    // P7带原文消息签名
    $sign_result = lajpCall("cfca.sadk.api.SignatureKit::P7SignMessageAttach", 'sha256WithRSAEncryption', $order_base64, $key_index, $cert_base64);
    return json_decode($sign_result)->Base64CertString;
}
/**
 * P7带原文消息验签
 * @param  string $check_value P7签名后的编码
 * @return object $result 验签结果
 */
function verify($check_value)
{
    $check_value = urldecode($check_value);
    $verify_result = lajpCall(
        "cfca.sadk.api.SignatureKit::P7VerifyMessageAttach", $check_value);	   
    $result = json_decode($verify_result);
    if(!isset($result->{'Base64Source'})){
        return [];
    }
    $result = json_decode(base64_decode(base64_decode($result->Base64Source)));
    return (array)$result;
}
//订单数据
// $order = array(
//     'version' => 10,
//     'cmd_id' => 204,
//     'mer_cust_id' => '6666000000007629',
//     'user_cust_id' => '6666000000007872',
//     'order_id' => time(),
//     'order_data' => date('Ymd', time()),
//     'trans_amt' => 0.01,
//     'ret_url' => 'http://www.test.com/return',
//     'bg_ret_url' => 'http://www.test.com/notify',
//     'div_detail' => '[{"divCustId":"6666000000007629","divAcctId":"7945","divAmt":"0.01","divFreezeFg":"00"}]',
// );
// P7签名
// $sign = sign($order);
// echo 'P7 Sign:' . $sign . '<br>';
// 验签平台返回数据
$result = 'TUlJSmZBWUpLb1pJaHZjTkFRY0NvSUlKYlRDQ0NXa0NBUUV4RHpBTkJnbGdoa2dCWlFNRUFnRUZBRENDQTJNR0NTcUdTSWIzRFFFSEFhQ0NBMVFFZ2dOUVpYbEtlVnBZVG5kWU1sSnNZekpOYVU5cFRHdDFjVlJ0YlVwUWJXbEtSR3hwY0RocFRFTkthVm94T1hsYVdGSm1aRmhLYzBscWIybGhTRkl3WTBSdmRreDZSWFZOVkVVMVRHcEZNRTVUTkRKT2FUbG9ZMGRyZGxreWFIQmliVVozWW01S1VWbFliRTlpTTFKd1dtNXJhVXhEU201aU1qbHJZekU1YTFwWVRtcEphbTlwWkVkV2VtUkRTWE5KYlU1MFdrWTVjRnBEU1RaSmFrbDNUMU5KYzBsdVNteGpXRlpzWXpOU1ptUkliSGRhVTBrMlNXcEJlVTFFWTNkTlJFRjNTV2wzYVZwSGJESllNbEpzWkVkR2NHSkRTVFpKYkhRM1dFTkthMkZZV2tSa1dFNHdVMWRTWTBscWNHTkphbGt5VG1wWmQwMUVRWGROUkVGM1RYcFZNRTU2V21OSmFYaGpTVzFTY0dSclJtcFpNMUpLV2taM2FVOXNkMmxOZW1NeFRtcEtZMGxwZUdOSmJWSndaR3RHZEdSR2QybFBiSGRwVFVNMGQwMVdkMmxNUm5kcFdrZHNNbEp1U214YVdIQnNVbTFrWTBscWNHTkpha0YzV0VOS09WaFRTWE5KYmtKeldWaFNiV0l6U25SWU0wNXNZMVk1Y0ZwRFNUWkpha2wzVFZSamVFMXFSVFZOUkVGM1RVUkJkMDlFVFRKUFUwbHpTVzVTZVZsWE5YcFlNa1owWkVOSk5rbHFRWFZOUkVWcFRFTktkbU50VW14amJEbHdXa05KTmtscVJURk5WRTB5VG1wVk0wNTZhMmxNUTBwcldsaGFjRmt5Vm1aaFZ6VnRZbmxKTmtscFNYTkpibFo2V2xoS1psa3pWbnBrUmpsd1drTkpOa2xwU1hOSmJWWTBaRWRXZFdNeWJIWmlhVWsyU1dsSmMwbHVSbmxaTWpscldsWTVNV050ZDJsUGFVbHBURU5LZDFsWWJHWmtTR3gzV2xOSk5rbHFRVEZKYVhkcFkyMVdlbU5HT1dwaU1sSnNTV3B2YVUxcVFUVk5SRUYzU1dsM2FWb3lPWFphU0U1bVpFaHNkMXBUU1RaSmFVbHpTVzA1ZDFwWVNtWmtXRTVzWTJ3NWNGcERTVFpKYVVselNXMHhiR05zT1hkamJXd3lTV3B2YVVscGQybGlNMHByV2xoS1pscEhSakJhVTBrMlNXcEpkMDFVWTNoTmFrVTFTV2wzYVdJelNtdGFXRXBtV2xob2QyRllTbXhZTTFKd1lsZFZhVTlwU1dsTVEwcDBXbGhLWmxrelZucGtSamx3V2tOSk5rbHFXVEpPYWxsM1RVUkJkMDFFUVhkTmVsVXdUbnBaYVV4RFNubGFXRkptWkZoS2MwbHFiMmxoU0ZJd1kwUnZka3g2UlhWTlZFVTFUR3BGTUU1VE5ESk9hVGt6V2xkSmRsa3lhSEJpYlVaM1ltNUtVVmxZYkZOYVdFNHhZa2hSYVdaUlBUMmdnZ1JWTUlJRVVUQ0NBem1nQXdJQkFnSUZRQUpJUjVNd0RRWUpLb1pJaHZjTkFRRUxCUUF3WFRFTE1Ba0dBMVVFQmhNQ1EwNHhNREF1QmdOVkJBb01KME5vYVc1aElFWnBibUZ1WTJsaGJDQkRaWEowYVdacFkyRjBhVzl1SUVGMWRHaHZjbWwwZVRFY01Cb0dBMVVFQXd3VFEwWkRRU0JCUTFNZ1ZFVlRWQ0JQUTBFek1UQWVGdzB4TnpBNU1UTXdOREUzTURWYUZ3MHhPVEE1TVRNd05ERTNNRFZhTUhneEN6QUpCZ05WQkFZVEFrTk9NUlV3RXdZRFZRUUtEQXhEUmtOQklGUkZVMVFnUTBFeEVUQVBCZ05WQkFzTUNFeHZZMkZzSUZKQk1Sa3dGd1lEVlFRTERCQlBjbWRoYm1sNllYUnBiMjVoYkMweE1TUXdJZ1lEVlFRRERCc3dOVEZBTVRBd01EQXhRRTR6TlRBeE1EUXdNREE1TXpFM1FERXdnZ0VpTUEwR0NTcUdTSWIzRFFFQkFRVUFBNElCRHdBd2dnRUtBb0lCQVFEQlJtVmt2NUllT3prOWtjbkc4TnBvTTlNYWJqc0JpeVpvQmsrZEFLVVJVSXFIT0RjK0JXY2EydWhPZHVhSkpJcWxrZ2xld2ZJWXRLOFpHK2ZsWHdVK09BTVczbXhtb2NWcTU2bkFYenQ0VXZVWWg5MkxKYXZxaHpuZWdSVW1JUkF0NHNFQ3JKdUsxL3NCbGI0amRabVYxRnpxUnlWWHZMdlFlMHloaWYxZTB4N253VHRGMEVEWjI3UkVRK0svRFNyMTZ2SksyWExpSDY5TDUvNlVMdEF3aGlhMTlQYk5URzNpREVtZVl3KzByck1zaVFOWGt6S3pUZUtFS09GaTBkRVVNRGZISVZTV084RDg4azNrOGlWRDV3VVJraWd2c0t0VjdpTmhQNWtFaWRycEpCalFwNGNKVnp1VTc2SFEzVTBib3VodDVDTTAvRnpyL2FTMVViSkxBZ01CQUFHamdmd3dnZmt3UHdZSUt3WUJCUVVIQVFFRU16QXhNQzhHQ0NzR0FRVUZCekFCaGlOb2RIUndPaTh2YjJOemNIUmxjM1F1WTJaallTNWpiMjB1WTI0Nk9EQXZiMk56Y0RBZkJnTlZIU01FR0RBV2dCU2FQYlN1WlZqN3psb0ZlQ2FnYlNzRWhyckc3REFNQmdOVkhSTUJBZjhFQWpBQU1Ea0dBMVVkSHdReU1EQXdMcUFzb0NxR0tHaDBkSEE2THk4eU1UQXVOelF1TkRJdU15OVBRMEV6TVM5U1UwRXZZM0pzTkRnd05pNWpjbXd3RGdZRFZSMFBBUUgvQkFRREFnYkFNQjBHQTFVZERnUVdCQlNRazFiaVlPdTBDOTFiQTdxSjJtWVFOOXhTcVRBZEJnTlZIU1VFRmpBVUJnZ3JCZ0VGQlFjREFnWUlLd1lCQlFVSEF3UXdEUVlKS29aSWh2Y05BUUVMQlFBRGdnRUJBSEZrN1poRG43NlFtRWpRRlhjcnE3bU9vbzc0WlQ3Z1RKeFJqS0V6MXE3Nm5ESFZPaFJFRE1YQkZLS0NoY1hzTWlvcEtpMmNndmxzWUZqaW5FYkRacXd6dnVZbVBWb2lkYld2M0V3N1dsbWd6WTVyNHZtcWk4YlNWK0pJQ21DRGZtb3hjZzV3NU9Ga25YZG40YzlDZVRmdFM2QVhpYlBUS1VNR2tmTGt0YVIwUU1VUzdmRng2KzhMRFBnV01vWXdsbk9vUjJ3OXJlQXV3SWliejNYeFdMQ2J4L29IQXdMWTZobk96VTZJRWpYM1ZpTTdFV0dUQTRhUDZrSEhFM05VZGNsUVhJUnVYL01oTXVWQXgwNGpUem5LTlNMMHd1TVd6RHkzRWpwNW84aFVNajFEd0Z1cGVPNmlkbEhYRFJ5VlB0WC9yc0tLY0oyOGhrelhFTVNlTUN3eGdnR1JNSUlCalFJQkFUQm1NRjB4Q3pBSkJnTlZCQVlUQWtOT01UQXdMZ1lEVlFRS0RDZERhR2x1WVNCR2FXNWhibU5wWVd3Z1EyVnlkR2xtYVdOaGRHbHZiaUJCZFhSb2IzSnBkSGt4SERBYUJnTlZCQU1NRTBOR1EwRWdRVU5USUZSRlUxUWdUME5CTXpFQ0JVQUNTRWVUTUEwR0NXQ0dTQUZsQXdRQ0FRVUFNQTBHQ1NxR1NJYjNEUUVCQVFVQUJJSUJBTFJVQWRxSEVzQlhIUzZ3b1pIcWU3WnVyQWVENnQrT2JsNlpQbUxEb1B4RGtWWEFpWTU2OE5qc1NLSmIrajlJeXh0TG1NT3Y4citNK04wL1lXZlRjODhuQnhIenk1ajZwRVl1b016Z05uMFg1L3dVVE1vbEtnK0RUSXNkcXNZcFFiMG1SRi84YSsrYTlyNEc1Y3R1bWdDUC96YmlqdEgwUE5zN0MzSEx0SUZMcVd4dW1hMk1IU2RUZ1BoL1JwVGZGbW0vOVVpSlVVdFdHV2grTEtySytXQld4LytaTnJJZkRmRmRqeUYycHdkN013aDdXSEJlN0tTdHVTVDlNZWkxeVVMdndJSlVSK0pJSmN4T3MzU1c3SmNuMXJ2bUlXd2VIajNxUmhuZ1NlNWpKbE5jbzJWNVIySW8rSjQyeG9iNThRalA1S2xTaW1sQW16QWlYdHFMK2pNPQ==';
$verify = verify($result);

echo '<pre>';
print_r($verify);
exit;


$url = "http://mertest.chinapnr.com/npay/merchantRequest";
$order = array(
    'version'=>10,
    'cmd_id'=>218,
    'app_id'=>'wx2a5538052969956e',    
    'mer_cust_id' => '6666000000035476',
    'in_acct_id'=>'37562', 
    'in_cust_id'=>'6666000000035476',
    'order_date' => date('Ymd', time()),
    'order_id' => time(),
    'pay_type'=>'10',
    'trans_amt'=>'100.00',
    'goods_desc'=>'test',
    'ret_url'=>'http://www.licaishi.sina.com.cn/return',
    'bg_ret_url'=>'http://www.licaishi.sina.com.cn/return',        
);

$sign = sign($order);
$order['check_value'] = $sign;
$o = "";
foreach ($order as $k => $v ) 
{ 
    $o .= "$k=" . urlencode($v). "&";
}
$post_data = substr($o,0,-1);

$res = request_post($url, $post_data);       
// echo $res;
$result = json_decode($res,true);
$verify = verify($result['check_value']);
var_dump($verify);




function request_post($url = '', $param = '') {
    if (empty($url) || empty($param)) {
        return false;
    }
    
    $postUrl = $url;
    $curlPost = $param;
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    $data = curl_exec($ch);//运行curl
    curl_close($ch);
    
    return $data;
}





