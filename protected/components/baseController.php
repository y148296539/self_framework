<?php
/**
 * 所有action基于本类与基础action桥接
 *
 * @author cao_zl
 */
abstract class baseController extends ApplicationAction{
    

    /**
     * 定义页面渲染使用的模板名
     */
    protected function _beforeRender() {
        $this->_layout = ($this->_layout === false || $this->_layout === null) ? $this->_layout : 'mobile_top_body_bottom.phtml';
    }
    
    /**
     * 触发动作以前
     */
    public function beforeAction(){
        parent::beforeAction();
        utils_debug::systemRunStatus()->trace('beforeAction');
    }
    
    /**
     * 动作触发以后
     */
    public function afterAction(){
        utils_debug::systemRunStatus()->trace('afterAction');
        return parent::afterAction();
    }
    
    
    /**
     * 输出JSON格式的结果
     * 
     * @param code $status 状态码，200为成功，其他值均为错误码
     * @param mixed $message 成功的结果集
     * @param mixed $errorInfo 错误信息
     */
    public function returnJson($status , $message , $errorInfo){
        $this->_content_type = utils_http::CONTENT_TYPE_JSON;
        $returnJson = array(
            'code'      => intval($status),
            'message'   => $message,
            'errorInfo' => $errorInfo,
        );
        return json_encode($returnJson);
    }
}