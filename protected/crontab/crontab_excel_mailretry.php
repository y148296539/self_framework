<?php
/**
 * 邮件报表重发脚本
 */
include dirname(__FILE__) . DIRECTORY_SEPARATOR . '_crontab_bootstrap.php';
echo '<-- start -->' ,"\n";


$retry = new logic_excel_retrymail();
$retry->retryFaildMail();


echo '<-- end -->' , "\n";
exit;

