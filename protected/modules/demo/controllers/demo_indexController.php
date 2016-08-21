<?php
/**
 * 演示使用模板控制器
 */
class demo_indexController extends baseController{
    
    public function testAction(){
        for($i = 0; $i < 10000 ; $i ++){
            $key = 'kv'.$i;
//            $value = 'v'.$i;
//            utils_cache::memcache()->set($key, $value, 200);
            $ks[] = $key;
        }
        utils_debug::systemRunStatus()->trace('all');
        utils_cache::memcache()->get($ks);
        utils_debug::systemRunStatus()->trace('all_end');
        for($i = 0; $i < 10000 ; $i ++){
            $key = 'k'.$i;
            utils_cache::memcache()->get($key);
        }
        return utils_debug::systemRunStatus()->trace('one_end')->show(utils_http::CONTENT_TYPE_HTML);
        
        $deadLine = strtotime(date('Y-m-d' , strtotime('-1 day')));//删除两天以前的所有临时文件
        $dir = substr(APPLICATION_TMP_PATH , 0 , strrpos(APPLICATION_TMP_PATH , DIRECTORY_SEPARATOR. 'tmp' . DIRECTORY_SEPARATOR) + 5);
        $handle = opendir($dir);
        while(false !== ($file = readdir($handle))){
            if(!in_array($file, array('.' , '..'))){
                $filePath = $dir.$file;
                echo $filePath , 'is a ' , date('Y-m-d H:i:s' , fileatime($filePath)) , '<hr>';
            }
        }
        return utils_debug::systemRunStatus()->trace('test_end')->show();
    }
    
    
    /**
     * 已有演示列表
     */
    public function indexAction(){
        $this->_forward('index', 'list');
    }
    
    /**
     * 切割头像合成
     * @throws Exception
     */
    public function cutavatarAction() {
        $bgPicUrl = 'http://img.dev.dtd365.com/combo_img/xxxx/yyyy/zzzz/baseAvatar.jpg';
        if(utils_http::isPostRequest()){
            try{
//                $bgPicUrl = $this->getParam('bgPicUrl' , '');
                $pos = explode(',' ,$this->getParam('pos' , '0,0,0,0'));
                if(count($pos) < 4){
                    throw new Exception('postion params are missing');
                }
                $avatar = logic_image_magickwand::initImageFromUrl($bgPicUrl);
                $avatar->cropImage($pos[0], $pos[1], $pos[2], $pos[3]);
                $avatar->resizeImage(65, 95);
                $avatar->cutToRound();
                $localBasePath = APPLICATION_RUNTIME_PATH.'tmp'.DIRECTORY_SEPARATOR;
                $saveDir = 'combo_img/xxxx/yyyy/zzzz/';
                $saveName = 'test_jpg_cut.png';
                //背景图初始化
                $bgUrl = 'http://img.dev.dtd365.com/combo_img/xxxx/yyyy/zzzz/five_head.png';
                $bgClass = logic_image_magickwand::initImageFromUrl($bgUrl);
                $bgClass->mergeImage($avatar->getImageSource(), 213, 50);
                //合成完毕，图片本地保存
                $bgClass->save($localBasePath.$saveDir, $saveName);
                //FTP上传
                $ftp = utils_ftp::getInstance('img_combo');
                $ftp->preparePath('/'.$saveDir);
                $ftp->upload($localBasePath.$saveDir.$saveName, '/'.$saveDir.$saveName);
                //上传完毕，删除本地临时图片
                unlink($localBasePath.$saveDir.$saveName);
                $message = 'http://'.utils_config::getFile('domain')->get('static').'/'.$saveDir.$saveName;
            }catch(Exception $e){
                $status = $e->getCode() ? $e->getCode() : 999;
                $errorInfo = $e->getMessage();
            }
            return $this->returnJson(isset($status) ? $status : 200, isset($message) ? $message : '', isset($errorInfo) ? $errorInfo : '');
        }
        $show = array('bgPicUrl' => $bgPicUrl);
        $this->render('image/cutavatar.phtml' , $show);
    }
    
    /**
     * 移动二维码+自定义文字位置
     */
    public function movewordsAction() {
        
        if(utils_http::isPostRequest()){
            $host = 'http://192.168.11.15/';
            $url = $host . 'image/combo';
            $postKV = array(
                'mode'      => $this->getParam('mode'),
                'words'     => $this->getParam('words') ,
                'user_id'   => $this->getParam('user_id'),
                'tid'       => $this->getParam('tid'),//合成模板的ID
                //二维码相关参数
                'codeTop'   => $this->getParam('codeTop'),
                'codeLeft'  => $this->getParam('codeLeft'),
                //文字框相关参数
                'boxTop'    => $this->getParam('boxTop'),
                'boxLeft'   => $this->getParam('boxLeft'),
                'boxWidth'  => $this->getParam('boxWidth'),
                //文字参数
                'fontSize'  => 20,
                'fontName'  => '华康少女',
                'fontColor' => '#cccccc',
            );
            $content = http_build_query($postKV);
            list($statusCode , $html , $errorInfo) = utils_http::postRequest($url, $content , 20);
            if($statusCode === 200){
                $comboResult = json_decode($html , true);
                return $this->returnJson($comboResult['code'], $comboResult ? $comboResult['message'] : 'format_error', $comboResult ? $comboResult['errorInfo'] : $html);
            }else{
                return $this->returnJson($statusCode, $html, $errorInfo);
            }
        }
        
        
        $tid = (int) $this->getParam('tid' , 1);
        
        $show['choose'] = logic_image_combo::getInstance()->rule($tid);
        $show['templates'] = array();
        foreach(range(1,2) as $_tid){
            $show['templates'][] = logic_image_combo::getInstance()->rule($_tid);
        }

        
        $this->render('index/movewords.phtml' , $show);
                
    }
    
    
    /**
     * GIF动画合成
     * @return string
     */
    public function gifanimateAction() {
        if(utils_http::isPostRequest()){
            try{
                $images = $this->getParam('images' , array());
                
                $secs = $this->getParam('secs' , array());
                
                $imageClass = logic_image_magickwand::initImage();
                foreach($images as $key => $imageUrl){
                    $frameClass = logic_image_magickwand::initImageFromUrl($imageUrl);
                    $imageClass->addGifAnimateFrame($frameClass, $secs[$key]);
                    unset($frameClass);
                }
                $imageClass->comboGifAnimate($saveDir, $saveName);
            }catch(Exception $e){

            }
        }
        return '';
    }
    
}