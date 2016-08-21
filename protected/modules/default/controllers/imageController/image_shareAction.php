<?php
/**
 * 微信贺卡类别功能
 * - 测试结果，处理完成约用时 0.491812秒
 *
 * @author cao_zl
 */
class image_shareAction extends baseAction {
    
    /**
     * 二维码宽高
     * @var int 
     */
    private $_codeSize = 293;
    /**
     * 二维码 坐标x
     * @var int 
     */
    private $_codeX = 239;
    /**
     * 二维码 坐标y
     * @var int 
     */
    private $_codeY = 519;
    /**
     * 头像 宽
     * @var int 
     */
    private $_avatarWidth = 155;
    /**
     * 头像 高
     * @var int 
     */
    private $_avatarHeight = 155;
    /**
     * 头像 坐标x
     * @var int 
     */
    private $_avatarX = 300;
    /**
     * 头像 坐标y
     * @var int 
     */
    private $_avatarY = 135;
    /**
     * 文字一 距离上边距
     * @var int
     */
    private $_words1_margin_top = 310;
    /**
     * 文字二 距离上边距
     * @var int
     */
    private $_words2_margin_top = 360;
    /**
     * 文字三 距离上边距
     * @var int
     */
    private $_words3_margin_top = 945;
    
    private $_bgWidth = 750;
    
    private $_bgHeight = 1335;
    
    public function response() {
        try{
            //用户id
            $user_id = $this->getParam('user_id');
            if(!$user_id){
                throw new Exception('param: "user_id" not defined');
            }
            //背景图URL做本地缓存处理
            $bgImageUrl = $this->getParam('bg_image_url');
            if(!$bgImageUrl){
                throw new Exception('param: "bg_image_url" not defined');
            }
//            $bgClass = $this->_dealBgImage($bgImageUrl);
            //头像URL处理
            $avatar_url = $this->getParam('avatar_url');
            if(!$avatar_url){
                throw new Exception('param: "avatar_url" not defined');
            }
            $avatarClass = logic_image_magickwand::initImageFromUrl($avatar_url);
            $saveTmpDir = $this->_getTmpSaveDir();
            //二维码URL生成指定图片
            $jump_url = $this->getParam('jump_url');
            $code_image_url = $this->getParam('code_image_url');
            if($jump_url){
                $saveName = md5($jump_url).'.png';
                $codeSaveFilePath = $saveTmpDir.$saveName;
                logic_image_qrcode::getInstance()->createUrlQRcode($jump_url, $codeSaveFilePath, 'L', 4, false, array(0,0,0), 127, 0);
                $codeClass = logic_image_magickwand::initImage($codeSaveFilePath);
                unlink($codeSaveFilePath);
            }elseif($code_image_url){
                $codeClass = logic_image_magickwand::initImageFromUrl($code_image_url);
            }else{
                throw new Exception('param: "jump_url" or "code_image_url" not defined');
            }
            //画布
//            $bgBaseClass = logic_image_magickwand::initSpaceImage($this->_bgWidth , $this->_bgHeight);
            $bgBaseClass = $this->_dealBgImage($bgImageUrl);//根据测试，读图片比生成空白画布耗时少
            //将头像合成到背景图上
            $avatarClass->resizeImage($this->_avatarWidth, $this->_avatarHeight);
            $avatarClass->cutToRound();
            $bgBaseClass->mergeImage($avatarClass->getImageSource() , $this->_avatarX , $this->_avatarY);//
            //将蒙版合成到背景图上
//            $bgBaseClass->mergeImage($bgClass->getImageSource(), 0, 0);
            //二维码
            $codeClass->resizeImage($this->_codeSize, $this->_codeSize);//二维码变换大小
            $bgBaseClass->mergeImage($codeClass->getImageSource() , $this->_codeX , $this->_codeY);
            //显示用户名
            $name = $this->getParam('name');
            if(!$name){
                throw new Exception('param: "name" not defined');
            }
            //字体颜色参数
            $fc = strtolower($this->getParam('font_color' , ''));
            $fontColor = preg_match('%^[a-f\d]{6}$%' , $fc) ? '#'.$fc : '#000000';
            
            $bgBaseClass->writeWords($name, 0, $this->_words1_margin_top, $fontColor, 30, '华康少女' , 'NorthGravity');
            //显示关系字符串
            $relationString = $this->getParam('relation_string');
            if(!$relationString){
                throw new Exception('param: "relation_string" not defined');
            }
            $bgBaseClass->writeWords($relationString, 0, $this->_words2_margin_top, $fontColor, 30, '华康少女' , 'NorthGravity');
            //自定义文字
            $words = $this->getParam('self_words');
            if(!$relationString){
                throw new Exception('param: "self_words" not defined');
            }
            $bgBaseClass->writeWords($words, 0, $this->_words3_margin_top, $fontColor, 30, '蔡云汉清叶' , 'NorthGravity');
            //本地保存
            $fileName = $user_id.'_'.time().'_'.$bgBaseClass->getImageWidth()."X".$bgBaseClass->getImageHeight().'.png';
            $bgBaseClass->save($saveTmpDir, $fileName);
            //上传FTP服务器
            $ftp = utils_ftp::getInstance('img_combo');
            $ftpDir = '/combo_img/'.date('Y/m/d/');
            $ftp->preparePath($ftpDir);
            if($ftp->upload($saveTmpDir.$fileName, $ftpDir.$fileName)){
                $message = 'http://'.utils_config::getFile('domain')->get('static').$ftpDir.$fileName;
            }else{
                throw new Exception('ftp_upload_failed');
            }
            //删除本地图片
            unlink($saveTmpDir. $fileName);
        }  catch ( Exception $e){
            $statusCode = $e->getCode() ? $e->getCode() : 999;
            $errorInfo = $e->getMessage();
        }
        
        return $this->returnJson(isset($statusCode) ? $statusCode : 200,isset($message) ? $message : '' , isset($errorInfo) ? $errorInfo : '');
        
    }
    
    /**
     * 处理中的图片的临时保存地地址
     * @return string
     */
    private function _getTmpSaveDir(){
        $saveDir = APPLICATION_TMP_PATH.'share'.DIRECTORY_SEPARATOR;
        utils_file::preparePath($saveDir);
        return $saveDir;
    }
    
    
    
    
    /**
     * 处理背景头像到本地缓存处理
     * @param string $bgImageUrl
     * @return logic_image_magickwand
     */
    private function _dealBgImage($bgImageUrl){
        $bgSavePath = APPLICATION_TMP_BASE_PATH.'wechat_card'.DIRECTORY_SEPARATOR.'bg_cache'.DIRECTORY_SEPARATOR;
        $bgName = md5($bgImageUrl).'.png';
        $file = $bgSavePath . $bgName;
        if(!file_exists($file)){
            $bgClass = logic_image_magickwand::initImageFromUrl($bgImageUrl);
            $bgClass->save($bgSavePath, $bgName);
        }else{
            $bgClass = logic_image_magickwand::initImage($file);
        }
        return $bgClass;
    }
    
}



    
    