<?php
/**
 * 客户账报表 
 * - 原意是取所有融资人
 * - 取所有已起息的标
 *
 * @author cao_zl
 */
class logic_excel_account_financier extends logic_excel_base {
    
    
    /**
     * 所有用户的账务报表文件名前缀
     * @var string
     */
    private $_fileNameFixedFinancier = 'account_financier_';
    
    private $_tbl_borrow = 'deayou_borrow';
    
    public function createFinancierAccountExcel($date){
        $minId = 0;
        $file_list = array();
        $fcount = 1;
        $limit = $this->_maxLineNum;
        while(true){//一次循环生成一份excl文件
            $file_name = $this->_fileNameFixedFinancier.$date.'_'.$fcount++;
            $dataList = array();
            $records = utils_mysql::getSelector()->from($this->_tbl_borrow)
                    ->fromColumns(array(
                        'id', 'borrow_nid', 'account', 'user_id' , 'borrow_style' , 'reverify_time' , 'borrow_period',
                        'borrow_apr_interest' , 'borrow_apr' , 'borrow_type'
                    ))
                    ->where('id > ?' , $minId)
                    ->where('status in (3 , 6)')
                    ->order('id')
                    ->limit($limit)
                    ->fetchAll();
            if(!$records){
                break;
            }
            foreach($records as $k => $record){
                $minId = $record['id'];
                $userinfo = logic_user_user::getInstance()->getUserInfo($record['user_id']);
                $line = array(
                    $record['user_id'],
                    $userinfo ? $userinfo['realname'] : '',//'用户姓名'
                    $record['borrow_nid'],
                    logic_borrow_borrow::getInstance()->getBorrowTypeName($record['borrow_type']),//产品类型
                    logic_borrow_borrow::getInstance()->getBorrowStyleName($record['borrow_style']),//还款方式
                    date('Y-m-d' , $record['reverify_time']),
                    '',//到期日
                    logic_borrow_borrow::getInstance()->getBorrowDurationShow($record['borrow_period'], $record['borrow_style'], $record['borrow_type']),
                    '=G{line}-TODAY()',
                    $record['account'],
                    ($record['borrow_apr_interest'] > 0 ? $record['borrow_apr_interest'] : $record['borrow_apr']) / 100,
                    "",//逾期利率（每日）
                    
                    '',//借款利息1-6
                    '',//借款利息2-6
                    '',//借款利息3-6
                    '',//借款利息4-6
                    '',//借款利息5-6
                    '',//借款利息6-6
                    '',//借款利息 小计
                    
                    '',//居间服务费1-6 + 合计
                    '',//居间服务费1-6 + 合计
                    '',//居间服务费1-6 + 合计
                    '',//居间服务费1-6 + 合计
                    '',//居间服务费1-6 + 合计
                    '',//居间服务费1-6 + 合计
                    '',//居间服务费1-6 + 合计
                    
                    '',//账户管理费1-6 + 合计
                    '',//账户管理费1-6 + 合计
                    '',//账户管理费1-6 + 合计
                    '',//账户管理费1-6 + 合计
                    '',//账户管理费1-6 + 合计
                    '',//账户管理费1-6 + 合计
                    '',//账户管理费1-6 + 合计
                    
                    '',//息费总额 1-6 + 合计
                    '',//息费总额 1-6 + 合计
                    '',//息费总额 1-6 + 合计
                    '',//息费总额 1-6 + 合计
                    '',//息费总额 1-6 + 合计
                    '',//息费总额 1-6 + 合计
                    '',//息费总额 1-6 + 合计
                    
                    '',//实际还款情况 日期
                    '',//实际还款情况 金额
                    '',//实际还款情况 逾期
                    '',//实际还款情况 日期
                    '',//实际还款情况 金额
                    '',//实际还款情况 逾期
                    '',//实际还款情况 日期
                    '',//实际还款情况 金额
                    '',//实际还款情况 逾期
                    '',//实际还款情况 日期
                    '',//实际还款情况 金额
                    '',//实际还款情况 逾期
                    '',//实际还款情况 日期
                    '',//实际还款情况 金额
                    '',//实际还款情况 逾期
                    '',//实际还款情况 日期
                    '',//实际还款情况 金额
                    '',//实际还款情况 逾期
                    '',//实际还款情况 合计 日期
                    '',//实际还款情况 合计 金额
                    '',//实际还款情况 合计 逾期
                    
                    '',//备注
                );
                $this->_dealRepayList($record['borrow_nid'] , $line);
                $dataList[] = $line;
                unset($records[$k]);
                unset($accountChange);
            }
            if(!$dataList){
                break;
            }
            $file_list[] = $this->createExcel($file_name, array(), $dataList, true);
            echo 'createSuccessBiaoExcel_memory:',memory_get_usage() , "\n";
            unset($dataList);
        }
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_fileNameFixedFinancier.$date.'.zip';
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
    
    
    private function _dealRepayList($borrow_nid , &$line){
        $allRepayRecords = logic_repayment_repay::getInstance()->getRepayRecordsGroupPeriodByBorrowNid($borrow_nid);
        $countInfo = array(
            'sum_repay_account'         => 0,//应还总额
            'sum_repay_capital'         => 0,//应还本金
            'sum_repay_account_yes'     => 0,//已还金额
            'sum_repay_capital_yes'     => 0,//已还本金
            'sum_repay_interest_yes'    => 0,//已还利息
            
            'plan_last_repay_date'      => '',//计划还款的最后一次时间
            'last_repay_date'           => '',//最后一次还款日期
        );
        foreach($allRepayRecords as $repayRecord){
            $countInfo['sum_repay_account'] = round($countInfo['sum_repay_account'] + $repayRecord['sum_repay_account'] , 2);
            $countInfo['sum_repay_capital'] = round($countInfo['sum_repay_capital'] + $repayRecord['sum_repay_capital'] , 2);
            $countInfo['sum_repay_account_yes'] = round($countInfo['sum_repay_account_yes'] + $repayRecord['sum_repay_account_yes'] , 2);
            $countInfo['sum_repay_capital_yes'] = round($countInfo['sum_repay_capital_yes'] + $repayRecord['sum_repay_capital_yes'] , 2);
            $countInfo['sum_repay_interest_yes'] = round($countInfo['sum_repay_interest_yes'] + $repayRecord['sum_repay_interest_yes'] , 2);
            $countInfo['plan_last_repay_date'] = date('Y-m-d' , $repayRecord['repay_time']);
            if($repayRecord['repay_status'] == 1){
                $countInfo['last_repay_date'] = date('Y-m-d' , $repayRecord['repay_yestime']);
            }
            
            $line[(11 + $repayRecord['repay_period'])] = $repayRecord['sum_repay_interest'];//借款利息
            $line[(33 + $repayRecord['repay_period'])] = $repayRecord['sum_repay_interest'];//息费总额 - 既然没有费，那就只显示息
            $line[(40 + ($repayRecord['repay_period'] - 1) * 3 + 1)] = $countInfo['plan_last_repay_date'];//实际还款情况 - 日期
            $line[(40 + ($repayRecord['repay_period'] - 1) * 3 + 2)] = $repayRecord['sum_repay_account_yes'];//实际还款情况 - 金额
            $line[(40 + $repayRecord['repay_period'] * 3)] = ($repayRecord['repay_status'] == 1) ? (logic_date::getDelayTimeShow(date('Y-m-d' , $repayRecord['repay_time']), date('Y-m-d' , $repayRecord['repay_yestime']))) : '待还';//实际还款情况 - 逾期
        }
        
        $line[6]  = $countInfo['plan_last_repay_date'];//最后还款日期
        if($countInfo['sum_repay_account'] <= $countInfo['sum_repay_account_yes']){//判断是否结清
           $line[8] = '结清';
        }
        $line[18] = round($countInfo['sum_repay_account'] - $countInfo['sum_repay_capital']);//利息总额
        $line[40] = round($countInfo['sum_repay_account'] - $countInfo['sum_repay_capital']);//息费总额
        $line[59] = $countInfo['last_repay_date'];
        $line[60] = $countInfo['sum_repay_account_yes'];
        
    }
    
    /**
     * 设置个性化的表头
     * @return int
     */
    protected function _setTitle() {
        $titleCount = 63;
        $titleLineNum = 3;
        $this->setTitleCellNum($titleCount);
        $this->setTitleLineNum($titleLineNum);
        $this->_initCells();
        $activeSheet = $this->_excelObject->getActiveSheet();
        $title = '融资';
        $activeSheet->setTitle($title);
        //设置字体、加粗
        $activeSheet->getStyle('A1:'.$this->cells[$titleCount - 1].$titleLineNum)
                ->getFont()
                ->setColor(new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_WHITE))
                ->setName($this->_fontName)
                ->setBold(true);
        //居中
        $activeSheet->getStyle('A1:'.$this->cells[$titleCount - 1].$titleLineNum)->getAlignment()
                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
                ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $this->_textLineKey = array();
        //给标题栏加边框=。=
        $this->_writeBorder();
        //做报表表头
        $this->_dealA2B();
        $this->_dealC2I();
        $this->_dealJ();
        $this->_dealK2L();
        $this->_dealM2AO();
        $this->_dealAP2BJ();
        $this->_dealBK();
        
        $this->_setSelfCellFormat($titleLineNum);
        
        $activeSheet->freezePane('A1')->freezePane('A'.($titleLineNum + 1));
    }
    
    
    private function _dealA2B(){
        $this->_excelObject->getActiveSheet()->getStyle('A1:B3')->getFill()
                ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()->setRGB('800080');
        
        $name = '客户信息';
        $this->_excelObject->getActiveSheet()->setCellValue('A1', $name);
        $this->_excelObject->getActiveSheet()->mergeCells('A1:B1');
        
        $name = '编号';
        $this->_excelObject->getActiveSheet()->setCellValue('A2', $name);
        $this->_excelObject->getActiveSheet()->mergeCells('A2:A3');
        
        $name = '姓名';
        $this->_excelObject->getActiveSheet()->setCellValue('B2', $name);
        $this->_excelObject->getActiveSheet()->mergeCells('B2:B3');
    }
    
    
    private function _dealC2I(){
        $this->_excelObject->getActiveSheet()->getStyle('C1:I3')->getFill()
                ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()->setRGB('008000');
        
        $name = '借款信息';
        $this->_excelObject->getActiveSheet()->setCellValue('C1', $name)
            ->mergeCells('C1:I1');
        
        $name = '项目编号';
        $this->_excelObject->getActiveSheet()->setCellValue('C2', $name)
            ->mergeCells('C2:C3');
        
        $name = '产品类型';
        $this->_excelObject->getActiveSheet()->setCellValue('D2', $name)
            ->mergeCells('D2:D3');
        
        $name = '还款方式';
        $this->_excelObject->getActiveSheet()->setCellValue('E2', $name)
            ->mergeCells('E2:E3');
        
        $name = '放款日';
        $this->_excelObject->getActiveSheet()->setCellValue('F2', $name)
            ->mergeCells('F2:F3');
        
        $name = '到期日';
        $this->_excelObject->getActiveSheet()->setCellValue('G2', $name)
            ->mergeCells('G2:G3');
        
        $name = '期限';
        $this->_excelObject->getActiveSheet()->setCellValue('H2', $name)
            ->mergeCells('H2:H3');
        
        $name = '剩余天数';
        $this->_excelObject->getActiveSheet()->setCellValue('I2', $name)
            ->mergeCells('I2:I3')
            ->getStyle('I2')
            ->getFill()
            ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FF0000');
        
        
    }
    
    
    private function _dealJ(){
        $this->_excelObject->getActiveSheet()->getStyle('J1:J3')->getFill()
                ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()->setRGB('333399');
        
        $name = '贷款本金';
        $this->_excelObject->getActiveSheet()->setCellValue('J1', $name)
            ->mergeCells('J1:J3');
    }
    
    
    private function _dealK2L(){
        $this->_excelObject->getActiveSheet()->getStyle('K1:L3')->getFill()
                ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()->setRGB('993300');
        
        $name = '利率';
        $this->_excelObject->getActiveSheet()->setCellValue('K1', $name)
            ->mergeCells('K1:L1');
        
        $name = "借款利率\n（年化）";
        $this->_excelObject->getActiveSheet()->setCellValue('K2', $name)
            ->mergeCells('K2:K3')
            ->getStyle('K2')
            ->getAlignment()->setWrapText(true);
        
        $name = "逾期利率\n（每日）";
        $this->_excelObject->getActiveSheet()->setCellValue('L2', $name)
            ->mergeCells('L2:L3')
            ->getStyle('L2')
            ->getAlignment()->setWrapText(true);
        
    }
    
    
    private function _dealM2AO(){
        $this->_excelObject->getActiveSheet()->getStyle('M1:AO3')->getFill()
                ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()->setRGB('808000');
        
        $name = '贷款息费管理';
        $this->_excelObject->getActiveSheet()->setCellValue('M1', $name)
            ->mergeCells('M1:AO1');
        
        $name = '借款利息';
        $this->_excelObject->getActiveSheet()->setCellValue('M2', $name)
            ->mergeCells('M2:S2');
        
        for($i = 1; $i < 7 ; $i ++){
            $this->_excelObject->getActiveSheet()->setCellValue($this->cells[11 + $i].'3', $i.'-6');
        }
        
        $name = '小计';
        $this->_excelObject->getActiveSheet()->setCellValue('S3', $name);
        
        $name = '居间服务费';
        $this->_excelObject->getActiveSheet()->setCellValue('T2', $name)
            ->mergeCells('T2:Z2');
        
        for($i = 1; $i < 7 ; $i ++){
            $this->_excelObject->getActiveSheet()->setCellValue($this->cells[18 + $i].'3', $i.'-6');
        }
        
        $name = '小计';
        $this->_excelObject->getActiveSheet()->setCellValue('Z3', $name);
        
        $name = '账户管理费';
        $this->_excelObject->getActiveSheet()->setCellValue('AA2', $name)
            ->mergeCells('AA2:AG2');
        
        for($i = 1; $i < 7 ; $i ++){
            $this->_excelObject->getActiveSheet()->setCellValue($this->cells[25 + $i].'3', $i.'-6');
        }
        
        $name = '小计';
        $this->_excelObject->getActiveSheet()->setCellValue('AG3', $name);
        
        $name = '逾期利息';
        $this->_excelObject->getActiveSheet()->setCellValue('AH2', $name)
            ->mergeCells('AH2:AH3');
        
        //AI  - AO
        $name = '息费总额';
        $this->_excelObject->getActiveSheet()->setCellValue('AI2', $name)
            ->mergeCells('AI2:AO2');
        
        for($i = 1; $i < 7 ; $i ++){
            $this->_excelObject->getActiveSheet()->setCellValue($this->cells[33 + $i].'3', $i.'-6');
        }
        
        $name = '小计';
        $this->_excelObject->getActiveSheet()->setCellValue('AO3', $name);
    }
    
    
    
    private function _dealAP2BJ(){
        $this->_excelObject->getActiveSheet()->getStyle('AP1:BJ3')->getFill()
                ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()->setRGB('333399');
        
        $name = '实际还款情况';
        $this->_excelObject->getActiveSheet()->setCellValue('AP1', $name)
            ->mergeCells('AP1:BJ1');
        
        $baseCellIndex = 40;
        for($i = 1 ; $i <= 7 ; $i ++){
            $cellIndex_1 = $baseCellIndex + ($i - 1) * 3 + 1;
            $cellIndex_2 = $baseCellIndex + ($i - 1) * 3 + 2;
            $cellIndex_3 = $baseCellIndex + $i * 3;
            
            $name = '还款'.$i;
            $this->_excelObject->getActiveSheet()->setCellValue($this->cells[$cellIndex_1].'2', $name)
                ->mergeCells($this->cells[$cellIndex_1].'2:'.$this->cells[$cellIndex_3].'2');

            $name = '日期';
            $this->_excelObject->getActiveSheet()->setCellValue($this->cells[$cellIndex_1].'3', $name);

            $name = '金额';
            $this->_excelObject->getActiveSheet()->setCellValue($this->cells[$cellIndex_2].'3', $name);

            $name = '是否逾期';
            $this->_excelObject->getActiveSheet()->setCellValue($this->cells[$cellIndex_3].'3', $name);
        }
        
        $name = '合计';
        $this->_excelObject->getActiveSheet()->setCellValue('BH2', $name);
        
    }
    
    
    private function _dealBK(){
        $this->_excelObject->getActiveSheet()->getStyle('BK1:BK3')->getFill()
                ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()->setRGB('99CCFF');
        
        $name = '备注';
        $this->_excelObject->getActiveSheet()->setCellValue('BK1', $name)
            ->mergeCells('BK1:BK3');
    }
    
    /**
     * 设置列中数字格式
     * 
     * @param int $titleLineNum
     */
    protected function _setSelfCellFormat($titleLineNum){
        $dataLine = count($this->_dataList);
        
        //文本格式的列
        $this->_textLineKey = array(3);
        //浮点数格式
        $float_00_Key = array(9 , );
        //百分比格式
        $float_100_Key = array(10 , );
        
        $activeSheet = $this->_excelObject->getActiveSheet();
        foreach($float_00_Key as $cellIndex){
            $cell = $this->cells[$cellIndex];
            $activeSheet->getStyle($cell.($titleLineNum + 1))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $activeSheet->duplicateStyle($activeSheet->getStyle($cell.($titleLineNum + 1)), $cell.($titleLineNum + 2).':'.$cell.(count($this->_dataList)+$titleLineNum) );
        }
        foreach($float_100_Key as $cellIndex){
            $cell = $this->cells[$cellIndex];
            $activeSheet->getStyle($cell.($titleLineNum + 1))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00);
            $activeSheet->duplicateStyle($activeSheet->getStyle($cell.($titleLineNum + 1)), $cell.($titleLineNum + 2).':'.$cell.(count($this->_dataList)+$titleLineNum) );
        }
    }
}
