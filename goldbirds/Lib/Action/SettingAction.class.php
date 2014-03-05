<?php 
class SettingAction extends BaseAction {
    
    private function logincheck() {  //检测是否本系统已登录，并进行相应处理
        
        if(OJLoginInterface::isLogin()) {  //OJ已登录
            if(!(session('goldbirds_islogin') && session('goldbirds_oj') == OJLoginInterface::getLoginUser())) {  //OJ登录后首次访问本系统，加载登录信息到session
                $personDB = M('Person');
                $condition['ojaccount'] = OJLoginInterface::getLoginUser();
                $user = $personDB -> where($condition) -> find();  //查询关联该OJ的用户信息
                if($user) {
                    session('goldbirds_islogin', 1);
                    session('goldbirds_uid', $user['uid']);
                    session('goldbirds_group', $user['group']);
                    session('goldbirds_oj', OJLoginInterface::getLoginUser());
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
    
    public function index() {  //默认处理方法，未登录显示未登录页面，已登录显示个人信息profile页面
        
        $this -> profile();
    }
    

    //个人信息页面========================================
    
    public function profile() {  //个人信息页面
        
        $this -> commonassign();
        if($this -> logincheck() == 0) {  //未登录处理
            $this -> assign('url', OJLoginInterface::getLoginURL());
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
        
        if(!session('goldbirds_islogin')) $this -> ajaxReturn(null, '[错误]还未登录，无权限。', 2);  //无权限处理
        
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
            $this -> ajaxReturn(null, $personDB -> getError(), 1);
        }
        else {  //自动验证成功
            if(false === $personDB -> where('uid = '.session('goldbirds_uid')) -> save($data)) {
                $this -> ajaxReturn(null, '[数据库错误]请重试...', 3);
            }
            else {
                $this -> ajaxReturn(null, '[成功]', 0);
            }
        }
    }
    
    public function ajax_upload_face() {  //上传头像
        
        //使用iframe模拟AJAX上传图片，导致该函数无法使用ajaxReturn，请注意。
        if(!session('goldbirds_islogin')) $this -> ajaxReturn(null, '[错误]还未登录，无权限。', 2);  //无权限处理
        
        import('ORG.Net.UploadFile');
        $upload = new UploadFile();  //实例化上传类
        $upload -> maxSize = 10485760 ;  //设置附件上传大小
        $upload -> allowExts = array('jpg', 'gif', 'png', 'jpeg');  // 设置附件上传类型
        $upload -> savePath = './upload/';  //设置附件上传目录
        if(!$upload -> upload()) {  //上传错误提示错误信息
            echo json_encode(array('info' => ('[错误]'.$upload -> getErrorMsg()), 'status' => 1));
        } 
        else {  //上传成功
            $fileinfo = $upload -> getUploadFileInfo();
            $personDB = D('Person');
            $oldphoto = $personDB -> where('uid = '.session('goldbirds_uid')) -> find();
            $oldphoto = $oldphoto['photo'];
            $newphoto = 'upload/'.$fileinfo[0]['savename'];
            
            if(false === $personDB -> where('uid = '.session('goldbirds_uid')) -> setField('photo', $newphoto)) {
                unlink($newphoto);
                echo json_encode(array('info' => '[错误]写入数据库出错！', 'status' => 2));
            }
            else {
                unlink($oldphoto);
                echo json_encode(array('data' => 'upload/'.$fileinfo[0]['savename'], 'info' => '[成功]上传头像成功，文件大小'.sprintf("%.2lf", intval($fileinfo[0]['size'])/1024).'KB.', 'status' => 0));
            }
        }
    }
    
    public function ajax_verify_luckycode() {  //验证邀请码
        
        if($this -> logincheck() == 0) $this -> ajaxReturn(null, '[错误]还未登录，无权限。', 2);  //无权限处理
        
        $code = I('post.code');
        sleep(1);
        if(strlen($code) != 16)
            $this -> ajaxReturn(null, '[错误]无效的邀请码，请重试！', 1);
        
        $personDB = M('Person');
        $c['luckycode'] = $code;
        $data = $personDB -> field('uid, chsname, engname, ojaccount, email, phone') -> where($c) -> find();
        if($data) {
            if($data['ojaccount'] == null) {
                $r['code'] = 'UID:'.$data['uid'].'-'.$data['chsname'].'-'.$data['engname'];
                $r['oj'] = OJLoginInterface::getLoginUser();
                $r['email'] = $data['email'];
                $r['phone'] = $data['phone'];
                $this -> ajaxReturn($r, '[成功]', 0);
            }
            else $this -> ajaxReturn(null, '[错误]无效的邀请码，请重试！', 1);
        }
        else {
            $this -> ajaxReturn(null, '[错误]无效的邀请码，请重试！', 1);
        }
    }
    
    public function ajax_bind_luckycode() {  //验证邀请码
    
        if($this -> logincheck() == 0) $this -> ajaxReturn(null, '[错误]还未登录，无权限。', 2);  //无权限处理
        
        $code = I('post.code');
        $email = I('post.email', '', false);
        $phone = I('post.phone', '', false);
        $oj = OJLoginInterface::getLoginUser();
        sleep(1);
        if(strlen($code) != 16)
            $this -> ajaxReturn(null, '[错误]无效的邀请码，请重试！', 1);
        else if(!preg_match('/^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,5}$/', $email))
            $this -> ajaxReturn(null, '[错误]E-mail格式不正确！请重试。', 1);
        else if(strlen($phone) < 8 || strlen($phone) > 11)
            $this -> ajaxReturn(null, '[错误]联系电话格式不正确！请重试。', 1);
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
                        $this -> ajaxReturn(null, '[成功]', 0);
                    else {
                        $this -> ajaxReturn(null, '[错误]绑定失败，请刷新后重试。', 0);
                    }
                }
                else $this -> ajaxReturn(null, '[错误]无效的邀请码，请重试！', 1);
            }
            else {
                $this -> ajaxReturn(null, '[错误]无效的邀请码，请重试！', 1);
            }
        }
    }
    
    
    //通讯录页面===================================
    
    public function contacts() {
        
        $this -> commonassign();
        if($this -> logincheck() == 0) {  //未登录处理
            $this -> assign('url', OJLoginInterface::getLoginURL());
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
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $personDB = M('Person');
            $data = $personDB -> field('uid, chsname, sex, email, phone, grade, ojaccount, group, luckycode') -> where('uid > 0') -> order('uid ASC') -> select();
            if($data === false) {
                $this -> ajaxReturn(null, '[错误]数据库错误。', 1);
            }
            else if($data === 0) {
                $this -> ajaxReturn(null, '[错误]没有队员信息。', 2);
            }
            else {
                for($i = 0; $i < count($data); $i++) {
                    $data[$i]['chsname'] = htmlspecialchars($data[$i]['chsname']);
                    if($data[$i]['email']) $data[$i]['email'] = htmlspecialchars($data[$i]['email']);
                    if($data[$i]['phone']) $data[$i]['phone'] = htmlspecialchars($data[$i]['phone']);
                    if($data[$i]['ojaccount']) $data[$i]['ojaccount'] = htmlspecialchars($data[$i]['ojaccount']);
                }
                $this -> ajaxReturn($data, '[成功]', 0);
            }
        }
    }
    
    public function ajax_get_person() {  //获取一名队员详细信息
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $personDB = M('Person');
            $uid = intval(I('get.uid'));
            if($uid < 0) $this -> ajaxReturn(null, '[错误]UID无效。', 1);
            $data = $personDB -> where('uid = '.$uid) -> find();
            if($data === false) $this -> ajaxReturn(null, '[错误]数据库错误。', 2);
            else if(!$data) $this -> ajaxReturn(null, '[错误]UID无效。', 1);
            else {
                $this -> ajaxReturn($data, '[成功]', 0);
            }
        }
    }
    
    public function ajax_del_person() {  //删除用户
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $list = I('get.uid');
            $uids = explode(',', $list);
            $success = 0;
            $fail = 0;
            foreach ($uids as $uid) {
                if(!$this -> del_one_person($uid)) $success ++;
                else $fail ++;
            }
            if($success == 0 && $fail == 0) $this -> ajaxReturn(null, '[错误]无效的参数。', 2);
            else if($fail != 0 && $success == 0) $this -> ajaxReturn(null, '[错误]删除失败。', 1);
            else if($fail != 0 && $success != 0) $this -> ajaxReturn(null, '[提示]已成功删除'.$success.'名队员，删除失败'.$fail.'名。', 0);
            else $this -> ajaxReturn(null, '[成功]已成功删除'.$success.'名队员。', 0);
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
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
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
            if($success == 0 && $fail == 0) $this -> ajaxReturn(null, '[错误]无效的参数。', 2);
            else if($fail != 0 && $success == 0) $this -> ajaxReturn(null, '[错误]发送邮件失败。', 1);
            else if($fail != 0 && $success != 0) $this -> ajaxReturn(null, '[提示]已成功发送'.$success.'封邀请邮件，失败'.$fail.'封。', 0);
            else $this -> ajaxReturn(null, '[成功]已成功发送'.$success.'封邀请邮件。', 0);
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
            Vendor('phpMailer.phpmailer');
            $mail = new PHPMailer();
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
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $tmp = intval(I('post.nowuid'));
            if($tmp != 9999) $this -> ajaxReturn(null, '[错误]无效的参数。', 2);
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
            if($tmp == 0 || $tmp == 1 || $tmp == 9) $data['group'] = $tmp;
            else $tmp = 0;
            srand((double)microtime()*1000000);
            $data['luckycode'] = substr(md5('goldbirds'.'_xzz'.$data['chsname'].rand()), 10, 16);
            
            $data['photo'] = strcmp(substr(I('post.face_fn'), 0, 7), 'upload/') == 0 ? I('post.face_fn') : null;
            
            $personDB = D('Person');
            if(!$personDB -> create($data)) {
                $this -> ajaxReturn(null, $personDB -> getError(), 1);
            }
            else {
                if(false === ($tmp = $personDB -> add()))
                    $this -> ajaxReturn(null, '[错误]写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                else 
                  $this -> ajaxReturn($tmp.'-'.$data['chsname'].'-'.$data['engname'], '[成功]新增用户“'.$data['chsname'].'”，UID:'.$tmp, 0);
            }
        }
    }
    
    public function ajax_modify_person() {  //队员管理-修改一名队员
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $uid = intval(I('post.nowuid'));
            if($uid == 9999 || $uid <= 0) $this -> ajaxReturn(null, '[错误]无效的UID。', 2);
            
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
            if($tmp == 0 || $tmp == 1 || $tmp == 9) $data['group'] = $tmp;
            else $tmp = 0;
            
            $data['photo'] = strcmp(substr(I('post.face_fn'), 0, 7), 'upload/') == 0 ? I('post.face_fn') : null;
            
            
            $personDB = D('Person');
            if(!$personDB -> create($data)) {  //自动验证失败
                $this -> ajaxReturn(null, $personDB -> getError(), 1);
            }
            else {  //自动验证成功
                if(false === $personDB -> where('uid='.$uid) -> limit(1) -> save($data)) {
                    $this -> ajaxReturn(null, '[错误]写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                }
                else {
                    $this -> ajaxReturn(null, '[成功]', 0);
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
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $contestDB = D('Contest');
            $data = $contestDB -> field('cid, holdtime, site, university, type, medal, team, pic1, pic2') -> where('cid > 0') -> order('holdtime DESC, cid DESC') -> select();
            if($data === false) {
                $this -> ajaxReturn(null, '[错误]数据库错误。', 1);
            }
            else if($data === null) {
                $this -> ajaxReturn(null, '[错误]没有比赛信息。', 2);
            }
            else {
                for($i = 0; $i < count($data); $i++) {
                    $data[$i]['site'] = htmlspecialchars($data[$i]['site']);
                    $data[$i]['university'] = htmlspecialchars($data[$i]['university']);
                    $data[$i]['team'] = htmlspecialchars($data[$i]['team']);
                }
                $this -> ajaxReturn($data, '[成功]', 0);
            }
        }
    }

    public function ajax_get_contest() {  //获取一条比赛获奖记录信息
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $contestDB = D('Contest');
            $cid = intval(I('get.cid'));
            if($cid <= 0) $this -> ajaxReturn(null, '[错误]CID无效。', 1);
            $data = $contestDB -> relation(true) -> where('cid = '.$cid) -> find();
            if($data === false) $this -> ajaxReturn(null, '[错误]数据库错误。', 2);
            else if(!$data) $this -> ajaxReturn(null, '[错误]CID无效。', 1);
            else $this -> ajaxReturn($data, '[成功]', 0);
        }
    }
    
    public function ajax_get_typeaheaddata() {  //自动完成数据
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $personDB = M('Person');
            $data = $personDB -> field('uid, chsname, engname') -> where('`group` <> 9') -> order('uid ASC') -> select();
            if($data === false) {
                $this -> ajaxReturn(null, '[错误]数据库错误。', 1);
            }
            else if($data === null) {
                $this -> ajaxReturn('[]', '[提示]系统中没有用户。', 0);
            }
            else {
                $retstr = array();
                $i = 0;
                foreach($data as $d) {
                    $retstr[$i] = $d['uid'].'-'.$d['chsname'].'-'.$d['engname'];
                    $i++;
                }
                $this -> ajaxReturn($retstr, '[成功]', 0);
            }
        }
    }
    
    public function ajax_upload_contestpic() {  //上传照片
    
        //使用iframe模拟AJAX上传图片，导致该函数无法使用ajaxReturn，请注意。
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();  //实例化上传类
            $upload -> maxSize = 2097152;  //设置附件上传大小
            $upload -> allowExts = array('jpg', 'gif', 'png', 'jpeg');  // 设置附件上传类型
            $upload -> savePath = './upload/';  //设置附件上传目录
            if(!$upload -> upload()) {  //上传错误提示错误信息
                echo json_encode(array('info' => ('[错误]'.$upload -> getErrorMsg()), 'status' => 1));
            }
            else {  //上传成功
                $fileinfo = $upload -> getUploadFileInfo();
                $newphoto = 'upload/'.$fileinfo[0]['savename'];
                $data['filename'] = $newphoto;
                $data['id'] = intval(I('post.id'));

                echo json_encode(array('data' => $data, 'info' => '[成功]上传成功，文件大小'.sprintf("%.2lf", intval($fileinfo[0]['size'])/1024).'KB。', 'status' => 0));
            }
        }
    }
    
    public function ajax_add_contest() {  //添加获奖记录
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $tmp = intval(I('post.nowcid'));
            if($tmp != 9999) $this -> ajaxReturn(null, '[错误]无效的参数。', 2);
            
            if(false === strtotime(I('post.holdtime', '', false)))
                $this -> ajaxReturn(null, '[错误]日期格式不对！', 1);
            if(strtotime(I('post.holdtime', '', false)) >= strtotime('2037-12-31') 
                || strtotime(I('post.holdtime', '', false)) < strtotime('1960-1-1')) {
                $this -> ajaxReturn(null, '[错误]日期范围不太对！', 1);
            }
            $data['holdtime'] = I('post.holdtime', '', false);
            
            $data['team'] = I('post.team', '', false);
            $data['site'] = I('post.site', '', false);
            $data['university'] = I('post.university', '', false);
            $data['type'] = (intval(I('post.type')) >= 0 && intval(I('post.type')) <= 2) ? intval(I('post.type')) : 1;
            $data['medal'] = (intval(I('post.medal')) >= 0 && intval(I('post.medal')) <= 3) ? intval(I('post.medal')) : 3;
            $data['ranking'] = I('post.ranking', '', false) == '' ? null : I('post.ranking', '', false);
            $data['title'] = I('post.title', '', false) == '' ? null : I('post.title', '', false);
            
            $personDB = M('Person');
            $plist = explode('-', I('post.leader'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> ajaxReturn(null, '[错误]Leader的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['leader'] = $c['uid'];
            
            $plist = explode('-', I('post.teamer1'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> ajaxReturn(null, '[错误]Teamer1的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['teamer1'] = $c['uid'];
            
            $plist = explode('-', I('post.teamer2'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> ajaxReturn(null, '[错误]Teamer2的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['teamer2'] = $c['uid'];
            
            $data['pic1'] = strcmp(substr(I('post.pic1_fn'), 0, 7), 'upload/') == 0 ? I('post.pic1_fn') : null;
            $data['pic2'] = strcmp(substr(I('post.pic2_fn'), 0, 7), 'upload/') == 0 ? I('post.pic2_fn') : null;
            
            if($data['pic1'] === null && $data['pic2'] !== null) {
                $data['pic1'] = $data['pic2'];
                $data['pic2'] = null;
            }
            
            $contestDB = D('Contest');
            if(!$contestDB -> create($data)) {
                $this -> ajaxReturn(null, $contestDB -> getError(), 1);
            }
            else {
                if(false === ($tmp = $contestDB -> add()))
                    $this -> ajaxReturn(null, '[错误]写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                else
                  $this -> ajaxReturn(null, '[成功]新增获奖记录“'.$data['team'].'”，CID:'.$tmp, 0);
            }
        }
    }

    public function ajax_del_contest() {  //删除获奖记录
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
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
            if($success == 0 && $fail == 0) $this -> ajaxReturn(null, '[错误]无效的参数。', 2);
            else if($fail != 0 && $success == 0) $this -> ajaxReturn(null, '[错误]无效的CID。', 1);
            else if($fail != 0 && $success != 0) $this -> ajaxReturn(null, '[提示]已成功删除'.$success.'条获奖记录，删除失败'.$fail.'条。', 0);
            else $this -> ajaxReturn(null, '[成功]已成功删除'.$success.'条获奖记录。', 0);
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
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $cid = intval(I('post.nowcid'));
            if($cid == 9999 || $cid <= 0) $this -> ajaxReturn(null, '[错误]无效的CID。', 2);
            
            if(false === strtotime(I('post.holdtime', '', false)))
                $this -> ajaxReturn(null, '[错误]日期格式不对！', 1);
            if(strtotime(I('post.holdtime', '', false)) >= strtotime('2037-12-31')
            || strtotime(I('post.holdtime', '', false)) < strtotime('1960-1-1')) {
                $this -> ajaxReturn(null, '[错误]日期范围不太对！', 1);
            }
            $data['holdtime'] = I('post.holdtime', '', false);
            
            $data['team'] = I('post.team', '', false);
            $data['site'] = I('post.site', '', false);
            $data['university'] = I('post.university', '', false);
            $data['type'] = (intval(I('post.type')) >= 0 && intval(I('post.type')) <= 2) ? intval(I('post.type')) : 1;
            $data['medal'] = (intval(I('post.medal')) >= 0 && intval(I('post.medal')) <= 3) ? intval(I('post.medal')) : 3;
            $data['ranking'] = I('post.ranking', '', false) == '' ? null : I('post.ranking', '', false);
            $data['title'] = I('post.title', '', false) == '' ? null : I('post.title', '', false);
            
            $personDB = M('Person');
            $plist = explode('-', I('post.leader'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> ajaxReturn(null, '[错误]Leader的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['leader'] = $c['uid'];
            
            $plist = explode('-', I('post.teamer1'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> ajaxReturn(null, '[错误]Teamer1的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['teamer1'] = $c['uid'];
            
            $plist = explode('-', I('post.teamer2'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> ajaxReturn(null, '[错误]Teamer2的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['teamer2'] = $c['uid'];
            
            $data['pic1'] = strcmp(substr(I('post.pic1_fn'), 0, 7), 'upload/') == 0 ? I('post.pic1_fn') : null;
            $data['pic2'] = strcmp(substr(I('post.pic2_fn'), 0, 7), 'upload/') == 0 ? I('post.pic2_fn') : null;
            
            if($data['pic1'] === null && $data['pic2'] !== null) {
                $data['pic1'] = $data['pic2'];
                $data['pic2'] = null;
            }
            
            $contestDB = D('Contest');
            if(!$contestDB -> create($data)) {  //自动验证失败
                $this -> ajaxReturn(null, $contestDB -> getError(), 1);
            }
            else {  //自动验证成功
                if(false === $contestDB -> where('cid='.$cid) -> limit(1) -> save($data)) {
                    $this -> ajaxReturn(null, '[错误]写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                }
                else {
                    $this -> ajaxReturn(null, '[成功]', 0);
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
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $this -> init();
            $this -> ajaxReturn(null, null, 0);
        }
    }
    
    public function ajax_getsetting() {  //获取所有参数信息
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $settingDB = M('Setting');
            $data = $settingDB -> order('k ASC') -> select();
            if(!$data) {
                $this -> ajaxReturn(null, '[错误]获取参数错误，请重试。', 2);
            }
            else {
                $this -> ajaxReturn($data, '[成功]', 0);
            }
        }
    }
    
    public function ajax_savesetting() {  //保存一个参数
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $c['k'] = I('post.k', '', false);
            $settingDB = M('Setting');
            $data = $settingDB -> where($c) -> find();
            if(!$data) $this -> ajaxReturn(null, '[错误]参数键值错误！', 1);  //无效的K值或者查询错误
            if($data['type'] == 0) {  //bool型，非0-是，0-否
                if(intval(I('post.v')) == 0) $dat = '0';
                else $dat = '1';
            }
            else if($data['type'] == 1) {  //文本-转义
                if(get_magic_quotes_gpc()) {  //如果get_magic_quotes_gpc()是打开的
                    $_POST['v'] = stripslashes($_POST['v']);
                }
                $dat = I('post.v', '', 'htmlspecialchars');
            }
            else {  //文本-不转义
                if(get_magic_quotes_gpc()) {  //如果get_magic_quotes_gpc()是打开的
                    $_POST['v'] = stripslashes($_POST['v']);
                }
                $dat = I('post.v', '', false);
            }
            if(false === $settingDB -> where($c) -> setField('v', $dat)) {
                $this -> ajaxReturn(null, '[错误]保存参数错误，请重试！', 2);
            }
            else {
                $this -> setconfig($c['k'], $dat);
                $c['v'] = $dat;
                $this -> ajaxReturn($c, '[成功]保存参数 ['.$c['k'].'] 成功！参数缓存已更新。', 0);
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
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
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
            
            //整理结果
            $ret = array();
            foreach ($files as $k => $v) {
                if($v !== null) $ret[] = $v;
            }
            
            $this -> ajaxReturn($ret, '[成功]', 0);
        }
    }
    
    public function ajax_del_photos() {  //删除多个图片文件
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
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
            if(!$safe) $this -> ajaxReturn(null, '[错误]文件名有误，请重试。', 1);
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
                $this -> ajaxReturn(null, '[提示]成功删除'.$succ.'文件，失败'.$fail.'个。', 0);
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
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $ojhistoryDB = M('Ojhistory');
            $data = $ojhistoryDB -> field('vid, mainname') -> order('sortid DESC') -> select();
            for($i = 0; $i < count($data); $i++) {
                $data[$i]['mainname'] = htmlspecialchars($data[$i]['mainname']);
            }
            $this -> ajaxReturn($data, '[成功]', 0);
        }
    }
    
    public function ajax_get_oj_detail() {  //获取某版本OJ的详细信息
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $vid = intval(I('get.vid'));
            if($vid <= 0) $this -> ajaxReturn(null, '[错误]无效的VID，请检查。', 1);
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
                
                if($data) $this -> ajaxReturn($data, '[成功]', 0);
                else $this -> ajaxReturn(null, '[错误]无效的VID，请检查。', 1);
            }
        }
    }
    
    public function ajax_add_oj() {  //添加一条OJ记录
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $ojhistoryDB = M('Ojhistory');
            $data['sortid'] = 10;
            $data['mainname'] = '新建版本（请在后台修改）';
            $k = $ojhistoryDB -> data($data) -> add();
            if($k) $this -> ajaxReturn($k, '[成功]', 0);
            else $this -> ajaxReturn(null, '[错误]创建新OJ历史记录错误，请重试。', 2);
        }
    }
    
    public function ajax_del_oj() {  //删除一条OJ记录
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $vid = intval(I('get.vid'));
            if($vid <= 0) $this -> ajaxReturn(null, '[错误]无效的VID，请检查。', 1);
            else {
                $ojhistoryDB = M('Ojhistory');
                if($ojhistoryDB -> where('vid='.$vid) -> delete()) {
                    $this -> ajaxReturn($vid, '[成功]', 0);
                }
                else $this -> ajaxReturn(null, '[失败]删除失败。', 2);
            }
        }
    }
    
    public function ajax_upload_ojpic() {  //上传图片
        
        $this -> ajax_upload_contestpic();
    }
    
    public function ajax_modify_oj() {  //保存一条OJ历史的修改
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $vid = intval(I('post.vid'));
            if($vid <= 0) $this -> ajaxReturn(null, '[错误]无效的VID参数。', 1);
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
                    $this -> ajaxReturn(null, $ojhistoryDB -> getError(), 1);
                }
                else {  //自动验证成功
                    if(false === $ojhistoryDB -> where('vid='.$vid) -> limit(1) -> save($data)) {
                        $this -> ajaxReturn(null, '[错误]写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                    }
                    else {
                        $this -> ajaxReturn(htmlspecialchars($data['mainname']), '[成功]', 0);
                    }
                }
            }
        }
       
    }
    
    public function ajax_get_img_list() {  //获取图片文件名列表

        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
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
            
            $this -> ajaxReturn($files, '[成功]', 0);
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
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $newsDB = D('News');
            $data = $newsDB -> relation(true) -> field('nid, title, author, createtime, category, permission, top') -> order('nid DESC') -> select();
            if($data === false) {
                $this -> ajaxReturn(null, '[错误]数据库错误。', 1);
            }
            else if($data === 0) {
                $this -> ajaxReturn(null, '[错误]没有队员信息。', 2);
            }
            else {
                for($i = 0; $i < count($data); $i++) {
                    $data[$i]['title'] = htmlspecialchars($data[$i]['title']);
                    $data[$i]['category'] = htmlspecialchars($data[$i]['category']);
                }
                $this -> ajaxReturn($data, '[成功]', 0);
            }
        }
    }
    
    public function ajax_get_news() {  //获取一条新闻详细信息
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $newsDB = D('News');
            $nid = intval(I('get.nid'));
            if($nid < 0) $this -> ajaxReturn(null, '[错误]NID无效。', 1);
            $data = $newsDB -> relation(true) -> where('nid = '.$nid) -> find();
            if($data === false) $this -> ajaxReturn(null, '[错误]数据库错误。', 2);
            else if(!$data) $this -> ajaxReturn(null, '[错误]NID无效。', 1);
            else {
                $this -> ajaxReturn($data, '[成功]', 0);
            }
        }
    }
    
    public function ajax_del_news() {  //删除新闻
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $list = I('get.nid');
            $nids = explode(',', $list);
            $success = 0;
            $fail = 0;
            foreach ($nids as $nid) {
                if(!$this -> del_one_news($nid)) $success ++;
                else $fail ++;
            }
            if($success == 0 && $fail == 0) $this -> ajaxReturn(null, '[错误]无效的参数。', 2);
            else if($fail != 0 && $success == 0) $this -> ajaxReturn(null, '[错误]无效的NID。', 1);
            else if($fail != 0 && $success != 0) $this -> ajaxReturn(null, '[提示]已成功删除'.$success.'条新闻，删除失败'.$fail.'条。', 0);
            else $this -> ajaxReturn(null, '[成功]已成功删除'.$success.'条新闻。', 0);
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
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $nid = intval(I('post.nownid'));
            if($nid == 9999 || $nid <= 0) $this -> ajaxReturn(null, '[错误]无效的NID。', 2);
    
            $data['title'] = I('post.title', '', false);
            $data['category'] = I('post.category', '', false);
            $data['content'] = I('post.content', '', false) == '' ? null : I('post.content', '', false);
            $data['author'] = intval(session('goldbirds_uid'));
            //$data['createtime'] = date("Y-m-d h:i:s");
            $data['top'] = (I('post.top', '', false) ? 1 : 0);
            $data['permission'] = (I('post.permission', '', false) ? 1 : 0);

            $newsDB = D('News');
            if(!$newsDB -> create($data)) {  //自动验证失败
                $this -> ajaxReturn(null, $newsDB -> getError(), 1);
            }
            else {  //自动验证成功
                if(false === $newsDB -> where('nid='.$nid) -> limit(1) -> save($data)) {
                    $this -> ajaxReturn(null, '[错误]写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                }
                else {
                    $this -> ajaxReturn(null, '[成功]', 0);
                }
            }
        }
    }
    
    public function ajax_add_news() {  //新闻管理-添加一条新闻
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $tmp = intval(I('post.nownid'));
            if($tmp != 9999) $this -> ajaxReturn(null, '[错误]无效的参数。', 2);
            
            $data['title'] = I('post.title', '', false);
            $data['category'] = I('post.category', '', false);
            $data['content'] = I('post.content', '', false) == '' ? null : I('post.content', '', false);
            $data['author'] = intval(session('goldbirds_uid'));
            $data['createtime'] = date("Y-m-d H:i:s");
            $data['top'] = (I('post.top', false) ? 1 : 0);
            $data['permission'] = (I('post.permission', '', false) ? 1 : 0);
    
            $newsDB = D('News');
            if(!$newsDB -> create($data)) {
                $this -> ajaxReturn(null, $newsDB -> getError(), 1);
            }
            else {
                if(false === ($tmp = $newsDB -> add()))
                    $this -> ajaxReturn(null, '[错误]写入数据库出错，请检查数据格式或数据库是否正常。', 1);
                else
                  $this -> ajaxReturn($tmp, '[成功]添加新闻成功，NID:'.$tmp, 0);
            }
        }
    }
    
    public function ajax_get_category() {  //自动提示的类别
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $newsDB = M('News');
            $data = $newsDB -> distinct(true) -> field('category') -> select();
            if($data === false) {
                $this -> ajaxReturn(null, '[错误]数据库错误。', 1);
            }
            else if($data === null) {
                $this -> ajaxReturn('[]', '[提示]系统中没有分类。', 0);
            }
            else {
                $retstr = array();
                $i = 0;
                foreach($data as $d) {
                    $retstr[$i] = $d['category'];
                    $i++;
                }
                $this -> ajaxReturn($retstr, '[成功]', 0);
            }
        }
    }
    
    //其它ajax请求=============================================
    
    public function ajax_get_person_modal() {  //个人展示窗口数据
        
        $uid = intval(I('get.uid'));
        if($uid <= 0) $this -> ajaxReturn(null, '[错误]UID参数不正确。', 1);
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
            if(!$data) $this -> ajaxReturn(null, '[错误]UID参数不正确。', 1);
            else {
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
                $this -> ajaxReturn($data, '[成功]', 0);
            }
        }
    }
}
