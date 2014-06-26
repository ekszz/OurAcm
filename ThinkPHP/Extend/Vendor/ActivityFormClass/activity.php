<?php
class Standard implements ActivityForm {

    public function buildpage()
    {
        // TODO 添加标准页面，包含学号、姓名、性别、邮箱、手机
        
    }

    public function checkdata($data)
    {
        if(strlen($data[0]) != 10) return false;
        if(strlen($data[1]) < 4 || strlen($data[1]) > 20) return false;
        $data[2] = (intval($data[2]) == 0 ? 0 : 1);
        if(!preg_match('/^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,5}$/', $data[3])) return false;
        if(strlen($data[4]) != 11 || !is_numeric($data[4])) return false;
        return $data;
    }
}
