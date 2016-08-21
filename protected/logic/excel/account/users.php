<?php
/**
 * 客户账报表 
 * - 原意 取 所有站内用户
 * - 当前取的是所有投资过的账户
 *
 * @author cao_zl
 */
class logic_excel_account_users extends logic_excel_base {
    /**
     * 所有用户的账务报表文件名前缀
     * @var string
     */
    private $_fileNameFixedAllUsers = 'account_all_user_';
    /**
     * 投资表表名
     * @var array
     */
    private $_tbl_tender = 'deayou_borrow_tender';
    /**
     * 投资人的还款计划表名
     * @var string
     */
    private $_tbl_recover = 'deayou_borrow_recover';
    /**
     * 用户主表表名
     * @var string
     */
    private $_tbl_users = 'deayou_users';
    /**
     * 用户账总账需要被统计的列索引
     * @var array
     */
    private $_allUsersCountCellsSet = array(3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,21);
    /**
     * 逐行统计的结果
     * @var array 
     */
    private $_allUsersCountResult = array();
    
    
    protected function _setTitle() {
        parent::_setTitle();
        $this->_setNumFormat();
    }


    /**
     * 所有客户账报表
     * @param string $date
     * @return boolean|string
     */
    public function createAllUsersExcel($date){
        $minId = 0;
        if(utils_environment::isDevelopment()){
            $minId = 6009780;
        }
        $unlessUserids = $this->_getUnlessUserId();//需要排除的用户ID
        $file_list = array();
        $fcount = 1;
        $initResult = $this->_countAllUserAccountInit($minId);
        while(true){//一次循环生成一份excl文件
            $dataList = array();
            $records = utils_mysql::getSelector()->from($this->_tbl_users)
                    ->fromColumns(array('user_id' , 'username'))
                    ->where('user_id > ?' , $minId)
                    ->order('user_id')
                    ->limit($this->_maxLineNum)
                    ->fetchAll();
            if(!$records){
                break;
            }
            $file_name = $this->_fileNameFixedAllUsers.$date.'_'.$fcount++.'_'.$records[0]['user_id'];
            foreach($records as $k => $record){
                $minId = $record['user_id'];
                if(in_array($record['user_id'] , $unlessUserids)){
                    continue;
                }
                $accountChange = $this->_getDayAccountChange($record['user_id'] , $date);//取用户今日的账户变动日志
                $thisLine = array(
                    date('Y-m-d' , strtotime($date) - 86400),
                    $record['user_id'],
                    $record['username'],
                    $accountChange['start_account'],
                    $accountChange['recharge_success'],
                    $accountChange['packet_send'],//可提现红包
                    $accountChange['tender_packet'],//投资红包
                    $accountChange['privilege_add'],//特权收益
                    $accountChange['coupons_add'],//加息券
                    $accountChange['cash_cancel'],
                    $accountChange['sum_recover_capital'],
                    $accountChange['sum_recover_interest'],
                    $accountChange['cash_forst'],//提现冻结
                    $accountChange['tender_all'],
                    $accountChange['tender_forst'],
                    $accountChange['tender_success'],//投资成功
                    $accountChange['cash_fee'],//提现手续费
                    $accountChange['cash_success'],
                    $accountChange['debt_fee'],//债权手续费
                    '',//借方调整
                    '',//贷方调整
                    $accountChange['end_account'],
//                    '期初余额',
//                    '充值',
//                    '冻结解除',
//                    '还款',
//                    '冻结',
//                    '融资借款',
//                    '利息',
//                    '费用',
//                    '提现',
//                    '借方调整',
//                    '贷方调整',
//                    '期末余额',
//                    '附加1',
//                    '附加2',
//                    '附加3',
//                    '附加4',
                );
                $this->_dealAllUsersCountCells($thisLine);
                $dataList[] = $thisLine;
                unset($thisLine);
                unset($records[$k]);
                unset($accountChange);
            }
            if(!$dataList){
                break;
            }
            if($fcount > $initResult['last_page_num']){
                $dataList[] = array();
                $dataList[] = $this->_allUsersCountResult;
            }
            $file_name .= '_'.$minId;
            $file_list[] = $this->createExcel($file_name, $this->_allUsersExcelTitle(), $dataList, true);
            echo 'createAllUsersExcel_memory:',memory_get_usage() , "\n";
            unset($dataList);
        }
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_fileNameFixedAllUsers.$date.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                $string = '_fileNameFixedAllUsers：cretea_zip_failed';
                utils_log::write('crontab/excel', 'cash_zip_failed', $string);
                echo $string;
                return false;
            }
        }
    }
    
//    
//    /**
//     * 所有客户账报表 - 第二版
//     * @param string $date
//     * @return boolean|string
//     */
//    public function createAllUsersExcel_v2($date){
////        $unlessUserids = $this->_getUnlessUserId();//需要排除的用户ID
////        $fcount = 1;
////        $initResult = $this->_countAllUserAccountInit_v2();
//        $file_list = array();
//        $userNum = $this->_countUserNum();
//        $children = 10;//子进程数量
//        $maxPageNum = ceil($userNum / $this->_maxLineNum);
//        $everyNum = intval($maxPageNum / $children);
//        $moreNum = $maxPageNum % $children;
//        $resultFileList = array();
//        //给每个进程分配任务
//        for($child = 1; $child <= $children ; $child ++){
//            $pass_file_name = APPLICATION_RUNTIME_PATH. 'pfname_'.$child.'_'.time();
//            $command = 'php '.APPLICATION_PATH.'protected'.DIRECTORY_SEPARATOR.'crontab'.DIRECTORY_SEPARATOR.'__child_excel_account_docreate.php -e '.utils_environment::getEnvironent().' -o '.$pass_file_name;
//            $page_s = ($child - 1) * $everyNum + 1;
//            $page_e = $child * $everyNum;
//            $command .= ' --date '.$date;
//            $command .= ' --page '.$page_s.'_'.$page_e;
//            $command .= ' --limit '.$this->_maxLineNum;
//            if($moreNum && $moreNum >= $child){
//                $command .= ' --more '. ($children * $everyNum + $child);
//            }
//            $command .= ' > /dev/null 2>&1 &';
//            echo $command , "\n";
//            if(system($command) === false){
//                throw new Exception('createAllUsersExcel_v2:cmd exec failed('.$command.')');
//            }
//            $resultFileList[] = $pass_file_name;
//        }
//        $successNum = count($resultFileList);
//        //检查子进程是否执行完毕
//        while(true){
//            $pass = 0;
//            foreach ($resultFileList as $resultFile){
//                if(file_exists($resultFile)){
//                    $pass ++;
//                }
//                if($pass == $successNum){
//                    break;
//                }
//                sleep(5);
//            }
//        }die('all ok!');
//        //汇总子进程数据
//        foreach($resultFileList as $resultFile){
//            $excelFileInfo = include $resultFile;
//            if($excelFileInfo){
//                $file_list[] = unserialize($excelFileInfo);
//            }
//            unset($resultFile);
//        }
//        //压缩包
//        if($file_list){
//            $tarName = $this->_saveBasePath.$this->_fileNameFixedAllUsers.$date.'.zip';
//            $zip = new ZipArchive();
//            if($zip->open($tarName, ZipArchive::CREATE) === true){
//                foreach($file_list as $file_info){
//                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
//                }
//                $zip->close();
//                $this->_deleteBaseExcel($file_list);
//                return $tarName;
//            }else{
//                $string = '_fileNameFixedAllUsers：cretea_zip_failed';
//                utils_log::write('crontab/excel', 'cash_zip_failed', $string);
//                echo $string;
//                return false;
//            }
//        }
//    }
//    
//    
//    public function createAllUsersExcel_v2_child($date , $pageList , $limit , $passFilePath){
//        $initResult = $this->_countAllUserAccountInit_v2();
//        $file_list = array();
//        foreach($pageList as $page){//一次循环生成一份excl文件
//            $dataList = array();
//            $records = utils_mysql::getSelector()->from($this->_tbl_users)
//                    ->fromColumns(array('user_id' , 'username'))
//                    ->order('user_id')
//                    ->limit($limit , $page)
//                    ->fetchAll();
//            $file_name = $this->_fileNameFixedAllUsers.$date.'_'.$page.'_'.$records[0]['user_id'];
//            foreach($records as $k => $record){
//                $minId = $record['user_id'];
//                $accountChange = $this->_getDayAccountChange($record['user_id'] , $date);//取用户今日的账户变动日志
//                $thisLine = array(
//                    date('Y-m-d' , strtotime($date) - 86400),
//                    $record['user_id'],
//                    $record['username'],
//                    $accountChange['start_account'],
//                    $accountChange['recharge_success'],
//                    $accountChange['packet_send'],//可提现红包
//                    $accountChange['tender_packet'],//投资红包
//                    $accountChange['privilege_add'],//特权收益
//                    $accountChange['coupons_add'],//加息券
//                    $accountChange['cash_cancel'],
//                    $accountChange['sum_recover_capital'],
//                    $accountChange['sum_recover_interest'],
//                    $accountChange['cash_forst'],//提现冻结
//                    $accountChange['tender_forst'],
//                    $accountChange['tender_success'],//投资成功
//                    $accountChange['cash_fee'],//提现手续费
//                    $accountChange['cash_success'],
//                    '',//借方调整
//                    '',//贷方调整
//                    $accountChange['end_account'],
////                    '期初余额',
////                    '充值',
////                    '冻结解除',
////                    '还款',
////                    '冻结',
////                    '融资借款',
////                    '利息',
////                    '费用',
////                    '提现',
////                    '借方调整',
////                    '贷方调整',
////                    '期末余额',
////                    '附加1',
////                    '附加2',
////                    '附加3',
////                    '附加4',
//                );
//                $this->_dealAllUsersCountCells($thisLine);
//                $dataList[] = $thisLine;
//                unset($thisLine);
//                unset($records[$k]);
//                unset($accountChange);
//            }
//            if(!$dataList){
//                break;
//            }
//            if($page > $initResult['last_page_num']){
//                $dataList[] = array();
//                $dataList[] = $this->_allUsersCountResult;
//            }
//            $file_name .= '_'.$minId;
//            $file_list[] = $this->createExcel($file_name, $this->_allUsersExcelTitle(), $dataList, true);
//            echo 'createAllUsersExcel_memory:',memory_get_usage() , "\n";
//            unset($dataList);
//        }
//        file_put_contents($passFilePath , $file_list ? serialize($file_list) : '');
//    }
//    /**
//     * 账户统计初始化 - 第二版使用
//     * @param int $minId 特定查询条件处理
//     * @return array 返回基础设置值
//     */
//    private function _countAllUserAccountInit_v2(){
//        $this->_allUsersCountResult[0] = '账务统计：';
//        $lastIndex = end($this->_allUsersCountCellsSet);
//        for($i = 1 ; $i <= $lastIndex ; $i ++){
//            $this->_allUsersCountResult[$i] = in_array($i, $this->_allUsersCountCellsSet) ? 0 : '';
//        }
//        $userNum = $this->_countUserNum();
//        $pageNum = ceil($userNum / $this->_maxLineNum);
//        return array(
//            'user_count'    => $userNum,
//            'last_page_num' => $pageNum,
//        );
//    }
    
    /**
     * 生成所有用户账户账务报表第三版
     * @param string $date
     * @throws Exception
     */
    public function createAllUsersExcel_v3($date){
        $userNum = $this->_countUserNum();
        $children = 3;//子进程数量
        $maxPageNum = ceil($userNum / $this->_maxLineNum);
        $everyChildNum = intval($maxPageNum / $children);
        $moreNum = $maxPageNum % $children;
        $pidList = array();
        for($child = 1; $child <= $children ; $child ++){
            $pid= pcntl_fork();
            $passFilePath = APPLICATION_TMP_PATH .$date .'_' . $child . '_' . time() . '.tmp';
            if ($pid == -1) { 
                throw new Exception('could not fork');
            } else if($pid){//主进程记录PID
                $pidList[$pid] = array('passFilePath' => $passFilePath , 'child' => $child , 'children' => $children);
            } else {//子进程分发
                $page_s = ($child - 1) * $everyChildNum + 1;
                $page_e = $child * $everyChildNum;
                $page_more = ($moreNum && $moreNum >= $child) ? ($children * $everyChildNum + $child) : '';
                $this->createChildExcel_v3($date, $page_s, $page_e, $page_more, $passFilePath);
                exit;
            }
        }
        $file_list = array();
        $this->_allUsersCountResult[0] = date('Y-m-d' , strtotime($date) - 86400);
//        $this->_allUsersCountResult[1] = '数据统计';
        $this->_countAllUserAccountInit_v3();
        while($pidList){
            sleep(5);
            foreach($pidList as $pid => $childInfo){
                if(strpos(exec('ps --pid '.$pid), '<defunct>') !== false){
                    if(!file_exists($childInfo['passFilePath'])){//指定子进程出现错误
                        throw new Exception('createChildExcel_v3:'.$date.'|'.json_encode($childInfo));
                    }
                    $resultInfo = json_decode(file_get_contents($childInfo['passFilePath']) , true);
                    $file_list = array_merge($file_list , $resultInfo['file_list']);
                    $this->_dealAllUsersCountCells($resultInfo['count_info']);
                    unlink($childInfo['passFilePath']);
                    unset($pidList[$pid]);
                }
            }
        }
        $dateList = array($this->_allUsersCountResult);
        $file_list[] = $this->createExcel('count_'.$this->_fileNameFixedAllUsers.$date, $this->_allUsersExcelTitle(), $dateList, true);
        $tarName = $this->_saveBasePath.$this->_fileNameFixedAllUsers.$date.'.zip';
        $zip = new ZipArchive();
        if($zip->open($tarName, ZipArchive::CREATE) === true){
            foreach($file_list as $file_info){
                $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
            }
            $zip->close();
            $this->_deleteBaseExcel($file_list);
            return $tarName;
        }else{
            $string = '_fileNameFixedAllUsers：cretea_zip_failed';
            utils_file::writeLog('crontab/excel', 'cash_zip_failed', $string);
            echo $string;
            return false;
        }
    }
    
    
    function createChildExcel_v3($date , $page_s , $page_e , $page_more , $passFilePath){
        $pageList = range($page_s, $page_e);
        if($page_more && is_numeric($page_more)){
            $pageList[] = $page_more;
        }
        $file_list = array();
        utils_mysql::getSelector()->rebuildPdoConnect();
        $this->_countAllUserAccountInit_v3();
        foreach($pageList as $page){
            $dataList = array();
            $records = utils_mysql::getSelector()->from($this->_tbl_users)
                    ->fromColumns('user_id , username')
                    ->where('user_id >= (select user_id from '.$this->_tbl_users.' order by user_id limit '.($page - 1) * $this->_maxLineNum.' , 1)')
                    ->order('user_id')
                    ->limit($this->_maxLineNum)
                    ->fetchAll();
            $file_name = $this->_fileNameFixedAllUsers.$date.'_'.$page.'_'.$records[0]['user_id'];
            foreach($records as $k => $record){
                $accountChange = $this->_getDayAccountChange($record['user_id'] , $date);//取用户今日的账户变动日志
                $thisLine = array(
                    date('Y-m-d' , strtotime($date) - 86400),
                    $record['user_id'],
                    $record['username'],
                    $accountChange['start_account'],
                    $accountChange['recharge_success'],
                    $accountChange['packet_send'],//可提现红包
                    $accountChange['tender_packet'],//投资红包
                    $accountChange['privilege_add'],//特权收益
                    $accountChange['coupons_add'],//加息券
                    $accountChange['cash_cancel'],
                    $accountChange['sum_recover_capital'],
                    $accountChange['sum_recover_interest'],
                    $accountChange['cash_forst'],//提现冻结
                    $accountChange['tender_all'],
                    $accountChange['tender_forst'],
                    $accountChange['tender_success'],//投资成功
                    $accountChange['cash_fee'],//提现手续费
                    $accountChange['cash_success'],
                    $accountChange['debt_fee'],//债权手续费
                    '',//借方调整
                    '',//贷方调整
                    $accountChange['end_account'],
                );
                $this->_dealAllUsersCountCells($thisLine);
                $dataList[] = $thisLine;
                unset($thisLine);
                unset($records[$k]);
                unset($accountChange);
            }
            unset($records);
            $file_name .= '_'.$record['user_id'];
            
            $file_list[] = $this->createSimpleExcel($file_name, $this->_allUsersExcelTitle(), $dataList, true);
//            $file_list[] = $this->createExcel($file_name, $this->_allUsersExcelTitle(), $dataList, true);
            
//            $excel = new logic_excel_base();
//            $file_list[] = $excel->createExcel($file_name, $this->_allUsersExcelTitle(), $dataList, true);
//            unset($excel);

            unset($dataList);
        }
        $saveInfo = array(
            'file_list'     => $file_list,
            'count_info'    => $this->_allUsersCountResult,
        );
        utils_file::writeFile($passFilePath , json_encode($saveInfo) , 'w');
    }
    
    /**
     * 账户统计初始化
     * 
     * @return array 返回基础设置值
     */
    private function _countAllUserAccountInit_v3(){
        $lastIndex = end($this->_allUsersCountCellsSet);
        for($i = 1 ; $i <= $lastIndex ; $i ++){
            $this->_allUsersCountResult[$i] = in_array($i, $this->_allUsersCountCellsSet) ? 0 : '';
        }
    }
    
    /**
     * 用户账户统计变动
     * @param int $user_id
     * @param string $date
     * @return array
     */
    private function _getDayAccountChange($user_id ,$date){
        $account = logic_accounts_user::getInstance()->getUserAccounts($user_id);
        $yesterday = date('Y-m-d' , strtotime($date) - 86400);
        $etime = strtotime($date);
        $stime = $etime - 86400;
        $where = array(
            'stime'     => $stime,
            'user_id'   => $user_id,
        );
        $accountLogList = logic_accounts_user::getInstance()->getAccountLogList($where, 'id', 1 , 300);//某日单个用户出现了200+的日志，只能放大查询区间
        if($accountLogList){
            $targetDayLogList = array();//指定日期当天的记录
            $nextDayLog = array();//指定日之后的第一条记录
            foreach($accountLogList as $logRecord){
                if($logRecord['addtime'] < $etime){
                    $targetDayLogList[] = $logRecord;
                }else{
                    $nextDayLog = $logRecord;
                    break;
                }
            }
            unset($accountLogList);
            //若有记录，说明昨日有资金变动
            $start_account = $targetDayLogList ? $targetDayLogList[0]['balance_old'] : ($nextDayLog ? $nextDayLog['balance_old'] : 0);
            $end_account = $nextDayLog ? $nextDayLog['balance_old'] : 0;//持续覆盖，取最后的金额
            $recharge_success = 0;//充值金额总计
            $cash_cancel = 0;//取消提现金额
            $cash_success = 0;//提现成功
            $packet_send = 0;//红包统计
            $coupons_add = 0;//加息券到期入账
            $privilege_add = 0;//特权收益到期入账
            $cash_fee = 0;//提现手续费
            $debt_fee = 0;//债权转让手续费支出
            foreach($targetDayLogList as $accountLogRecord){
                $end_account = $accountLogRecord['balance'];
                switch ($accountLogRecord['code_type']):
                    case 'recharge_success'://充值成功
                        $recharge_success = round($recharge_success + $accountLogRecord['money'] , 2);
                        break;
                    case 'cash_cancel'://取消提现
                        $cash_cancel = round($cash_cancel + $accountLogRecord['money'] , 2);
                        break;
                    case 'cash_success'://提现成功
                        $cash_success = round($cash_success + $accountLogRecord['money'] , 2);
                        break;
                    case 'cash'://申请提现冻结
                        $cash_forst = isset($cash_forst) ? $cash_forst : logic_accounts_cash::getInstance()->getUserCashRorstSum($user_id, $yesterday);//提现冻结统计
                        break;
                    case 'cash_fee'://提现手续费
                        $cash_fee = round($cash_fee + $accountLogRecord['money'] , 2);
                        break;
                    case 'packet_send'://可提现红包激活
                        $packet_send = round($packet_send + $accountLogRecord['money'] , 2);
                        break;
                    case 'tender'://投资冻结
                        if(!isset($tender_all)){
                            $tenderCountInfo = $this->_getSumTenderForst($user_id, $stime, $etime);
                            $tender_all = $tenderCountInfo['all'];
                            $tender_forst = $tenderCountInfo['forst'];
                        }
                        break;
                    case 'borrow_interest_coupon_add'://加息券
                        $coupons_add = round($coupons_add + $accountLogRecord['money'] , 2);
                        break;
                    case 'privilege_add':
                        $privilege_add = round($privilege_add + $accountLogRecord['money'] , 2);
                        break;
                    case 'tender_success_frost'://存在投资成功冻结 - 一次总量
                        $tender_success = isset($tender_success) ? $tender_success : $this->_getSumTenderSuccess($user_id, $yesterday);
                        $tender_packet_count = isset($tender_packet_count) ? $tender_packet_count : logic_active_packet::getInstance()->countTenderSuccessPacketByDateAndUserId($user_id , $yesterday);
                        break;
                    case 'tender_recover_yes'://有当日被还款记录 - 一次总量
                        $repayCount = isset($repayCount) ? $repayCount : $this->_getRepayCount($user_id, $date);
                        break;
                    case 'debt_cession_frost'://债权转让手续费支出
                        $debt_fee = round($debt_fee + $accountLogRecord['money'] , 2);
                        break;
                endswitch;
            }
            unset($targetDayLogList);
            unset($nextDayLog);
        }elseif($account){
            $start_account = $account['balance'];
            $end_account = $account['balance'];
        }
        return array(
            'start_account'         => isset($start_account) ? $start_account : 0,
            'end_account'           => isset($end_account) ? $end_account : 0,
            'recharge_success'      => isset($recharge_success) ? $recharge_success : 0,
            'cash_cancel'           => isset($cash_cancel) ? $cash_cancel : 0,
            'cash_success'          => isset($cash_success) ? $cash_success : 0,
            'cash_forst'            => isset($cash_forst) ? $cash_forst : 0,
            'packet_send'           => isset($packet_send) ? $packet_send : 0,
            'sum_recover_capital'   => (isset($repayCount) && $repayCount) ? $repayCount['sum_recover_capital'] : 0,
            'sum_recover_interest'  => (isset($repayCount) && $repayCount) ? $repayCount['sum_recover_interest'] : 0,
            'tender_all'            => isset($tender_all) ? $tender_all : 0,//当日投资总额（包含起息和未起息）
            'tender_forst'          => isset($tender_forst) ? $tender_forst : 0,//投资冻结
            'tender_success'        => isset($tender_success) ? $tender_success : 0,//投资成功 - 当天起息的总投资金额，包含使用的红包等
            'tender_packet'         => isset($tender_packet_count) ? round($tender_packet_count['packet_sum'] + $tender_packet_count['extra_packet_sum'] , 2) : 0,//投资成功使用的红包金额 - 现在是投资红包和礼品券
            'coupons_add'           => isset($coupons_add) ? $coupons_add : 0,
            'privilege_add'         => isset($privilege_add) ? $privilege_add : 0,
            'cash_fee'              => isset($cash_fee) ? $cash_fee : 0,//提现手续费
            'debt_fee'              => isset($debt_fee) ? $debt_fee : 0,//债权转让手续费支出
        );
    }
    
    /**
     * 统计用户指定日期的投资金额
     * @param int $user_id
     * @param int $stime
     * @param int $etime
     * @return float
     */
    private function _getSumTenderForst($user_id , $stime , $etime){
        $records = utils_mysql::getSelector()->from($this->_tbl_tender)
                ->fromColumns('account , status')
                ->where('user_id = ?' , $user_id)
                ->where('addtime >= ?' , $stime)
                ->where('addtime < ?' , $etime)
//                ->where('status = 0')
                ->fetchAll();
        $forst = 0;
        $all = 0;
        foreach($records as $record){
            if($record['status'] == 0){
                $forst = round($forst + $record['account'] , 2);
            }
            $all = round($all + $record['account'] , 2);
        }
        return array('forst' => $forst , 'all' => $all);
    }
    
    /**
     * 统计用户投资成功金额
     * @param int $user_id
     * @param string $date
     * @return float
     */
    private function _getSumTenderSuccess($user_id , $date){
        $borrow_nids = logic_borrow_borrow::getInstance()->getReverifyBorrowNidByDate($date);
        $sumInfo = utils_mysql::getSelector()->from($this->_tbl_tender)
                ->fromColumns('sum(account) as sum_account')
                ->where('user_id = ?' , $user_id)
                ->where('borrow_nid in ("'.implode('","' , $borrow_nids).'")')
                ->fetchRow();
        return $sumInfo ? $sumInfo['sum_account'] : 0;
    }
    
    /**
     * 获取用户的指定日期还款统计
     * @param int $user_id
     * @param string $date
     * 
     * @return array
     */
    private function _getRepayCount($user_id , $date){
        $countRecord = utils_mysql::getSelector()->from($this->_tbl_recover)
                ->fromColumns(array(
                    'sum(recover_capital) as sum_recover_capital',
                    'sum(recover_interest) as sum_recover_interest',
                ))
                ->where('user_id = ?' , $user_id)
                ->where('recover_yestime >= ?' , strtotime($date) - 86400)
                ->where('recover_yestime < ?' , strtotime($date))
                ->group('user_id')
                ->fetchRow();
        return $countRecord;
    }
    
    /**
     * 账户统计初始化
     * @param int $minId 特定查询条件处理
     * @return array 返回基础设置值
     */
    private function _countAllUserAccountInit($minId){
        $this->_allUsersCountResult[0] = '账务统计：';
        $lastIndex = end($this->_allUsersCountCellsSet);
        for($i = 1 ; $i <= $lastIndex ; $i ++){
            $this->_allUsersCountResult[$i] = in_array($i, $this->_allUsersCountCellsSet) ? 0 : '';
        }
        $selector = utils_mysql::getSelector()->from($this->_tbl_users)
                ->fromColumns('count(1) as c');
        if($minId){
            $selector->where('user_id > ?' , $minId);
        }
        $userCount = $selector->fetchRow();
        $userNum = $userCount ? $userCount['c'] : 0;
        $pageNum = ceil($userNum / $this->_maxLineNum);
        return array(
            'user_count'    => $userNum,
            'last_page_num'=> $pageNum,
        );
    }
    
    /**
     * 获取用户账户数量
     * @return int
     */
    private function _countUserNum(){
        $cacheKey = '_countUserNum';
        $expire = 20;//秒
        $num = utils_cache::file()->get($cacheKey , null);
        if($num === null){
            $selector = utils_mysql::getSelector()->from($this->_tbl_users)
                    ->fromColumns('count(1) as c');
//                    $selector->where("(user_id in (select E.user_id from deayou_account_recharge E WHERE E.remark in('在线充值', '微信平台在线充值','用户电脑平台在线充值','电脑平台在线充值','苹果手机平台在线充值','安卓手机平台在线充值') and E.status=1 and E.accounttype=11) or user_id in (13900, 13504))");
            $userCount = $selector->fetchRow();
            $num = $userCount ? $userCount['c'] : 0;
            utils_cache::file()->set($cacheKey, $num, $expire);
        }
        return $num;
    }
    
    /**
     * 逐行累加
     * @param array $lineRow
     */
    private function _dealAllUsersCountCells($lineRow){
        foreach($this->_allUsersCountCellsSet as $ci){
            $this->_allUsersCountResult[$ci] = round($this->_allUsersCountResult[$ci] + $lineRow[$ci] , 2);
        }
    }
    
    /**
     * 获取排除掉的用户UserId
     * - 去掉 发标用户  大概11
     * - 去掉 黑名单用户  17人
     * - 去掉 企业充值用户 12人
     * - 保留 12274(刘佳), 13900, 13504
     * 
     * @return array
     */
    private function _getUnlessUserId(){
        //发标用户
        $borrowUids = utils_mysql::getSelector()->from('deayou_borrow')->fromColumns('distinct(user_id) as user_id')->fetchAll();
        $user_ids = utils_map::dealResult2SimpleArray($borrowUids, 'user_id');
        //黑名单用户
//        $blackUids = utils_mysql::getSelector()->from('black_namelist')->fromColumns('user_id')->fetchAll();
//        foreach($blackUids as $blackUid){
//            if(!in_array($blackUid['user_id'], $user_ids)){
//                $user_ids[] = $blackUid['user_id'];
//            }
//        }
        //企业充值用户
//        $rechargeUids = utils_mysql::getSelector()->from('deayou_account_recharge')->where('')
        
        //保留 12274(刘佳), 13900, 13504
        return array_diff($user_ids, array(12274 , 13900, 13504));
    }
    
    
    private function _allUsersExcelTitle(){
        return array(
            array(
                'value' => '账务日期',
                'bgColor' => 'CCFFCC',
            ),
            array(
                'value' => '客户userid',
                'bgColor' => 'CCFFCC',
            ),
            array(
                'value' => '客户姓名',
                'bgColor' => 'CCFFCC',
            ),
            array(
                'value' => '投资账户',
                'bgColor' => 'FFCC99',
                'children' => array(
                    '期初余额',
                    array(
                        'value' => '借方发生额',
                        'children' => array(
                            '充值', '可提现红包', '投资红包', '特权收益', '加息券', '取消提现', '本金收回', '利息收回',
                        ),
                    ),
                    array(
                        'value' => '贷方发生额',
                        'children' => array(
                            '提现冻结', '投资金额' , '投资冻结', '投资成功', '提现手续费', '提现成功', '债权手续费',
                        ),
                    ),
                    array(
                        'value' => '其他调整',
                        'children' => array(
                            '借方调整', '贷方调整',
                        ),
                    ),
                    '期末余额'
                ),
            ),
            array(
                'value' => '融资账户',
                'bgColor' => '99CCFF',
                'children' => array(
                    '期初余额',
                    array(
                        'value' => '借方发生额',
                        'children' => array('充值' , '冻结解除' , '还款'),
                    ),
                    array(
                        'value' => '贷方发生额',
                        'children' => array('冻结' , '融资借款' , '利息' , '费用', '提现'),
                    ),
                    array(
                        'value' => '其他调整',
                        'children' => array('借方调整' , '贷方调整'),
                    ),
                    '期末余额',
                ),
            ),
        );

    }
    
    /**
     * 设置表格中数字格式列
     */
    private function _setNumFormat(){
        $activeSheet = $this->_excelObject->getActiveSheet();
        foreach($this->_allUsersCountCellsSet as $index){
            $cell = $this->cells[$index];
            $activeSheet->getStyle($cell.($this->_titleLineNum + 1))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $activeSheet->duplicateStyle($activeSheet->getStyle($cell.($this->_titleLineNum + 1)), $cell.($this->_titleLineNum + 2).':'.$cell.(count($this->_dataList)+$this->_titleLineNum) );
        }
    }
    
}
