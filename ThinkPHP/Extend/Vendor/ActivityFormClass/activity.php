<?php
class Standard implements ActivityForm {

    static public function buildpage()
    {
        //添加标准页面，包含学号、姓名、性别、邮箱、手机
        return '<br /><label style="width:400px" for="stuid"><span class="badge badge-info">Welcome</span> 欢迎报名参加本活动，请填写以下注册表单：</label><br />'.
        '<label for="stuid">学号*</label><input type="text" class="input-large" id="stuid" name="regdata[]" placeholder="学号，S+9位数字"><br />'.
        '<label for="name">姓名*</label><input type="text" class="input-large" id="name" name="regdata[]">'.
        '<label for="sex">性别*</label><select class="input-medium" id="sex" name="regdata[]"><option value="0">男</option><option value="1">女</option></select><br />'.
        '<label for="email">邮箱*</label><input type="text" class="input-large" id="email" name="regdata[]" placeholder="example@fzu.edu.cn"><br />'.
        '<label for="phone">手机*</label><input type="text" class="input-large" id="phone" name="regdata[]" placeholder="13900000000"><br />';
    }

    static public function checkdata($data)
    {
        if(strlen($data[0]) != 10) return false;
        if(strlen($data[1]) < 4 || strlen($data[1]) > 20) return false;
        $data[2] = (intval($data[2]) == 0 ? 0 : 1);
        if(!preg_match('/^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,5}$/', $data[3])) return false;
        if(strlen($data[4]) != 11 || !is_numeric($data[4])) return false;
        return $data;
    }
}
