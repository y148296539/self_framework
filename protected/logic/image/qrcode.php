<?php
/**
 * 二维码处理相关逻辑
 *
 * @author cao_zl
 */
class logic_image_qrcode {
    
    /**
     * @staticvar null $_instance
     * @return logic_image_qrcode
     */
    public static function getInstance(){
        static $_instance = null;
        if($_instance === null){
            $_instance = new self();
        }
        return $_instance;
    }
    
    private function __construct() {
        $qrfile = APPLICATION_PATH.'protected'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'phpqrcode'.DIRECTORY_SEPARATOR.'phpqrcode.php';
        include $qrfile;
    }
    
    /**
     * 生成二维码
     * 
     * @param string $url 地址
     * @param string/false $saveFilePath 保存文件的路径
     * @param string $errorLevel 容错级别，L-7%的字码可被修正，M-15%的字码可被修正，Q-25%的字码可被修正，H-30%的字码可被修正
     * @param int $pointSize 图片中点的像素值
     * @param boolean $print 是否直接输出图片
     * @param array $codeColor 二维码颜色，数组，rgb
     * @param int $bg_alpha 背景透明度，0~127,127透明，0不透明
     * @param int $margin 外边框宽度
     * 
     * @return mixed
     */
    public function createUrlQRcode($url , $saveFilePath=false , $errorLevel='L' , $pointSize='4' , $print=false , $codeColor=array(0,0,0) , $bg_alpha=0 , $margin=1){
        $selfSet = array(
            'codeColor'     => $codeColor,
            'bg_alpha'      => $bg_alpha,
        );
        return QRcode::png($url, $saveFilePath , $errorLevel, $pointSize , $margin , $print , $selfSet);
    }
    
    
    
}
