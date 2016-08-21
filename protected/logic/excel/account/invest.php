<?php
/**
 * 客户账报表 
 * - 指定日期前一天还款的标为主体
 *
 * @author cao_zl
 */
class logic_excel_account_invest extends logic_excel_base {
    
    private $_fileNameFixedInvest = 'account_invest_repayment_';
    
    private $_tbl_borrow = 'deayou_borrow';
    
    private $_tbl_recover = 'deayou_borrow_recover';
    
    /**
     * 借款记录临时存储位
     * @var array 
     */
    private $_tmpSaveBorrow = array();
    
    /**
     * 客户账报表 
     * - 指定日期前一天还款的标为主体
     * 
     * @param string $date 指定日期，格式：2015-05-05
     * @return boolean|string
     */
    public function createInvestPaymentAccountExcel($date){
        $minId = 0;
        $file_list = array();
        $fcount = 1;
        $limit = $this->_maxLineNum;
        while(true){//一次循环生成一份excl文件
            $file_name = $this->_fileNameFixedInvest.$date.'_'.$fcount++;
            $dataList = array();
            $records = utils_mysql::getSelector()->from($this->_tbl_recover)
                    ->fromColumns(array(
                        'max(id) as max_id',
                        'borrow_nid','recover_yestime',
                        'sum(recover_account) as sum_recover_account',
                        'user_id',
                    ))
                    ->where('id > ?' , $minId)
                    ->where('recover_yestime >= ?' , strtotime($date)-86400)
                    ->where('recover_yestime < ?' , strtotime($date))
                    ->where('recover_type = ?' , 'yes')
                    ->group('borrow_nid, user_id')
                    ->order('max_id')
                    ->limit($this->_maxLineNum)
                    ->fetchAll();
            if(!$records){
                break;
            }
            foreach($records as $k => $record){
                $minId = $record['max_id'];
                $userinfo = logic_user_user::getInstance()->getUserInfo($record['user_id']);
                $borrowRow = $this->_getTmpSaveBorrow($record['borrow_nid']);
                $line = array(
                    $record['user_id'],
                    $userinfo ? $userinfo['realname'] : '',//'用户姓名'
                    $borrowRow['borrow_nid'],
                    logic_borrow_borrow::getInstance()->getBorrowTypeName($borrowRow['borrow_type']),//产品类型
                    logic_borrow_borrow::getInstance()->getBorrowStyleName($borrowRow['borrow_style']),//还款方式
                    date('Y-m-d' , $borrowRow['reverify_time']),
                    '',//到期日
                    logic_borrow_borrow::getInstance()->getBorrowDurationShow($borrowRow['borrow_period'], $borrowRow['borrow_style'], $borrowRow['borrow_type']),
                    '=G{line}-TODAY()',
                    '',//投资总额
                    $borrowRow['borrow_apr'] / 100,
                    "",//逾期利率（每日）
                    
                    '',//借款利息1-6
                    '',//借款利息2-6
                    '',//借款利息3-6
                    '',//借款利息4-6
                    '',//借款利息5-6
                    '',//借款利息6-6
                    '',//借款利息 小计
                    '',//逾期利息
                    '',//合计
                    
                    '',//收款1 日期
                    '',//收款1 金额
                    '',//收款1 是否逾期
                    
                    '',//收款2 日期
                    '',//收款2 金额
                    '',//收款2 是否逾期
                    
                    '',//收款3 日期
                    '',//收款3 金额
                    '',//收款3 是否逾期
                    
                    '',//收款4 日期
                    '',//收款4 金额
                    '',//收款4 是否逾期
                    
                    '',//收款5 日期
                    '',//收款5 金额
                    '',//收款5 是否逾期
                    
                    '',//收款6 日期
                    '',//收款6 金额
                    '',//收款6 是否逾期
                    
                    '',//合计 日期
                    '',//合计 金额
                    '',//合计 是否逾期
                    
                    '',//备注
                );
                $this->_dealRecoverList($record['borrow_nid'] , $line);
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
            $this->_tmpSaveBorrow = array();
            $tarName = $this->_saveBasePath.$this->_fileNameFixedInvest.$date.'.zip';
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
    
    
    private function _dealRecoverList($borrow_nid , &$line){
        $recoverList = logic_repayment_repay::getInstance()->getRecoverRecordsGroupPeriodByBorrowNidAndUserid($borrow_nid, $line[0]);
        
        $countInfo = array(
            'sum_recover_account'       => 0,//还款总金额
            'sum_recover_capital'       => 0,//应还本金总额
            'sum_recover_interest'      => 0,//应还利息
            'sum_recover_account_yes'   => 0,//已还总金额
            'sum_recover_capital_yes'   => 0,//已还本金
            'plan_last_repay_date'      => '',//计划最后一期的还款时间
            'last_repay_date'           => '',//实际的最后一次还款时间
        );
        foreach($recoverList as $recoverInfo){
            $countInfo['sum_recover_account'] = round($countInfo['sum_recover_account'] + $recoverInfo['sum_recover_account'] , 2);
            $countInfo['sum_recover_capital'] = round($countInfo['sum_recover_capital'] + $recoverInfo['sum_recover_capital'] , 2);
            $countInfo['sum_recover_interest'] = round($countInfo['sum_recover_interest'] + $recoverInfo['sum_recover_interest'] , 2);
            $countInfo['sum_recover_account_yes'] = round($countInfo['sum_recover_account_yes'] + $recoverInfo['sum_recover_account_yes'] , 2);
            $countInfo['sum_recover_capital_yes'] = round($countInfo['sum_recover_capital_yes'] + $recoverInfo['sum_recover_capital_yes'] , 2);
            $countInfo['plan_last_repay_date'] = date('Y-m-d' , $recoverInfo['recover_time']);
            if($recoverInfo['recover_status'] == 1){
                $countInfo['last_repay_date'] = date('Y-m-d' , $recoverInfo['recover_yestime']);
            }
            $line[11 + $recoverInfo['recover_period']] = $recoverInfo['sum_recover_interest'];//应还利息 X-6
            $line[20 + ($recoverInfo['recover_period'] - 1) * 3 + 1] = ($recoverInfo['recover_status'] == 1) ? date('Y-m-d',$recoverInfo['recover_time']) : '';
            $line[20 + ($recoverInfo['recover_period'] - 1) * 3 + 2] = ($recoverInfo['recover_status'] == 1) ? $recoverInfo['sum_recover_account'] : 0;
            $line[20 + $recoverInfo['recover_period'] * 3] = ($recoverInfo['recover_status'] == 1) ? logic_date::getDelayTimeShow(date('Y-m-d' , $recoverInfo['recover_time']), date('Y-m-d' , $recoverInfo['recover_yestime'])) : '待还';
        }

        $line[6]  = $countInfo['plan_last_repay_date'];//投资到期日
        if($countInfo['sum_recover_account'] <= $countInfo['sum_recover_account_yes']){
            $line[8]  = '结清';
        }
        $line[9]  = $countInfo['sum_recover_capital'];//投资本金
        
        $line[18] = $countInfo['sum_recover_interest'];//投资总额 - 小计
        $line[20] = $countInfo['sum_recover_interest'];//投资总额 - 合计
        //合计
        $line[39] = $countInfo['last_repay_date'];
        $line[40] = $countInfo['sum_recover_account_yes'];
//        $line[41] = $countInfo[];
    }
    
    /**
     * 设置个性化的表头
     * @return int
     */
    protected function _setTitle() {
        $titleCount = 43;
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
        $this->_dealM2U();
        $this->_dealV2AP();
        $this->_dealAQ();
        
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
        
        $name = '投资信息';
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
        
        $name = '投资本金';
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
    
    
    private function _dealM2U(){
        $this->_excelObject->getActiveSheet()->getStyle('M1:U3')->getFill()
                ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()->setRGB('808000');
        
        $name = '投资利息管理';
        $this->_excelObject->getActiveSheet()->setCellValue('M1', $name)
            ->mergeCells('M1:U1');
        
        $name = '借款利息';
        $this->_excelObject->getActiveSheet()->setCellValue('M2', $name)
            ->mergeCells('M2:S2');
        
        for($i = 1; $i < 7 ; $i ++){
            $this->_excelObject->getActiveSheet()->setCellValue($this->cells[11 + $i].'3', $i.'-6');
        }
        
        $name = '小计';
        $this->_excelObject->getActiveSheet()->setCellValue('S3', $name);
        
        $name = '逾期利息';
        $this->_excelObject->getActiveSheet()->setCellValue('T2', $name)
            ->mergeCells('T2:T3');
        
        $name = '合计';
        $this->_excelObject->getActiveSheet()->setCellValue('U2', $name)
            ->mergeCells('U2:U3');
    }
    
    
    
    private function _dealV2AP(){
        $this->_excelObject->getActiveSheet()->getStyle('V1:AP3')->getFill()
                ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()->setRGB('333399');
        
        $name = '实际收回情况';
        $this->_excelObject->getActiveSheet()->setCellValue('V1', $name)
            ->mergeCells('V1:AP1');
        
        $baseCellIndex = 20;
        for($i = 1 ; $i <= 7 ; $i ++){
            $cellIndex_1 = $baseCellIndex + ($i - 1) * 3 + 1;
            $cellIndex_2 = $baseCellIndex + ($i - 1) * 3 + 2;
            $cellIndex_3 = $baseCellIndex + $i * 3;
            
            $name = '收款'.$i;
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
        $this->_excelObject->getActiveSheet()->setCellValue('AN2', $name);
        
    }
        
    
    private function _dealAQ(){
        $this->_excelObject->getActiveSheet()->getStyle('AQ1:AQ3')->getFill()
                ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                ->getStartColor()->setRGB('99CCFF');
        
        $name = '备注';
        $this->_excelObject->getActiveSheet()->setCellValue('AQ1', $name)
            ->mergeCells('AQ1:AQ3');
    }
    
    /**
     * 设置列中数字格式
     * 
     * @param int $titleLineNum
     */
    protected function _setSelfCellFormat($titleLineNum){
        $dataLineNum = count($this->_dataList);
        
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
            $activeSheet->duplicateStyle($activeSheet->getStyle($cell.($titleLineNum + 1)), $cell.($titleLineNum + 2).':'.$cell.($dataLineNum+$titleLineNum) );
        }
        foreach($float_100_Key as $cellIndex){
            $cell = $this->cells[$cellIndex];
            $activeSheet->getStyle($cell.($titleLineNum + 1))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00);
            $activeSheet->duplicateStyle($activeSheet->getStyle($cell.($titleLineNum + 1)), $cell.($titleLineNum + 2).':'.$cell.($dataLineNum+$titleLineNum) );
        }
    }
    
    /**
     * 临时存储借款记录，报表生成后清空
     * 
     * @param string $borrow_nid 标的标记
     * @return array
     */
    private function _getTmpSaveBorrow($borrow_nid){
        if(!isset($this->_tmpSaveBorrow[$borrow_nid])){
            $this->_tmpSaveBorrow[$borrow_nid] = utils_mysql::getSelector()->from($this->_tbl_borrow)
                    ->where('borrow_nid = ?' , $borrow_nid)->fetchRow();
        }
        return $this->_tmpSaveBorrow[$borrow_nid];
    }
    
}
