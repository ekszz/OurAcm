<?php 
class BaseAction extends Action {
    
    protected function init() {  //初始化参数缓存
        $configDB = M('Setting');
        $data = $configDB -> field('k, v') -> select();
        foreach($data as $d) {
            F($d['k'], $d['v']);
        }
    }
    
    protected function getconfig($key) {  //获取某个参数
        $data = F($key);
        if($data === false) { $this -> init(); $data = $data = F($key); }  //增加一次容错重试
        if($data === false) return null;
        else return $data;
    }
    
    protected function setconfig($key, $value) {  //更改缓存中的参数值
        F($key, $value);
    }
    
    protected function commonassign() {  //公共assign值
        $this -> assign('config_title', $this -> getconfig('config_title'));
        $this -> assign('footer_additional_code', $this -> getconfig('footer_additional_code'));
    }
}