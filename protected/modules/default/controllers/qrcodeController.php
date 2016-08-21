<?php
/**
 * 二维码相关控制器
 */
class qrcodeController extends baseController{
    
    
    public function indexAction(){
        
    }
    
    /**
     * 内网自用
     */
    public function showAction(){
        if($url = $this->getParam('url')){
            $url = urldecode($url);
            logic_image_qrcode::getInstance()->createUrlQRcode($url , false , 'L' , '4' , true);
            exit;
        }
        return $this->returnJson(998, '', 'url not defined');
    }
    
    
    /**
     * 生成二维码保存到img服务器上
     * 
     * @param url 二维码跳转的地址
     * @param user_id 用户id，必须无userid可传0
     * @param error_level 容错级别，四级，接受'L' , 'M' , 'Q' , 'H'
     * @param width 最后生成的二维码宽度
     * 
     * @throws Exception
     */
    public function createAction() {
        try{
            $url = $this->getParam('url');
            if(!$url){
                throw new Exception('param "jump_url" is empty');
            }
            $user_id = $this->getParam('user_id');//系统级的可传0
            if(!is_numeric($user_id)){
                throw new Exception('param "user_id" is empty');
            }
            $errorLevel = in_array(strtoupper($this->getParam('error_level' , 'L')) , array('L' , 'M' , 'Q' , 'H')) ? strtoupper($this->getParam('error_level' , 'L')) : 'L';
            $fileName = md5($user_id.'|'.$url).'.png';
            $tmpDir = APPLICATION_RUNTIME_PATH.'tmp'.DIRECTORY_SEPARATOR.date('Ym').DIRECTORY_SEPARATOR;
            $tmpSavePath = $tmpDir.$fileName;
            $pointPix = 4;
            $codeColor =array(0,0,0);
            $bg_alpha = 127;
            $margin = 0;
            logic_image_qrcode::getInstance()->createUrlQRcode($url , $tmpSavePath , $errorLevel , false, $pointPix , $codeColor , $bg_alpha, $margin);
            if(!file_exists($tmpSavePath)){
                throw new Exception('qrcode create failed');
            }
            $width = $this->getParam('width' , 200);
            $imageClass = logic_image_magickwand::initImage($tmpSavePath);
            $imageClass->resizeImage($width, $width);
            $imageClass->save($tmpDir, $fileName);
            $cstring = sprintf('%010d' , $user_id);
            $ftpDir = 'qrcode/'.substr($cstring , 0 , 2).'/'.substr($cstring , 2 , 2) .'/'. substr($cstring, 4 , 2) .'/'. substr($cstring, 6 , 2).'/';
            $ftpClass = utils_ftp::getInstance('img_combo');
            $ftpClass->preparePath($ftpDir);
            if(!$ftpClass->upload($tmpSavePath, $ftpDir.$fileName)){
                throw new Exception('ftp upolad failed');
            }
            unset($tmpSavePath);
            $message = 'http://'. utils_config::getFile('domain')->get('static').'/'. $ftpDir.$fileName;
        }catch(Exception $e){
            $statusCode = $e->getCode() ? $e->getCode() : 999;
            $errorInfo = $e->getMessage();
        }
        return $this->returnJson(isset($statusCode) ? $statusCode : 200, isset($message) ? $message : '', isset($errorInfo) ? $errorInfo : '');
    }
    
    
    
    
    
    
    
    
}