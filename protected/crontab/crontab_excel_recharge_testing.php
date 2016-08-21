<?php
/*
 * 载入引导文件
 */
include dirname(__FILE__) . DIRECTORY_SEPARATOR . '_crontab_bootstrap.php';
echo '<-- start -->' ,"\n";

$systemOptParams = getCMDParams(array('email'));
$today = $systemOptParams['o'] ? $systemOptParams['o'] : date('Y-m-d');
$attachs = array();
$logName = date('m').'.log';
$logDir = 'crontab/excel_one/'.date('Y');
$stime = microtime(true);
utils_file::writeLog($logName, 'start --> '.$stime , $logDir);

//充值报表
$recharge = new logic_excel_recharge();
$attachs[] = array($recharge->createRechargeSuccessExcel($today) , '测试环境充值成功'.$today.'.zip');
unset($recharge);
$string = 'pass:recharge';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);

//发邮件
$subject = '测试环境下充值成功财务报表 - '.utils_environment::getEnvironent().'环境下';
$body = "发布时间:".date('Y-m-d H:i:s')."<hr/>测试环境下的充值成功报表,若无附件，则表示没有检索到对应记录";
if(utils_environment::isDevelopment()){
    print_r($attachs);
}else{
    $emails = array('dujunjie@dtd365.com' , 'xiongyan@dtd365.com.cn' , 'hulei@dtd365.com.cn' , 'tangqinqi@dtd365.com.cn');
    $ccMailList = array('caozhongliang@dtd365.com');
    utils_mail::sendSystemMail($subject, $body, $emails , $attachs , $ccMailList);
}
$etime = microtime(true);
utils_file::writeLog($logName, 'end --> '.$etime."\n".'use_time:'.($etime - $stime) , $logDir);
        
echo '<-- end -->' , "\n";
exit;