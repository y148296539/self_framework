<?php
/**
 * 礼品卡报表相关逻辑
 *
 * @author cao_zl
 */
class logic_excel_giftcard extends logic_excel_base {
    
    private $_tbl_cardlog = 'deayou_users_cardlog';
    
    private $_excel_giftcard_active_prefix = 'gift_card_active_';
    
    /**
     * 创建制定日期前一日礼品卡激活报表
     * @param string $date 格式："2015-08-26",给定的日期，产生报表内容的后一日
     * @return mixed
     */
    public function createGiftCardActiveExcel($date){
        $minId = 0;
        $fcount = 1;
        $file_list = array();
        while(true){
            $file_name = $this->_excel_giftcard_active_prefix.$date.'_'.$fcount++;
            $dataList = array();
            $records = utils_mysql::getSelector()->from($this->_tbl_cardlog , 'c')
                    ->fromColumns(array('c.id' , 'c.card_id' , 'c.member_id' , 'c.create_time' , 'u.username' , 'c.account'))
                    ->leftJoin('deayou_users', 'u', 'c.member_id = u.user_id')
                    ->where('c.id > ?' , $minId)
                    ->where('c.packet_type = 1')
                    ->where('c.pstatus = 1')
                    ->where('c.create_time >= ?' , strtotime($date) - 86400)
                    ->where('c.create_time < ?' , strtotime($date))
                    ->order('c.id')
                    ->limit($this->_maxLineNum)
                    ->fetchAll();
            if(!$records){
                break;
            }
            foreach($records as $record){
                $minId = $record['id'];
                $dataList[] = array(
                    $record['card_id'],
                    $record['member_id'],
                    $record['username'],
                    $record['account'],
                    $record['create_time'] ? date('Y-m-d H:i:s' , $record['create_time']) : '-',
                );
            }
            $count = count($records);
            unset($records);
            $dataList[] = array();
            $dataList[] = array('共计：' , $count.'条' , '', '=SUM(D2:D'.($count + 1).')');
            $createResult = $this->createExcel($file_name, $this->_giftCardActiveTitle(), $dataList, true);
            unset($dataList);
            $file_list[] = $createResult;
        }
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_excel_giftcard_active_prefix.$date.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                $string = 'createGiftCardActiveExcel：cretea_zip_failed';
                utils_file::writeLog('cash_zip_failed', $string , 'crontab/excel');
                echo $string;
                return false;
            }
        }
            
    }
    
    
    private function _giftCardActiveTitle(){
        return array(
            array('format' => 'text' , 'value' => '卡号'),
            '用户userid', 
            array('format' => 'text' , 'value' => '用户名'),
            array('format' => 'float_00' , 'value' => '金额'),
            '激活时间',
        );
    }
    
    /**
     * 创建制定日期前一日礼品卡总的激活报表
     * - 考虑只在每月的第一日产出上一月的总激活
     * - 取出所有投资表中已起息的标对应的礼品卡位红包对应金额
     * 
     * @param string $date 格式："2015-08-26",给定的日期，产生报表内容的后一日
     * @return mixed
     */
    public function createGiftUseCountExcel($date){
        $cutYmd = explode('-' , $date);
        if(!isset($cutYmd[2]) || intval($cutYmd[2]) > 1){
            return false;
        }
        $dataList = utils_mysql::getSelector()->from('deayou_borrow' , 'b')
                ->fromColumns(array(
                    'from_unixtime(b.reverify_time , "%Y-%m") as ym',
                    'sum(p.returnprice) as sum_price',
                ))
                ->leftJoin('deayou_borrow_tender', 't', 'b.borrow_nid = t.borrow_nid')
                ->leftJoin('deayou_packet_users', 'p' , 't.extra_packid = p.packid')
                ->where('b.reverify_status = 3')
                ->where('b.reverify_time > unix_timestamp("2015-06-26")')
                ->where('length(t.extra_packid) > 5')
                ->group('ym')
                ->order('ym desc')
                ->limit($this->_maxLineNum)
                ->fetchAll();
        $dataList[] = array();
        $dataList[] = array('总计：' , '=SUM(B2:B'.(count($dataList)).')');
        $file_info = $this->createExcel('giftcard_month_end_count', array('起息年月' , '已使用总金额'), $dataList , true);
        unset($dataList);
        $tarName = $this->_saveBasePath.'giftcard_month_end_count_'.$date.'.zip';
        $zip = new ZipArchive();
        if($zip->open($tarName, ZipArchive::CREATE) === true){
            $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
            $zip->close();
            unlink($file_info['save_path'].$file_info['file_name']);
            return $tarName;
        }else{
            $string = 'createXianjinPacketExcel：cretea_zip_failed';
            utils_file::writeLog('cash_zip_failed', $string , 'crontab/excel');
            echo $string;
            return false;
        }
    }
    
}
