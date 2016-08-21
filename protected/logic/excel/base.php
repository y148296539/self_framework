<?php
/**
 * 基础报表参数类
 *
 * @author cao_zl
 */
class logic_excel_base extends logic_excel_abstractBase {
    
    /**
     * 每次存储的最大长度 ，设置为2M
     * @var int
     */
    protected $_partMaxLengthForSave = 2097152;
    /**
     * 每个excl表中最大行数，目前一次5000
     * @var int
     */
    protected $_maxLineNum = 5000;
    
    
    protected function _setTitle(){
        $this->_initTitleCellAndLine();
        $this->_initCells();
        $this->_writeBorder();
        $this->_boldCenter();
        $this->_mergeTitleCell();
        //冻结标题栏
        $this->_excelObject->getActiveSheet()->freezePane('A1')->freezePane('A'.($this->_titleLineNum + 1));
    }
    
    /**
     * 初始化设置标题栏的行列数
     */
    protected function _initTitleCellAndLine(){
        $lineNum = $this->_countLineNum();
        $this->setTitleLineNum($lineNum);
        $cellNum = $this->_countCellNum();
        $this->setTitleCellNum($cellNum);
    }
    
    /**
     * 加粗\居中
     */
    protected function _boldCenter(){
        $activeSheetStyle = $this->_excelObject->getActiveSheet()->getStyle('A1:'.$this->cells[$this->_titleCellNum - 1].$this->_titleLineNum);
        //设置字体、加粗
        $activeSheetStyle->getFont()
                ->setColor(new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_BLACK))
                ->setName($this->_fontName)
                ->setBold(true);
        //居中
        $activeSheetStyle->getAlignment()
                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
                ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    }

    /**
     * 给标题栏中的所有单元格加右与下边框
     * 
     * @param string $RGB 边框颜色
     */
    protected function _writeBorder($RGB='000000'){
        for($i = 0; $i < $this->_titleCellNum ; $i ++){
            for($j = 1 ; $j <= $this->_titleLineNum ; $j ++){
                $objBorder = $this->_excelObject->getActiveSheet()->getStyle($this->cells[$i].$j)->getBorders();
                $objBorder->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                $objBorder->getTop()->getColor()->setRGB($RGB);
                $objBorder->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                $objBorder->getRight()->getColor()->setRGB($RGB);
            }
        }
    }

    /**
     * 统计给定的标题栏的行数 - 特定格式
     * @return int
     */
    protected function _countLineNum(){
        $line = 1;
        if(is_array($this->_titleKV) && $this->_titleKV){
            foreach($this->_titleKV as $titlePart){
                $line = max($line , $this->_countPartLineNum($titlePart));
            }
        }
        return $line;
    }
    
    /**
     * 指定节点以下的行数
     * @param array $partTitle
     * @return int
     */
    protected function _countPartLineNum(&$partTitle){
        $children = $this->_getChildren($partTitle);
        if($children && is_array($children)){
            if(isset($children['value'])){
                $checkTarget = array($children);
            }else{
                $checkTarget = $children;
            }
            for($i = 2; $i < 100 ; $i ++){
                $comboChild = array();
                foreach($checkTarget as $child){
                    if($c = $this->_getChildren($child)){
                        if(is_array($c) && !$this->_getChildren($c)){
                            $comboChild = array_merge($comboChild , $c);
                        }else{
                            array_push($comboChild , $c);
                        }
                    }
                }
                if(!$comboChild){
                    break;
                }
                $checkTarget = $comboChild;
            }
        }else{
            $i = 1;
        }
        return $i;
    }


    /**
     * 返回当前栏位数组的子集标题
     * @param array $child
     * @return array/boolean
     */
    protected function _getChildren($child){
        if(is_array($child) && isset($child['children']) && $child['children']){
            return $child['children'];
        }
        return false;
    }
    
    /**
     * 统计数组标题栏所占的总列数
     * @return int 
     */
    protected function  _countCellNum(){
        if(is_array($this->_titleKV) && $this->_titleKV){
            $cellNum = 0;
            foreach($this->_titleKV as $partTitle){
                $cellNum += $this->_countPartCellNum($partTitle);
            }
        }else{
            $cellNum = 1;
        }
        return $cellNum;
    }
    
    /**
     * 指定节点以下的列数
     * @param array $partTitle
     * @return int
     */
    protected function _countPartCellNum(&$partTitle){
        $num = 1;
        if($children = $this->_getChildren($partTitle)){
            $num = 0;
            if($next = $this->_getChildren($children)){
                foreach($next as $child){
                    $num += $this->_countPartCellNum($child);
                }
            }elseif(is_array($children) && !isset($children['value'])){
                foreach($children as $child){
                    $num += $this->_countPartCellNum($child);
                }
            }else{
                $num = 1;
            }
        }
        return $num;
    }


    /**
     * 合并处理标题单元格
     */
    protected function _mergeTitleCell(){
        $fromCellIndex = 0;
        $fromLine = 1;
        foreach($this->_titleKV as $partTitle){
            $partCellNum = $this->_dealPartTitle($partTitle , $fromLine , $fromCellIndex);
            $fromCellIndex = $fromCellIndex + $partCellNum;
        }
    }
    
    /**
     * 部分合并处理
     * @param array $partTitleCut 指定处理的标题部分
     * @param int $nowLine 当前行号
     * @param int $nowCellIndex 当前列索引
     */
    protected function _dealPartTitle($partTitleCut , $fromLine , $fromCellIndex){
        $bgFillSave = array();
        //本次操作的起始单元格
        $startLCNum = $this->cells[$fromCellIndex].$fromLine;
        if(is_array($partTitleCut) ){
            $titleValue = isset($partTitleCut['value']) ? $partTitleCut['value'] : '-';
            $lineCount = $this->_countPartLineNum($partTitleCut);
            $cellCount = $this->_countPartCellNum($partTitleCut);
            if($cellCount > 1){//存在子列，
                $mergeLCEnd = $this->cells[$fromCellIndex + $cellCount - 1].$fromLine;
                $children = $this->_getChildren($partTitleCut);
                if(is_array($children)){
                    $tmp_fromCell = $fromCellIndex;
                    foreach($children as $child){
                        $partCellNum = $this->_dealPartTitle($child, $fromLine + 1, $tmp_fromCell);
                        $tmp_fromCell += $partCellNum;
                    }
                }else{
                    $this->_dealPartTitle($children, $fromLine + 1, $fromCellIndex); 
                }
            }
            //背景色-顺序需要颠倒过来，所以先记录
            if(isset($partTitleCut['bgColor'])){
                $bgFillSave[] = array($startLCNum.(($lineCount > 1 || $cellCount > 1) ? ':'.$this->cells[$fromCellIndex + $cellCount - 1].$this->_titleLineNum : '') , $partTitleCut['bgColor']);
            }
            //指定列格式
            if(isset($partTitleCut['format'])){
                $this->_setCellFormat($fromCellIndex , $partTitleCut['format']);
            }
        }else{
            $titleValue = $partTitleCut; 
            $lineCount = $cellCount = 1;
        }
        //填充标题文字
        $this->_excelObject->getActiveSheet()->setCellValue($startLCNum , $titleValue);
        //背景色
        if($bgFillSave){
            $bgFillSaveInfo = array_reverse($bgFillSave);
            foreach($bgFillSaveInfo as $bgInfo){
                $this->_excelObject->getActiveSheet()->getStyle($bgInfo[0])->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                $this->_excelObject->getActiveSheet()->getStyle($bgInfo[0])->getFill()->getStartColor()->setRGB($bgInfo[1]);
            }
        }
        //合并单元格
        if($lineCount == 1 && $fromLine < $this->_titleLineNum){//触底
            $mergeLCEnd = $this->cells[$fromCellIndex].$this->_titleLineNum;
        }
        if(isset($mergeLCEnd)){
            $this->_excelObject->getActiveSheet()->mergeCells($startLCNum.':'.$mergeLCEnd);
        }
        return $cellCount;
    }
    
    /**
     * 设置单元格的格式
     * 
     * @param int $cellIndex 列索引
     * @param string $format 格式标记
     */
    protected function _setCellFormat($cellIndex , $format){
        $activeSheet = $this->_excelObject->getActiveSheet();
        $cell = $this->cells[$cellIndex];
        switch ($format):
            case 'text' :
                $this->_textLineKey[] = $cellIndex + 1;//字符串格式咋设置都无效，只能在后面对每一格单独设置
                break;
            case 'float_00' ://浮点数保留两位补0，如：98.00
                $activeSheet->getStyle($cell.($this->_titleLineNum + 1))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $activeSheet->duplicateStyle($activeSheet->getStyle($cell.($this->_titleLineNum + 1)), $cell.($this->_titleLineNum + 2).':'.$cell.(count($this->_dataList)+$this->_titleLineNum) );
                break;
            case '%'://转换为百分数 - 传入 0.234，显示为 23.4%
                $activeSheet->getStyle($cell.($this->_titleLineNum + 1))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00);
                $activeSheet->duplicateStyle($activeSheet->getStyle($cell.($this->_titleLineNum + 1)), $cell.($this->_titleLineNum + 2).':'.$cell.(count($this->_dataList)+$this->_titleLineNum) );
                break;
            default:
                $activeSheet->getStyle($cell.($this->_titleLineNum + 1))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
                $activeSheet->duplicateStyle($activeSheet->getStyle($cell.($this->_titleLineNum + 1)), $cell.($this->_titleLineNum + 2).':'.$cell.(count($this->_dataList)+$this->_titleLineNum) );
        endswitch;
    }
    
    /**
     * 删除原始生成的excel文件
     * @param array $fileList
     */
    protected function _deleteBaseExcel($file_list){
        if($file_list){
            foreach($file_list as $file_info){
                unlink($file_info['save_path'].$file_info['file_name']);
            }
        }
    }
    
    
    public function createSimpleExcel($fileName , $title , &$dates , $otherParam=false){
        $savePath = $this->_saveBasePath;
        $fName = $fileName.'.csv';
        $fileSavePath = $savePath.$fName;
        if(!file_exists($fileSavePath)){
            $lt = '';
            foreach($title as $t){                
                $lt .= (is_array($t)) ? '标题' : $t."\t";
            }
            $lt .= "\r\n";
            utils_file::writeFile($fileSavePath , $lt , 'w');
        }
        $lineNum = 0;
        $partSave = '';
        foreach($dates as $data){
            $line = "";
            foreach($data as $v){
                $line .= $v."\t";
            }
            $line .= "\r\n";
            $lineNum ++;
            $partSave .= $line;
            if($lineNum % 200 == 0){
                utils_file::writeFile($fileSavePath , $partSave , 'a');
                $partSave = '';
            }
        }
        if($partSave !== ''){
            utils_file::writeFile($fileSavePath , $partSave , 'a');
            unset($partSave);
        }
        return array(
            'save_path'     => $savePath,
            'file_name'     => $fName,
        );
    }
    
    
    public function createSimpleExcelFormatTable($fileName , $title , &$dates , $appendEndFlag=true){
        $savePath = $this->_saveBasePath;
        utils_file::preparePath($savePath);
        $fName = $fileName.'.xlsx';
        $fileSavePath = $savePath.$fName;
        if(!file_exists($fileSavePath)){
            $table = '<style type="text/css">.No2Txt{mso-number-format:@}</style><table border="1"><tr>';
            foreach($title as $t){
                $table .= "<th>".$t."</th>";
            }
            $table .= "</tr>";
            file_put_contents($fileSavePath , $table);
        }
        foreach($dates as $data){
            $line = "<tr>";
            foreach($data as $v){
//                $line .= "<td".(preg_match('%^\d+(\.\d+)?$%' , $v) ? " style='mso-number-format:@'" : '').">".$v."</td>";
                $line .= "<td".(is_numeric($v) ? " class='No2Txt'" : '').">".$v."</td>";
            }
            $line .= "</tr>";
            file_put_contents($fileSavePath , $line, FILE_APPEND);
        }
        if($appendEndFlag){
            $end = '</table>';
            file_put_contents($fileSavePath , $end, FILE_APPEND);
        }
        return array(
            'save_path'     => $savePath,
            'file_name'     => $fName,
        );
    }
    
}
