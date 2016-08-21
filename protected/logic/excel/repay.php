<?php
/**
 * 还款记录报表
 *
 * @author cao_zl
 */
class logic_excel_repay extends logic_excel_base {
    
    private $_recoverlistExcelFixed = 'recoverlist_';
    
    private $_repaylistExcelFixed = 'repaylist_';
    
    private $_tbl_repay = 'deayou_borrow_repay';
    
    private $_tbl_recover = 'deayou_borrow_recover';
    
    private $_borrowRecords = array();
    
    /**
     * 生成还款报表 - 融资人维度
     * @param string $date 指定日期
     */
    public function createRepayListExcel($date){
        $minId = 0;
        $file_list = array();
        $fcount = 1;
        while(true){//一次循环生成一份excl文件
            $file_name = $this->_repaylistExcelFixed.$date.'_'.$fcount++;
            $records = utils_mysql::getSelector()
                    ->from($this->_tbl_repay , 'rp')
                    ->fromColumns(array(
                        'rp.id' , 'rp.borrow_nid' , 'rp.user_id' , 'rp.repay_yestime' ,
                        'rp.repay_period',
                        'sum(rp.repay_account) as sum_repay_account',
                    ))
                    ->where('rp.id > ?' , $minId)
                    ->where('rp.repay_yestime >= ?' , strtotime($date)-86400)
                    ->where('rp.repay_yestime < ?' , strtotime($date))
                    ->where('rp.repay_type = ?' , 'yes')
                    ->group('rp.borrow_nid, rp.user_id')
                    ->order('rp.id')
                    ->limit($this->_maxLineNum)
                    ->fetchAll();
            if(!$records){
                break;
            }
            $dataList = array();
            foreach($records as $record){
                $minId = $record['id'];
                $borrowRow = $this->_getTempBorrowRow($record['borrow_nid']);
                $repayAll = utils_mysql::getSelector()->from($this->_tbl_repay)
                        ->fromColumns('repay_account_all , repay_account , repay_period , repay_time')
                        ->where('borrow_nid = ?' , $record['borrow_nid'])
                        ->fetchAll();
                $everRepay = 0;
                $willRepay = 0;
                foreach($repayAll as $repayOne){
                    if($repayOne['repay_period'] <= $record['repay_period']){
                        $everRepay = round($everRepay + $repayOne['repay_account'], 2);
                    }else{
                        $willRepay = round($willRepay + $repayOne['repay_account'], 2);
                    }
                }
                $dataList[] = array(
                    $record['borrow_nid'],
                    $record['user_id'],
                    logic_borrow_borrow::getInstance()->getBorrowStyleName($borrowRow['borrow_style']),
                    $record['sum_repay_account'],
                    $record['repay_period'],
                    $everRepay,
                    $willRepay,
                    $record['repay_yestime'] ? date('Y-m-d H:i:s' , $record['repay_yestime']) : '',
                    $repayOne['repay_time'] ? date('Y-m-d H:i:s' , $repayOne['repay_time']) : '',
                );
            }
            $count = count($records);
            unset($records);
            $dataList[] = array();
            $dataList[] = array('共计：' , $count.'条' , '' , '=SUM(D2:D'.($count + 1).')');
            $file_list[] = $this->createExcel($file_name, $this->_repayListTitle(), $dataList, true);
            unset($dataList);
        }
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_repaylistExcelFixed.$date.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                $string = 'createRepayListExcel：cretea_zip_failed';
                utils_log::write('crontab/excel', 'cash_zip_failed', $string);
                echo $string;
            }
        }
        return false;
    }
    
    
    private function _repayListTitle(){
        return array(
            array('format' => 'text' , 'value' => '融资项目号'),
            '融资用户号',
            '还款方式',
            array('format' => 'float_00' , 'value' => '本次还款金额'),
            '还款期号',
            array('format' => 'float_00' , 'value' => '已还本息'),
            array('format' => 'float_00' , 'value' => '未还本息'),
            "还款时间",
            "最后还款日" ,
        );
    }
    
    
    /**
     * 生成还款记录报表 - 投资人维度
     * 
     * 参考SQL
     * SELECT A.borrow_nid '项目号',A.user_id '用户号',A.amt '待还本息',A.last_time '最后还款日期',B.wx_acconut_interest '特权收益金额' 
     * FROM (
     *      select borrow_nid, user_id, sum(recover_account) amt, from_unixtime(max(recover_time)) last_time 
     *      from deayou_borrow_recover where recover_type= 'yes' 
     *      group by borrow_nid, user_id having max(recover_time)<= UNIX_TIMESTAMP(date_add(curdate(),interval -1 day))
     * ) A 
     * left join deayou_circle_user_privilege_nid B on (A.user_id= B.user_id and A.borrow_nid= B.borrow_nid and A.last_time<= from_unixtime(B.repay_last_time))  
     * where B.wx_acconut_interest is not null  order by A.borrow_nid, A.user_id
     * 
     * @param string $date
     * @return boolean|string
     */
    public function createRecoverListExcel($date){
        $minId = 0;
        $file_list = array();
        $fcount = 1;
        while(true){//一次循环生成一份excl文件
            $file_name = $this->_recoverlistExcelFixed.$date.'_'.$fcount++;
            $records = utils_mysql::getSelector()
                    ->from($this->_tbl_recover , 'rc')
                    ->fromColumns(array(
                        'max(rc.id) as max_id',
                        'rc.borrow_nid','rc.recover_yestime',
                        'sum(rc.recover_account) as sum_recover_account',
                        'rc.user_id',
                    ))
                    ->where('rc.id > ?' , $minId)
                    ->where('rc.recover_yestime >= ?' , strtotime($date)-86400)
                    ->where('rc.recover_yestime < ?' , strtotime($date))
                    ->where('rc.recover_type = ?' , 'yes')
                    ->group('rc.borrow_nid, rc.user_id')
                    ->order('max_id')
                    ->limit($this->_maxLineNum)
                    ->fetchAll();
            if(!$records){
                break;
            }
            $dataList = array();
            foreach($records as $record){
                $minId = $record['max_id'];
                $borrowRecord = $this->_getTempBorrowRow($record['borrow_nid']);
                $privilegeRecord = logic_active_privilege::getInstance()->getUserPrivilegeNidByBorrowNid($record['user_id'], $record['borrow_nid']);
                $couponsRecord = logic_active_coupons::getInstance()->getCouponsPayInfoByUseridAndBorrowId($record['user_id'], $borrowRecord['id']);
                $countRecoverInfo = $this->_countRecoverInfo($record['user_id'], $record['borrow_nid']);
                $dataList[] = array(
                    $record['borrow_nid'],
                    $record['user_id'],
                    $record['sum_recover_account'],//本次还款
                    $countRecoverInfo['sum_recover_account_yes'],//已还本息
                    $countRecoverInfo['sum_recover_account_wait'],//未还本息
                    $countRecoverInfo['sum_recover_account'],//应还总额
                    $record['recover_yestime'] ? date('Y-m-d H:i:s' , $record['recover_yestime']) : '-',
                    $borrowRecord['repay_last_time'] ? date('Y-m-d',$borrowRecord['repay_last_time']) : '-',
                    $privilegeRecord ? $privilegeRecord['wx_acconut_interest'] : 0,
                    $couponsRecord ? $couponsRecord['profit_amount'] : 0,
                );
            }
            $count = count($records);
            unset($records);
            $dataList[] = array();
            $dataList[] = array('共计：' , $count.'条' , '=SUM(C2:C'.($count + 1).')');
            $file_list[] = $this->createExcel($file_name, $this->_recoverListTitle(), $dataList, true);
            unset($dataList);
        }
        $this->_borrowRecords = array();
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_recoverlistExcelFixed.$date.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                $string = 'createRecoverListExcel：cretea_zip_failed';
                utils_log::write('crontab/excel', 'cash_zip_failed', $string);
                echo $string;
            }
        }
        return false;
    }
    
    /**
     * 统计投资人收到还款信息
     * 
     * @param int $user_id
     * @param string $borrow_nid
     * @return array
     */
    private function _countRecoverInfo($user_id, $borrow_nid){
        $recoverCountList = logic_repayment_repay::getInstance()->getRecoverRecordsGroupPeriodByBorrowNidAndUserid($borrow_nid, $user_id);
        $countInfo = array(
            'sum_recover_account'       => 0,//共计需还
            'sum_recover_account_yes'   => 0,//已还本息
        );
        foreach($recoverCountList as $info){
            $countInfo['sum_recover_account'] = round($countInfo['sum_recover_account'] + $info['sum_recover_account'] , 2);
            if($info['recover_status'] == '1'){
                $countInfo['sum_recover_account_yes'] = round($countInfo['sum_recover_account_yes'] + $info['sum_recover_account_yes'] , 2);
            }
        }
        $countInfo['sum_recover_account_wait'] = round($countInfo['sum_recover_account'] - $countInfo['sum_recover_account_yes'] , 2);
        return $countInfo;
    }
    
    /**
     * 投资人的被还款列表标题
     * @return array
     */
    private function _recoverListTitle(){
        return array(
            array('format' => 'text' , 'value' => '还款项目号'),
            array('format' => 'text' , 'value' => '投资用户号'),
            array('format' => 'float_00' , 'value' => '本次还款'),
            array('format' => 'float_00' , 'value' => '已还本息') ,
            array('format' => 'float_00' , 'value' => '未还本息'),
            array('format' => 'float_00' , 'value' => '应还总额'),
            "还款日期",
            "最后还款日" ,
            array('format' => 'float_00' , 'value' => '特权收益金额'),
            array('format' => 'float_00' , 'value' => '加息券'),
        );
    }
    
    /**
     * 临时中转借款标记录 - 相同标只查询一次
     * @param string $borrow_nid
     * @return array
     */
    private function _getTempBorrowRow($borrow_nid){
        if(!isset($this->_borrowRecords[$borrow_nid])){
            $this->_borrowRecords[$borrow_nid] = utils_mysql::getSelector()->from('deayou_borrow')
                ->where('borrow_nid = ?' , $borrow_nid)
                ->fetchRow();
        }
        return $this->_borrowRecords[$borrow_nid];
    }
    
}