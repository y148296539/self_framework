<?php
/**
 * 脚本作废
 * 
 * 调用命令如下：
 * php /protected/crontab/__child_excel_account_docreate.php -page 1_3 -limit 5000 -more 10 > /dev/null 2>&1 &
 * 
 * 创建所有用户的数据报表子进程
 */
exit;
/*
 * 载入引导文件
 */
include dirname(__FILE__) . DIRECTORY_SEPARATOR . '_crontab_bootstrap.php';
echo '<-- start -->' ,"\n";


$params = getCMDParams(array('page' , 'limit' , 'more' , 'date'));

$pass_file_name = $params['o'];//当前进程处理完创建的文件名
$page_s_e = explode('_' ,$params['page']);
$limit = $params['limit'];
$more = intval($params['more']);
$date = $params['date'];

//文件的页码
$pageList = range($page_s_e[0] , $page_s_e[1]);
if($more){
    $pageList[] = $more;
}
$excel = new logic_excel_account_users();
$excel->createAllUsersExcel_v2_child($date , $pageList, $limit, $pass_file_name);

echo '<-- end -->' ,"\n";