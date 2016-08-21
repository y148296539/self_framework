<?php
/*
 * 报表定时执行脚本，每天凌晨4点开始执行
 * - 充值报表
 * - 提现清算报表
 * - 提现结算报表
 * - 借款人提现报表（已停发）
 * - 满标报表
 * - 起息报表
 * - 还款报表(借款人维度)
 * - 还款报表(投资人维度)
 * - 礼品卡当日激活报表
 * - 礼品卡月度统计（只在每月1日才会触发）
 * - 可提现红包报表(增量)
 * - 投资红包报表(新)
 * - 债权转让手续费
 * - 客户账 融资人所有起息借款
 * - 客户账 投资人被还款报表
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
$attachs[] = array($recharge->createRechargeSuccessExcel($today) , '充值成功'.$today.'.zip');
//$attachs[] = array($recharge->createRechargeFailedExcel($today) , '充值失败'.$today.'.zip');
unset($recharge);
$string = 'pass:recharge';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);

$cash = new logic_excel_cash();
//提现清算报表 - 看要付多少
$attachs[] = array($cash->createQingsuanSuccessExcel($today) , '清算成功'.$today.'.zip');
$attachs[] = array($cash->createQingsuanFailedExcel($today) , '清算失败'.$today.'.zip');
$string = 'pass:cash_qingsuan';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);

//提现结算报表 - 付款
$attachs[] = array($cash->createJiesuanSuccessExcel($today) , '结算成功'.$today.'.zip');
$attachs[] = array($cash->createJiesuanFailedExcel($today) , '结算失败'.$today.'.zip');
unset($cash);
$string = 'pass:cash_jiesuan';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);

////借款人提现报表
$borrowExcel = new logic_excel_borrow();
//$attachs[] = array($borrowExcel->createBorrowUserCashExcel($today) , '借款人提现'.$today.'.zip');
//$string = 'pass:borrower_cash';
//echo $string , "\n";
//utils_file::writeLog($logName, $string , $logDir);

//满标\起息报表
$attachs[] = array($borrowExcel->createBorrowFullExcel($today) , '满标报表'.$today.'.zip');
$attachs[] = array($borrowExcel->createBorrowReverifyExcel($today) , '起息报表'.$today.'.zip');
unset($borrowExcel);
$string = 'pass:borrow_full';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);

//当天还款记录
$repayExcel = new logic_excel_repay();
$attachs[] = array($repayExcel->createRepayListExcel($today) , '还款报表(借款人维度)'.$today.'.zip');
$attachs[] = array($repayExcel->createRecoverListExcel($today) , '还款报表(投资人维度)'.$today.'.zip');
unset($repayExcel);
$string = 'pass:repay_list';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);

//礼品卡统计报表 - 只在每月1日才会触发
$giftCardExcel = new logic_excel_giftcard();
$attachs[] = array($giftCardExcel->createGiftCardActiveExcel($today) , '礼品卡当日激活报表'.$today.'.zip');
$attachs[] = array($giftCardExcel->createGiftUseCountExcel($today) , '礼品卡月度统计'.$today.'.zip');
unset($giftCardExcel);
$string = 'pass:giftCard';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);

$redpacketExcel = new logic_excel_redpacket();
//已被激活的可提现红包报表 - 增量
$attachs[] = array($redpacketExcel->createKetixianPacketExcelAppend($today) , '可提现红包报表(增量)'.$today.'.zip');
$string = 'pass:redpacket_ketixian_append';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);

//获取已起息标的所有投资红包记录
$attachs[] = array($redpacketExcel->createReverifyTenderPacketExcel($today) , '投资红包报表(新)'.$today.'.zip');
$string = 'pass:redpacket_tender';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);

//债权转让报表
$debtExcel = new logic_excel_debt();
$attachs[] = array($debtExcel->createDebtFeeExcel($today) , '债权转让手续费'.$today.'.zip');
$string = 'pass:redpacket_tender';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);

//客户账 - 融资人所有起息借款
$financierAccountExcel = new logic_excel_account_financier();
$attachs[] = array($financierAccountExcel->createFinancierAccountExcel($today) , '客户账(融资)'.$today.'.zip');
unset($financierAccountExcel);
$string = 'pass:user_account_financier';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);

//客户账 - 投资人被还款报表
$investRepaymentAccountExcel = new logic_excel_account_invest();
$attachs[] = array($investRepaymentAccountExcel->createInvestPaymentAccountExcel($today) , '客户账(投资人被还款)'.$today.'.zip');
unset($investRepaymentAccountExcel);
$string = 'pass:user_account_invest_repayment';
echo $string , "\n";
utils_file::writeLog($logName, $string , $logDir);

//发邮件
$subject = '财务报表中转（一） - '.utils_environment::getEnvironent().'环境下';
$body = "发布时间:".date('Y-m-d H:i:s')."<hr/>第一批报表，稍后会转入后台环境";
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