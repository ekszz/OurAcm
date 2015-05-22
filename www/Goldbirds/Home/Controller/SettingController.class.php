<?php 
namespace Home\Controller;

class SettingController extends BaseController {
    
    public function index() {  //默认处理方法，未登录显示未登录页面，已登录显示个人信息profile页面
        
        $this -> profile();
    }
    

    //个人信息页面========================================
    
    public function profile() {  //个人信息页面
        
        $this -> commonassign();
        if($this -> logincheck() == 0) {  //未登录处理
            $this -> assign('url', \OJLoginInterface::getLoginURL());
            $this -> display('nologin');
            return ;
        }
        
        if($this -> logincheck() == 1) {  //无权限处理
            $this -> display('noallow');
            return ;
        }
        
        $personDB = M('Person');
        $condition['uid'] = session('goldbirds_uid');
        $data = $personDB -> where($condition) -> find();
        
        $this -> assign('lock_person_introduce', intval($this -> getconfig('lock_person_introduce')));
        $this -> assign('data', $data);
        $this -> display('profile');
    }
    
    public function ajax_modify_profile() {  //更新个人资料
        
        if(!session('goldbirds_islogin')) $this -> myajaxReturn(null, '还未登录，无权限。', 2);  //无权限处理
        
        $personDB = D('Person');
        if(I('post.email', '', false) === '') $data['email'] = null;
        else if(strlen(I('post.email', '', false)) > 0) $data['email'] = I('post.email', '', false);
        if(I('post.phone', '', false) === '') $data['phone'] = null;
        else if(strlen(I('post.phone', '', false)) > 0) $data['phone'] = I('post.phone', '', false);
        if(I('post.address', '', false) === '') $data['address'] = null;
        else if(strlen(I('post.address', '', false)) > 0) $data['address'] = I('post.address', '', false);
        if(!intval($this -> getconfig('config_lock_person_introduce'))) {
            if(I('post.introduce', '', false) === '') $data['introduce'] = null;
            else if(strlen(I('post.introduce', '', false)) > 0) $data['introduce'] = I('post.introduce', '', false);
        }
        if(I('post.detail', '', false) === '') $data['detail'] = null;
        else if(strlen(I('post.detail', '', false)) > 0) $data['detail'] = I('post.detail', '', false);
        
        if(!$personDB -> create($data)) {  //自动验证失败
            $this -> myajaxReturn(null, $personDB -> getError(), 1);
        }
        else {  //自动验证成功
            if(false === $personDB -> where('uid = '.session('goldbirds_uid')) -> save($data)) {
                $this -> myajaxReturn(null, '[数据库错误]请重试...', 3);
            }
            else {
                $this -> myajaxReturn(null, '[成功]', 0);
            }
        }
    }
    
    public function ajax_upload_face() {  //上传头像
        
        //使用iframe模拟AJAX上传图片，导致该函数无法使用myajaxReturn，请注意。
        if(!session('goldbirds_islogin')) $this -> myajaxReturn(null, '还未登录，无权限。', 2);  //无权限处理
        
        $upload = new \Think\Upload();  //实例化上传类
        $upload -> maxSize = 10485760 ;  //设置附件上传大小
        $upload -> exts = array('jpg', 'gif', 'png', 'jpeg');  // 设置附件上传类型
        $upload -> rootPath = './upload/';  //设置附件上传目录
        $upload -> savePath = '';
        $upload -> autoSub = false;
        if(!($info = $upload -> upload())) {  //上传错误提示错误信息
            echo json_encode(array('info' => (''.$upload -> getError()), 'status' => 1));
        } 
        else {  //上传成功
            $fileinfo = current($info);
            $personDB = D('Person');
            $oldphoto = $personDB -> where('uid = '.session('goldbirds_uid')) -> find();
            $oldphoto = $oldphoto['photo'];
            $newphoto = 'upload/'.$fileinfo['savename'];
            
            if(false === $personDB -> where('uid = '.session('goldbirds_uid')) -> setField('photo', $newphoto)) {
                unlink($newphoto);
                echo json_encode(array('info' => '写入数据库出错！', 'status' => 2));
            }
            else {
                if($oldphoto) unlink($oldphoto);
                echo json_encode(array('data' => 'upload/'.$fileinfo['savename'], 'info' => '上传头像成功，文件大小'.sprintf("%.2lf", intval($fileinfo['size'])/1024).'KB.', 'status' => 0));
            }
        }
    }
    
    public function ajax_verify_luckycode() {  //验证邀请码
        
        if($this -> logincheck() == 0) $this -> myajaxReturn(null, '还未登录，无权限。', 2);  //无权限处理
        
        $code = I('post.code');
        sleep(1);
        if(strlen($code) != 16)
            $this -> myajaxReturn(null, '无效的邀请码，请重试！', 1);
        
        $personDB = M('Person');
        $c['luckycode'] = $code;
        $data = $personDB -> field('uid, chsname, engname, ojaccount, email, phone') -> where($c) -> find();
        if($data) {
            if($data['ojaccount'] == null) {
                $r['code'] = 'UID:'.$data['uid'].'-'.$data['chsname'].'-'.$data['engname'];
                $r['oj'] = \OJLoginInterface::getLoginUser();
                $r['email'] = $data['email'];
                $r['phone'] = $data['phone'];
                $this -> myajaxReturn($r, '[成功]', 0);
            }
            else $this -> myajaxReturn(null, '无效的邀请码，请重试！', 1);
        }
        else {
            $this -> myajaxReturn(null, '无效的邀请码，请重试！', 1);
        }
    }
    
    public function ajax_bind_luckycode() {  //验证邀请码
    
        if($this -> logincheck() == 0) $this -> myajaxReturn(null, '还未登录，无权限。', 2);  //无权限处理
        
        $code = I('post.code');
        $email = I('post.email', '', false);
        $phone = I('post.phone', '', false);
        $oj = \OJLoginInterface::getLoginUser();
        sleep(1);
        if(strlen($code) != 16)
            $this -> myajaxReturn(null, '无效的邀请码，请重试！', 1);
        else if(!preg_match('/^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,5}$/', $email))
            $this -> myajaxReturn(null, 'E-mail格式不正确！请重试。', 1);
        else if(strlen($phone) < 8 || strlen($phone) > 11)
            $this -> myajaxReturn(null, '联系电话格式不正确！请重试。', 1);
        else {
            $personDB = M('Person');
            $c['luckycode'] = $code;
            $data = $personDB -> field('uid, chsname, engname, ojaccount') -> where($c) -> find();
            if($data) {
                if($data['ojaccount'] == null) {  //验证完毕，准备绑定
            
                    $data['ojaccount'] = $oj;
                    $data['phone'] = $phone;
                    $data['email'] = $email;
                    if($personDB -> where('uid = '.$data['uid']) -> limit(1) -> save($data))
                        $this -> myajaxReturn(null, '[成功]', 0);
                    else {
                        $this -> myajaxReturn(null, '绑定失败，请刷新后重试。', 0);
                    }
                }
                else $this -> myajaxReturn(null, '无效的邀请码，请重试！', 1);
            }
            else {
                $this -> myajaxReturn(null, '无效的邀请码，请重试！', 1);
            }
        }
    }
    
    
    //通讯录页面===================================
    
    public function contacts() {
        
        $this -> commonassign();
        if($this -> logincheck() == 0) {  //未登录处理
            $this -> assign('url', \OJLoginInterface::getLoginURL());
            $this -> display('nologin');
            return ;
        }
        
        if($this -> logincheck() == 1) {  //无权限处理
            $this -> display('noallow');
            return ;
        }
        
        $personDB = M('Person');
        
        $teacher = $personDB -> field('chsname, sex, email, phone, introduce') -> where('`group` = 2') -> order('uid ASC') -> select();
        $data = $personDB -> field('chsname, sex, email, phone, grade, address, introduce') -> where('`group` < 2 AND uid > 0') -> select();
        
        for($i = 0; $i < count($teacher); $i++) {
            $teacher[$i]['email'] = htmlspecialchars($teacher[$i]['email']);
            $teacher[$i]['introduce'] = htmlspecialchars($teacher[$i]['introduce']);
        }
        
        for($i = 0; $i < count($data); $i++) {
            $data[$i]['email'] = htmlspecialchars($data[$i]['email']);
            $data[$i]['address'] = htmlspecialchars($data[$i]['address']);
            $data[$i]['introduce'] = htmlspecialchars($data[$i]['introduce']);
        }

        $this -> assign('teacher', $teacher);
        $this -> assign('data', $data);
        $this -> display('contacts');
    }
    
    //队员管理页面===================================
    
    public function person() {  //队员管理
        
        $this -> commonassign();
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> profile();
        else {
            $this -> display('person');
        }
    }
    
    public function ajax_load_person() {  //返回所有队员列表
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $personDB = M('Person');
            $data = $personDB -> field('uid, chsname, sex, email, phone, grade, ojaccount, group, luckycode') -> where('uid > 0') -> order('uid ASC') -> select();
            if($data === false) {
                $this -> myajaxReturn(null, '数据库错误。', 1);
            }
            else if($data === 0) {
                $this -> myajaxReturn(null, '没有队员信息。', 2);
            }
            else {
                for($i = 0; $i < count($data); $i++) {
                    $data[$i]['chsname'] = htmlspecialchars($data[$i]['chsname']);
                    if($data[$i]['email']) $data[$i]['email'] = htmlspecialchars($data[$i]['email']);
                    if($data[$i]['phone']) $data[$i]['phone'] = htmlspecialchars($data[$i]['phone']);
                    if($data[$i]['ojaccount']) $data[$i]['ojaccount'] = htmlspecialchars($data[$i]['ojaccount']);
                }
                $this -> myajaxReturn($data, '[成功]', 0);
            }
        }
    }
    
    public function ajax_get_person() {  //获取一名队员详细信息
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $personDB = M('Person');
            $uid = intval(I('get.uid'));
            if($uid < 0) $this -> myajaxReturn(null, 'UID无效。', 1);
            $data = $personDB -> where('uid = '.$uid) -> find();
            if($data === false) $this -> myajaxReturn(null, '数据库错误。', 2);
            else if(!$data) $this -> myajaxReturn(null, 'UID无效。', 1);
            else {
                $this -> myajaxReturn($data, '[成功]', 0);
            }
        }
    }
    
    public function ajax_del_person() {  //删除用户
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $list = I('get.uid');
            $uids = explode(',', $list);
            $success = 0;
            $fail = 0;
            foreach ($uids as $uid) {
                if(!$this -> del_one_person($uid)) $success ++;
                else $fail ++;
            }
            if($success == 0 && $fail == 0) $this -> myajaxReturn(null, '无效的参数。', 2);
            else if($fail != 0 && $success == 0) $this -> myajaxReturn(null, '删除失败。', 1);
            else if($fail != 0 && $success != 0) $this -> myajaxReturn(null, '[提示]已成功删除'.$success.'名队员，删除失败'.$fail.'名。', 0);
            else $this -> myajaxReturn(null, '已成功删除'.$success.'名队员。', 0);
        }
    }
    
    private function del_one_person($uid) {  //删除一位用户，ajax_del_person具体实现，返回：1-失败，0-成功
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            return 1;
        else {
            $uid = intval($uid);
            if($uid <= 0) return 1;
            
            $personDB = M('Person');
            $contestDB = M('Contest');
            
            //先更新Contest中的队员为UID=0
            $ret = $contestDB -> where('teamer1 = '.$uid) -> setField('teamer1', 0);
            if(false === $ret) return 1;
            $ret = $contestDB -> where('teamer2 = '.$uid) -> setField('teamer2', 0);
            if(false === $ret) return 1;
            $ret = $contestDB -> where('leader = '.$uid) -> setField('leader', 0);
            if(false === $ret) return 1;
            
            $res = $personDB -> where('uid='.$uid) -> limit(1) -> delete();
            if(false === $res) return 1;
            else if(0 === $res) return 1;
            else return 0;
        }
    }
    
    public function ajax_sendinv() {  //发送邀请函
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $list = I('get.uid');
            $uids = explode(',', $list);
            $success = 0;
            $fail = 0;
            foreach ($uids as $uid) {
                $ret = $this -> sendinv_impl($uid);
                if($ret == 9) continue;
                else if(!$ret) $success ++;
                else $fail ++;
            }
            if($success == 0 && $fail == 0) $this -> myajaxReturn(null, '无效的参数。', 2);
            else if($fail != 0 && $success == 0) $this -> myajaxReturn(null, '发送邮件失败。', 1);
            else if($fail != 0 && $success != 0) $this -> myajaxReturn(null, '[提示]已成功发送'.$success.'封邀请邮件，失败'.$fail.'封。', 0);
            else $this -> myajaxReturn(null, '已成功发送'.$success.'封邀请邮件。', 0);
        }
    }
    
    private function sendinv_impl($uid) {
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            return 1;
        else {
            $uid = intval($uid);
            if($uid <= 0) return 1;
        
            //获取队员信息
            $personDB = M('Person');
            $person = $personDB -> where('uid = '.$uid) -> field('chsname, engname, email, luckycode, ojaccount') -> find();
            if(!$person) return 1;
            if(!$person['email'] || $person['ojaccount']) return 9;  //已注册用户，略过
            
            //发送邮件
            import('Vendor.phpMailer.phpmailer');
            $mail = new \PHPMailer();
            $mail -> IsSMTP();
            $mail -> CharSet = 'UTF-8';
            $mail -> AddAddress($person['email'], $person['engname']);  //收件人地址
            $mail -> Body = str_replace('{url}', 'http://'.$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"].'?z=setting', str_replace('{code}', $person['luckycode'], str_replace('{engname}', $person['engname'], str_replace('{chsname}', $person['chsname'], $this -> getconfig('config_invite_content')))));  //设置邮件正文
            $mail -> From = $this -> getconfig('config_smtp_account');  //设置邮件头的From字段
            $mail -> FromName = $this -> getconfig('config_smtp_fromname');  //设置发件人名字
            $mail -> Subject = $this -> getconfig('config_invite_title');  //设置邮件标题
            $mail -> Host = $this -> getconfig('config_smtp_host');  //设置SMTP服务器

            $mail -> SMTPAuth = (intval($this -> getconfig('config_smtp_needauth')) == '0' ? false : true);  //需要验证
            if($mail -> SMTPAuth) $mail -> Username = $this -> getconfig('config_smtp_username');  //设置用户名和密码
            if($mail -> SMTPAuth) $mail -> Password = $this -> getconfig('config_smtp_password');
            
            // 发送邮件
            if($mail->Send()) return 0;
            else return 2;
        }
    }
    
    public function ajax_add_person() {  //队员管理-添加一名队员
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $tmp = intval(I('post.nowuid'));
            if($tmp != 9999) $this -> myajaxReturn(null, '无效的参数。', 2);
            $data['chsname'] = I('post.chsname', '<ERROR NAME>', false);
            $data['engname'] = I('post.engname', '', false) == '' ? null : I('post.engname', '', false);
            $data['email'] = I('post.email', '', false) == '' ? null : I('post.email', '', false);
            $data['phone'] = I('post.phone', '', false) == '' ? null : I('post.phone', '', false);
            $data['address'] = I('post.address', '', false) == '' ? null : I('post.address', '', false);
            if(intval(I('post.sex')) == 1) $data['sex'] = 1;
            else $data['sex'] = 0;
            $tmp = intval(I('post.grade'));
            if($tmp > 1950 && $tmp < 2100) $data['grade'] = $tmp;
            else $data['grade'] = null;
            $data['introduce'] = I('post.introduce', '', false) == '' ? null : I('post.introduce', '', false);
            $data['detail'] = I('post.detail', '', false) == '' ? null : I('post.detail', '', false);
            $data['ojaccount'] = I('post.ojaccount') == '' ? null : I('post.ojaccount');
            $tmp = intval(I('post.group'));
            if($tmp == 0 || $tmp == 1 || $tmp == 2 || $tmp == 9) $data['group'] = $tmp;
            else $data['group'] = 0;
            srand((double)microtime()*1000000);
            $data['luckycode'] = substr(md5('goldbirds'.'_xzz'.$data['chsname'].rand()), 10, 16);
            
            $data['photo'] = strcmp(substr(I('post.face_fn'), 0, 7), 'upload/') == 0 ? I('post.face_fn') : null;
            
            $personDB = D('Person');
            if(!$personDB -> create($data)) {
                $this -> myajaxReturn(null, $personDB -> getError(), 1);
            }
            else {
                if(false === ($tmp = $personDB -> add()))
                    $this -> myajaxReturn(null, '写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                else 
                  $this -> myajaxReturn($tmp.'-'.$data['chsname'].'-'.$data['engname'], '新增用户“'.$data['chsname'].'”，UID:'.$tmp, 0);
            }
        }
    }
    
    public function ajax_modify_person() {  //队员管理-修改一名队员
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $uid = intval(I('post.nowuid'));
            if($uid == 9999 || $uid <= 0) $this -> myajaxReturn(null, '无效的UID。', 2);
            
            $data['chsname'] = I('post.chsname', '<ERROR NAME>', false);
            $data['engname'] = I('post.engname', '', false) == '' ? null : I('post.engname', '', false);
            $data['email'] = I('post.email', '', false) == '' ? null : I('post.email', '', false);
            $data['phone'] = I('post.phone', '', false) == '' ? null : I('post.phone', '', false);
            $data['address'] = I('post.address', '', false) == '' ? null : I('post.address', '', false);
            if(intval(I('post.sex')) == 1) $data['sex'] = 1;
            else $data['sex'] = 0;
            $tmp = intval(I('post.grade'));
            if($tmp > 1950 && $tmp < 2100) $data['grade'] = $tmp;
            else $data['grade'] = null;
            $data['introduce'] = I('post.introduce', '', false) == '' ? null : I('post.introduce', '', false);
            $data['detail'] = I('post.detail', '', false) == '' ? null : I('post.detail', '', false);
            $data['ojaccount'] = I('post.ojaccount') == '' ? null : I('post.ojaccount');
            $tmp = intval(I('post.group'));
            if($tmp == 0 || $tmp == 1 || $tmp == 2 || $tmp == 9) $data['group'] = $tmp;
            else $data['group'] = 0;
            
            $data['photo'] = strcmp(substr(I('post.face_fn'), 0, 7), 'upload/') == 0 ? I('post.face_fn') : null;
            
            
            $personDB = D('Person');
            if(!$personDB -> create($data)) {  //自动验证失败
                $this -> myajaxReturn(null, $personDB -> getError(), 1);
            }
            else {  //自动验证成功
                if(false === $personDB -> where('uid='.$uid) -> limit(1) -> save($data)) {
                    $this -> myajaxReturn(null, '写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                }
                else {
                    $this -> myajaxReturn(null, '[成功]', 0);
                }
            }
        }
    }
    
    public function ajax_upload_personface() {  //队员管理-上传照片
        $this -> ajax_upload_contestpic();
    }
    
    //获奖记录管理===========================================
    
    public function contest() {
        
        $this -> commonassign();
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> profile();
        else {
            $this -> display('contest');
        }
    }
    
    public function ajax_load_contest() {  //AJAX获取比赛信息列表
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $contestDB = D('Contest');
            $data = $contestDB -> field('cid, holdtime, site, university, type, medal, team, pic1, pic2') -> where('cid > 0') -> order('holdtime DESC, cid DESC') -> select();
            if($data === false) {
                $this -> myajaxReturn(null, '数据库错误。', 1);
            }
            else if($data === null) {
                $this -> myajaxReturn(null, '没有比赛信息。', 2);
            }
            else {
                for($i = 0; $i < count($data); $i++) {
                    $data[$i]['site'] = htmlspecialchars($data[$i]['site']);
                    $data[$i]['university'] = htmlspecialchars($data[$i]['university']);
                    $data[$i]['team'] = htmlspecialchars($data[$i]['team']);
                }
                $this -> myajaxReturn($data, '[成功]', 0);
            }
        }
    }

    public function ajax_get_contest() {  //获取一条比赛获奖记录信息
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $contestDB = D('Contest');
            $cid = intval(I('get.cid'));
            if($cid <= 0) $this -> myajaxReturn(null, 'CID无效。', 1);
            $data = $contestDB -> relation(true) -> where('cid = '.$cid) -> find();
            if($data === false) $this -> myajaxReturn(null, '数据库错误。', 2);
            else if(!$data) $this -> myajaxReturn(null, 'CID无效。', 1);
            else $this -> myajaxReturn($data, '[成功]', 0);
        }
    }
    
    public function ajax_get_typeaheaddata() {  //自动完成数据
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $personDB = M('Person');
            $data = $personDB -> field('uid, chsname, engname') -> where('`group` <> 9') -> order('uid ASC') -> select();
            if($data === false) {
                $this -> myajaxReturn(null, '数据库错误。', 1);
            }
            else if($data === null) {
                $this -> myajaxReturn('[]', '[提示]系统中没有用户。', 0);
            }
            else {
                $retstr = array();
                $i = 0;
                foreach($data as $d) {
                    $retstr[$i] = $d['uid'].'-'.$d['chsname'].'-'.$d['engname'];
                    $i++;
                }
                $this -> myajaxReturn($retstr, '[成功]', 0);
            }
        }
    }
    
    public function ajax_upload_contestpic() {  //上传照片
    
        //使用iframe模拟AJAX上传图片，导致该函数无法使用myajaxReturn，请注意。
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $upload = new \Think\Upload();  //实例化上传类
            $upload -> maxSize = 2097152;  //设置附件上传大小
            $upload -> exts = array('jpg', 'gif', 'png', 'jpeg');  // 设置附件上传类型
            $upload -> rootPath = './upload/';
            $upload -> savePath = '';  //设置附件上传目录
            $upload -> autoSub = false;
            $info = $upload -> upload();
            if(!$info) {  //上传错误提示错误信息
                echo json_encode(array('info' => (''.$upload -> getError()), 'status' => 1));
            }
            else {  //上传成功
                $fileinfo = current($info);
                $newphoto = 'upload/'.$fileinfo['savename'];
                $data['filename'] = $newphoto;
                $data['id'] = intval(I('post.id'));

                echo json_encode(array('data' => $data, 'info' => '上传成功，文件大小'.sprintf("%.2lf", intval($fileinfo['size'])/1024).'KB。', 'status' => 0));
            }
        }
    }
    
    public function ajax_add_contest() {  //添加获奖记录
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $tmp = intval(I('post.nowcid'));
            if($tmp != 9999) $this -> myajaxReturn(null, '无效的参数。', 2);
            
            if(false === strtotime(I('post.holdtime', '', false)))
                $this -> myajaxReturn(null, '日期格式不对！', 1);
            if(strtotime(I('post.holdtime', '', false)) >= strtotime('2037-12-31') 
                || strtotime(I('post.holdtime', '', false)) < strtotime('1960-1-1')) {
                $this -> myajaxReturn(null, '日期范围不太对！', 1);
            }
            $data['holdtime'] = I('post.holdtime', '', false);
            
            $data['team'] = I('post.team', '', false);
            $data['site'] = I('post.site', '', false);
            $data['university'] = I('post.university', '', false);
            $data['type'] = (intval(I('post.type')) >= 0 && intval(I('post.type')) <= 2) ? intval(I('post.type')) : 1;
            $data['medal'] = (intval(I('post.medal')) >= 0 && intval(I('post.medal')) <= 4) ? intval(I('post.medal')) : 3;
            if($data['type'] == 0 && $data['medal'] == 4) $data['medal'] = 3;
            $data['ranking'] = I('post.ranking', '', false) == '' ? null : I('post.ranking', '', false);
            $data['title'] = I('post.title', '', false) == '' ? null : I('post.title', '', false);
            
            $personDB = M('Person');
            $plist = explode('-', I('post.leader'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> myajaxReturn(null, 'Leader的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['leader'] = $c['uid'];
            
            $plist = explode('-', I('post.teamer1'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> myajaxReturn(null, 'Teamer1的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['teamer1'] = $c['uid'];
            
            $plist = explode('-', I('post.teamer2'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> myajaxReturn(null, 'Teamer2的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['teamer2'] = $c['uid'];
            
            $data['pic1'] = strcmp(substr(I('post.pic1_fn'), 0, 7), 'upload/') == 0 ? I('post.pic1_fn') : null;
            $data['pic2'] = strcmp(substr(I('post.pic2_fn'), 0, 7), 'upload/') == 0 ? I('post.pic2_fn') : null;
            
            if($data['pic1'] === null && $data['pic2'] !== null) {
                $data['pic1'] = $data['pic2'];
                $data['pic2'] = null;
            }
            
            $contestDB = D('Contest');
            if(!$contestDB -> create($data)) {
                $this -> myajaxReturn(null, $contestDB -> getError(), 1);
            }
            else {
                if(false === ($tmp = $contestDB -> add()))
                    $this -> myajaxReturn(null, '写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                else
                  $this -> myajaxReturn(null, '新增获奖记录“'.$data['team'].'”，CID:'.$tmp, 0);
            }
        }
    }

    public function ajax_del_contest() {  //删除获奖记录
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $list = I('get.cid', '', false);
            $delpic = intval(I('get.delpic'));
            $cids = explode(',', $list);
            $success = 0;
            $fail = 0;
            foreach ($cids as $cid) {
                if(!$this -> del_one_contest($cid, $delpic)) $success ++;
                else $fail ++;
            }
            if($success == 0 && $fail == 0) $this -> myajaxReturn(null, '无效的参数。', 2);
            else if($fail != 0 && $success == 0) $this -> myajaxReturn(null, '无效的CID。', 1);
            else if($fail != 0 && $success != 0) $this -> myajaxReturn(null, '[提示]已成功删除'.$success.'条获奖记录，删除失败'.$fail.'条。', 0);
            else $this -> myajaxReturn(null, '已成功删除'.$success.'条获奖记录。', 0);
        }
    }
    
    private function del_one_contest($cid, $delpic) {  //删除一条获奖记录，ajax_del_contest具体实现，返回：1-失败，0-成功
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            return 1;
        else {
            $cid = intval($cid);
            if($cid <= 0) return 1;
        
            $contestDB = M('Contest');
        
            $data = $contestDB -> field('cid, pic1, pic2') -> where('cid='.$cid) -> find();
            if(!$data) return 1;
            if($delpic == 1) {
                if($data['pic1']) unlink($data['pic1']);
                if($data['pic2']) unlink($data['pic2']);
            }
        
            $res = $contestDB -> where('cid='.$cid) -> delete();
            if(false === $res) return 1;
            else if(0 === $res) return 1;
            else return 0;
        }
    }
    
    public function ajax_modify_contest() {  //修改获奖记录
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $cid = intval(I('post.nowcid'));
            if($cid == 9999 || $cid <= 0) $this -> myajaxReturn(null, '无效的CID。', 2);
            
            if(false === strtotime(I('post.holdtime', '', false)))
                $this -> myajaxReturn(null, '日期格式不对！', 1);
            if(strtotime(I('post.holdtime', '', false)) >= strtotime('2037-12-31')
            || strtotime(I('post.holdtime', '', false)) < strtotime('1960-1-1')) {
                $this -> myajaxReturn(null, '日期范围不太对！', 1);
            }
            $data['holdtime'] = I('post.holdtime', '', false);
            
            $data['team'] = I('post.team', '', false);
            $data['site'] = I('post.site', '', false);
            $data['university'] = I('post.university', '', false);
            $data['type'] = (intval(I('post.type')) >= 0 && intval(I('post.type')) <= 2) ? intval(I('post.type')) : 1;
            $data['medal'] = (intval(I('post.medal')) >= 0 && intval(I('post.medal')) <= 4) ? intval(I('post.medal')) : 3;
            if($data['type'] == 0 && $data['medal'] == 4) $data['medal'] = 3;
            $data['ranking'] = I('post.ranking', '', false) == '' ? null : I('post.ranking', '', false);
            $data['title'] = I('post.title', '', false) == '' ? null : I('post.title', '', false);
            
            $personDB = M('Person');
            $plist = explode('-', I('post.leader'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> myajaxReturn(null, 'Leader的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['leader'] = $c['uid'];
            
            $plist = explode('-', I('post.teamer1'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> myajaxReturn(null, 'Teamer1的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['teamer1'] = $c['uid'];
            
            $plist = explode('-', I('post.teamer2'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> myajaxReturn(null, 'Teamer2的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['teamer2'] = $c['uid'];
            
            $data['pic1'] = strcmp(substr(I('post.pic1_fn'), 0, 7), 'upload/') == 0 ? I('post.pic1_fn') : null;
            $data['pic2'] = strcmp(substr(I('post.pic2_fn'), 0, 7), 'upload/') == 0 ? I('post.pic2_fn') : null;
            
            if($data['pic1'] === null && $data['pic2'] !== null) {
                $data['pic1'] = $data['pic2'];
                $data['pic2'] = null;
            }
            
            $contestDB = D('Contest');
            if(!$contestDB -> create($data)) {  //自动验证失败
                $this -> myajaxReturn(null, $contestDB -> getError(), 1);
            }
            else {  //自动验证成功
                if(false === $contestDB -> where('cid='.$cid) -> limit(1) -> save($data)) {
                    $this -> myajaxReturn(null, '写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                }
                else {
                    $this -> myajaxReturn(null, '[成功]', 0);
                }
            }
        }
    }

    //参数管理================================================
    
    public function setting() {  //参数设置
        
        $this -> commonassign();
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> profile();
        else {
            $this -> display();
        }
    }

    public function ajax_flushconfig() {  //更新参数缓存
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $this -> init();
            $this -> myajaxReturn(null, null, 0);
        }
    }
    
    public function ajax_getsetting() {  //获取所有参数信息
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $settingDB = M('Setting');
            $data = $settingDB -> order('k ASC') -> select();
            if(!$data) {
                $this -> myajaxReturn(null, '获取参数错误，请重试。', 2);
            }
            else {
                $this -> myajaxReturn($data, '[成功]', 0);
            }
        }
    }
    
    public function ajax_savesetting() {  //保存一个参数
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $c['k'] = I('post.k', '', false);
            $settingDB = M('Setting');
            $data = $settingDB -> where($c) -> find();
            if(!$data) $this -> myajaxReturn(null, '参数键值错误！', 1);  //无效的K值或者查询错误
            if($data['type'] == 0) {  //bool型，非0-是，0-否
                if(intval(I('post.v')) == 0) $dat = '0';
                else $dat = '1';
            }
            else if($data['type'] == 1) {  //文本-转义
                $dat = I('post.v', '', 'htmlspecialchars');
                if(!$dat) $dat = null;
            }
            else {  //文本-不转义
                $dat = I('post.v', '', false);
                if(!$dat) $dat = null;
            }
            if(false === $settingDB -> where($c) -> setField('v', $dat)) {
                $this -> myajaxReturn(null, '保存参数错误，请重试！', 2);
            }
            else {
                $this -> setconfig($c['k'], $dat);
                $c['v'] = $dat;
                $this -> myajaxReturn($c, '保存参数 ['.$c['k'].'] 成功！参数缓存已更新。', 0);
            }
        }
    }
    
    //图片管理================================================
    
    public function image() {  //图片管理页面
        
        $this -> commonassign();
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> profile();
        else {
            $this -> display('');
        }
    }
    
    public function ajax_upload_image() {  //上传图片
        
        $this -> ajax_upload_contestpic();   
    }

    public function ajax_get_unlink_filename() {  //获取未引用的文件
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            //获取upload目录下所有文件
            $handler = opendir('upload');
            $files = array();  //upload下文件名xxx.jpg形式
            while (($filename = readdir($handler)) !== false) {  //务必使用!==，防止目录下出现类似文件名“0”等情况
                if ($filename != "." && $filename != ".." && $filename != "thumb") {
                    $files[iconv('GBK', 'UTF-8', strtolower($filename))] = iconv('GBK', 'UTF-8', $filename);  //filename-无用，null-有关联，需要中文编码转换
                }
            }
            closedir($handler);
            
            //查找contest中的关联图片
            $contestDB = M('Contest');
            $data = $contestDB -> field('pic1, pic2') -> select();
            foreach($data as $d) {
                if($d['pic1'] && array_key_exists(strtolower(substr($d['pic1'], 7)), $files)) $files[strtolower(substr($d['pic1'], 7))] = null;
                if($d['pic2'] && array_key_exists(strtolower(substr($d['pic2'], 7)), $files)) $files[strtolower(substr($d['pic2'], 7))] = null;
            }
            
            //查找用户头像关联图片
            $personDB = M('Person');
            $data = $personDB -> field('photo') -> select();
            foreach($data as $d) {
                if($d['photo'] && array_key_exists(strtolower(substr($d['photo'], 7)), $files)) $files[strtolower(substr($d['photo'], 7))] = null;
            }
            
            //查找OJ历史中是否有关联图片
            $ojhistoryDB = M('Ojhistory');
            $data = $ojhistoryDB -> field('photos') -> select();
            foreach ($data as $d) {
                $ps = explode(',', $d['photos']);
                foreach ($ps as $p) {
                    if(array_key_exists(strtolower(substr($p, 7)), $files)) $files[strtolower(substr($p, 7))] = null;
                }
            }
            
            //判断首页HTML中是否有图片关联
            $var = strtolower($this -> getconfig('home_mainarea'));
            foreach($files as $k => $v) {
                if($v !== null && strpos($var, $k) !== false) $files[$k] = null;
            }
            
            //判断“我们”模块中是否有图片关联
            $var1 = strtolower($this -> getconfig('we_icpc_introduce'));
            $var2 = strtolower($this -> getconfig('we_team_introduce'));
            foreach($files as $k => $v) {
                if($v !== null && (strpos($var1, $k) !== false || strpos($var2, $k) !== false)) $files[$k] = null;
            }
            
            //判断新闻中心中是否有图片关联
            $newsDB = M('News');
            $news = $newsDB -> field('content') -> select();
            foreach($files as $k => $v) {
                if($v === null) continue;
                foreach ($news as $n) {  //每条新闻遍历
                    if(stripos($n['content'], $k) !== false) {
                        $files[$k] = null;
                        break;
                    }
                }
            }
            
            //整理结果
            $ret = array();
            foreach ($files as $k => $v) {
                if($v !== null) $ret[] = $v;
            }
            
            $this -> myajaxReturn($ret, '[成功]', 0);
        }
    }
    
    public function ajax_del_photos() {  //删除多个图片文件
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $photos = I('post.photos', '', false);
            $safe = true;
            for($i = 0; $i < count($photos); $i++) {
                if(stripos($photos[$i], '/') !== false || stripos($photos[$i], "\\") !== false) {
                    $safe = false;
                    break;
                }
                else {
                    $photos[$i] = iconv('UTF-8', 'GBK', $photos[$i]);
                }
            }
            if(!$safe) $this -> myajaxReturn(null, '文件名有误，请重试。', 1);
            else {
                $succ = 0;
                $fail = 0;
                for($i = 0; $i < count($photos); $i++) {
                    if(file_exists('upload/'.$photos[$i])) {
                        if(file_exists('upload/thumb/'.$photos[$i]))
                            @unlink('upload/thumb/'.$photos[$i]);
                        if(unlink('upload/'.$photos[$i])) $succ++;
                        else $fail++;
                    }
                    else $fail++;
                }                
                $this -> myajaxReturn(null, '[提示]成功删除'.$succ.'文件，失败'.$fail.'个。', 0);
            }
        }
    }
    
    //OJ历史管理==============================================
    
    public function ojhistory() {  //OJ历史管理页面
        
        $this -> commonassign();
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> profile();
        else {
            $this -> display('ojhistory');
        }
    }
    
    public function ajax_get_oj_list() {  //获取OJ列表
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $ojhistoryDB = M('Ojhistory');
            $data = $ojhistoryDB -> field('vid, mainname') -> order('sortid DESC') -> select();
            for($i = 0; $i < count($data); $i++) {
                $data[$i]['mainname'] = htmlspecialchars($data[$i]['mainname']);
            }
            $this -> myajaxReturn($data, '[成功]', 0);
        }
    }
    
    public function ajax_get_oj_detail() {  //获取某版本OJ的详细信息
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $vid = intval(I('get.vid'));
            if($vid <= 0) $this -> myajaxReturn(null, '无效的VID，请检查。', 1);
            else {
                $ojhistoryDB = M('Ojhistory');
                $data = $ojhistoryDB -> where('vid='.$vid) -> find();
                $data['mainname'] = $data['mainname'];
                $data['devname'] = $data['devname'];
                $data['introduce'] = $data['introduce'];
                if($data['photos']) {
                    $tmparray = explode(',', $data['photos']);
                    $data['photos'] = $tmparray;
                    for($i = 0; $i < count($data['photos']); $i++) {
                        $data['photos'][$i] = substr($data['photos'][$i], 7);
                    }
                    $data['titles'] = explode(',', $data['titles']);
                    for($i = 0; $i < count($data['titles']); $i++) {
                        $data['titles'][$i] = base64_decode($data['titles'][$i]);
                    }
                    $data['descs'] = explode(',', $data['descs']);
                    for($i = 0; $i < count($data['descs']); $i++) {
                        $data['descs'][$i] = base64_decode($data['descs'][$i]);
                    }
                }
                
                if($data) $this -> myajaxReturn($data, '[成功]', 0);
                else $this -> myajaxReturn(null, '无效的VID，请检查。', 1);
            }
        }
    }
    
    public function ajax_add_oj() {  //添加一条OJ记录
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $ojhistoryDB = M('Ojhistory');
            $data['sortid'] = 10;
            $data['mainname'] = '新建版本（请在后台修改）';
            $k = $ojhistoryDB -> data($data) -> add();
            if($k) $this -> myajaxReturn($k, '[成功]', 0);
            else $this -> myajaxReturn(null, '创建新OJ历史记录错误，请重试。', 2);
        }
    }
    
    public function ajax_del_oj() {  //删除一条OJ记录
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $vid = intval(I('get.vid'));
            if($vid <= 0) $this -> myajaxReturn(null, '无效的VID，请检查。', 1);
            else {
                $ojhistoryDB = M('Ojhistory');
                if($ojhistoryDB -> where('vid='.$vid) -> delete()) {
                    $this -> myajaxReturn($vid, '[成功]', 0);
                }
                else $this -> myajaxReturn(null, '[失败]删除失败。', 2);
            }
        }
    }
    
    public function ajax_upload_ojpic() {  //上传图片
        
        $this -> ajax_upload_contestpic();
    }
    
    public function ajax_modify_oj() {  //保存一条OJ历史的修改
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $vid = intval(I('post.vid'));
            if($vid <= 0) $this -> myajaxReturn(null, '无效的VID参数。', 1);
            else {
                $data['sortid'] = intval(I('post.sortid'));
                $data['mainname'] = I('post.mainname', '', false);
                $data['devname'] = I('post.devname', '', false) == '' ? null : I('post.devname', '', false);
                $data['introduce'] = I('post.introduce', '', false) == '' ? null : I('post.introduce', '', false);
                $photos = I('post.photos', '', false);
                $titles = I('post.titles', '', false);
                $descs = I('post.descs', '', false);
                $data['photos'] = '';
                $data['titles'] = '';
                $data['descs'] = '';
                for($i = 0; $i < count($photos); $i++) {
                    if(!$photos[$i]) continue;
                    if($i != 0) {
                        $data['photos'] .= ',';
                        $data['titles'] .= ',';
                        $data['descs'] .= ',';
                    }
                    $data['photos'] .= ('upload/'.$photos[$i]);
                    if($titles[$i]) $data['titles'] .= base64_encode($titles[$i]);
                    if($descs[$i]) $data['descs'] .= base64_encode($descs[$i]);
                }
                if(!$data['photos']) { $data['photos'] = null; $data['titles'] = null; $data['descs'] = null; }
                if(!$data['titles']) $data['titles'] = null;
                if(!$data['descs']) $data['descs'] = null;
                
                $ojhistoryDB = D('Ojhistory');
                if(!$ojhistoryDB -> create($data)) {  //自动验证失败
                    $this -> myajaxReturn(null, $ojhistoryDB -> getError(), 1);
                }
                else {  //自动验证成功
                    if(false === $ojhistoryDB -> where('vid='.$vid) -> limit(1) -> save($data)) {
                        $this -> myajaxReturn(null, '写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                    }
                    else {
                        $this -> myajaxReturn(htmlspecialchars($data['mainname']), '[成功]', 0);
                    }
                }
            }
        }
       
    }
    
    public function ajax_get_img_list() {  //获取图片文件名列表

        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            //获取upload目录下所有文件
            $handler = opendir('upload');
            $files = array();  //upload下文件名xxx.jpg形式
            while (($filename = readdir($handler)) !== false) {  //务必使用!==，防止目录下出现类似文件名“0”等情况
                if ($filename != "." && $filename != "..") {
                    $files[] = iconv('GBK', 'UTF-8', $filename);  //filename-无用，null-有关联，需要中文编码转换
                }
            }
            closedir($handler);
            
            $this -> myajaxReturn($files, '[成功]', 0);
        }
    }
    
    //新闻管理================================================
    
    public function news() {  //新闻管理
        
        $this -> commonassign();
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> profile();
        else {
            $this -> display('news');
        }
    }
    
    public function ajax_load_news() {  //返回所有新闻列表
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $newsDB = D('News');
            $data = $newsDB -> relation(true) -> field('nid, title, author, createtime, category, permission, top') -> order('nid DESC') -> select();
            if($data === false) {
                $this -> myajaxReturn(null, '数据库错误。', 1);
            }
            else if($data === 0) {
                $this -> myajaxReturn(null, '没有队员信息。', 2);
            }
            else {
                for($i = 0; $i < count($data); $i++) {
                    $data[$i]['title'] = htmlspecialchars($data[$i]['title']);
                    $data[$i]['category'] = htmlspecialchars($data[$i]['category']);
                }
                $this -> myajaxReturn($data, '[成功]', 0);
            }
        }
    }
    
    public function ajax_get_news() {  //获取一条新闻详细信息
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $newsDB = D('News');
            $nid = intval(I('get.nid'));
            if($nid < 0) $this -> myajaxReturn(null, 'NID无效。', 1);
            $data = $newsDB -> relation(true) -> where('nid = '.$nid) -> find();
            if($data === false) $this -> myajaxReturn(null, '数据库错误。', 2);
            else if(!$data) $this -> myajaxReturn(null, 'NID无效。', 1);
            else {
                $this -> myajaxReturn($data, '[成功]', 0);
            }
        }
    }
    
    public function ajax_del_news() {  //删除新闻
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $list = I('get.nid');
            $nids = explode(',', $list);
            $success = 0;
            $fail = 0;
            foreach ($nids as $nid) {
                if(!$this -> del_one_news($nid)) $success ++;
                else $fail ++;
            }
            if($success == 0 && $fail == 0) $this -> myajaxReturn(null, '无效的参数。', 2);
            else if($fail != 0 && $success == 0) $this -> myajaxReturn(null, '无效的NID。', 1);
            else if($fail != 0 && $success != 0) $this -> myajaxReturn(null, '[提示]已成功删除'.$success.'条新闻，删除失败'.$fail.'条。', 0);
            else $this -> myajaxReturn(null, '已成功删除'.$success.'条新闻。', 0);
        }
    }
    
    private function del_one_news($nid) {  //删除一条新闻，ajax_del_news具体实现，返回：1-失败，0-成功
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            return 1;
        else {
            $nid = intval($nid);
            if($nid <= 0) return 1;
    
            $newsDB = M('News');
    
            $res = $newsDB -> where('nid='.$nid) -> limit(1) -> delete();
            if(false === $res) return 1;
            else if(0 === $res) return 1;
            else return 0;
        }
    }
    
    public function ajax_modify_news() {  //新闻管理-修改一条新闻
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $nid = intval(I('post.nownid'));
            if($nid == 9999 || $nid <= 0) $this -> myajaxReturn(null, '无效的NID。', 2);
    
            $data['title'] = I('post.title', '', false);
            $data['category'] = I('post.category', '', false);
            $data['content'] = I('post.content', '', false) == '' ? null : I('post.content', '', false);
            $data['author'] = intval(session('goldbirds_uid'));
            $data['top'] = (I('post.top', '', false) ? 1 : 0);
            $data['permission'] = (I('post.permission', '', false) ? 1 : 0);

            $newsDB = D('News');
            if(!$newsDB -> create($data)) {  //自动验证失败
                $this -> myajaxReturn(null, $newsDB -> getError(), 1);
            }
            else {  //自动验证成功
                if(false === $newsDB -> where('nid='.$nid) -> limit(1) -> save($data)) {
                    $this -> myajaxReturn(null, '写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                }
                else {
                    $this -> myajaxReturn(null, '[成功]', 0);
                }
            }
        }
    }
    
    public function ajax_add_news() {  //新闻管理-添加一条新闻
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $tmp = intval(I('post.nownid'));
            if($tmp != 9999) $this -> myajaxReturn(null, '无效的参数。', 2);
            
            $data['title'] = I('post.title', '', false);
            $data['category'] = I('post.category', '', false);
            $data['content'] = I('post.content', '', false) == '' ? null : I('post.content', '', false);
            $data['author'] = intval(session('goldbirds_uid'));
            $data['createtime'] = date("Y-m-d H:i:s");
            $data['top'] = (I('post.top', false) ? 1 : 0);
            $data['permission'] = (I('post.permission', '', false) ? 1 : 0);
    
            $newsDB = D('News');
            if(!$newsDB -> create($data)) {
                $this -> myajaxReturn(null, $newsDB -> getError(), 1);
            }
            else {
                if(false === ($tmp = $newsDB -> add()))
                    $this -> myajaxReturn(null, '写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                else
                  $this -> myajaxReturn($tmp, '添加新闻成功，NID:'.$tmp, 0);
            }
        }
    }
    
    public function ajax_get_category() {  //自动提示的类别
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $newsDB = M('News');
            $data = $newsDB -> distinct(true) -> field('category') -> select();
            if($data === false) {
                $this -> myajaxReturn(null, '数据库错误。', 1);
            }
            else if($data === null) {
                $this -> myajaxReturn('[]', '[提示]系统中没有分类。', 0);
            }
            else {
                $retstr = array();
                $i = 0;
                foreach($data as $d) {
                    $retstr[$i] = $d['category'];
                    $i++;
                }
                $this -> myajaxReturn($retstr, '[成功]', 0);
            }
        }
    }
    
    //其它ajax请求=============================================
    
    public function ajax_get_person_modal() {  //个人展示窗口数据
        
        $uid = intval(I('get.uid'));
        if($uid <= 0) $this -> myajaxReturn(null, 'UID参数不正确。', 1);
        else {
            $arrayStr = array('一个人的单程旅途，一个人的朝朝暮暮，一个人的韶华倾负。 [系统随机]',
            '妙笔难书一纸愁肠，苍白的誓言，终究抵不过岁月的遗忘。 [系统随机]',
            '看樱花满天，悲伤在流转，却掩不住斑驳的流年。 [系统随机]',
            '凡真心尝试助人者，没有不帮到自己的。 [系统随机]',
            '行动是成功的阶梯，行动越多，登得越高。 [系统随机]',
            '成功需要成本，时间也是一种成本，对时间的珍惜就是对成本的节约。 [系统随机]',
            '有事者，事竟成；破釜沉舟，百二秦关终归楚；苦心人，天不负；卧薪尝胆，三千越甲可吞吴。 [系统随机]',
            '燃尽的风华，为谁化作了彼岸花？ [系统随机]',
            '一切似乎没有改变，其实一切都已改变的生命罅隙。 [系统随机]',
            '那个寻找失落了的单车的少年，是否还有勇气回一回头？ [系统随机]'
            );
            $personDB = M('Person');
            $data = $personDB -> field('chsname, engname, email, phone, address, grade, introduce, detail, photo') -> where('uid = '.$uid.' AND `group` < 2') -> find();  //获取个人信息
            if(!$data) $this -> myajaxReturn(null, 'UID参数不正确。', 1);
            else {
                $data['chsname'] = htmlspecialchars($data['chsname']);
                $data['engname'] = htmlspecialchars($data['engname']);
                if(session('goldbirds_islogin')) $data['email'] = htmlspecialchars($data['email']);
                else $data['email'] = '[登录后可见]';
                $data['address'] = htmlspecialchars($data['address']);
                if(session('goldbirds_islogin')) $data['phone'] = htmlspecialchars($data['phone']);
                else $data['phone'] = '[登录后可见]';
                $data['introduce'] = htmlspecialchars($data['introduce']);
                if($data['detail']) $data['detail'] = htmlspecialchars($data['detail']);
                else $data['detail'] = $arrayStr[rand(0, count($arrayStr) - 1)];
                
                $contestDB = M('Contest');
                $contestdata = $contestDB -> field('YEAR(holdtime) AS y, MONTH(holdtime) AS m, site, type, medal, team') -> where('leader = '.$uid.' OR teamer1='.$uid.' OR teamer2='.$uid) -> order('type ASC, medal ASC, holdtime DESC') -> select();
                if(!$contestdata) $data['contest'] = null;
                else {
                    for($i = 0; $i < count($contestdata); $i++) {  //转义HTML字符
                        $contestdata[$i]['site'] = htmlspecialchars($contestdata[$i]['site']);
                        $contestdata[$i]['team'] = htmlspecialchars($contestdata[$i]['team']);
                    }
                    $data['contest'] = $contestdata;  //拼接比赛结果
                }
                $this -> myajaxReturn($data, '[成功]', 0);
            }
        }
    }

    //活动管理============================
    public function activity() {
    
        $this -> commonassign();
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> profile();
        else {
            $this -> display('activity');
        }
    }
    
    public function ajax_load_activity() {  //AJAX获取活动报名列表
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $activitylistDB = M('Activitylist');
            $data = $activitylistDB -> field('aid, title, deadline, isinner, ispublic, isneedreview') -> order('aid DESC') -> select();
            if($data === false) {
                $this -> myajaxReturn(null, '数据库错误。', 1);
            }
            else if($data === null) {
                $this -> myajaxReturn(null, '没有活动报名信息。', 2);
            }
            else {
                $activitydataDB = M('Activitydata');
                $sum = $activitydataDB -> field('aid, count(*) AS sum') -> group('aid') -> order('aid DESC') -> select();
                $accept = $activitydataDB -> field('aid, count(*) AS accept') -> group('aid') -> where('state = 2') -> order('aid DESC') -> select();
                $j = 0;
                $k = 0;
                for($i = 0; $i < count($data); $i++) {
                    $data[$i]['title'] = htmlspecialchars($data[$i]['title']);
                    $data[$i]['sum'] = 0;
                    $data[$i]['accept'] = 0;
                    while($j < count($sum) && $sum[$j]['aid'] >= $data[$i]['aid']) {
                        if($sum[$j]['aid'] == $data[$i]['aid']) { $data[$i]['sum'] = $sum[$j]['sum']; $j++; break; }
                        $j++;
                    }
                    while($k < count($accept) && $accept[$k]['aid'] >= $data[$i]['aid']) {
                        if($accept[$k]['aid'] == $data[$i]['aid']) { $data[$i]['accept'] = $accept[$k]['accept']; $k++; break; }
                        $k++;
                    }
                }
                $this -> myajaxReturn($data, '[成功]', 0);
            }
        }
    }
    
    public function ajax_get_activity() {  //获取一条活动信息
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $activitylistDB = D('Activitylist');
            $aid = intval(I('get.aid'));
            if($aid <= 0) $this -> myajaxReturn(null, 'AID无效。', 1);
            $data = $activitylistDB -> relation(true) -> where('aid = '.$aid) -> find();
            if($data === false) $this -> myajaxReturn(null, '数据库错误。', 2);
            else if(!$data) $this -> myajaxReturn(null, 'AID无效。', 1);
            else {
                if($data['adminuid'] == 0) $data['admin_detail'] = null;
                $this -> myajaxReturn($data, '[成功]', 0);
            }
        }
    }
    
    public function ajax_add_activity() {  //添加一条活动记录
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $tmp = intval(I('post.nowaid'));
            if($tmp != 9999) $this -> myajaxReturn(null, '无效的参数。', 2);
    
            if(false === strtotime(I('post.deadline', '', false)))
                $this -> myajaxReturn(null, '日期格式不对！', 1);
            if(strtotime(I('post.deadline', '', false)) >= strtotime('2037-12-31')
            || strtotime(I('post.deadline', '', false)) < strtotime('1960-1-1')) {
                $this -> myajaxReturn(null, '日期范围不太对！', 1);
            }
            $data['deadline'] = I('post.deadline', '', false);
    
            $data['title'] = I('post.title', '', false);
            $data['desc'] = I('post.content', '', false) == '' ? null : I('post.content', '', false);
            $data['addtime'] = date("Y-m-d H:i:s");
            
            if(null === $this -> explain_reg_rule(I('post.form', '', false))) {
                $this -> myajaxReturn(null, '注册信息格式不对:(', 1);
            }
            else {
                $data['form'] = I('post.form', '', false);
            }
            
            $data['isinner'] = intval(I('post.isinner')) == 0 ? 0 : 1;
            $data['ispublic'] = intval(I('post.ispublic')) == 0 ? 0 : 1;
            $data['isneedreview'] = intval(I('post.isneedreview')) == 0 ? 0 : 1;
            
            $personDB = M('Person');
            $plist = explode('-', I('post.admin', '', false));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $data['adminuid'] = 0;
            else $data['adminuid'] = $c['uid'];
    
            $activitylistDB = D('Activitylist');
            if(!$activitylistDB -> create($data)) {
                $this -> myajaxReturn(null, $activitylistDB -> getError(), 1);
            }
            else {
                if(false === ($tmp = $activitylistDB -> add()))
                    $this -> myajaxReturn(null, '写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                else
                  $this -> myajaxReturn(null, '新增活动，AID:'.$tmp, 0);
            }
        }
    }
    
    public function ajax_del_activity() {  //删除活动记录
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $list = I('get.aid', '', false);
            $aids = explode(',', $list);
            $success = 0;
            $fail = 0;
            foreach ($aids as $aid) {
                if(!$this -> del_one_activity($aid)) $success ++;
                else $fail ++;
            }
            if($success == 0 && $fail == 0) $this -> myajaxReturn(null, '无效的参数。', 2);
            else if($fail != 0 && $success == 0) $this -> myajaxReturn(null, '无效的AID。', 1);
            else if($fail != 0 && $success != 0) $this -> myajaxReturn(null, '[提示]已成功删除'.$success.'条活动记录，删除失败'.$fail.'条。', 0);
            else $this -> myajaxReturn(null, '已成功删除'.$success.'条活动记录。', 0);
        }
    }
    
    private function del_one_activity($aid) {  //删除一条活动，ajax_del_activity具体实现，返回：1-失败，0-成功
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            return 1;
        else {
            $aid = intval($aid);
            if($aid <= 0) return 1;
    
            $activitylistDB = M('Activitylist');
            $res = $activitylistDB -> where('aid = '.$aid) -> delete();
            if(false === $res) return 1;
            else if(0 === $res) return 1;
            else {
                $activitydataDB = M('Activitydata');
                $activitydataDB -> where('aid = '.$aid) -> delete();
                return 0;
            }
        }
    }
    
    public function ajax_modify_activity() {  //修改获奖记录
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> myajaxReturn(null, '无权限。', 3);
        else {
            $aid = intval(I('post.nowaid'));
            if($aid == 9999 || $aid <= 0) $this -> myajaxReturn(null, '无效的参数AID。', 2);
    
            if(false === strtotime(I('post.deadline', '', false)))
                $this -> myajaxReturn(null, '日期格式不对！', 1);
            if(strtotime(I('post.deadline', '', false)) >= strtotime('2037-12-31')
            || strtotime(I('post.deadline', '', false)) < strtotime('1960-1-1')) {
                $this -> myajaxReturn(null, '日期范围不太对！', 1);
            }
            $data['deadline'] = I('post.deadline', '', false);
    
            $data['title'] = I('post.title', '', false);
            $data['desc'] = I('post.content', '', false) == '' ? null : I('post.content', '', false);
            $data['addtime'] = date("Y-m-d H:i:s");
            
            if(null === $this -> explain_reg_rule(I('post.form', '', false))) {
                $this -> myajaxReturn(null, '注册信息格式不对:(', 1);
            }
            else {
                $data['form'] = I('post.form', '', false);
            }
            
            $data['isinner'] = intval(I('post.isinner')) == 0 ? 0 : 1;
            $data['ispublic'] = intval(I('post.ispublic')) == 0 ? 0 : 1;
            $data['isneedreview'] = intval(I('post.isneedreview')) == 0 ? 0 : 1;
            
            $personDB = M('Person');
            $plist = explode('-', I('post.admin', '', false));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $data['adminuid'] = 0;
            else $data['adminuid'] = $c['uid'];
    
            $activitylistDB = D('Activitylist');
            if(!$activitylistDB -> create($data)) {  //自动验证失败
                $this -> myajaxReturn(null, $activitylistDB -> getError(), 1);
            }
            else {  //自动验证成功
                if(false === $activitylistDB -> where('aid='.$aid) -> limit(1) -> save($data)) {
                    $this -> myajaxReturn(null, '写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                }
                else {
                    $this -> myajaxReturn(null, '[成功]', 0);
                }
            }
        }
    }

    //系统信息============================
    public function sysinfo() {  //系统信息
        
        if(!session('goldbirds_islogin')) {  //未登录
            $this -> profile();
        }
        else {
            $info_sys = '<p>系统时间: '.date("Y-m-d H:i:s").'</p>
                        <p>主机域名: '.$_SERVER['SERVER_NAME'].'</p>
                        <p>主机端口: '.$_SERVER["SERVER_PORT"].'</p>
                        <p>访问协议: '.$_SERVER["SERVER_PROTOCOL"].'</p>';
            $isadmin = 3;
            
            if(intval(session('goldbirds_group')) >= 1) {  //带管理权限
            
                $isadmin = 10;
                $info_sys .= '<p>PHP版本: '.phpversion().'</p>
                    <p>时区: '.ini_get('date.timezone').'</p>
                    <p>操作系统: '.php_uname().'</p>
                    <p>PHP运行方式: '.php_sapi_name().'</p>
                    <p>当前进程用户名: '.get_current_user().'</p>
                    <p>操作系统目录: '.$_SERVER['SystemRoot'].'</p>
                    <p>POST限制: '.ini_get('post_max_size').'</p>
                    <p>运行时间限制: '.ini_get('max_execution_time').'</p>
                    <p>运行内存限制: '.ini_get('memory_limit').'</p>';
            
                $info_mysql = '<p>MYSQL主机信息: '.mysql_get_host_info().'</p>
                    <p>MYSQL服务器版本: '.mysql_get_server_info().'</p>
                    <p>MYSQL协议版本: '.mysql_get_proto_info().'</p>
                    <p>MYSQL客户端版本: '.mysql_get_client_info().'</p>';
                $this -> assign('info_mysql', $info_mysql);
            }
            
            $this -> assign('nid', $isadmin);
            $info_sys .= '<hr /><p>客户端IP: '.get_client_ip().'</p>';
            $this -> assign('info_sys', $info_sys);
            $this -> commonassign();
            $this -> display('info');
        }
    }

}
