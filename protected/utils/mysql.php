<?php
/**
 * mysql直连查询类
 *
 * @author lenovo
 */
class utils_mysql {
    
    /**
     * 查询 - 可用
     * @return utils_sql_selector
     */
    public static function getSelector(){
       return utils_sql_selector::initSelector();
    }
    
    /**
     * 修改 - 未完工
     * @return 
     */
    public static function getUpdater(){
        return utils_sql_updater::init($tableName);
    }
    
    /**
     * 插入 - 未完工
     * 
     * @param type $table
     * @param type $insertKeyValue
     */
    public static function insert($table , $insertKeyValue){
        
    }
    
    
}
