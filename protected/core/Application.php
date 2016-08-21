<?php
//定义系统核心文件路径
define('APPLICATION_CORE_PATH', dirname(__FILE__).DIRECTORY_SEPARATOR);
//定义根路径
define('APPLICATION_PATH', APPLICATION_CORE_PATH . '..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);
//定义保护路径
define('APPLICATION_PROTECTED_PATH', APPLICATION_PATH . 'protected'. DIRECTORY_SEPARATOR);
//定义插件路径
define('APPLICATION_PLUGIN_PATH', APPLICATION_PROTECTED_PATH . 'plugins'.DIRECTORY_SEPARATOR);
//定义运行可写文件夹路径
define('APPLICATION_RUNTIME_PATH', APPLICATION_PROTECTED_PATH . 'runtime' . DIRECTORY_SEPARATOR);
//定义临时文件夹主路径 - 不定时清空位置
define('APPLICATION_TMP_BASE_PATH', APPLICATION_RUNTIME_PATH . 'tmp' . DIRECTORY_SEPARATOR);
//定义定时清空的文件夹路径
define('APPLICATION_TMP_PATH', APPLICATION_TMP_BASE_PATH . date('Y'.DIRECTORY_SEPARATOR.'m'.DIRECTORY_SEPARATOR.'d') . DIRECTORY_SEPARATOR);

/**
 * 应用初始化
 * @author cao_zl
 */
class Application {
    /**
     * 模式：站点应用
     */
    const MODE_WEBAPPLICATION = 'web_application';
    /**
     * 模式：命令行任务
     */
    const MODE_COMMAND = 'command';
    /**
     * 方位使用的模式
     * @var string
     */
    public static $mode = '';
    /**
     * 记录全局变量
     * @var array
     */
    public static $global = array();
    /**
     * 记录全局变量配置
     * @var utils_config
     */
    public static $config = null;
    /**
     * 初始化站点配置信息
     * 
     * @param string $configFile
     * @return Application
     */
    public static function web($configFile){
        $class = self::_init(Application::MODE_WEBAPPLICATION , $configFile);
        return $class;
    }
    /**
     * 初始化命令行配置信息
     * 
     * @param string $configFile
     * @return Application
     */
    public static function command($configFile){
        $class = self::_init(Application::MODE_COMMAND , $configFile);
        return $class;
    }
    
    private static function _init($mode , $configFile){
        static $_applicationList = array();
        if(!isset($_applicationList[$mode])){
            $_applicationList[$mode] = new self($mode , $configFile);
            $_applicationList[$mode]->_registerAutoload();
            $_applicationList[$mode]->_registerDebug();
            $_applicationList[$mode]->_loadGlobalConfig();
        }
        return $_applicationList[$mode];
    }
    
    private function __construct($mode , $configFile) {
        Application::$mode = $mode;
        //系统开始执行时间
        Application::$global['system_start_time'] = microtime(true);
        //系统开始阶段占用的内存量
        Application::$global['system_start_memory'] = memory_get_usage(true);
        //系统全局配置文件类别名
        Application::$global['configFile'] = $configFile;
    }
    
    /**
     * 注册自动加载规则
     */
    private function _registerAutoload(){
        include APPLICATION_CORE_PATH . 'ApplicationAutoload.php';
        spl_autoload_register(array('ApplicationAutoload', 'register'));
    }
    
    /**
     * 注册处理debug模块
     * - 致命错误
     * - 异常捕获
     */
    private function _registerDebug(){
        include APPLICATION_CORE_PATH . 'ApplicationDebug.php';
        register_shutdown_function(array("ApplicationDebug" , "catchFatalError"));
        set_error_handler(array('ApplicationDebug' , 'catchErrorInfo'));// , E_ALL|E_STRICT 
        set_exception_handler(array('ApplicationDebug' , 'catchException'));
    }
    
    /**
     * 读取全局配置
     */
    private function _loadGlobalConfig(){
        Application::$config = utils_config::getFile(Application::$global['configFile']);
    }

    /**
     * 运行
     */
    public function run(){
        //初始化路由
        $routerClass = ApplicationRouter::routerTranslate();
        
        ApplicationRouter::routerDistribution($routerClass);
    }
    
}
