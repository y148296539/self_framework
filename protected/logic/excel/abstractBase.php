<?php
/**
 * excl辅助类
 * - 对PHPExcel类进行一些简单封装，简化操作流程
 * - 若打算使用更多功能，还请使用excl原类
 * 
 * @author cao_zl
 */
abstract class logic_excel_abstractBase {
    
    /**
     * 保存excel的基础路径
     * @var string
     */
    protected $_saveBasePath = '';
    /**
     *
     * @var PHPExcel
     */
    protected $_excelObject = null;
    /**
     * 字体：宋体、黑体
     * @var string 
     */
    protected $_fontName = '宋体';
    /**
     * 存储的文件名
     * @var string
     */
    protected $_fileName = '';
    /**
     * excel报表的标题栏
     * @var array
     */
    protected $_titleKV = array();
    /**
     * 保存的数据列
     * @var array
     */
    protected $_dataList = array();
    /**
     * 保存强制字符串格式的列名
     * @var array 
     */
    protected $_textLineKey = array();
    /**
     * 标题栏所占列数
     * @var int
     */
    protected $_titleCellNum = 0;
    /**
     * 标题栏所占行数
     * @var int
     */
    protected $_titleLineNum = 0;
    /**
     * 列标基础值 
     */
    protected $cells = array(
        'A' , 'B' , 'C' , 'D' , 'E' , 'F' , 'G' , 
        'H' , 'I' , 'J' , 'K' , 'L' , 'M' , 'N' , 
        'O' , 'P' , 'Q' , 'R' , 'S' , 'T' , 'U' , 
        'V' , 'W' , 'X' , 'Y' , 'Z' ,
    );

    public function __construct() {
        if(!class_exists('PHPExcel', false)){
            set_include_path(get_include_path() . PATH_SEPARATOR . APPLICATION_PLUGIN_PATH.'PHPExcel');
            include 'PHPExcel.php';
            /** PHPExcel_Writer_Excel2007 */
            include 'Writer/Excel2007.php';//PHPExcel/
        }
        $this->_saveBasePath = APPLICATION_RUNTIME_PATH .'excel'. DIRECTORY_SEPARATOR.date('Y'.DIRECTORY_SEPARATOR.'m').DIRECTORY_SEPARATOR;
    }
    
    
    public function __destruct() {
        if($this->_titleKV){
            $this->_titleKV = array();
        }
        if($this->_dataList){
            $this->_dataList = array();
        }
        if($this->_excelObject){
            $this->_excelObject = null;
        }
    }
    
    /**
     * 生成一个excel表格
     * 
     * @param string $file_name 文件名
     * @param array $titleKV 表头
     * @param array $dataList 表体的内容
     * @param boolean $saveFile 传入true,则保存EXCEL文件，并返回文件保存信息；返回false，则直接以下载形式返回整个文件 
     * 
     * @return array 
     */
    public function createExcel($file_name , $titleKV , &$dataList , $saveFile=false){
        //初始化参数
        $this->_initCreate($file_name , $titleKV , $dataList , $saveFile);
        //设置头信息
        $this->_setCreateHeadInfo();
        //设置标题列，并返回标题所占行数
        $this->_setTitle();
        //写入数据
        $this->_setDatas();
        return $this->_output($saveFile);
    }
    
    
    /**
     * 初始化所有参数
     * @param string $fileName 文件名
     * @param array $titleKV 标题
     * @param array $dataList 数据列
     */
    protected function _initCreate($fileName , $titleKV , &$dataList){        
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        $cacheSettings = array('memoryCacheSize'=>'16MB');
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
        
        $this->_excelObject = new PHPExcel();
        $this->_excelObject->setActiveSheetIndex(0);
        $this->_fileName = $fileName.'.xlsx';
        $this->_titleKV = $titleKV;
        $this->_dataList = $dataList;
        $this->_textLineKey = array();
        
        $this->setTitleCellNum(0);
        $this->setTitleLineNum(0);
        
        $this->_excelObject->getDefaultStyle()->getFont()->setSize(10);
    }

    /**
     * 设置一些不重要的信息
     */
    protected function _setCreateHeadInfo(){
        //创建人
        $this->_excelObject->getProperties()->setCreator("PHP Excel System");
        //最后修改人
        $this->_excelObject->getProperties()->setLastModifiedBy("Apache");
        //标题
        $this->_excelObject->getProperties()->setTitle("Office 2007 XLSX Test Document");
        //题目
        $this->_excelObject->getProperties()->setSubject("Office 2007 XLSX Test Document");
        //描述
        $this->_excelObject->getProperties()->setDescription("Test document for Office 2007 XLSX, generated using php classes.");
        //关键字
        $this->_excelObject->getProperties()->setKeywords("office 2007 openxml php");
        //种类
        $this->_excelObject->getProperties()->setCategory("Data File");
    }
    
    /**
     * 设置标题栏
     * 
     * @return int 返回标题栏所占用的行数
     */
    abstract protected function _setTitle();
//    protected function _setTitle(){
//        if(!$this->_titleCellNum){
//            $this->setTitleCellNum(count($this->_titleKV));
//        }
//        $this->setTitleLineNum(1);
//        $this->_initCells();
//        $activeSheet = $this->_excelObject->getActiveSheet();
////        $activeSheet->setTitle($title);
//        
//        //设置字体、加粗
//        $activeSheet->getStyle('A1:'.$this->cells[$this->_titleCellNum - 1].$this->_titleLineNum)
//                ->getFont()
//                ->setColor(new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_BLACK))
//                ->setName($this->_fontName)//设置字体
//                ->setBold(true);//加粗
//        //居中
//        $activeSheet->getStyle('A1:'.$this->cells[$this->_titleCellNum - 1].$this->_titleLineNum)->getAlignment()
//                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
//                ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
//
//        $index = 0;
//        $this->_textLineKey = array();
//        $key = 0;
//        $this->_titleLineNum = ($this->_titleLineNum > 0) ? intval($this->_titleLineNum) : 1;
//        foreach($this->_titleKV as $title){
//            $key ++;
//            $cell = $this->cells[$index];
//            $activeSheet->getColumnDimension($cell)->setAutoSize(true);
//            if(is_array($title) && isset($title['format'])){
//                switch ($title['format']):
//                    case 'text' :
//                        $this->_textLineKey[] = $key;//字符串格式咋设置都无效，只能在后面对每一格单独设置
//                        break;
//                    case 'float_00' :
//                        $activeSheet->getStyle($cell.($this->_titleLineNum + 1))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
//                        $activeSheet->duplicateStyle($activeSheet->getStyle($cell.($this->_titleLineNum + 1)), $cell.($this->_titleLineNum + 2).':'.$cell.(count($this->_dataList)+$this->_titleLineNum) );
//                        break;
//                    case '%':
//                        $activeSheet->getStyle($cell.($this->_titleLineNum + 1))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00);
//                        $activeSheet->duplicateStyle($activeSheet->getStyle($cell.($this->_titleLineNum + 1)), $cell.($this->_titleLineNum + 2).':'.$cell.(count($this->_dataList)+$this->_titleLineNum) );
//                        break;
//                    default:
//                        $activeSheet->getStyle($cell.($this->_titleLineNum + 1))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
//                        $activeSheet->duplicateStyle($activeSheet->getStyle($cell.($this->_titleLineNum + 1)), $cell.($this->_titleLineNum + 2).':'.$cell.(count($this->_dataList)+$this->_titleLineNum) );
//                endswitch;
//                $activeSheet->setCellValue($cell.'1', $title['value']);
//            }else{
//                $activeSheet->setCellValue($cell.'1', $title);
//            }
//            $index ++;
//        }
//        $activeSheet->freezePane('A1')->freezePane('A'.($this->_titleLineNum + 1));
//    }
    
    /**
     * 补充列坐标轴
     */
    protected function _initCells(){
        $cellsTurn = utils_environment::isDevelopment() ? 6 : ceil($this->_titleCellNum / count($this->cells));
        //若列数大于已给出的基本列坐标，自动补充列坐标（该做法有效列数为 26 * 26）
        if($cellsTurn-- > 1){
            $baseCells = $this->cells;
            for($i = 0 ; $i < $cellsTurn ; $i ++){
                foreach($baseCells as $cellName){
                    $this->cells[] = $baseCells[$i] . $cellName;
                }
            }
        }
    }
    
    /**
     * 写入报表内容
     */
    protected function _setDatas(){
        $activeSheet = $this->_excelObject->getActiveSheet();
        foreach($this->_dataList as $k => $dataRow){
            //0>A , 1>B , 2>C ....
            $index = 0;
            //写入第几行 - 第一行给标题了，所以加2
            $line = $k + $this->_titleLineNum + 1;
            foreach($dataRow as $elementIndex => $elementValue){
                $input = strtr($elementValue , array('{cell}' => $this->cells[$index] , '{line}' => $line));
                if($this->_textLineKey && in_array($index + 1, $this->_textLineKey)){
                    $activeSheet->setCellValueExplicit($this->cells[$index] . $line , $input, PHPExcel_Cell_DataType::TYPE_STRING);
                }else{
                    $activeSheet->setCellValue($this->cells[$index] . $line, $input);
                }
                $index ++;
                unset($this->_dataList[$k][$elementIndex]);
            }
            unset($this->_dataList[$k]);
        }
    }
    
    /**
     * 输出结果
     * 
     * @param boolean $saveFile 传入true,则保存EXCEL文件，并返回文件保存信息；返回false，则直接以下载形式返回整个文件 
     * @return mixed
     */
    protected function _output($saveFile){
        $objWriter = new PHPExcel_Writer_Excel2007($this->_excelObject);
        if($saveFile){
            $savePath = $this->_saveBasePath;
            utils_file::preparePath($savePath);
            $objWriter->save($savePath.$this->_fileName);
            unset($objWriter);
            $this->_excelObject->disconnectWorksheets();
            $this->_excelObject = null;
            $this->_titleKV = array();
            $this->_dataList = array();
            $this->_textLineKey = array();
            return array(
                'save_path'     => $savePath,
                'file_name'     => $this->_fileName
            );
        }else{
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");;
            header('Content-Disposition:attachment;filename="'.$this->_fileName.'"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
            exit;
        }
    }


    /**
     * 设置标题栏列数
     * @param int $num
     */
    public function setTitleCellNum($num){
        $this->_titleCellNum = (int) $num;
    }
    
    /**
     * 设置标题栏行数
     * @param type $num
     */
    public function setTitleLineNum($num){
        $this->_titleLineNum = (int) $num;
    }
    
    
}


/**
 * demo

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set properties
$objPHPExcel->getProperties()->setCreator("Maarten Balliauw");
$objPHPExcel->getProperties()->setLastModifiedBy("ni cai");
$objPHPExcel->getProperties()->setTitle("Office 2007 XLSX Test Document");
$objPHPExcel->getProperties()->setSubject("Office 2007 XLSX Test Document");
$objPHPExcel->getProperties()->setDescription("Test document for Office 2007 XLSX, generated using php classes.");
$objPHPExcel->getProperties()->setKeywords("office 2007 openxml php");
$objPHPExcel->getProperties()->setCategory("Test result file");


// Add some data
$objPHPExcel->setActiveSheetIndex(0);
$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Hello');
$objPHPExcel->getActiveSheet()->setCellValue('B2', 'world!');
$objPHPExcel->getActiveSheet()->setCellValue('C1', 'Hello');
$objPHPExcel->getActiveSheet()->setCellValue('D2', 'world!');

// Rename sheet
$title = '标签位';utils_array::changeTargeEncoding($title);
$objPHPExcel->getActiveSheet()->setTitle($title);


//合并单元格
$objPHPExcel->getActiveSheet()->mergeCells('A5:G5');


//设置表头行高
//$objPHPExcel->getRowDimension(1)->setRowHeight(35);
//$objPHPExcel->getRowDimension(2)->setRowHeight(22);
//$objPHPExcel->getRowDimension(3)->setRowHeight(20);
//设置字体样式
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setName('黑体');
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
//$objPHPExcel->getStyle('A3:G3')->getFont()->setBold(true);
//$objPHPExcel->getStyle('A2')->getFont()->setName('宋体');
//$objPHPExcel->getStyle('A2')->getFont()->setSize(16);
//$objPHPExcel->getStyle('A4:G'.($k+4))->getFont()->setSize(10);


// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);



////设置居中
//$ActiveSheet->getStyle('A1:G'.($k+4))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
////所有垂直居中
//$ActiveSheet->getStyle('A1:G'.($k+4))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

       
// Save Excel 2007 file
$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
$objWriter->save('/test_demo.xlsx');
 * 
 * 
 * 
 */