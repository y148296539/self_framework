<?php
/**
 * 路由处理类
 */
class ApplicationRouter{
    
    /**
     * 路由解析分发时的默认值
     * @var array 
     */
    public static $router_default = array(
        'module'        => 'default',
        'controller'    => 'index',
        'action'        => 'index',
    );
    
    /**
     * 获取路由解析对象
     * 
     * @return ApplicationRouterTranslate
     */
    public static function routerTranslate(){
        $default_controller = Application::$config->get('default_controller' , self::$router_default['controller']);
        $default_action = Application::$config->get('default_action' , self::$router_default['action']);
        $default_module = Application::$config->get('default_module' , self::$router_default['module']);
        
        $router = new ApplicationRouterTranslate();
        $router->setModule($default_module);
        $router->setController($default_controller);
        $router->setAction($default_action);
        $router->setUri();
        $router->translateRule();
        $router->coverTranslateRouter();
        return $router;
    }
    
    /**
     * 路由分发
     * 
     * @param ApplicationRouterTranslate $routerTranslate 路由解析结果对象
     */
    public static function routerDistribution(ApplicationRouterTranslate $routerTranslate){
        $modulesName = $routerTranslate->getModule();
        $controllerName = $routerTranslate->getController();
        $actionName = $routerTranslate->getAction();
        ApplicationRouterDistribution::adapter($actionName, $controllerName, $modulesName, $routerTranslate->getParams());
    }
    
    /**
     * 站内请求地址 路由重写
     * 
     * @param string $moduleControllerAction 请求的动作，形如"controller/action","module/controller/action"
     * @param array $paramsKeyValue 参数键值对数组
     */
    public static function routerRewrite($moduleControllerAction , $paramsKeyValue=array()){
        $url = substr($moduleControllerAction , 0 , 1) == '/' ? $moduleControllerAction : '/'.$moduleControllerAction;
        if($paramsKeyValue){
            foreach($paramsKeyValue as $key => $value){
                $url .= '/'.$key.'/'.$value;
            }
        }
        return $url;
    }
    
}


/**
 * 路由 解析
 */
class ApplicationRouterTranslate{
    
    
    private $_request_uri = null;
    
    private $_module = null;
    
    private $_controller = null;
    
    private $_action = null;
    
    private $_params = array();
    
    public function setUri() {
        if($this->_request_uri === null){
            $this->_request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        }
    }
    
    public function getUri(){
        return $this->_request_uri;
    }
    
    /**
     * 路由重写规则
     */
    public function translateRule(){
        $defaultModel = Application::$config->get('default_module' , 'default');
        $modules = Application::$config->get('register_modules' , array($defaultModel));
        if(preg_match('%^/('.implode('|' , $modules).')/([\w\d_]+)(/([\w\d_]+)((/([\w\d_]+)/([^/\?]+))+)?)?%', $this->_request_uri , $matchs)){
            //样例：/module/controller/action/k1/value1/k2/value2...
            $this->_module = $matchs[1];
            $this->_controller = $matchs[2];
            if(isset($matchs[4])){
                $this->_action = $matchs[4];
            }
            if(isset($matchs[5]) && $matchs[5]){
                $cutParams = explode('/', substr($matchs[5] , 1));
                $key = '';
                foreach ($cutParams as $now => $string){
                    if($now % 2 == 0){
                        $key = $string;
                    }else{
                        $this->_setParam($key, $string);
                    }
                }
            }
        }elseif(preg_match('%^/([\w\d_]+)(/([\w\d_]+)((/([\w\d_]+)/([^/\?]+))+)?)?%', $this->_request_uri , $matchs)){
            //样例：/controller/action/k1/value1/k2/value2...
            $this->_controller = $matchs[1];
            if(isset($matchs[3])){
                $this->_action = $matchs[3];
            }
            if(isset($matchs[4]) && $matchs[4]){
                $cutParams = explode('/', substr($matchs[4] , 1));
                $key = '';
                foreach ($cutParams as $now => $string){
                    if($now % 2 == 0){
                        $key = $string;
                    }else{
                        $this->_setParam($key, $string);
                    }
                }
            }
        }
    }
    
    /**
     * 自定义路由 
     * - 若与基础定义冲突，则会覆盖基础路由
     */
    public function coverTranslateRouter(){
        
    }
    
    public function setModule($module){
        $this->_module = $module;
    }
    
    public function setController($controller){
        $this->_controller = $controller;
    }
    
    
    public function setAction($action){
        $this->_action = $action;
    }

    public function getModule(){
        return $this->_module;
    }

    public function getController(){
        return $this->_controller;
    }
    
    public function getAction(){
        return $this->_action;
    }
    
    private function _setParam($key , $value){
        $this->_params[$key] = $value;
    }


    public function getParams(){
        return $this->_params;
    }
}
/**
 * 路由分发
 */
class ApplicationRouterDistribution{
    
    /**
     * 注册控制器列表
     * @var array
     */
    private static $_registerController = array();
    /**
     * 注册的动作列表
     * @var array
     */
    private static $_registerAction = array();
    
    private $_actionName = '';
    
    private $_controllerName = '';
    
    private $_moduleName = '';
    
    private $_params = array();
    
    /**
     * 引导动作适配器
     * 
     * @param string $actionName 动作名
     * @param string $controllerName 控制器名
     * @param string $modulesName 模块名
     * @param array $params 参数数组
     */
    public static function adapter($actionName , $controllerName=null , $modulesName=null , $params=array()){
        $switch = new self($actionName , $controllerName , $modulesName , $params);
        if(!$switch->tryControllerType() && !$switch->tryActionType()){
            $switch->throw404Exception();
        }
        unset($switch);
        exit;
    }
    
    private function __construct($actionName , $controllerName , $modulesName , $params) {
        $this->_actionName = $actionName;
        $this->_controllerName = $controllerName;
        $this->_moduleName = $modulesName;
        $this->_params = $params;
    }
    
    
    public function __destruct() {
        $this->_fileSwitch = false;
        $this->_actionName = '';
        $this->_controllerName = '';
        $this->_moduleName = '';
        $this->_params = array();
    }
    
    public function tryControllerType(){
        $controller = $this->_getControllerClassName();
        $controllerFile = $this->_getControllerPath().$controller.'.php';
        if(file_exists($controllerFile)){
            $registerKey = $this->_moduleName.'|'.$this->_controllerName;
            if(!isset(ApplicationRouterDistribution::$_registerController[$registerKey])){
                include $controllerFile;
                ApplicationRouterDistribution::$_registerController[$registerKey] = new $controller($this->_moduleName , $this->_controllerName , $this->_actionName);
            }
            ApplicationRouterDistribution::$_registerController[$registerKey]->setAllParams($this->_params);
            ApplicationRouterDistribution::$_registerController[$registerKey]->beforeAction();
            $action = $this->_actionName.'Action';
            if(!method_exists(ApplicationRouterDistribution::$_registerController[$registerKey], $action)){
                return false;
            }
            $response = ApplicationRouterDistribution::$_registerController[$registerKey]->$action();
            ApplicationRouterDistribution::$_registerController[$registerKey]->afterAction();
            ApplicationRouterDistribution::$_registerController[$registerKey]->endActionOutput($response);
            return true;
        }
        return false;
    }
    
    /**
     * 获取controller类名
     * @return string
     */
    public function _getControllerClassName(){
        $className = ((ApplicationRouter::$router_default['module'] == $this->_moduleName) ? 
                $this->_controllerName : $this->_moduleName.'_'.$this->_controllerName).'Controller';
        return $className;
    }
    
    /**
     * 获取action类名
     * @return string
     */
    public function _getActionClassName(){
        $className = ((ApplicationRouter::$router_default['module'] == $this->_moduleName) ? 
                $this->_controllerName : $this->_moduleName.'_'.$this->_controllerName). '_' . $this->_actionName .'Action';
        return $className;
    }
    
    /**
     * 获取当前模块下的控制器文件夹路径
     * @return atring
     */
    private function _getControllerPath(){
        return Application::$config->get('module_path').
                $this->_moduleName.DIRECTORY_SEPARATOR.
                'controllers'.DIRECTORY_SEPARATOR;
    }
    
    
    public function tryActionType(){
        $actionKey = $this->_moduleName.'|'.$this->_controllerName.'|'.$this->_actionName;
        if(!isset(self::$_registerAction[$actionKey])){
            $controllerPath = $this->_getControllerPath();
            $action = $this->_getActionClassName();
            $actionFile = $controllerPath.$this->_getControllerClassName().DIRECTORY_SEPARATOR.$action.'.php';
            if(!file_exists($actionFile)){
                return false;
            }
            include $actionFile;
            self::$_registerAction[$actionKey] = new $action($this->_moduleName , $this->_controllerName , $this->_actionName);
        }
        self::$_registerAction[$actionKey]->setAllParams($this->_params);
        self::$_registerAction[$actionKey]->beforeAction();
        $response = self::$_registerAction[$actionKey]->response();
        self::$_registerAction[$actionKey]->afterAction();
        self::$_registerAction[$actionKey]->endActionOutput($response);
        return true;
    }
    
    
    public function throw404Exception(){
//        $previous = array(
//            'action'        => $this->_actionName,
//            'controller'    => $this->_controllerName,
//            'module'        => $this->_moduleName,
//        );
//        $previous = json_encode($previous);
//        throw new Exception($this->_moduleName.'|'.$this->_controllerName.'|'.$this->_actionName, 404 , $previous);
        throw new Exception($this->_moduleName.'|'.$this->_controllerName.'|'.$this->_actionName, 404);
    }
    
}