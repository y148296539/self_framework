<?php

class utils_string{

    public static function changeTargeEncoding(&$target , $in_charset='UTF-8' , $out_charset='GBK'){
        if($target){
            if(is_string($target) && !is_numeric($target)){
                $target = iconv($in_charset, $out_charset.'//IGNORE', $target);
            }elseif(is_array($target)){
                foreach($target as $k => $v){
                    self::changeTargeEncoding($target[$k], $in_charset, $out_charset);
                }
            }elseif(is_object($target)){
                foreach($target as $k => $v){
                    self::changeTargeEncoding($target->$k, $in_charset, $out_charset);
                }
            }
        }
    }
    
    public static function getChineseRegForUtf8(){
        return '%[\x{4e00}-\x{9fa5}]%u';
    }
    
    public static function getChineseRegForGBK(){
        return "/([".chr(0xb0)."-".chr(0xf7)."][".chr(0xa1)."-".chr(0xfe)."])/";
    }
    
    public static function replaceMoreSpaceToSingle($targetString){
        $returnString = '';
        if($targetString){
            $returnString = strtr($targetString , array("\r\n" => "\n"));
            $returnString = preg_replace('%[ ]{2,}%', ' ', trim($returnString));
        }
        return $targetString;
    }
    
    /**
     * 自动换行函数 - 给字符串自动添加换行符
     * @param tring $baseString 原始字符串
     * @param int $wordsLineLimitNum 每行最多显示的中文字数
     * @param mixed $changeFlag 添加的换行符，默认\\n
     * @return string 返回结果
     */
    public static function autoChangeLine($baseString , $wordsLineLimitNum , $changeFlag="\n"){
        $wordsLineLimitNum *= 2;
        $targetString = self::replaceMoreSpaceToSingle($baseString);
        $resultString = '';
        $arrCut = self::_cutString($targetString);
        $nowLineNum = 0;
        $elementNum = count($arrCut);
        for($i = 0; $i < $elementNum ; $i ++){
            if(preg_match('%[[:punct:]\w\d]%', $arrCut[$i])){
                $add = 1;
                $nowLineNum += 1;//半角字符加一
            }else{
                $add = 2;
                $nowLineNum += $add;//全角字符加二
            }
            if($nowLineNum >= $wordsLineLimitNum){
                if(($arrCut[$i] !== "\n") && isset($arrCut[$i + 1]) && $arrCut[$i + 1] === "\n"){
                    $nowLineNum = 0;//换行重置
                    $resultString .= $arrCut[$i].$changeFlag;
                    $i = $i + 1;
                }elseif($arrCut[$i] === "\n"){
                    $nowLineNum = 0;//换行重置
                    $resultString .= $changeFlag;
                }elseif(isset($arrCut[$i + 1]) && in_array($arrCut[$i + 1], array(',' , '.' , '!' , '~'))){
                    $resultString .= $changeFlag.$arrCut[$i].$arrCut[$i + 1];
                    $nowLineNum = $add + 1;//换行重置
                    $i = $i + 1;
                }else{
                    $nowLineNum = 0;//换行重置
                    $resultString .= $arrCut[$i].$changeFlag;
                }
                continue;
            }elseif($arrCut[$i] === "\n"){
                $nowLineNum = 0;//换行重置
                $resultString .= $changeFlag;
                continue;
            }
            $resultString .= $arrCut[$i];
        }
        return $resultString;
    }
    
    /**
     * 将字符串切割成单字符数组
     * @param string $baseString
     * @return array
     */
    private static function _cutString($baseString){
        $reg_cut_string = '%[\x{00A4}-\x{FFFD}\d\s\w[:punct:]]%u';//2E80
        $arr = array();
        if(preg_match_all($reg_cut_string, $baseString , $matchs)){
            $arr = $matchs[0];
        }
        return $arr;
    }
    
    
    
}
    