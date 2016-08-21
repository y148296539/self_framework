<?php

class demo_list_indexAction extends baseAction{
    
    public function response() {
        $list = array(
            array('当前页面' , 'http://192.168.11.15/demo/index'),
            array('微信分享自定二维码' , 'http://192.168.11.15:8080/index.php/Home/Qrcode/show'),
            array('(未定型)移动二维码+自定义文字位置' , 'http://192.168.11.15/demo/index/movewords'),
        );
        $this->render('index/index.phtml' , array('list' => $list));
    }
    
    
}