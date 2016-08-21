<?php
/**
 * 基础控制器类
 *
 * @author cao_zl
 */
abstract class ApplicationAction {
    
    private $_params = array();
    
    private $_controllerName = null;
    
    private $_actionName = null;
    
    private $_moduleName = null;
    
    protected $_content_type = '';
    /**
     * 指定模板的路径，若不指定，尝试获取配置中的路径
     * @var string
     */
    protected $_templateDir = null;
    /**
     * 布局文件的基础文件夹
     * @var string
     */
    protected $_layoutDir = null;
    /**
     * 布局文件，若不想使用布局，则对其赋值 false 或 null
     * @var mixed
     */
    protected $_layout = '';
    /**
     * 渲染用的模板名
     * @var string
     */
    protected $_showTemplateName = '';
    /**
     * 给模板内注册的变量键值对
     * @var array
     */
    protected $_showTemplateKV = array();


    public function __construct($module , $controller , $action) {
        $this->_moduleName = $module;
        $this->_controllerName = $controller;
        $this->_actionName = $action;
        $this->_init();
    }
    
    protected function _init(){
        
    }

    /**
     * 动作重定向
     * 
     * @param string $actionName
     * @param string $controllerName
     * @param string $moduleName
     * @param array $params
     */
    protected function _forward($actionName , $controllerName=null , $moduleName=null , $params=array()){
        $actionName = $actionName ? $actionName : $this->_actionName;
        $controllerName = $controllerName ? $controllerName : $this->_controllerName;
        $moduleName = $moduleName ? $moduleName : $this->_moduleName;
        $params = $params ? utils_map::mergeArray($this->_params, $params) : $this->_params;
        ApplicationRouterDistribution::adapter($actionName, $controllerName , $moduleName , $params);
    }

    public function setAllParams($params=array()){
        $paramsMerge = $params ? utils_map::mergeArray($this->_params, $params) : $this->_params;
        $this->_params = $paramsMerge ? $paramsMerge : array();
    }
    
    public function getParam($key , $default=null){
        if(isset($this->_params[$key])){
            return $this->_params[$key];
        }
        return utils_http::getParam($key , $default);
    }

    /**
     * 触发动作以前
     */
    public function beforeAction(){
    }
    
    /**
     * 动作触发以后
     */
    public function afterAction(){
    }
    
    /**
     * 渲染模板之前操作
     */
    protected function _beforeRender(){
        header('content-type:text/html;charset='.Application::$config->get('web_page_charset' , 'UTF-8'));
    }
    
    protected function _initTemplatePath(){
        if($this->_templateDir === null){
            $this->_templateDir = Application::$config->get('module_path').
                    $this->_moduleName.DIRECTORY_SEPARATOR.
                    'views'.DIRECTORY_SEPARATOR;
        }
        if($this->_layoutDir===null){
            $this->_layoutDir = Application::$config->get('layout_dir');
        }
    }

    /**
     * 渲染页面输出
     * - 注册参数，会在afterAction后统一格式输出
     * 
     * @param string $templateName 模板的名称，相对于指定视图目录的路径
     * @param array $registKeyValue
     */
    public function render($templateName , $registKeyValue=array()){
        $this->_content_type = utils_http::CONTENT_TYPE_HTML;
        $this->_showTemplateName = $templateName;
        $this->_showTemplateKV = $registKeyValue;
    }
    
    /**
     * 拼接模板文件的绝对路径
     * 
     * @return string
     */
    private function _getTemplateFile(){
        $templateFile = $this->_templateDir.$this->_showTemplateName;
        if(!is_file($templateFile)){
            throw new Exception('template file ('.$templateFile.') not found!');
        }
        return $templateFile;
    }
    
    /**
     * 局部渲染
     * 
     * @param string $viewFile 本地视图文件的绝对路径
     * @param array $registKeyValue 注册进视图文件的变量键值对
     * @param boolean $_return 是否返回为变量字符串
     * @return mixed
     */
    public function renderPartical($viewFile, $registKeyValue=array() , $_return=false){
        if(!is_file($viewFile)){
            throw new Exception('renderFile:('.$viewFile.') not be found!');
        }
        extract($registKeyValue,EXTR_PREFIX_SAME,'data');
        if($_return){
            ob_start();
            ob_implicit_flush(false);
            require($viewFile);
            return ob_get_clean();
        }else{
            require($viewFile);
        }
    }
    
    /**
     * 渲染完成，输出内容
     * @throws Exception
     * @param $response action中返回的内容
     */
    public function endActionOutput($response){
        if(!$this->_content_type){
            $this->_content_type = Application::$mode == Application::MODE_WEBAPPLICATION ? utils_http::CONTENT_TYPE_HTML : utils_http::CONTENT_TYPE_text;
        }
        header('content-type:'.$this->_content_type.';charset='.Application::$config->get('web_page_charset', 'utf-8'));
        if($this->_showTemplateName){
            $this->_beforeRender();
            $this->_initTemplatePath();
            $templateFile = $this->_getTemplateFile();
            if($this->_layout){
                $contents = $this->renderPartical($templateFile, $this->_showTemplateKV , true);
                $layout = $this->_layoutDir.$this->_layout;
                if(!is_file($layout)){
                    throw new Exception('layout file ('.$layout.') not found!');
                }
                $this->renderPartical($layout, array('contents' => $contents) );
            }else{
                $this->renderPartical($templateFile, $this->_showTemplateKV);
            }
        }else{
            echo $response;
        }
    }
}
