<?php
/**
 * 图片合成功能
 *
 * @author cao_zl
 */
class image_comboAction extends baseAction {
    
    public function response() {        
        $user_id = (int) $this->getParam('user_id' , 0);
        $tid = (int) $this->getParam('tid' , 1);
        if(!$user_id || !in_array($tid , array(1, 2))){
            $status = 989;
            $message = 'user_id:'.$user_id.'|tid:'.$tid;
            $errorInfo = 'params(user_id|tid) must be set';
        }else{
            $comboRule = logic_image_combo::getInstance()->rule($tid);
            $codeInfo = array(
//                'imageUrl'      => 'http://www.selfframework.com/source/image/combo/xxx.png',
                'jumpUrl'       => 'http://www.baidu.com',
                'width'         => $comboRule['codeWidth'],
                'left'          => $comboRule['codeLeft'],
                'top'           => $comboRule['codeTop'],
            );
            $wordsInfo = array(
                'words'         => $this->getParam('words'),
                'width'         => $comboRule['boxWidth'],
                'left'          => $comboRule['left'],
                'top'           => $comboRule['top'],
                'fontSize'      => $comboRule['fontSize'],
                'fontName'      => $comboRule['fontName'],
                'fontColor'     => $comboRule['fontColor'],
            );
            $saveInfo = array(
                'ftpDir'        => '/combo_img/xxxx/yyyy/zzzz/',
                'fileName'      => $user_id.'_test_here.jpg',
            );
            $this->_dealSelfMove($codeInfo, $wordsInfo);
            list($status , $message , $errorInfo) = logic_image_combo::getInstance()->commonCombo($tid, $codeInfo, $wordsInfo , $saveInfo);
        }
        return $this->returnJson($status, $message, $errorInfo);
    }
    
    /**
     * 覆盖模板中的定义
     * 
     * @param array $codeInfo 二维码相关参数
     * @param array $wordsInfo 文字相关参数
     */
    private function _dealSelfMove(&$codeInfo, &$wordsInfo){
        if($this->getParam('mode') == 'selfset'){//设置了该参数的允许自定义参数覆盖
            $codeInfo = utils_map::mergeArray($codeInfo , array(
                'left'          => $this->getParam('codeLeft' , null) !== null ? $this->getParam('codeLeft') : $wordsInfo['left'],
                'top'           => $this->getParam('codeTop' , null) !== null ? $this->getParam('codeTop') : $wordsInfo['top'],
            ));
            $wordsInfo = utils_map::mergeArray($wordsInfo , array(
                'width'         => $this->getParam('boxWidth' , null) !== null ? $this->getParam('boxWidth') : $wordsInfo['width'],
                'left'          => $this->getParam('boxLeft' , null) !== null ? $this->getParam('boxLeft') : $wordsInfo['left'],
                'top'           => $this->getParam('boxTop' , null) !== null ? $this->getParam('boxTop') : $wordsInfo['top'],
                'fontSize'      => $this->getParam('fontSize') ? $this->getParam('fontSize') : $wordsInfo['fontSize'],
                'fontName'      => $this->getParam('fontName') ? $this->getParam('fontName') : $wordsInfo['fontName'],
                'fontColor'     => $this->getParam('fontColor') ? $this->getParam('fontColor') : $wordsInfo['fontColor'],
            ));
            
            
            
        }
    }
    
    
}



    
    