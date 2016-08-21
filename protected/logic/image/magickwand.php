<?php
/**
 * magickwand 接口封装
 * @link URL http://www.magickwand.org/
 * 
 * @author cao_zl
 */
class logic_image_magickwand {
    
    protected $_imageSource = null;
    
    /**
     * 初始化指定的本地图片
     * -不给图片地址则直接初始化 magickwand 对象
     * @param string $imagePath
     * @return logic_image_magickwand
     */
    public static function initImage($imagePath=''){
        $imageClass = new self();
        $imageClass->_loadLocalImage($imagePath);
        return $imageClass;
    }
    
    /**
     * 初始化指定的网络图片 - 给定的是图片的URL地址
     * @param string $imageUrl 图片的URL地址
     * @return logic_image_magickwand
     */
    public static function initImageFromUrl($imageUrl){
        $imageClass = new self();
        $imageClass->_loadImageFromUrl($imageUrl);
        return $imageClass;
    }
    
    /**
     * 初始化指定的图片流资源 - 给定的是图片的blob流
     * @param string $imageUrl 图片的URL地址
     * @return logic_image_magickwand
     */
    public static function initImageFromBlob($imageBlob){
        $imageClass = new self();
        $imageClass->_loadImageFromBlob($imageBlob);
        return $imageClass;
    }
    
    /**
     * 初始化一个空白背景图片
     * @param int $width 背景图宽度
     * @param int $height 背景图高度
     * @param string $imgType 图片类型，值目前测试了"JPEG" , "PNG"
     * @param string $bgColor 背景颜色 - 未测试可用值格式，默认应该是黑色的样子
     * @return logic_image_magickwand
     */
    public static function initSpaceImage($width , $height , $imgType="JPEG", $bgColor=''){
        $imageClass = new self();
        $imageClass->_createSpaceImage($width , $height , $imgType , $bgColor);
        return $imageClass;
    }
    
    private function __construct() {
        $this->_imageSource = NewMagickWand();
    }
    
    public function __destruct() {
        DestroyMagickWand($this->_imageSource);
    }

    /**
     * 读取指定的本地图片
     * 
     * @param string $imagePath
     * @throws Exception
     */
    private function _loadLocalImage($imagePath){
        if($imagePath){
            if(!file_exists($imagePath)){
                throw new Exception('local_image_not_found:'.$imagePath);
            }
            MagickReadImage($this->_imageSource, $imagePath);
        }
    }
    
    /**
     * 从指定的图片URL创建图片资源
     * 
     * @param string $url
     * @throws Exception
     */
    private function _loadImageFromUrl($url){
        list($statusCode , $imageBlob, $errorInfo , $headerArray) = utils_http::getRequest($url,8,false,true,array(),true); 
        if($statusCode != 200 || !isset($headerArray['Content-Type']) || (substr($headerArray['Content-Type'] , 0 , 5) != 'image')){
            throw new Exception('get web image failed:'.$errorInfo.' base request url:('.$url.')');
        }
        $this->_loadImageFromBlob($imageBlob);
    }
    
    /**
     * 从数据流中创建图片资源
     * @param BLOB $imageBlob
     */
    private function _loadImageFromBlob($imageBlob){
        MagickReadImageBlob($this->_imageSource, $imageBlob);
    }
    
    /**
     * 创建空白图片画布
     * 
     * @param int $width 画布宽
     * @param int $height 画布高
     * @param string $imgType 图片类型，“JPEG”,"PNG"，‘GIF’
     * @param type $bgColor
     */
    private function _createSpaceImage($width , $height , $imgType="JPEG", $bgColor=''){
        MagickNewImage($this->_imageSource , $width , $height , $bgColor);
//        MagickSetImageScene($this->_imageSource , 1);
        MagickSetImageFormat($this->_imageSource , $imgType);
    }
    
    /**
     * 在图片上写文字 - 类似水印
     * 
     * @param string $words 指定的文字内容，自己控制长度
     * @param float $start_x 距离图片左上角X轴的偏移像素数，右方向为正方向 - 默认0
     * @param float $start_y 距离图片左上角Y轴的偏移像素数，下方向为正方向 - 默认0
     * @param string $color 文字的颜色，格式"#ff0000" - 默认黑色
     * @param int $fontSize 文字的大小，字号 - 默认14像素，字号最大和字库中的最大有关
     * @param string $fontAward 指定文字的，默认左上角
     * 
     * @param string $fontName 字体名称
     */
    public function writeWords($words , $start_x=0, $start_y=0, $color='#000000', $fontSize=14 , $fontName='宋体' , $fontAward=''){
        $wordsSource = NewDrawingWand();
        DrawSetFont($wordsSource, $this->getFontTtfFile($fontName));
        DrawSetFontSize($wordsSource, $fontSize);
        DrawSetFontWeight($wordsSource , 900);
        if($fontAward == 'NorthGravity'){//图片上方为基准，文字居中对齐
            DrawSetGravity($wordsSource, MW_NorthGravity);
        }else{
            DrawSetGravity($wordsSource, MW_NorthWestGravity);
        }
        $colorSource = NewPixelWand();
        PixelSetColor($colorSource, $color);
        DrawSetFillColor($wordsSource, $colorSource);
        MagickAnnotateImage( $this->_imageSource, $wordsSource, $start_x, $start_y, 0, $words);
    }
    
    /**
     * 改变图片大小
     * 
     * @param int $newWidth 宽
     * @param int $newHeight 高
     */
    public function resizeImage($newWidth , $newHeight){
        MagickSampleImage($this->_imageSource , $newWidth , $newHeight);
    }
    
    /**
     * 将指定图片的magickwand资源合并到当前图片资源上
     * 
     * @param source $imageSource
     * @param int $start_x
     * @param int $start_y
     */
    public function mergeImage($imageSource , $start_x=0 , $start_y=0){
        MagickCompositeImage($this->_imageSource , $imageSource , MW_OverCompositeOp , $start_x , $start_y );
    }
    
    /**
     * 切割图片
     * 
     * @param int $start_x 切割的起始点距离左上角的x坐标
     * @param int $start_y 切割的起始点距离左上角的y坐标
     * @param int $cut_width 切割的宽度
     * @param int $cut_height 切割的高度
     */
    public function cropImage($start_x, $start_y, $cut_width, $cut_height){
        MagickCropImage($this->_imageSource , $cut_width, $cut_height , $start_x, $start_y);
    }
    
    /**
     * 旋转图片
     * 
     * @param int $degrees 旋转的角度，顺时针为正值
     * @param string $bgColor 旋转后的背景色,默认透明
     */
    public function rotateImage($degrees , $bgColor='rgba(0,0,0,0)'){
        $bgColorSource = NewPixelWand();
        PixelSetColor($bgColorSource, $bgColor);
        MagickRotateImage($this->_imageSource, $bgColorSource, $degrees);
    }
    
    /**
     * 给定图片切成圆形 - 被切割部分以透明补充
     */
    public function cutToRound(){
        $roundImagePath = APPLICATION_PATH.'source'.DIRECTORY_SEPARATOR.'image'.DIRECTORY_SEPARATOR.'protected'.DIRECTORY_SEPARATOR.'round_100X100.png';
        if(!file_exists($roundImagePath)){
            throw new Exception('destination image ('.$roundImagePath.') not found');
        }
        $destinationClass = logic_image_magickwand::initImage($roundImagePath);
        if($this->getImageWidth() !== $destinationClass->getImageWidth() || $this->getImageHeight() !== $destinationClass->getImageHeight()){
            $destinationClass->resizeImage($this->getImageWidth(), $this->getImageHeight());
        }
        MagickSetFormat($this->_imageSource , "PNG");
        //MW_CopyOpacityCompositeOp: 拷贝不透明部分的混合
        MagickCompositeImage($this->_imageSource , $destinationClass->getImageSource() , MW_CopyOpacityCompositeOp , 0, 0);
    }
    
    
    public function changeImageColor(){
        $roundImagePath = APPLICATION_PATH.'source'.DIRECTORY_SEPARATOR.'image'.DIRECTORY_SEPARATOR.'protected'.DIRECTORY_SEPARATOR.'round_100X100.png';
        $destinationClass = logic_image_magickwand::initImage($roundImagePath);
        MagickCompositeImage($this->_imageSource , $destinationClass->getImageSource() , MW_CopyBlackCompositeOp , 0, 0);
    }
    
    /**
     * 添加GIF动画帧
     * 
     * @param logic_image_magickwand $mwc
     * @param int $liveSecond 当前帧驻留时间，单位秒
     */
    public function addGifAnimateFrame(logic_image_magickwand $mwc , $liveSecond){
        MagickSetImageDelay($mwc->getImageSource(),$liveSecond * 100);
        MagickAddImage($this->_imageSource ,$mwc->getImageSource());
    }
    
    /**
     * 合成GIF动画
     * 
     * @param string $gifPath 动画保存路径
     */
    public function comboGifAnimate($saveDir , $saveName){
        MagickSetFormat($this->_imageSource , "GIF");
        $gifPath = utils_file::preparePath($saveDir) . DIRECTORY_SEPARATOR . $saveName;
        MagickWriteImages($this->_imageSource, $gifPath , true);
    }
    
    /**
     * 获取图片宽度
     * @return float
     */
    public function getImageWidth(){
        return MagickGetImageWidth($this->_imageSource);
    }
    
    /**
     * 获取图片高度
     * @return float
     */
    public function getImageHeight(){
        return MagickGetImageHeight($this->_imageSource);
    }


    /**
     * 返回图片资源句柄
     * @return NewMagickWand
     */
    public function getImageSource(){
        return $this->_imageSource;
    }
    
    /**
     * 保存图片到指定位置
     * 
     * @param string $saveDir 保存文件夹路径
     * @param string $fileName 保存的文件名
     * 
     * @return boolean
     */
    public function save($saveDir , $fileName){
        utils_file::preparePath($saveDir);
        $savePath = $saveDir.((strrpos($saveDir , '/') === 0 || strrpos($saveDir , '\\') === 0) ? '' : DIRECTORY_SEPARATOR).$fileName;
        return MagickWriteImage($this->_imageSource , $savePath);
    }
    
    /**
     * 图片直接输出
     * 
     * @param string $imageType 输出图片类型
     */
    public function outputImage($imageType='gif'){
        if($this->returnError()){
            echo 'magickwand error:', $this->returnError();exit;
        }
        if($imageType == 'gif'){
            header('Content-Type: image/gif');
            MagickEchoImageBlob($this->_imageSource);
        }elseif($imageType == 'png'){
            header('Content-Type: image/png');
            MagickEchoImageBlob($this->_imageSource);
        }
    }
    
    /**
     * 返回最后一条图片错误信息
     * 
     * @return string
     */
    public function returnError(){
        echo MagickGetExceptionString($this->_imageSource);
    }
    
    /**
     * 通过指定字体名称返回字体文件路径
     * 
     * @param string $fontName 
     * @return string
     */
    public function getFontTtfFile($fontName){
        if(DIRECTORY_SEPARATOR == '\\'){
            $selfDir = dirname(__FILE__);
            $fontDir = substr($selfDir , 0 , strpos($selfDir , DIRECTORY_SEPARATOR) + 1).'fonts'.DIRECTORY_SEPARATOR;
        }else{
            $fontDir = '/usr/share/fonts/chinese/TrueType/';
        }
        switch ($fontName):
            case '楷书':
                $fontFile = 'simkai.ttf';
                break;
            case '宋体'://fc-match -v "SimSum" 字体无法匹配，使用新宋统一
                $fontFile = 'stsong.ttf';
                break;
            case '仿宋':
                $fontFile = 'simfang.ttf';
                break;
            case '幼圆':
                $fontFile = 'simyou.ttf';
                break;
            case '微软雅黑':
                $fontFile = 'msyh.ttf';
                break;
            case '蔡云汉清叶':
                $fontFile = 'caiyunhanqingye.ttf';
                break;
            case '华康少女':
                $fontFile = 'huakangshaonv.ttf';
                break;
            default:
                throw new Exception('getFontTtfFile error : ('.$fontName.') not be found');
        endswitch;
        return $fontDir.$fontFile;
    }
    
}