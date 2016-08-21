<?php
/**
 * 图片合成逻辑
 *
 * @author cao_zl
 */
class logic_image_combo {
    
    const IMAGE_COMBO_BG_1 = 'test_bg_01.png';
    
    const IMAGE_COMBO_BG_2 = 'test_bg_02.png';
    
    private $_imageComboTmpPath = '';
    
    /**
     * @staticvar logic_image_combo $_instance
     */
    public static function getInstance(){
        static $_instance = null;
        if($_instance === null){
            $_instance = new self();
        }
        return $_instance;
    }
    
    /**
     * 规则定义
     * 
     * @param string $words 用户指定的图片
     * 
     * @return array
     */
    public function rule($template_id){
        $config = array(
            'tid'       => $template_id,
            'bg'        => strtr('http://'. utils_config::getFile('domain')->get('static').'/'.//
                    logic_image_combo::getInstance()->getComboImageRelativeDir().
                    logic_image_combo::getInstance()->getComboBgName($template_id)  , '\\' , '/' ),
            'fontSize'  => 20,
            'fontName'  => '宋体',
            'fontEName' => 'SimSun',
            'fontColor' => '#000000',
            'boxBorder' => 1,//文字盒模型的边框宽
            'boxWidth'  => 200,//文字盒模型的宽度
            'boxHeight' => 300,
            'top'       => 50,//文字盒模型的上边距
            'left'      => 50,//文字盒模型的左边距
            'codeWidth' => 100,//二维码宽高
            'codeLeft'  => 5,
            'codeTop'   => 5,
        );
        $method = '_coverComboRule'.$template_id;
        $coverConfig = $this->$method();
        return utils_map::mergeArray($config, $coverConfig);
    }
    
    
    private function _coverComboRule1(){
        $config = array(
            'fontSize'  => 20,
            'fontName'  => '仿宋',//SimSun SimHei YouYuan 
            'fontEName' => 'STFangsong',
            'fontColor' => '#000000',
            'boxBorder' => 1,
            'boxWidth'  => 200,
            'boxHeight' => 300,
            'top'       => 60,
            'left'      => 20,
            'codeWidth' => 100,//二维码宽高
            'codeLeft'  => 190,
            'codeTop'   => 390,
        );
        return $config;
    }
    
    
    private function _coverComboRule2(){
        $config = array(
            'fontSize'  => 20,
            'fontName'  => '仿宋',//SimSun SimHei YouYuan 
            'fontEName' => 'STFangsong',
            'fontColor' => '#000000',
            'boxBorder' => 1,
            'boxWidth'  => 200,
            'boxHeight' => 300,
            'top'       => 50,
            'left'      => 50,
            'codeLeft'  => 100,
            'codeTop'   => 400,
        );
        return $config;
    }
    
    /**
     * 合并通用规则
     * 
     * @param int $bgTemplateId
     * @param array $codeInfo
     * @param array $wordsInfo
     * @param array $saveInfo
     * @return array statusCode => int , message => url , errorinfo => mixed
     * @throws Exception
     */
    public function commonCombo($bgTemplateId , $codeInfo , $wordsInfo , $saveInfo=false){
        try{
            $stime = microtime(true);
            $rule = $this->rule($bgTemplateId);
            //背景图层初始化
//            $imagePath = $this->getComboBgLocalPath($bgTemplateId);
//            $bgClass = logic_image_magickwand::initImage($imagePath);
            $imageUrl = $rule['bg'];
            $bgClass = logic_image_magickwand::initImageFromUrl($imageUrl);
            if($codeInfo){
                //二维码图层处理
                if(isset($codeInfo['imageUrl']) && $codeInfo['imageUrl']){
                    $codeClass = logic_image_magickwand::initImageFromUrl($codeInfo['imageUrl']);
                }elseif(isset($codeInfo['jumpUrl']) && $codeInfo['jumpUrl']){
                    $tmpDir = APPLICATION_TMP_PATH;
                    utils_file::preparePath($tmpDir);
                    $tmpCodeFilePath = $tmpDir.time().round(10000 , 99999);
                    logic_image_qrcode::getInstance()->createUrlQRcode($codeInfo['jumpUrl'], $tmpCodeFilePath, 'L', 4, false , array(253,15,157) , 127);//#FC159C
                    $codeClass = logic_image_magickwand::initImage($tmpCodeFilePath);
                    unlink($tmpCodeFilePath);
                }else{
                    throw new Exception('_dealCodeImageInfo error: not found "imageUrl" or "jumpUrl"');
                }
                $codeWidth = isset($codeInfo['width']) ? ($codeInfo['width'] > 500 ? 500 : abs(intval($codeInfo['width'])) ) : 100;
                $codeClass->resizeImage($codeWidth, $codeWidth);
                $merginLeft = isset($codeInfo['left']) ? ($codeInfo['left'] > 900 ? 900 : abs(intval($codeInfo['left'])) ) : 0;
                $merginTop = isset($codeInfo['top']) ? ($codeInfo['top'] > 900 ? 900 : abs(intval($codeInfo['top'])) ) : 0;
                $bgClass->mergeImage($codeClass->getImageSource(), $merginLeft, $merginTop);
            }
            //自定义文字处理
            $fontName = isset($wordsInfo['fontName']) ? $wordsInfo['fontName'] : '华康少女';
            $fontSize = isset($wordsInfo['fontSize']) ? $wordsInfo['fontSize'] : 15;
            $fontColor = isset($wordsInfo['fontColor']) ? $wordsInfo['fontColor'] : '#000000';
            $wordsWidth = isset($wordsInfo['width']) ? ($wordsInfo['width'] > 1200 ? 1200 : abs(intval($wordsInfo['width'])) ) : 200;
            $words = utils_string::autoChangeLine($wordsInfo['words'], intval(($wordsWidth / $fontSize) - 1) );
            $fromLeft = isset($wordsInfo['left']) ? ($wordsInfo['left'] > 1400 ? 1400 : abs(intval($wordsInfo['left'])) ) : 0;
            $fromTop = isset($wordsInfo['top']) ? ($wordsInfo['top'] > 1400 ? 1400 : abs(intval($wordsInfo['top'])) ) : 0;
            $bgClass->writeWords($words, $fromLeft, $fromTop , $fontColor , $fontSize , $fontName);
            //图片保存信息
            if($saveInfo){
                if(isset($saveInfo['localDir']) && isset($saveInfo['fileName'])){//保存在本地磁盘的文件夹地址
                    $saveResult = $bgClass->save($saveInfo['localDir'], $saveInfo['fileName']);
                    $errorInfo = microtime(true) - $stime;
                }elseif(isset($saveInfo['ftpDir']) && isset($saveInfo['fileName'])){//ftp服务器上的文件夹路径
                    $tmpDir = APPLICATION_TMP_PATH;
                    $bgClass->save($tmpDir , $saveInfo['fileName']);
                    $errorInfo = microtime(true) - $stime;
                    utils_ftp::getInstance('img_combo')->preparePath($saveInfo['ftpDir']);
                    $saveResult = utils_ftp::getInstance('img_combo')->upload($tmpDir.$saveInfo['fileName'], $saveInfo['ftpDir'].$saveInfo['fileName']);
                    unlink($tmpDir.$saveInfo['fileName']);
                }else{
                    throw new Exception('_dealSave error: save params not found');
                }
                $message = $saveResult ? 'http://'.utils_config::getFile('domain')->get('static').$saveInfo['ftpDir'].$saveInfo['fileName'] : 'ftp_error';
            }else{
                $bgClass->outputImage();
                exit;
            }
        } catch (Exception $e) {
            $statusCode = $e->getCode() ? $e->getCode() : 998;
            $errorInfo = $e->getMessage();
        }
        return array(isset($statusCode) ? $statusCode : 200 , isset($message) ? $message : '' , isset($errorInfo) ? $errorInfo : '');
    }
       
    
    /**
     * 获取合成图片背景的本地路径
     * 
     * @param int $template_id 模板ID
     * @return string
     */
    public function getComboBgLocalPath($template_id){
        return APPLICATION_PATH . $this->getComboImageRelativeDir() . $this->getComboBgName($template_id);
    }
    
    /**
     * 获取背景文件的相对路径文件夹
     * @return string
     */
    public function getComboImageRelativeDir(){
        return 'combo_img' .DIRECTORY_SEPARATOR .'template'.DIRECTORY_SEPARATOR;
    }
    
    /**
     * 获取合成用背景图名称
     * @param int $template_id 模板ID
     * @return string
     */
    public function getComboBgName($template_id){
        $constName = 'logic_image_combo::IMAGE_COMBO_BG_'.$template_id;
        return constant($constName);
    }
    
    /**
     * 获取图片合成用的临时保存文件夹
     * @return string
     */
    public function getImageTmpSaveDir(){
        if(!$this->_imageComboTmpPath){
            $this->_imageComboTmpPath = APPLICATION_PATH . 'img_combo_tmp'. DIRECTORY_SEPARATOR. date('Y_m') .DIRECTORY_SEPARATOR;
            utils_file::preparePath($this->_imageComboTmpPath);
        }
        return $this->_imageComboTmpPath;
    }
    
    
    
    public function test(){
        
        $imgBlob = logic_image_qrcode::getInstance()->createUrlQRcode('随便聊表点什么吧', false, 'L', 4, true);
        
        $i = logic_image_magickwand::initImageFromBlob($imgBlob);
        $i->resizeImage(300, 100);
        $i->outputImage();
        exit;
        
        header('content-type:text/html;charset=utf-8');
        $s ='“"我"个人觉得，这主要还是网民觉得非常有趣、好玩，不断创造出新图片，才会推动这件事不断升温。”邱欣宇告诉记者，“我的前奥美同事、广告专业从业者也开始制作此类图片。他们的目的纯粹是好玩。”';
        echo utils_string::autoChangeLine($s, 9);
        
//        $imageBg1 = $this->getComboImageBasePath().$this->getBg1();
//        $bgClass = logic_image_magickwand::initImage($imageBg1);
//        $bgClass->writeWords('这里随便给点中文', 20, 100);
//        $codeImage = $this->getComboImageBasePath().'erweima.png';
//        $codeImageClass = logic_image_magickwand::initImage($codeImage);
//        $codeImageClass->resizeImage(100, 100);
////        $codeImageClass->rotateImage(20);
//        $bgClass->mergeImage($codeImageClass->getImageSource() , 870, 165);
        
        $length = 500;
        
        $erweima = 'G:\tmp\black.jpg';
//        $url = 'http://baike.baidu.com/link?url=9ufBx72Gvstn2nRcVP9nF7t9jrJvG52b0CD7zvDhq6G4EQGQWoOn4lFzLtoAv_ftccR0t7YtBp3PThROFRsY9IA5P-UONSPELl_NZRRFgCK';
//        logic_image_qrcode::getInstance()->createUrlQRcode($url , $erweima);
        $erweimaClass = logic_image_magickwand::initImage($erweima);
        $erweimaClass->resizeImage($length, $length);
        
        $w = 0;
        for($i = 0 ; $i < 365 ; $i ++){
            $erweimaClass->rotateImage(1);
            $w = $w ? $w : (($erweimaClass->getImageHeight() - $length) / 2);
            $erweimaClass->cropImage($w, $w, $length, $length);
        }
        
//        for($i = 0 ; $i <= 90 ; $i ++){
//            $erweimaClass->rotateImage(-1);
//            $erweimaClass->cropImage($w, $w, $length, $length);
//        }
        
        $erweimaClass->resizeImage(100, 100);
        
//        $avatar = 'G:\tmp\huangguoliang.jpg';
//        $avatarClass = logic_image_magickwand::initImage($avatar);
//        $avatarClass->resizeImage(200, 200);
//        $erweimaClass->mergeImage($avatarClass->getImageSource(), 300, 300);
        
//        $bgClass->cutImage(200, 200, 200, 100);
        
        var_dump($erweimaClass->save('G:\tmp' , 'yuan_365.png'));
    }
}



