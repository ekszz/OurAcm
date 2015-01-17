<?php
namespace Home\Controller;
use Think\Controller;

class BaseController extends Controller {
    
    protected function _initialize() {
        define('GOLDBIRDS_VER', '0.8.0');
        define('GOLDBIRDS_VER_DIS', '0.8.0 beta1');
        if(version_compare(PHP_VERSION, '5.4.0') < 0) {  //PHP 5.4版本以下，判断magic_quotes_gpc是否打开，打开则关闭
            if(get_magic_quotes_gpc()) {
                $_GET = BaseController::stripslashesRecursive($_GET);
                $_POST = BaseController::stripslashesRecursive($_POST);
            }
        }
    }
    
    static protected function stripslashesRecursive(array $array)
    {
        foreach ($array as $k => $v) {
            if (is_string($v)) {
                $array[$k] = stripslashes($v);
            } else if (is_array($v)) {
                $array[$k] = BaseController::stripslashesRecursive($v);
            }
        }
        return $array;
    }
    
    protected function init() {  //初始化参数缓存
        $configDB = M('Setting');
        $data = $configDB -> field('k, v') -> select();
        foreach($data as $d) {
            S($d['k'], $d['v']);  //不理解为啥TP缓存F方法要判断0长度的字符串无效?
        }
    }
    
    protected function getconfig($key) {  //获取某个参数
        $data = S($key);
        if($data === false) { $this -> init(); $data = S($key); }  //增加一次容错重试
        if($data === false) return null;
        else return $data;
    }
    
    protected function setconfig($key, $value) {  //更改缓存中的参数值
        S($key, $value);
    }
    
    protected function commonassign() {  //公共assign值
        $this -> assign('GOLDBIRDS_VER_DIS', GOLDBIRDS_VER_DIS);
        $this -> assign('config_title', $this -> getconfig('config_title'));
        $this -> assign('footer_additional_code', $this -> getconfig('footer_additional_code'));
    }
    
    protected function logincheck() {  //检测是否本系统已登录，并进行相应处理
    
        if(\OJLoginInterface::isLogin()) {  //OJ已登录
            if(!(session('goldbirds_islogin') && session('goldbirds_oj') == \OJLoginInterface::getLoginUser())) {  //OJ登录后首次访问本系统，加载登录信息到session
                $personDB = M('Person');
                $condition['ojaccount'] = \OJLoginInterface::getLoginUser();
                $user = $personDB -> where($condition) -> find();  //查询关联该OJ的用户信息
                if($user) {
                    session('goldbirds_islogin', 1);
                    session('goldbirds_uid', $user['uid']);
                    session('goldbirds_group', $user['group']);
                    session('goldbirds_oj', \OJLoginInterface::getLoginUser());
                    return 2;  //OJ登录且关联用户
                }
                else {
                    session('goldbirds_islogin', null);
                    session('goldbirds_uid', null);
                    session('goldbirds_group', null);
                    session('goldbirds_oj', null);
                    return 1;  //OJ登录但无关联用户
                }
            }
            else return 2;
        }
        else {  //OJ未登录或已登出，清空本系统session
            session('goldbirds_islogin', null);
            session('goldbirds_uid', null);
            session('goldbirds_group', null);
            session('goldbirds_oj', null);
            return 0;
        }
    }
    
    protected function explain_reg_rule($string) {  //解析注册字段字符串，成功返回Array，失败返回null
        //0|classname（可选classname,包含checkdata(返回true成功,false失败)和buildpage(可选)函数）,1|0(是否公开显示，1显示，0不显示)|text_input类型标题|input-xxlarge,
        //2|0|只读类型标题|classname|value,3|密码类型标题|classname,4|HTML值str
        //5|0|combobox类型标题|classname(不可省)|项一|项二|项三,6|0|textarea类型标题|classname,7|span类型|classname
        //如果只有0一项，则使用class中的buildpage方法生成注册表单。class中的checkdata用来验证提交数据。
        $ret = array();
        $ret[0]['classname'] = null;
        $i = 1;
    
        Vendor('ActivityFormClass.activity');
    
        $list = explode(',', $string);
        foreach($list as $l) {
            $units = explode('|', $l);
            $id = intval($units[0]);
            $len = count($units);
    
            switch($id) {
                case 0:
                    if($len != 2) return null;
                    if(!class_exists($units[1]) || !method_exists($units[1], 'checkdata')) return null;
                    if(count($list) == 1 && (!class_exists($units[1]) || !method_exists($units[1], 'checkdata') || !method_exists($units[1], 'buildpage'))) return null;
                    $ret[0]['classname'] = $units[1];
                    break;
                case 1:
                    if($len != 3 && $len != 4) return null;
                    $ret[$i]['type'] = 1;
                    $ret[$i]['public'] = intval($units[1]) == 0 ? 0 : 1;
                    $ret[$i]['dis'] = $units[2];
                    if($len == 4) $ret[$i]['class'] = $units[3];
                    else $ret[$i]['class'] = '';
                    $i++;
                    break;
                case 2:
                    if($len != 4 && $len != 5) return null;
                    $ret[$i]['type'] = 2;
                    $ret[$i]['public'] = intval($units[1]) == 0 ? 0 : 1;
                    $ret[$i]['dis'] = $units[2];
                    $ret[$i]['class'] = $units[3];
                    if($len == 5) $ret[$i]['item'] = $units[4];
                    else $ret[$i]['item'] = '';
                    $i++;
                    break;
                case 3:
                    if($len != 2 && $len != 3) return null;
                    $ret[$i]['type'] = 3;
                    $ret[$i]['public'] = 0;
                    $ret[$i]['dis'] = $units[1];
                    if($len == 3) $ret[$i]['class'] = $units[2];
                    else $ret[$i]['class'] = '';
                    $i++;
                    break;
                case 4:
                    if($len != 2) return null;
                    $ret[$i]['type'] = 4;
                    $ret[$i]['public'] = 0;
                    $ret[$i]['dis'] = $units[1];
                    $i++;
                    break;
                case 5:
                    if($len < 5) return null;
                    $ret[$i]['type'] = 5;
                    $ret[$i]['public'] = intval($units[1]) == 0 ? 0 : 1;
                    $ret[$i]['dis'] = $units[2];
                    $ret[$i]['class'] = $units[3];
                    $ret[$i]['item'] = array();
                    for($j = 0; $j < $len - 4; $j++) {
                        array_push($ret[$i]['item'], $units[4 + $j]);
                    }
                    $i++;
                    break;
                case 6:
                    if($len != 3 && $len != 4) return null;
                    $ret[$i]['type'] = 6;
                    $ret[$i]['public'] = intval($units[1]) == 0 ? 0 : 1;
                    $ret[$i]['dis'] = $units[2];
                    if($len == 4) $ret[$i]['class'] = $units[3];
                    else $ret[$i]['class'] = '';
                    $i++;
                    break;
                case 7:
                    if($len != 2 && $len != 3) return null;
                    $ret[$i]['type'] = 7;
                    $ret[$i]['public'] = 0;
                    $ret[$i]['dis'] = $units[1];
                    if($len == 3) $ret[$i]['class'] = $units[2];
                    else $ret[$i]['class'] = '';
                    $i++;
                    break;
                default:
                    return null;
                    break;
            }
        }
        $ret[0]['count'] = $i;
        return $ret;
    }
    
    protected function myajaxReturn($data, $info, $status) {
        $d['info'] = $info;
        $d['status'] = $status;
        $d['data'] = $data;
        $this -> ajaxReturn($d);
    }
    
    Public function verify() {  //生成验证码
    
        $config =    array(
        'fontSize'    =>    15,    // 验证码字体大小
        'length'      =>    4,     // 验证码位数
        'useCurve'    =>    false, // 关闭验证码杂点
        'codeSet'     =>    '0123456789', //字符集
        );
    
        $Verify = new \Think\Verify($config);
        $Verify->entry();
    }
}