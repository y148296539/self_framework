<?php
/**
 * 0点执行的报表脚本
 * - 注意：当前脚本必须指定每天的凌晨0点0分执行，因特定数据存在变动性，且没有日志可追溯历史状态
 * 
 * - 已被激活的可提现红包报表 - 全量
 * - 投资红包报表(旧)
 * - 未起息投资红包报表
 *
 * @author cao_zl
 */
include dirname(__FILE__) . DIRECTORY_SEPARATOR . '_crontab_bootstrap.php';
echo '<-- start -->' ,"\n";

$systemOptParams = getCMDParams(array('email'));
$today = $systemOptParams['o'] ? $systemOptParams['o'] : date('Y-m-d');
$attachs = array();
$logName = date('m').'.log';
$logDir = 'crontab/excel_timezero/'.date('Y');
$stime = microtime(true);
utils_file::writeLog($logName, 'start --> '.$stime , $logDir);

//已被激活的可提现红包报表 - 全量
$redpacketExcel = new logic_excel_redpacket();
$attachs[] = array($redpacketExcel->createKetixianPacketExcelAll($today) , '可提现红包报表(全量)'.$today.'.zip');
$string = 'pass:redpacket_ketixian_all';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);

//投资红包报表(旧)
$attachs[] = array($redpacketExcel->createXianjinPacketExcel($today) , '投资红包报表(旧)'.$today.'.zip');
$string = 'pass:redpacket_xianjin';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);

//未起息投资红包报表
$attachs[] = array($redpacketExcel->createNotReverifyTenderPacketExcel($today) , '未起息投资红包报表(新)'.$today.'.zip');
$string = 'pass:redpacket_tender_not_reverify';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);
unset($redpacketExcel);

//发邮件
$subject = '财务报表中转(三) - '.utils_environment::getEnvironent().'环境下';
$body = "发布时间:".date('Y-m-d H:i:s')."<hr/>第三批报表，必须指定每天的凌晨0点0分执行，因特定数据存在变动性，且没有日志可追溯历史状态";

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