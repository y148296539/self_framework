<?php
/**
 * 红包相关报表
 *
 * @author cao
 */
class logic_excel_redpacket extends logic_excel_base {
    /**
     * 现金红包使用情况报表前缀
     * @var string
     */
    private $_xianjinPacketFixed = 'xianjinPacket_';
    /**
     * 可提现红包（全量）报表前缀
     * @var string
     */
    private $_ketixianPacketAllFixed = 'ketixianPacket_all_';
    /**
     * 可提现红包（增量）报表前缀
     * @var string 
     */
    private $_ketixianPacketAppendFixed = 'ketixianPacket_append_';
    /**
     * 投资红包报表前缀
     * @var string 
     */
    private $_tenderPacketFixed = 'tenderPacket_reverify_';
    /**
     * 冻结账变动日志表名
     * @var string
     */
    private $_tbl_offi_forst_log = 'dtd_official_forst_changelog';
    /**
     * 用户拥有红包表表名
     * @var string
     */
    private $_tbl_packet = 'deayou_packet_users';
    /**
     * 用户投资表表名
     * @var string
     */
    private $_tbl_tender = 'deayou_borrow_tender';
    /**
     * 资金变动日志表
     * @var string
     */
    private $_tbl_account_log = 'deayou_account_log';
    /**
     * 现金报表表头
     * @return array
     */
    private function _getXianjinExcelTitle(){
        return array(
            '投资项目',
            array('format' => 'float_00' , 'value' => '投资金额'),
            '用户号',
            '红包信息',
            array('format' => 'float_00' , 'value' => '红包金额'),
            '红包类型',
            '使用状态',
//            '使用时间',
            '赠送时间',
            '失效时间',
        );
    }
    
    /**
     * 创建现金红包报表
     * - 条件紊乱，临时处理
     * - 状态取指定时间区间中 “已使用” 和 “使用中” 的记录(状态为1和3的记录)
     * 
     * @param $date 生成日期，数据将取该日期以前24小时的记录
     * @return mixed
     */
    public function createXianjinPacketExcel($date){
        $etime = strtotime($date);
        $stime = $etime - 86400;
//        $min_id = 0;
//        $columns = array('p.id', 'p.infoid','p.typereturn','p.returnprice','p.packid',
//            'p.pstatus','p.remark','p.uptime' , 'p.ctime' , 'p.etime', 'p.member_id',
//            't.id as tender_id' , 't.account' , 't.borrow_nid' ,
//        );
        $file_list = array();
        $fcount = 1;
        while(true){
            if($fcount > 1){
                break;
            }
            $file_name = $this->_xianjinPacketFixed.$date.'_'.$fcount++;
            $records = utils_mysql::getSelector()->fetchAllBySql("SELECT B.borrow_nid,B.account,B.user_id,A.infoid,A.returnprice,A.typereturn,A.pstatus,A.givetime,A.invalidtime FROM (SELECT member_id user_id,infoid,returnprice,packid,typereturn,pstatus,FROM_UNIXTIME(ctime, '%Y-%m-%d %H:%i:%S' ) givetime,FROM_UNIXTIME(etime, '%Y-%m-%d %H:%i:%S' ) invalidtime FROM deayou_packet_users) AS A right JOIN deayou_borrow_tender AS B ON A.packid=B.packid WHERE B.addtime>=".$stime." and B.addtime<=".$etime);
            if(!$records){
                break;
            }
            $dataList = array();
            foreach($records as $record){
//                $min_id = $record['id'];
                $packetInfo = logic_active_info::getInstance()->getPacketTypeInfo($record['infoid']);
//                if(!$record['tender_id']){
//                    $tender = utils_mysql::getSelector()->from($this->_tbl_tender)->fromColumns('id,account,borrow_nid')->where('extra_packid = ?' , $record['packid']);
//                    $record['tender_id'] = $tender ? $tender['id'] : 0;
//                    $record['account'] = $tender ? $tender['account'] : 0;
//                    $record['borrow_nid'] = $tender ? $tender['borrow_nid'] : '-';
//                }
                $dataList[] = array(
                    $record['borrow_nid'],
                    $record['account'],
                    $record['user_id'],
                    $packetInfo ? $packetInfo['title'] : $record['infoid'],
                    $record['returnprice'],
                    $record['typereturn'] ? logic_active_packet::getInstance()->getPacketType($record['typereturn']) : '',
                    is_numeric($record['pstatus']) ? logic_active_packet::getInstance()->getPacketStatus($record['pstatus']) : '',
//                    date('Y-m-d H:i:s' , $record['givetime']),
                    $record['givetime'],//date('Y-m-d H:i:s' , $record['ctime']),
                    $record['invalidtime'],//date('Y-m-d H:i:s' , $record['etime']),
                );
            }
            $count = count($records);
            unset($records);
            $dataList[] = array();
            $dataList[] = array('共计：' , $count.'条' , '', '');//=SUM(D2:D'.($count + 1).')
            $createResult = $this->createExcel($file_name, $this->_getXianjinExcelTitle(), $dataList, true);
            unset($dataList);
            $file_list[] = $createResult;
        }
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_xianjinPacketFixed.$date.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                $string = 'createXianjinPacketExcel：cretea_zip_failed';
                utils_file::writeLog('cash_zip_failed', $string , 'crontab/excel');
                echo $string;
                return false;
            }
        }
        
    }
    
    
    
    /**
     * 创建可提现红包报表(已激活红包) - 全量
     * - 数据量可能出现不定时井喷，5W+
     * @param string $date 生成日期后一天
     */
    public function createKetixianPacketExcelAll($date){
        $min_id = 0;
        $file_list = array();
//        $fcount = 1;
        while(true){
//            $file_name = $this->_ketixianPacketAllFixed.$date.'_'.$fcount++;
            $file_name = $this->_ketixianPacketAllFixed.$date.'_all';
            $records = utils_mysql::getSelector()->from($this->_tbl_packet)
                    ->fromColumns(array('id' , 'member_id' , 'infoid' , 'returnprice' , 'ctime'))
                    ->where('typereturn = 0')
                    ->where('pstatus = 5')
                    ->where('id > ?' , $min_id)
                    ->order('id')
                    ->limit($this->_maxLineNum)
                    ->fetchAll();
            if(!$records){
                break;
            }
            $dataList = array();
            foreach($records as $record){
                $min_id = $record['id'];
                $packetInfo = logic_active_info::getInstance()->getPacketTypeInfo($record['infoid']);
                $dataList[] = array(
                    $record['member_id'],
                    $record['returnprice'],
                    $packetInfo ? trim($packetInfo['title']) : $record['infoid'],
                    $record['infoid'],
                );
            }
            $count = count($records);
            unset($records);
            $createDatasResult = $this->createSimpleExcel($file_name, $this->_ketixianAllTitle(), $dataList , false);
            unset($dataList);
        }
        //统计信息
        $countType = utils_mysql::getSelector()->from($this->_tbl_packet)
                ->fromColumns(array('infoid' , 'count(id) as count' , 'sum(returnprice) as sum_returnprice'))
                ->where('typereturn = 0')
                ->where('pstatus=5')
                ->group('infoid')
                ->fetchAll();
        $dataList = array(
            array(),array(),
            array('全量报表统计：'),
            array('类型', '数量' , '总金额' , '类型标记'),
        );
        $countNum = 0;
        $countPrice = 0;
        foreach($countType as $count){
            $countNum += $count['count'];
            $countPrice = round($countPrice + $count['sum_returnprice'] , 2);
            $packetInfo = logic_active_info::getInstance()->getPacketTypeInfo($count['infoid']);
            $dataList[] = array(
                $packetInfo ? trim($packetInfo['title']) : $count['infoid'],
                $count['count'],
                $count['sum_returnprice'],
                $count['infoid'],
            );
        }
        $dataList[] = array('汇总' , $countNum , $countPrice);
        $createResult = $this->createSimpleExcel($file_name, $this->_ketixianAllTitle(), $dataList);
        unset($dataList);
        $file_list[] = $createResult;
        
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_ketixianPacketAllFixed.$date.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                $string = 'createKetixianPacketExcelAll：cretea_zip_failed';
                utils_file::writeLog('packet_zip_failed', $string , 'crontab/excel');
                echo $string;
                return false;
            }
        }
    }
    
    
    private function _ketixianAllTitle(){
        return array(
            '用户号',
            '可提现红包金额',
            '红包类型',
            '红包类型标记',
        );
    }
    
    /**
     * 指定日期红包报表-增量
     * @param string $date
     */
    public function createKetixianPacketExcelAppend($date){
        $etime = strtotime($date);
        $stime = $etime - 86400;
        $file_list = array();
        $fcount = 1;
        $columns = array(
            'l.id' , 'l.money' , 'l.user_id', 'from_unixtime(l.addtime) as create_time_str',
            'p.infoid' , 'p.typereturn',
        );
        $min_id = 0;
        $countInfo = array();
        while(true){
            $file_name = $this->_ketixianPacketAppendFixed.$date.'_'.$fcount++;
            $records = utils_mysql::getSelector()->from($this->_tbl_account_log , 'l')
                    ->fromColumns($columns)
                    ->leftJoin($this->_tbl_packet, 'p', 'l.code_nid = p.id')
                    ->where('l.type = "packet_send"')
                    ->where('l.id > ?' , $min_id)
                    ->where('l.addtime >= ?' , $stime)
                    ->where('l.addtime < ?' , $etime)
                    ->order('l.id')
                    ->limit($this->_maxLineNum)
                    ->fetchAll();
            if(!$records){
                break;
            }
            $dataList = array();
            foreach($records as $record){
                $min_id = $record['id'];
                $packetInfo = logic_active_info::getInstance()->getPacketTypeInfo($record['infoid']);
                $dataList[] = array(
                    $record['user_id'],
                    $record['money'],
                    $packetInfo ? trim($packetInfo['title']) : $record['infoid'],
                    $record['create_time_str'],
                    $record['infoid'],
                );
                $countInfo[$record['infoid']] = isset($countInfo[$record['infoid']]) ? array(
                    'sum'       => round($countInfo[$record['infoid']]['sum'] + $record['money'] , 2),
                    'count'     => $countInfo[$record['infoid']]['count'] + 1,
                ) : array(
                    'sum'       => $record['money'],
                    'count'     => 1,
                );
            }
            $count = count($records);
            unset($records);
            $dataList[] = array();
            $dataList[] = array('共计：' , $count.'条' , '', '');
            $dataList[] = array();
            $dataList[] = array('增量报表统计：');
            $dataList[] = array('类型', '数量' , '总金额' , '类型标记');
            foreach($countInfo as $infoid => $cInfo){
                $packetInfo = logic_active_info::getInstance()->getPacketTypeInfo($infoid);
                $dataList[] = array(
                    $packetInfo ? trim($packetInfo['title']) : $infoid,
                    $cInfo['count'],
                    $cInfo['sum'],
                    $infoid,
                );
            }
            $file_list[] = $this->createExcel($file_name, $this->_ketixianAppendTitle(), $dataList, true);
            unset($dataList);
        }
        unset($countInfo);
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_ketixianPacketAppendFixed.$date.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                $string = 'createKetixianPacketExcelAppend：create_zip_failed';
                utils_file::writeLog('packet_zip_failed', $string , 'crontab/excel');
                echo $string;
                return false;
            }
        }
    }
    
    /**
     * 可提现红包(增量)报表表头
     * @return array
     */
    private function _ketixianAppendTitle(){
        return array(
            '用户号',
            '可提现红包金额',
            '红包类型',
            '激活时间',
            '红包类型标记',
        );
    }
    
    /**
     * 获取已起息标的所有投资红包记录
     */
    public function createReverifyTenderPacketExcel($date){
        $min_id = 0;
        $file_list = array();
        $fcount = 1;
        $yesterday = date('Y-m-d' , strtotime($date) - 86400);
        $borrow_nids = logic_borrow_borrow::getInstance()->getReverifyBorrowNidByDate($yesterday);
        $countInfo = array();
        while($borrow_nids){
            $file_name = $this->_tenderPacketFixed.$date.'_'.$fcount++;
            $records = utils_mysql::getSelector()->from($this->_tbl_tender , 't')
                    ->fromColumns(array(
                        't.id' , 't.user_id' , 't.addtime',
                        'p.infoid as p_infoid' , 'p.returnprice as p_returnprice' ,
                        'p2.infoid as p2_infoid' , 'p2.returnprice as p2_returnprice' ,
                        't.account','t.borrow_nid','t.packid','t.extra_packid',
                    ))
                    ->leftJoin($this->_tbl_packet, 'p', 't.packid = p.packid')
                    ->leftJoin($this->_tbl_packet, 'p2', 't.extra_packid = p2.packid')
                    ->where('t.borrow_nid in ("'.  implode('","', $borrow_nids).'")')
                    ->where('(length(t.packid) > 5 or length(t.extra_packid) > 5)')
                    ->where('t.id > ?' , $min_id)
                    ->order('t.id')
                    ->limit($this->_maxLineNum - 100)
                    ->fetchAll();
            if(!$records){
                break;
            }
            $dataList = array();
            foreach($records as $record){
                $min_id = $record['id'];
                $packetInfo = $record['p_infoid'] ? logic_active_info::getInstance()->getPacketTypeInfo($record['p_infoid']) : array();
                $packetInfo2 = $record['p2_infoid'] ? logic_active_info::getInstance()->getPacketTypeInfo($record['p2_infoid']) : array();
                if($record['p_infoid']){
                    $dataList[] = array(
                        $record['p_infoid'],
                        $record['addtime'] ? date('Y-m-d H:i:s' , $record['addtime']) : '-',
                        $record['borrow_nid'],
                        $record['account'],
                        $record['user_id'],
                        $packetInfo ? trim($packetInfo['title']) : $record['p_infoid'],
                        $record['p_returnprice'],
                    );
                }
                if($record['p2_infoid']){
                    $dataList[] = array(
                        $record['p2_infoid'],
                        $record['addtime'] ? date('Y-m-d H:i:s' , $record['addtime']) : '-',
                        $record['borrow_nid'],
                        $record['account'],
                        $record['user_id'],
                        $packetInfo2 ? trim($packetInfo2['title']) : $record['p2_infoid'],
                        $record['p2_returnprice'],
                    );
                }
            }
            $count = 0;
            //整体重新循环一遍，重新统计一遍 - 性能允许
            foreach($dataList as $record){
                $count ++;
                $countInfo[$record[0]] = isset($countInfo[$record[0]]) ? array(
                    'sum'       => round($countInfo[$record[0]]['sum'] + $record[6] , 2),
                    'count'     => $countInfo[$record[0]]['count'] + 1,
                ) : array(
                    'sum'       => $record[6],
                    'count'     => 1,
                );
            }
            unset($records);
            $dataList[] = array();
            $dataList[] = array('总计：' , '' , '' , '' , '' , '' , '=SUM(G2:G'.($count+1).')');
            $dataList[] = array();
            $dataList[] = array('增量报表统计：');
            $dataList[] = array('类型', '数量' , '总金额' , '类型标记');
            foreach($countInfo as $infoid => $cInfo){
                $packetInfo = logic_active_info::getInstance()->getPacketTypeInfo($infoid);
                $dataList[] = array(
                    $packetInfo ? trim($packetInfo['title']) : $infoid,
                    $cInfo['count'],
                    $cInfo['sum'],
                    $infoid,
                );
            }
            $file_list[] = $this->createExcel($file_name, $this->_tenderPacketTitle(), $dataList , true);
            unset($dataList);
        }
        if($file_list){
            $tarName = $this->_saveBasePath.$this->_tenderPacketFixed.$date.'.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                $string = 'createReverifyTenderPacketExcel：cretea_zip_failed';
                utils_file::writeLog('packet_zip_failed', $string , 'crontab/excel');
                echo $string;
                return false;
            }
        }
    }
    
    
    private function _tenderPacketTitle(){
        return array(
            '红包标记',
            '投资日期',
            '投资项目',
            array('format' => 'float_00' , 'value' => '投资金额'),
            '用户号',
            '红包类型',
            array('format' => 'float_00' , 'value' => '红包金额'),
        );
    }
    
    /**
     * 创建未起息的投资红包报表
     * 
     * @param string $date
     */
    public function createNotReverifyTenderPacketExcel($date){
        $file_list = array();
        $countInfo = array();
        for($page = 1; $page < 100; $page ++){
            $dataList = utils_mysql::getSelector()->from('deayou_borrow_tender' , 't')
                    ->fromColumns(array(
                        't.user_id' , 't.borrow_nid' , 
                        'from_unixtime(t.addtime) as tender_date',
                        'p.returnprice','p.infoid'
                    ))
                    ->leftJoin('deayou_packet_users', 'p', 't.packid = p.packid')
                    ->where('t.borrow_nid in (select borrow_nid from deayou_borrow where reverify_status <> 3 or (reverify_status =  3 and from_unixtime(reverify_time , "%Y-%m-%d") = "'.$date.'" ))')
                    ->where('t.addtime < unix_timestamp("'.$date.'")')
                    ->where('length(t.packid) > 5')
                    ->order('t.id')
                    ->limit(5000 , $page)
                    ->fetchAll();
            if(!$dataList){
                break;
            }
            foreach($dataList as $key => $row){
                $packInfo = logic_active_info::getInstance()->getPacketTypeInfo($row['infoid']);
                $dataList[$key]['infoid'] = $packInfo ? $packInfo['title'] : $row['infoid'];
                $countInfo[$row['infoid']] = isset($countInfo[$row['infoid']]) ? array(
                    'sum'       => round($countInfo[$row['infoid']]['sum'] + $row['returnprice'] , 2),
                    'count'     => $countInfo[$row['infoid']]['count'] + 1,
                ) : array(
                    'sum'       => $row['returnprice'] , 
                    'count'     => 1,
                );
            }
            $dataList[] = array();
            $dataList[] = array('总计：' , '' , '' , '=SUM(D2:D'.(count($dataList)).')');
            $dataList[] = array();
            $dataList[] = array('截止当前页面统计');
            $dataList[] = array('红包标记' , '红包类型' , '数量' , '金额');
            foreach($countInfo as $activeFlag => $info){
                $packInfo = logic_active_info::getInstance()->getPacketTypeInfo($activeFlag);
                $dataList[] = array($activeFlag , $packInfo ? $packInfo['title'] : $activeFlag ,  $info['count'] , $info['sum']);
            }
            $file_list[] = $this->createExcel('not_reverify_tender_packet_'.$page, array('投资用户' , '标的标号' , '投资时间' , array('format' => 'float_00' , 'value' => '红包金额'), '红包类型'), $dataList , true);
        }
        if($file_list){
            $tarName = $this->_saveBasePath.'not_reverify_tender_packet_'.$date.'未起息投资红包总额.zip';
            $zip = new ZipArchive();
            if($zip->open($tarName, ZipArchive::CREATE) === true){
                foreach($file_list as $file_info){
                    $zip->addFile($file_info['save_path'].$file_info['file_name'] , './'.$file_info['file_name']);
                }
                $zip->close();
                $this->_deleteBaseExcel($file_list);
                return $tarName;
            }else{
                $string = 'createNotReverifyTenderPacketExcel：cretea_zip_failed';
                utils_file::writeLog('packet_zip_failed', $string , 'crontab/excel');
                echo $string;
                return false;
            }
        }
    }
    
    
    
}
