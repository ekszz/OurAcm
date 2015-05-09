<?php
class OJLoginInterface {  //OJ登录接口，请根据自己的OJ修改
    static public function isLogin() {  //判断是否OJ已登录
        if(!session('uname')) 
            return false;
        else 
           return true;
    }
    
    static public function getLoginUser() {  //获取OJ登录名
        if(OJLoginInterface::isLogin())
            return session('uname');
        else
           return null;
    }
    
    static public function getLoginURL() {  //返回OJ登录地址
        return '/fasast/login.php';
    }
    
    static public function getRegURL() {  //返回OJ注册地址
        return '/fasast/register.php';
    }
    
    static public function getProblemURL($pid) {  //返回题目ID为$pid的对应题目URL，返回null则禁用相关功能
        return '/fasast/problem.php?pid='.$pid;
    }
    
    static public function getUserURL($uname) {  //返回用户名为$uname的详细信息，返回null则禁用相关功能
        return 'fasast/user.php?uname='.$uname;
    }

	static public function getOJURL() {  //返回OJ主页地址
        return '/fasast/';
    }
}

interface ActivityForm
{
    static public function checkdata($data);
}
