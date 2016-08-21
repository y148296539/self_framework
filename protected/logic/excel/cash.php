<?php
/**
 * 提现相关报表
 * - 清算 成功、失败 报表
 * - 结算 成功、失败 报表
 *
 * @author cao_zl
 */
class logic_excel_cash extends logic_excel_base {
    
    private $_cash_tbl = 'deayou_account_cash';
    
    private $_qingsuanSuccessFixed = 'qingsuan_success_';
    
    private $_qingsuanFailedFixed = 'qingsuan_failed_';
    
    private $_jiesuanSuccessFixed = 'jiesuan_success_';
    
    private $_jiesuanFailedFixed = 'jiesuan_failed_';
    
    /**
     * 标题基本列
     * @return array
     */
    private function _qingsuan_titile(){
        return array(
            '记录ID',
            '提现人uid', 
            array('format' => 'text' , 'value' => '提现流水号'),
            array('format' => 'float_00' , 'value' => '提现金额'),
            '提现状态',
            array('format' => 'text' , 'value' => '提现账户'),
            '提现通道',
            '审核说明',
            '申请时间',
            '审核时间',
        );
    } 
    
    /**
     * 生成清算成功报表
     * - 指定时间周期中，自动审核通过
     * - 截止周期前，手动审核通过的
     * 
     * @param string $date 日期
     */
    public function createQingsuanSuccessExcel($date){
        $minId = 0;
        $limit = $this->_maxLineNum;
        $file_list = array();
        $fcount = 1;
        while(true){//一次循环生成一份excl文件
            $file_name = $this->_qingsuanSuccessFixed.$date.'_'.$fcount++;
            $records = utils_mysql::getSelector()->from($this->_cash_tbl)
                    ->fromColumns('id,user_id,nid,credited,status,account,payment,verify_remark,addtime,verify_time')
                    ->where('id > ?' , $minId)
                    ->where('addtime >= ?' , strtotime($date) - 86400)
                    ->where('addtime < ?' , strtotime($date))
                    ->where('status = ?' , -2)
                    ->order('id')
                    ->limit($limit)
                    ->fetchAll();
            if(!$records){
                break;
            }
            $dataList = array();
            foreach($records as $record){
                $minId = $record['id'];
                $dataList[] = array(
                    $record['id'],
                    $record['user_id'], 
                    $record['nid'],
                    $record['credited'],
                    logic_accounts_cash::getInstance()->translateStatus($record['status']),
                    $record['account'],
                    ($record['payment'] == 'llpay') ? '连连' : (($record['payment'] == 'sina') ? '新浪' : '中金'),
                    $record['verify_remark'],
                    $record['addtime'] ? date('Y-m-d H:i:s' , $record['addtime']) : '-',
                    $record['verify_time'] ? date('Y-m-d H:i:s' , $record['verify_time']) : '',
                );
            }
            $count = count($records);
            unset($records);
            $dataList[] = array();
            $dataList[] = array('共计：' , $count.'条' , '', '=SUM(D2:D'.($count + 1).')');
            $createResult = $this->createExcel($file_name, $this->_qingsuan_titile(), $dataList, true);
            unset($dataList);
            $file_list[] = $createResult;
        }
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_qingsuanSuccessFixed.$date.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                $string = 'createQingsuanSuccessExcel：cretea_zip_failed';
                utils_file::writeLog('cash_zip_failed', $string , 'crontab/excel');
                echo $string;
                return false;
            }
        }
    }
    
    /**
     * 生成清算失败报表
     * @param string $date 指定日期
     */
    public function createQingsuanFailedExcel($date){
        $minId = 0;
        $limit = $this->_maxLineNum;
        $file_list = array();
        $fcount = 1;
        while(true){//一次循环生成一份excl文件
            $file_name = $this->_qingsuanFailedFixed.$date.'_'.$fcount++;
            $records = utils_mysql::getSelector()->from($this->_cash_tbl)
                    ->fromColumns('id,user_id,nid,credited,status,account,payment,verify_remark,addtime,verify_time')
                    ->where('id > ?' , $minId)
                    ->where('addtime >= ?' , strtotime($date) - 86400)
                    ->where('addtime < ?' , strtotime($date))
                    ->where('status = 6')
                    ->order('id')
                    ->limit($limit)
                    ->fetchAll();
            if(!$records){
                break;
            }
            $dataList = array();
            foreach($records as $record){
                $minId = $record['id'];
                $dataList[] = array(
                    $record['id'],
                    $record['user_id'], 
                    $record['nid'],
                    $record['credited'],
                    logic_accounts_cash::getInstance()->translateStatus($record['status']),
                    $record['account'],
                    ($record['payment'] == 'llpay') ? '连连' : (($record['payment'] == 'sina') ? '新浪' : '中金'),
                    $record['verify_remark'],
                    $record['addtime'] ? date('Y-m-d H:i:s' , $record['addtime']) : '-',
                    $record['verify_time'] ? date('Y-m-d H:i:s' , $record['verify_time']) : '',
                );
            }
            $count = count($records);
            unset($records);
            $dataList[] = array();
            $dataList[] = array('共计：' , $count.'条' , '', '=SUM(D2:D'.($count + 1).')');
            $createResult = $this->createExcel($file_name, $this->_qingsuan_titile(), $dataList, true);
            unset($dataList);
            $file_list[] = $createResult;
        }
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_qingsuanFailedFixed.$date.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                $string = 'createQingsuanFailedExcel：cretea_zip_failed';
                utils_file::writeLog('cash_zip_failed', $string , 'crontab/excel');
                echo $string;
                return false;
            }
        }
    }
    
    /**
     * 生成结算成功报表
     * - 昨天下午4点之前的所有-2记录和今天手动成功的记录
     * @param string $date 指定日期,例如：2015-01-20
     * 
     */
    public function createJiesuanSuccessExcel($date){
        $minId = 0;
        $limit = $this->_maxLineNum;
        $file_list = array();
        $fcount = 1;
        while(true){//一次循环生成一份excl文件
            $file_name = $this->_jiesuanSuccessFixed.$date.'_'.$fcount++;
            $records = utils_mysql::getSelector()->from($this->_cash_tbl)
                    ->fromColumns('id,user_id,nid,credited,status,account,payment,verify_remark,addtime,verify_time')
                    ->where('id > ?' , $minId)
                    ->where('verify_time >= ?' , strtotime($date) - 86400)
                    ->where('verify_time < ?' , strtotime($date))
                    ->where('status = ?' , 1)
//                    ->where("( (addtime >=".(strtotime($date)-56*3600)." and addtime < ".(strtotime($date)-32*3600)." 
//and verify_remark not like '审核通过%' and status<>6) or (verify_time >=".(strtotime($date)-24*3600)." and verify_time < ".strtotime($date)." 
//and verify_remark like '审核通过%' and status=1) )")
//                    ->where('((addtime < "'.(strtotime($date) - 8 * 3600).'" and status = "-2") or (verify_time >= "'.(strtotime($date)-32*3600).'" and verify_time < "'.(strtotime($date)-8*3600).'" and verify_remark like "审核通过%" and status=1) )')
//                    ->where("((addtime >=".(strtotime($date)-56*3600)." and verify_remark not like '审核通过%' and status<>6) or (verify_time >= ".(strtotime($date)-24*3600)." and verify_time < ".strtotime($date)." and verify_remark like '审核通过%' and status=1))")
//                    ->where("((addtime between UNIX_TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL -56 HOUR)) and UNIX_TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL -32 HOUR)) and verify_remark not like '审核通过%' and status<>6) or (verify_time between UNIX_TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL -24 HOUR)) and UNIX_TIMESTAMP(CURDATE()) and verify_remark like '审核通过%' and status=1))")
                    ->order('id')
                    ->limit($limit)
                    ->fetchAll();
            if(!$records){
                break;
            }
            $dataList = array();
            foreach($records as $record){
                $minId = $record['id'];
                $dataList[] = array(
                    $record['id'],
                    $record['user_id'], 
                    $record['nid'],
                    $record['credited'],
                    logic_accounts_cash::getInstance()->translateStatus($record['status']),
                    $record['account'],
                    ($record['payment'] == 'llpay') ? '连连' : (($record['payment'] == 'sina') ? '新浪' : '中金'),
                    $record['verify_remark'],
                    $record['addtime'] ? date('Y-m-d H:i:s' , $record['addtime']) : '-',
                    $record['verify_time'] ? date('Y-m-d H:i:s' , $record['verify_time']) : '',
                );
            }
            $count = count($records);
            unset($records);
            $dataList[] = array();
            $dataList[] = array('共计：' , $count.'条' , '' , '=SUM(D2:D'.(count($dataList) + 1).')');
            $createResult = $this->createExcel($file_name, $this->_qingsuan_titile(), $dataList, true);
            unset($dataList);
            $file_list[] = $createResult;
        }
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_jiesuanSuccessFixed.$date.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                $string = 'createJiesuanSuccessExcel：cretea_zip_failed';
                utils_file::writeLog('cash_zip_failed', $string , 'crontab/excel');
                echo $string;
                return false;
            }
        }
    }
    
    
    
    /**
     * 生成结算失败报表
     * @param string $date 指定日期
     */
    public function createJiesuanFailedExcel($date){
        $minId = 0;
        $limit = $this->_maxLineNum;
        $file_list = array();
        $fcount = 1;
        while(true){//一次循环生成一份excl文件
            $file_name = $this->_jiesuanFailedFixed.$date.'_'.$fcount++;
            $records = utils_mysql::getSelector()->from($this->_cash_tbl)
                    ->fromColumns('id,user_id,nid,credited,status,account,payment,verify_remark,addtime,verify_time')
                    ->where('id > ?' , $minId)
                    ->where('verify_time >= ?' , strtotime($date) - 86400)
                    ->where('verify_time < ?' , strtotime($date))
                    ->where('status in (7,11,4,5,2)')
//                    ->where('addtime >= ?' , strtotime($date)-56*3600)
//                    ->where('addtime < ?' , strtotime($date)-32*3600)
//                    ->where('status in (0,2,4,5,6,7,8,10)')
//                    ->where('verify_remark not like "审核通过%"')
                    ->order('id')
                    ->limit($limit)
                    ->fetchAll();
            if(!$records){
                break;
            }
            $dataList = array();
            foreach($records as $record){
                $minId = $record['id'];
                $dataList[] = array(
                    $record['id'],
                    $record['user_id'], 
                    $record['nid'],
                    $record['credited'],
                    logic_accounts_cash::getInstance()->translateStatus($record['status']),
                    $record['account'],
                    ($record['payment'] == 'llpay') ? '连连' : (($record['payment'] == 'sina') ? '新浪' : '中金'),
                    $record['verify_remark'],
                    $record['addtime'] ? date('Y-m-d H:i:s' , $record['addtime']) : '-',
                    $record['verify_time'] ? date('Y-m-d H:i:s' , $record['verify_time']) : '',
                );
            }
            $count = count($records);
            unset($records);
            $dataList[] = array();
            $dataList[] = array('共计：' , $count.'条' , '' , '=SUM(D2:D'.($count + 1).')');
            $createResult = $this->createExcel($file_name, $this->_qingsuan_titile(), $dataList, true);
            unset($dataList);
            $file_list[] = $createResult;
        }
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_jiesuanFailedFixed.$date.'.zip';
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
                utils_file::writeLog('cash_zip_failed', $string , 'crontab/excel');
                echo $string;
                return false;
            }
        }
    }
    
}
