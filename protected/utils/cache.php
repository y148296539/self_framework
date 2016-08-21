<?php
/**
 * 缓存处理类
 */
class utils_cache{
    
    public static function memcache($config_key='memcache'){
        static $_cache = array();
        if(!isset($_cache[$config_key])){
            $_cache[$config_key] = new selfMemcache($config_key);
            $_cache[$config_key]->connect();
        }
        return $_cache[$config_key];
    }
    
    
    /**
     * 文件缓存处理
     * 
     * @param string $type 缓存类别名
     * @return selfFileCache
     */
    public static function file($type='default'){
        static $_instance = array();
        if (!isset($_instance[$type])) {
            $_instance[$type] = new selfFileCache($type);
        }
        return $_instance[$type];
    }
    
    
    public static function redis(){
        
    }
    
}


class selfMemcache{
    
    private $_config = array();
    /**
     * memecache实例
     * @var Memcache 
     */
    private $_memcache = null;
    
    public function __construct($config_key) {
        $this->_config = Application::$config->get($config_key);
    }
    
    
    public function __destruct() {
        $this->_memcache->close();
    }
    
    /**
     * 连接MEMCACHE，正常情况下不需要显式调用
     */
    public function connect(){
        $this->_memcache  = new Memcache ;
        $this->_memcache->connect($this->_config[0], $this->_config[1], $this->_config[2]);
    }
    
    /**
     * 写入缓存
     *
     * @param string $cacheKey 缓存的键名
     * @param mixed $cacheValue 缓存的内容
     * @param int $expire 过期时间，单位：秒
     * @return boolean
     */
    public function set($key , $value , $expire){
        return $this->_memcache->set($key , $value , 0 , $expire);
    }
    
    /**
     * 
     * @param string $key
     * @return mixed 未找到返回false
     */
    public function get($key){
        return $this->_memcache->get($key);
    }
    
    
    public function delete($key){
        return $this->_memcache->delete($key);
    }
    
    
    public function flush(){
        return $this->_memcache->flush();
    }
    
}


class selfRedis{
    
    
}

/**
 * 简单文件缓存类
 * 只提供不稳定的简单缓存功能
 */
class selfFileCache {

    /**
     * 缓存文件的文件名和路径
     *
     * @var string
     */
    private $_cacheFilePath = '';

    /**
     * 缓存文件中保存的字符串
     *
     * @var string
     */
    private $_cacheFileValue = null;

    /**
     * 实例化
     *
     * @param string $type 缓存类别名
     */
    public function __construct($type) {
        $dir = APPLICATION_RUNTIME_PATH . 'cache' . DIRECTORY_SEPARATOR;
        utils_file::preparePath($dir);
        $this->_cacheFilePath = $dir . $type.'.cache';
    }

    /**
     * 读取缓存文件中的保存内容 - 返回的可能是对象或数组，看原始传入的是什么类型
     *
     * @staticvar string $_file
     * @return mixed
     */
    private function _getCacheOrig() {
        if ($this->_cacheFileValue === null && file_exists($this->_cacheFilePath)) {
            $fileString = file_get_contents($this->_cacheFilePath);
            $this->_cacheFileValue = $fileString ? unserialize($fileString) : '';
        }
        return $this->_cacheFileValue;
    }

    /**
     * 保存缓存的原始值
     *
     * @param array $cacheArray 写入文件的缓存键值对
     * @return boolean
     */
    private function _saveCacheOrig($cacheArray) {
        $this->_cacheFileValue = $cacheArray ? $cacheArray : '';
        $saveString = $this->_cacheFileValue ? serialize($this->_cacheFileValue) : '';
        return utils_file::writeFile($this->_cacheFilePath, $saveString , 'w') ? true : false;
    }

    /**
     * 清理缓存文件，将过期内容删除掉
     *
     * @param array $cacheArray 即将被保存的键值对
     */
    private function _clearExpireValue(&$cacheArray) {
        if ($cacheArray && count($cacheArray) > 200) {
            $now = time();
            foreach ($cacheArray as $key => $value) {
                if ($value['e'] < $now) {
                    unset($cacheArray[$key]);
                }
            }
        }
    }

    /**
     * 写入缓存
     *
     * @param string $cacheKey 缓存的键名
     * @param mixed $cacheValue 缓存的内容
     * @param int $expire 过期时间，单位：秒
     * @return boolean
     */
    public function set($cacheKey, $cacheValue, $expire) {
        $cacheArray = $this->_getCacheOrig() ? $this->_getCacheOrig() : array();
        $this->_clearExpireValue($cacheArray);
        $cacheArray[$cacheKey] = array(
            'v' => $cacheValue,
            'e' => time() + intval($expire),
        );
        return $this->_saveCacheOrig($cacheArray);
    }

    /**
     * 读取缓存
     *
     * @param string $cacheKey 缓存的键名
     * @param mixed $defaultValue 如果没有取到缓存值，返回的默认返回值
     * @return mixed
     */
    public function get($cacheKey, $defaultValue = false) {
        $cacheValue = $defaultValue;
        $cacheArray = $this->_getCacheOrig();
        if ($cacheArray && isset($cacheArray[$cacheKey]) && ($cacheArray[$cacheKey]['e'] > time())) {
            $cacheValue = $cacheArray[$cacheKey]['v'];
        }
        return $cacheValue;
    }

    /**
     * 删除某一缓存
     *
     * @param string $cacheKey 缓存键名
     */
    public function delete($cacheKey){
        $cacheArray = $this->_getCacheOrig();
        if($cacheArray && isset($cacheArray[$cacheKey])){
            unset($cacheArray[$cacheKey]);
            $this->_saveCacheOrig($cacheArray);
        }
    }

    /**
     * 清空全部缓存 - 或者直接手动删除缓存保存文件
     */
    public function flush(){
        $this->_cacheFileValue = '';
        unlink($this->_cacheFilePath);
    }

}