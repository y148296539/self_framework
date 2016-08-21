<?php
/**
 * 债权转让相关报表逻辑
 */
class logic_excel_debt extends logic_excel_base{
    /**
     * 合同状态:成功
     */
    const CONTRCT_STATUS_SUCCESS = 0;
    /**
     * 合同状态：未支付
     */
    const CONTRCT_STATUS_NOPAY = 1;
    /**
     * 合同状态: 交易失败
     */
    const CONTRCT_STATUS_FAILED = 2;
    
    /**
     * 债权转让合同签订表表名
     * @var string
     */
    private $_tbl_debt_c_contract = 'dtd_borrow_debt_cession_contract';
    
    private $_fileNameFixedDebtFee = 'debt_fee_';
    /**
     * 借款标第表
     * @var string
     */
    private $_tbl_borrow = 'deayou_borrow';
    
    /**
     * 创建债权转让手续费报表 - 每日报表
     * 
     * @param string $date 执行报表的日期，生成的内容为该日期的前一日
     * 
     * @return mixed
     */
    public function createDebtFeeExcel($date){
        $minId = 0;
        $file_list = array();
        $fcount = 1;
        while(true){//一次循环生成一份excl文件
            $file_name = $this->_fileNameFixedDebtFee.$date.'_'.$fcount++;
            $records = utils_mysql::getSelector()->from($this->_tbl_debt_c_contract , 'd')
                    ->fromColumns(array(
                        'd.debt_cession_contract_id' ,//自增ID 
                        'contract_sn' , 'd.create_time',
                        'borrow_debt_id' , 'creditor_user_id' , 'assignee_user_id' ,
                        'cession_capital' , 'cession_price' , 'cession_fee',
                        'b.borrow_nid' , 'b.name' , 'b.repay_last_time',
                    ))
                    ->leftJoin($this->_tbl_borrow, 'b', 'd.borrow_id = b.id')
                    ->where('debt_cession_contract_id > ?' , $minId)
                    ->where('d.create_time >= ?' , strtotime($date) - 86400)
                    ->where('d.create_time < ?' , strtotime($date))
                    ->where('contract_status = ?' , logic_excel_debt::CONTRCT_STATUS_SUCCESS)
                    ->order('debt_cession_contract_id')
                    ->limit($this->_maxLineNum)
                    ->fetchAll();
            if(!$records){
                break;
            }
            $tmpUsers = array();
            $dataList = array();
            foreach($records as $record){
                $tmpUsers[$record['creditor_user_id']] = 0;
                $tmpUsers[$record['assignee_user_id']] = 0;
                $minId = $record['debt_cession_contract_id'];
                $dataList[] = array(
                    $record['contract_sn'],
                    &$tmpUsers[$record['creditor_user_id']],//$record['creditor_user_id'],
                    &$tmpUsers[$record['assignee_user_id']],//$record['assignee_user_id'],//'受让人',
                    '-',
                    $record['create_time'] ? date('Y-m-d H:i:s' , $record['create_time']) : '-',
                    $record['borrow_nid'],
                    $record['cession_capital'],
                    $record['cession_price'],
                    $record['cession_fee'],
                    $record['name'],
                    $record['repay_last_time'] ? date('Y-m-d H:i:s' , $record['repay_last_time']) : '-',
                );
            }
            $saveUserids = array_keys($tmpUsers);
            $users = utils_mysql::getSelector()->from('deayou_users')->fromColumns('user_id , username')
                    ->where('user_id in ('.implode(',', $saveUserids).')')
                    ->fetchAll();
            unset($saveUserids);
            foreach($users as $user){
                $tmpUsers[$user['user_id']] = $user['username'];
            }
            unset($tmpUsers);
            $count = count($records);
            unset($records);
            $dataList[] = array();
            $dataList[] = array('合计' , $count.'条' ,'' , '' , '', '' , '', '', '=SUM(I2:I'.($count + 1).')');
            $file_list[] = $this->createExcel($file_name, $this->_titleDebtFee(), $dataList, true);
            unset($dataList);
            
        }
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_fileNameFixedDebtFee.$date.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                echo 'zip open failed';
            }
        }
        return false;
    }
    
    /**
     * 债权转让手续费报表表头
     * @return array
     */
    private function _titleDebtFee(){
        return array(
            array('format' => 'text' , 'value' => '交易单号'),
            array('format' => 'text' , 'value' => '转债人'),
            array('format' => 'text' , 'value' => '受让人'),
            '-转让日期',
            '成交日期',
            array('format' => 'text' , 'value' => '标的编号'),
            array('format' => 'float_00' , 'value' => '原始债权'),
            array('format' => 'float_00' , 'value' => '受让金额'),
            array('format' => 'float_00' , 'value' => '手续费'),
            '标的名',
            '最后还款日',
        );
    }
    
    /**
     * 债权转让手续费统计用报表
     * @return string
     */
    public function createDebtFeeCountCountExcel(){
        $countRecords = utils_mysql::getSelector()->from($this->_tbl_debt_c_contract)
                ->fromColumns('sum(cession_fee) as sum_cession_fee,from_unixtime(create_time , "%Y-%m") as ym')
                ->group('ym')->order('ym desc')->limit($this->_maxLineNum)->fetchAll();
        $minId = 0;
        $file_list = array();
        $fcount = 1;
        while(true){//一次循环生成一份excl文件
            $file_name = 'count_'.$this->_fileNameFixedDebtFee.'_'.$fcount++;
            $records = utils_mysql::getSelector()->from($this->_tbl_debt_c_contract)
                    ->fromColumns(array(
                        'debt_cession_contract_id' ,//自增ID 
                        'contract_sn' , 'borrow_id' , 'create_time',
                        'borrow_debt_id' , 'creditor_user_id' , 'assignee_user_id' ,
                        'cession_capital' , 'cession_price' , 'cession_fee',
                    ))
                    ->where('debt_cession_contract_id > ?' , $minId)
                    ->where('contract_status = ?' , logic_excel_debt::CONTRCT_STATUS_SUCCESS)
                    ->order('debt_cession_contract_id')
                    ->limit($this->_maxLineNum)
                    ->fetchAll();
            if(!$records){
                break;
            }
            $dataList = array();
            foreach($records as $record){
                $minId = $record['debt_cession_contract_id'];
                $dataList[] = array(
                    $record['contract_sn'],
                    $record['creditor_user_id'],
                    $record['assignee_user_id'],//'受让人',
                    '-',
                    $record['create_time'] ? date('Y-m-d H:i:s' , $record['create_time']) : '-',
                    $record['borrow_id'],
                    $record['cession_capital'],
                    $record['cession_price'],
                    $record['cession_fee'],
                    $record['create_time'] ? date('Y-m' , $record['create_time']) : '-',
                );
            }
            $count = count($records);
            unset($records);
            $dataList[] = array();
            $dataList[] = array('合计' , $count.'条' ,'' , '' , '', '' , '', '', '=SUM(I2:I'.($count + 1).')');
            $dataList[] = array();
            $dataList[] = array('','','','','', '月份' , '手续费');
            foreach($countRecords as $countRow){
                $dataList[] = array('','','','','', $countRow['ym'] , $countRow['sum_cession_fee']);
            }
            $title = $this->_titleDebtFee();
            $title[] = '年月筛选';
            $file_list[] = $this->createExcel($file_name, $title , $dataList, true);
            unset($dataList);
        }
        if($file_list){
            $tarName = $this->_saveBasePath.'count_'.$this->_fileNameFixedDebtFee.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                echo 'zip open failed';
            }
        }
        
        
    }
    
    
    
    
    
    
    
    
}