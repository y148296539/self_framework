<?php

/**
 * 首页控制器
 */
class indexController extends \baseController{
    
    public function indexAction(){
        if(utils_environment::isDevelopment()){
            $this->_forward('index' , 'index' , 'demo');
        }
        $this->returnJson(200, 'welcome to selfframework!', null);
    }
    
    
}