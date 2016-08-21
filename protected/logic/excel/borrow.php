<?php
/**
 * 借款相关报表
 * 1.借款人提现报表 - 起息当日借款人的提现记录
 * 2.满标报表两张表 - a.融资人主体 b.投资人主体
 *
 * @author cao_zl
 */
class logic_excel_borrow extends logic_excel_base {
    
    private $_tbl_borrow = 'deayou_borrow';
    
    private $_tbl_tender = 'deayou_borrow_tender';
    /**
     * 借款人提现报表文件名前缀
     * @var string
     */
    private $_borrowerCashExcelFixed = 'borrower_cash_';
    /**
     * 借款满标报表报表文件名前缀 - 借款人视角
     * @var string
     */
    private $_fileNameFixed_BorrowFullBorrower = 'borrow_full_from_borrower_';
    /**
     * 借款满标报表报表文件名前缀 - 投资人视角
     * @var type 
     */
    private $_fileNameFixed_BorrowFullInvester = 'borrow_full_from_invester_';
    /**
     * 借款起息报表报表文件名前缀 - 借款人视角
     * @var string
     */
    private $_fileNameFixed_BorrowReverifyBorrower = 'borrow_reverify_from_borrower_';
    /**
     * 借款满标报表报表文件名前缀 - 投资人视角
     * @var type 
     */
    private $_fileNameFixed_BorrowReverifyInvester = 'borrow_reverify_from_invester_';
    /**
     * 借款人提现报表 - 起息当日借款人的提现记录
     * @param $date 指定日期，获取的是指定日期之前以前的记录(前24小时)
     */
    public function createBorrowUserCashExcel($date){
        $fcount = 1;
        $borrowRows = utils_mysql::getSelector()->from($this->_tbl_borrow)
                ->fromColumns('name , borrow_nid , user_id , account , reverify_time')
                ->where('reverify_time >= ?' , strtotime($date) - 86400)
                ->where('reverify_time < ?' , strtotime($date))
                ->where('status = 3')
                ->fetchAll();
        $dataList = array();
        $file_name = $this->_borrowerCashExcelFixed.$date.'_'.$fcount++;
        if($borrowRows){
            foreach($borrowRows as $borrowRow){
                $cashAbout = $this->_getCashAbout($borrowRow['user_id'] , $date);
                if($cashAbout){
                    foreach($cashAbout as $row){
                        $dataList[] = array(
                            $borrowRow['user_id'],
                            $borrowRow['borrow_nid'],
                            $borrowRow['name'],
                            $borrowRow['account'],
                            $borrowRow ? date('Y-m-d H:i:s',$borrowRow['reverify_time']) : '-',//起息时间
                            $row['account'],//提现账号
                            $row ? date('Y-m-d H:i:s' , $row['addtime']) : '-',
                            $row ? $row['credited'] : '-',
                            $row ? $row['verify_remark'] : '-',
                        );
                    }
                }
            }
        }
        $count = count($dataList);
        $dataList[] = array();
        $dataList[] = array('共计：' , $count.'条');
        $createResult = $this->createExcel($file_name, $this->_getBorrowerCashTitles(), $dataList, true);
        unset($dataList);
        $file_list[] = $createResult;
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_borrowerCashExcelFixed.$date.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                $string = '_createJiesuanFailedExcel：cretea_zip_failed';
                utils_log::write('crontab/excel', 'cash_zip_failed', $string);
                echo $string;
                return false;
            }
        }
    }
    
    /**
     * 借款人提现 报表标题
     * @return array
     */
    private function _getBorrowerCashTitles(){
        return array(
            '融资人userid',
            array('format' => 'text' , 'value' => '融资项目标号'),
            '融资项目名',
            array('format' => 'float_00' , 'value' => '融资金额'),
            '起息时间',
            array('format' => 'text' , 'value' => '提现账号'),
            '提现时间',
            array('format' => 'float_00' , 'value' => '提现金额'),
            '提现状态',
        );
    }
    
    
    /**
     * 提现相关记录
     * @param int $user_id
     * @param string $date
     * @return array
     */
    private function _getCashAbout($user_id , $date){
        $cashRows = utils_mysql::getSelector()->from('deayou_account_cash')
                ->fromColumns('credited , verify_remark , addtime , account')
                ->where('user_id = ?' , $user_id)
                ->where('addtime >= ?' , strtotime($date) - 86400)
                ->where('addtime < ?' , strtotime($date))
                ->fetchAll();
        return $cashRows;
    }
    
    /**
     * 生成满标报表
     * - a.融资人主体 
     * - b.投资人主体
     * 
     * @param string $date 当天日期
     * @return boolean|string
     */
    public function createBorrowFullExcel($date){
        //同一天满标的数据不会很多，所以先以该方式获取，若以后有了tender_last_time，则可以简化该逻辑
        $fullBorrows = $this->_getFullBorrows($date);
        //以借款人视角生成报表
        $file_list1 = $this->_createBorrowExcelInBorrower($date , $fullBorrows , 'full');
        //以投资人视角生成报表
        $file_list2 = $this->_createBorrowExcelInInvester($date , $fullBorrows , 'full');
        $file_list = array_merge($file_list1 , $file_list2);
        if($file_list){
            $tarName = $this->_saveBasePath.'borrow_full_'.$date.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                $string = 'createBorrowFullExcel：cretea_zip_failed';
                utils_log::write('crontab/excel', 'cash_zip_failed', $string);
                echo $string;
            }
        }
        return false;
    }
    
    
    /**
     * 获取当日的满标标的
     * - 条件一：当前起息的标
     * - 条件二：当前投满的标
     * @param string $date
     * @return array
     */
    private function _getFullBorrows($date){
        //这里取出所有满标的标
        $fullRecords = utils_mysql::getSelector()->from($this->_tbl_borrow)
                ->fromColumns(array(
                    'name' , 'account' , 'user_id' , 'borrow_period' , 'borrow_style' ,
                    'borrow_type' , 'tender_last_time' , 'borrow_nid' , 'id' , 'borrow_apr',
                    'addtime' , 'is_activity' , 'borrow_apr_manage' , 'borrow_apr_interest',
                    'reverify_time',
                ))
                ->where('borrow_status = 1 and account = borrow_account_yes')
                ->where('tender_last_time >= ?' , strtotime($date) - 86400)
                ->where('tender_last_time < ?' , strtotime($date))
                ->order('id')
                ->limit($this->_maxLineNum)
                ->fetchAll();
        return $fullRecords;
    }
    
    /**
     * 以借款人视角生成报表
     * @param string $date 指定日期
     * @param array $fullBorrows
     * @param string $type 类型，full满标，reverify起息
     * 
     * @return array
     */
    private function _createBorrowExcelInBorrower($date, $fullBorrows , $type){
        $dataList = array();
        if($fullBorrows){
            foreach($fullBorrows as $borrowRow){
                $user = logic_user_user::getInstance()->getUser($borrowRow['user_id']);
                $privilegeSum = $borrowRow['is_activity'] ? logic_active_privilege::getInstance()->getSumPrivilegeByBorrowNid($borrowRow['borrow_nid']) : 0;
                $dataList[] = array(
                    $borrowRow['user_id'],
                    $user ? $user['username'] : '无匹配用户',
                    $borrowRow['borrow_nid'],
                    $borrowRow['account'],
                    $borrowRow['addtime'] ? date('Y-m-d H:i:s' , $borrowRow['addtime']) : '-',
                    $borrowRow['tender_last_time'] ? date('Y-m-d H:i:s' , $borrowRow['tender_last_time']) : '-',
                    $borrowRow['reverify_time'] ? date('Y-m-d H:i:s' , $borrowRow['reverify_time']) : '-',
                    logic_borrow_borrow::getInstance()->getBorrowStyleName($borrowRow['borrow_style']),
                    $borrowRow['borrow_apr_interest'].'%',//borrow_apr
                    $borrowRow['borrow_apr_manage'].'%',//borrow_apr_manage
                    $privilegeSum,
                );
            }
        }
        $count = count($dataList);
        $dataList[] = array();
        $dataList[] = array('共计：' , $count.'条' , '' , '=SUM(D2:D'.($count + 1).')' , '' , '' , '' , '' , '' , '=SUM(J2:J'.($count + 1).')');
        $fileName = ($type == 'full') ? $this->_fileNameFixed_BorrowFullBorrower.$date.'_1' : $this->_fileNameFixed_BorrowReverifyBorrower.$date.'_1';
        $file_list[0] = $this->createExcel($fileName, $this->_getTitlesBorrowInBorrower($type), $dataList, true);
        unset($dataList);
        return $file_list;
    }
    
    
    private function _getTitlesBorrowInBorrower($type){
        return array(
            '融资人UID',
            array('format' => 'text' , 'value' => '融资人用户名'),
            '项目编号',
            array('format' => 'float_00' , 'value' => '融资金额'),
            '发标时间',
            '满标时间',
            '起息时间',
            '还款方式',
            '还款利率',//borrow_apr
            '咨询服务费费率',//borrow_apr_manage
            array('format' => 'float_00' , 'value' => '特权金额'),
        );
    }
    
    /**
     * 以投资人视角生成报表
     * @param string $date 指定日期
     * @param array $fullBorrows 满标的记录
     */
    private function _createBorrowExcelInInvester($date , $fullBorrows , $type){
        $file_list = array();
        if($fullBorrows){
            $borrow_nid_array = utils_map::dealResult2SimpleArray($fullBorrows, 'borrow_nid');
            $fullBorrowsMap = $this->_dealSimpleArray2KeyValue($fullBorrows, 'borrow_nid');
            $minId = 0;
            $fcount = 1;
            while(true){
                $dataList = array();
                $file_name = ($type == 'full') ? $this->_fileNameFixed_BorrowFullInvester.$date.'_'.$fcount++ : $this->_fileNameFixed_BorrowReverifyInvester.$date.'_'.$fcount++;
                $tenderList = utils_mysql::getSelector()->from($this->_tbl_tender)
                        ->fromColumns('min(id) as id , sum(account) as sum_account, user_id, borrow_nid , addtime , max(packid) as max_packid , max(extra_packid) as max_extra_packid')
                        ->where('user_id > ?' , $minId)
                        ->where('borrow_nid in ("'.implode('","', $borrow_nid_array).'")')
                        ->group('user_id , borrow_nid')
                        ->order('user_id')
                        ->limit($this->_maxLineNum)
                        ->fetchAll();
                if(!$tenderList){
                    break;
                }
                foreach ($tenderList as $tenderRecord){
                    $minId = $tenderRecord['user_id'];
                    $borrowRow = $fullBorrowsMap[$tenderRecord['borrow_nid']];
                    $invester = logic_user_user::getInstance()->getUser($tenderRecord['user_id']);
                    $privilege = $borrowRow['is_activity'] ? logic_active_privilege::getInstance()->getUserPrivilegeNidByBorrowNid($tenderRecord['user_id'] , $tenderRecord['borrow_nid']) : array();
                    $tender_packet = 0;
                    if($tenderRecord['max_packid']){//有投资红包
                        $tenderPacketSum = utils_mysql::getSelector()->from($this->_tbl_tender , 't')
                                ->fromColumns('sum(p.returnprice) as sum_price')
                                ->leftJoin('deayou_packet_users' , 'p' , 't.packid = p.packid')
                                ->where('t.user_id = ?' , $tenderRecord['user_id'])
                                ->where('t.borrow_nid = ?' , $tenderRecord['borrow_nid'])
                                ->where('length(t.packid) > 5')
                                ->fetchRow();
                        $tender_packet = $tenderPacketSum ? $tenderPacketSum['sum_price'] : 0;
                    }
                    $extra_packet = 0;
                    if($tenderRecord['max_extra_packid']){//有立减红包 - 现在记录的是礼品卡
                        $tenderPacketSum2 = utils_mysql::getSelector()->from($this->_tbl_tender , 't')
                                ->fromColumns('sum(p.returnprice) as sum_price')
                                ->leftJoin('deayou_packet_users' , 'p' , 't.extra_packid = p.packid')
                                ->where('t.user_id = ?' , $tenderRecord['user_id'])
                                ->where('t.borrow_nid = ?' , $tenderRecord['borrow_nid'])
                                ->where('length(t.extra_packid) > 5')
                                ->fetchRow();
                        $extra_packet = $tenderPacketSum2 ? $tenderPacketSum2['sum_price'] : 0;
                    }
                    $dataList[] = array(
                        $tenderRecord['user_id'],
                        $invester ? $invester['username'] : '无匹配用户',
                        $tenderRecord['borrow_nid'],
                        $tenderRecord['sum_account'],
                        $tenderRecord['addtime'] ? date('Y-m-d H:i:s' , $tenderRecord['addtime']) : '-',
                        $borrowRow['tender_last_time'] ? date('Y-m-d H:i:s' , $borrowRow['tender_last_time']) : '-',
                        $borrowRow['reverify_time'] ? date('Y-m-d H:i:s' , $borrowRow['reverify_time']) : '-',//起息时间
                        logic_borrow_borrow::getInstance()->getBorrowStyleName($borrowRow['borrow_style']),
                        $borrowRow['borrow_apr'].'%',//borrow_apr
                        logic_borrow_rate::getTargetPeriodInterest($tenderRecord['sum_account'], $borrowRow['borrow_apr']/100, $borrowRow['borrow_style'], $borrowRow['borrow_period']),
                        $privilege ? $privilege['wx_acconut_interest'] : 0,
                        
                        $tender_packet,//投资红包统计
                        $extra_packet,//礼品卡统计
                    );
                }
                $count = count($dataList);
                $dataList[] = array();
                $dataList[] = array('共计：' , $count.'条' , '' , '=SUM(D2:D'.($count + 1).')' , '' , '' , '' , '' , '' , '=SUM(J2:J'.($count + 1).')' , '=SUM(K2:K'.($count + 1).')' , '=SUM(L2:L'.($count + 1).')','=SUM(M2:M'.($count + 1).')');
                $file_list[] = $this->createExcel($file_name, $this->_getTitlesBorrowInInvester($type), $dataList, true);
                unset($dataList);
            }
        }
        return $file_list;
    }
    
    
    private function _getTitlesBorrowInInvester($type){
        return array(
            '投资人UID',
            array('format' => 'text' , 'value' => '投资人用户名'),
            '投资项目编号',
            array('format' => 'float_00' , 'value' => '投资金额'),
            '投标时间',
            '满标时间',
            '起息时间',
            '还款方式',
            '投资利率',
            array('format' => 'float_00' , 'value' => '预期利息'),//borrow_apr_manage
            array('format' => 'float_00' , 'value' => '特权金额'),
            
            array('format' => 'float_00' , 'value' => '投资红包'),
            array('format' => 'float_00' , 'value' => '礼品卡'),
        );
    }
    
    /**
     * 生成起息报表
     * - a.融资人主体 
     * - b.投资人主体
     * 
     * @param string $date 当天日期
     * @return boolean|string
     */
    public function createBorrowReverifyExcel($date){
        //同一天满标的数据不会很多，所以先以该方式获取，若以后有了tender_last_time，则可以简化该逻辑
        $reverifyBorrows = $this->_getReverifyBorrows($date);
        //以借款人视角生成报表
        $file_list1 = $this->_createBorrowExcelInBorrower($date , $reverifyBorrows , 'reverify');
        //以投资人视角生成报表
        $file_list2 = $this->_createBorrowExcelInInvester($date , $reverifyBorrows , 'reverify');
        $file_list = array_merge($file_list1 , $file_list2);
        if($file_list){
            $tarName = $this->_saveBasePath.'borrow_reverify_'.$date.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                $string = 'createBorrowReverifyExcel：cretea_zip_failed';
                utils_log::write('crontab/excel', 'cash_zip_failed', $string);
                echo $string;
            }
        }
        return false;
    }
    
    /**
     * 获取当日的起息标的
     * - 条件一：当前起息的标
     * - 条件二：当前投满的标
     * @param string $date
     * @return array
     */
    private function _getReverifyBorrows($date){
        //取出所有已起息的标
        $reverifyRecords = utils_mysql::getSelector()->from($this->_tbl_borrow)
                ->fromColumns(array(
                    'name' , 'account' , 'user_id' , 'borrow_period' , 'borrow_style' ,
                    'borrow_type' , 'tender_last_time' , 'borrow_nid' , 'id' , 'borrow_apr',
                    'addtime' , 'is_activity' , 'borrow_apr_manage' , 'borrow_apr_interest',
                    'reverify_time',
                ))
                ->where('status = 3')
                ->where('reverify_time >= ?' , strtotime($date) - 86400)
                ->where('reverify_time < ?' , strtotime($date))
                ->order('id')
                ->limit($this->_maxLineNum)
                ->fetchAll();
        return $reverifyRecords;
    }
    
    
    /**
     * 将简单数组转为指定键的关联数组
     * @param array $array 原始数组
     * @param string $key 指定键名
     * @return array
     */
    
    private function _dealSimpleArray2KeyValue($array , $key){
        $result = array();
        foreach($array as $one){
            $result[$one[$key]] = $one;
        }
        return $result;
    }
    
    
}

