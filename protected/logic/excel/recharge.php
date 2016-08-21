<?php
/**
 * 充值报表
 * - 充值成功、失败报表
 *
 * @author cao_zl
 */
class logic_excel_recharge extends logic_excel_base {
    
    private $_successNameFixed = 'recharge_success_';
    
    private $_failedNameFiexed = 'recharge_failed_';
    
    /**
     * 充值标题基本列
     * @return array
     */
    private function _titile(){
        return array(
            'ID',
            array('format' => 'text' , 'value' => '用户名'),
            'userid',
            '姓名',
            array('format' => 'float_00' , 'value' => '金额'),
            array('format' => 'text' , 'value' => '充值流水号'),
            '发起时间',
            '成功时间',
            '充值平台',
            '红包相关',
            '类型',
            '备注',
        );
    } 
    
    /**
     * 生成充值成功报表
     * 
     * @param string $date 日期
     */
    public function createRechargeSuccessExcel($date){
        $minId = 0;
        $limit = $this->_maxLineNum;
        $where = array('status' => 1, 'verify_time_stime' => strtotime($date) - 86400 , 'verify_time_etime' => strtotime($date));
        $file_list = array();
        $fcount = 1;
        while(true){//一次循环生成一份excl文件
            $file_name = $this->_successNameFixed.$date.'_'.$fcount++;
            $columns = array('id' , 'user_id' , 'money' , "payment" , 'nid' , 'addtime' , 'remark' , 'type' , 'verify_time' , 'addtime');
            $where['min_id'] = $minId;
            $records = logic_accounts_recharge::getInstance()->getListOrderById($columns, $where, $limit);
            if(!$records){
                break;
            }
            $dataList = array();
            foreach($records as $record){
                $minId = $record['id'];
                $user = logic_user_user::getInstance()->getUser($record['user_id']);
                $userinfo = logic_user_user::getInstance()->getUserInfo($record['user_id']);
                $dataList[] = array(
                    $record['id'],
                    $user ? $user['username'] : '用户未找到',
                    $record['user_id'],
                    $userinfo ? $userinfo['realname'] : '',
                    $record['money'],
                    $record['nid'],
                    $record['addtime'] ? date('Y-m-d H:i:s' , $record['addtime']) : '-',
                    $record['verify_time'] ? date('Y-m-d H:i:s' , $record['verify_time']) : '-',
                    ($record['payment'] == 'llpay') ? '连连' : (($record['payment'] == 'sina') ? '新浪' : '中金'),
                    '-',
                    ($record['type'] == 1) ? '线上充值' : '线下充值',
                    $record['remark'],
                );
            }
            $count = count($records);
            unset($records);
            $dataList[] = array();
            $dataList[] = array('合计' , '' , '' , $count.'条' , '=SUM(E2:E'.($count + 1).')');
            $createResult = $this->createExcel($file_name, $this->_titile(), $dataList, true);
            unset($dataList);
            $file_list[] = $createResult;
        }
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_successNameFixed.$date.'.zip';
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
     * 生成充值失败报表
     * @param string $date 日期
     */
    public function createRechargeFailedExcel($date){
        $minId = 0;
        $limit = $this->_maxLineNum;
        $where = array('status' => 1, 'stime' => strtotime($date) - 86400 , 'etime' => strtotime($date));
        $file_list = array();
        $fcount = 1;
        while(true){//一次循环生成一份excl文件
            $file_name = $this->_failedNameFiexed.$date.'_'.$fcount++;
            $columns = array('id' , 'nid' , 'user_id' , 'money' , 'payment' , 'type' , 'addtime' , 'remark');
            $where['min_id'] = $minId;
            $records = logic_accounts_recharge::getInstance()->getListOrderById($columns, $where, $limit);
            if(!$records){
                break;
            }
            $dataList = array();
            foreach($records as $record){
                $minId = $record['id'];
                $user = logic_user_user::getInstance()->getUser($record['user_id']);
                $userinfo = logic_user_user::getInstance()->getUserInfo($record['user_id']);
                $dataList[] = array(
                    $record['id'],
                    $user ? $user['username'] : '用户未找到',
                    $record['user_id'],
                    $userinfo ? $userinfo['realname'] : '',
                    $record['money'],
                    $record['nid'],
                    date('Y-m-d H:i:s' , $record['addtime']),
                    ($record['payment'] == 'llpay') ? '连连' : (($record['payment'] == 'sina') ? '新浪' : '中金'),
                    '-',
                );
            }
            $count = count($records);
            unset($records);
            $dataList[] = array();
            $dataList[] = array('合计' , '' , '' , $count.'条' , '=SUM(E2:E'.($count + 1).')');
            $createResult = $this->createExcel($file_name, $this->_titile(), $dataList, true);
            unset($dataList);
            $file_list[] = $createResult;
        }
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_failedNameFiexed.$date.'.zip';
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
                return false;
            }
        }
        
    }
    
}
