<?php
/**
 * 用户账报表脚本
 * - 该脚本需要遍历所有用户的数据，执行时间很长，所以单独处理（以小时为单位）
 */
include dirname(__FILE__) . DIRECTORY_SEPARATOR . '_crontab_bootstrap.php';
echo '<-- start -->' ,"\n";

$systemOptParams = getCMDParams(array('email'));
$today = $systemOptParams['o'] ? $systemOptParams['o'] : date('Y-m-d');
$attachs = array();
$logName = date('m').'.log';
$logDir = 'crontab/excel_two/'.date('Y');
$stime = microtime(true);
utils_file::writeLog($logName, 'start --> '.$stime , $logDir);

//客户账明细（三张表）
//所有用户账
$userAccountExcel = new logic_excel_account_users();
$attachs[] = array($userAccountExcel->createAllUsersExcel_v3($today) , '客户账(客户账明细)'.$today.'.zip');
unset($userAccountExcel);
$string = 'pass:user_account_all';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);

//发邮件
$subject = '财务报表中转(二) - '.utils_environment::getEnvironent().'环境下';
$body = "发布时间:".date('Y-m-d H:i:s')."<hr/>第二批报表，用户账有关报表，内容过大且执行时间较长，分开发出";

if(utils_environment::isDevelopment()){
    $emails = array(array('caozhongliang@dtd365.com' , '神一般的我'));
//    $ccMailList = array('cao881216@163.com');
}elseif(utils_environment::isTesting()){
    print_r($attachs);
}else{
    $emails = $systemOptParams['email'] ? array($systemOptParams['email']) : array('dujunjie@dtd365.com' , 'xiongyan@dtd365.com.cn' , 'hulei@dtd365.com.cn' , 'tangqinqi@dtd365.com.cn');
    $ccMailList = $systemOptParams['email'] ? array() : array(
        'majiajun@dtd365.com' , 'maoweijie@dtd365.com' , 'zhuqi@dtd365.com' , 'hetaiyan@dtd365.com' , 'yangyue@dtd365.com' , 'caozhongliang@dtd365.com');
}
if(isset($emails) && $emails){
    $excelMail = new logic_excel_retrymail();
    $excelMail->sendMail($subject, $body, $emails, $attachs, isset($ccMailList) ? $ccMailList : array());
}

$etime = microtime(true);
utils_file::writeLog($logName, 'end --> '.$etime."\n".'use_time:'.($etime - $stime) , $logDir);

echo '<-- end -->' , "\n";
exit;