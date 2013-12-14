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
        if($this -> _post('email') === '') $data['email'] = null; 
        else if(strlen($this -> _post('email')) > 0) $data['email'] = $this -> _post('email');
        if($this -> _post('phone') === '') $data['phone'] = null;
        else if(strlen($this -> _post('phone')) > 0) $data['phone'] = $this -> _post('phone');
        if($this -> _post('address') === '') $data['address'] = null;
        else if(strlen($this -> _post('address')) > 0) $data['address'] = $this -> _post('address');
        if(!intval($this -> getconfig('config_lock_person_introduce'))) {
            if($this -> _post('introduce') === '') $data['introduce'] = null;
            else if(strlen($this -> _post('introduce')) > 0) $data['introduce'] = $this -> _post('introduce');
        }
        if($this -> _post('detail') === '') $data['detail'] = null;
        else if(strlen($this -> _post('detail')) > 0) $data['detail'] = $this -> _post('detail');
        
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
        
        $code = $this -> _post('code');
        sleep(1);
        if(strlen($code) != 16)
            $this -> ajaxReturn(null, '[错误]无效的邀请码，请重试！', 1);
        
        $personDB = M('Person');
        $c['luckycode'] = $code;
        $data = $personDB -> field('uid, chsname, engname, ojaccount') -> where($c) -> find();
        if($data) {
            if($data['ojaccount'] == null) {
                $r['code'] = $data['uid'].'-'.$data['chsname'].'-'.$data['engname'];
                $r['oj'] = OJLoginInterface::getLoginUser();
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
        
        $code = $this -> _post('code');
        $oj = OJLoginInterface::getLoginUser();
        sleep(1);
        if(strlen($code) != 16)
            $this -> ajaxReturn(null, '[错误]无效的邀请码，请重试！', 1);
    
        $personDB = M('Person');
        $c['luckycode'] = $code;
        $data = $personDB -> field('uid, chsname, engname, ojaccount') -> where($c) -> find();
        if($data) {
            if($data['ojaccount'] == null) {  //验证完毕，准备绑定
                
                if($personDB -> where('uid = '.$data['uid']) -> limit(1) -> setField('ojaccount', $oj))
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
            $data = $personDB -> field('uid, chsname, sex, email, phone, grade, ojaccount, group') -> where('uid > 0') -> order('uid ASC') -> select();
            if($data === false) {
                $this -> ajaxReturn(null, '[错误]数据库错误。', 1);
            }
            else if($data === 0) {
                $this -> ajaxReturn(null, '[错误]没有队员信息。', 2);
            }
            else {
                $this -> ajaxReturn($data, '[成功]', 0);
            }
        }
    }
    
    public function ajax_get_person() {  //获取一名队员详细信息
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $personDB = M('Person');
            $uid = intval($this -> _get('uid'));
            if($uid < 0) $this -> ajaxReturn(null, '[错误]UID无效。', 1);
            $data = $personDB -> where('uid = '.$uid) -> find();
            if($data === false) $this -> ajaxReturn(null, '[错误]数据库错误。', 2);
            else if(!$data) $this -> ajaxReturn(null, '[错误]UID无效。', 1);
            else $this -> ajaxReturn($data, '[成功]', 0);
        }
    }
    
    public function ajax_del_person() {  //删除用户
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $list = $this -> _get('uid');
            $uids = explode(',', $list);
            $success = 0;
            $fail = 0;
            foreach ($uids as $uid) {
                if(!$this -> del_one_person($uid)) $success ++;
                else $fail ++;
            }
            if($success == 0 && $fail == 0) $this -> ajaxReturn(null, '[错误]无效的参数。', 2);
            else if($fail != 0 && $success == 0) $this -> ajaxReturn(null, '[错误]无效的UID。', 1);
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
    
    public function ajax_add_person() {  //队员管理-添加一名队员
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $tmp = intval($this -> _post('nowuid'));
            if($tmp != 9999) $this -> ajaxReturn(null, '[错误]无效的参数。', 2);
            $data['chsname'] = $this -> _post('chsname');
            $data['engname'] = $this -> _post('engname') == '' ? null : $this -> _post('engname');
            $data['email'] = $this -> _post('email') == '' ? null : $this -> _post('email');
            $data['phone'] = $this -> _post('phone') == '' ? null : $this -> _post('phone');
            $data['address'] = $this -> _post('address') == '' ? null : $this -> _post('address');
            if(intval($this -> _post('sex')) == 1) $data['sex'] = 1;
            else $data['sex'] = 0;
            $tmp = intval($this -> _post('grade'));
            if($tmp > 1950 && $tmp < 2100) $data['grade'] = $tmp;
            else $data['grade'] = null;
            $data['introduce'] = $this -> _post('introduce') == '' ? null : $this -> _post('introduce');
            $data['detail'] = $this -> _post('detail') == '' ? null : $this -> _post('detail');
            $data['photo'] = null;
            $data['ojaccount'] = $this -> _post('ojaccount') == '' ? null : $this -> _post('ojaccount');
            $tmp = intval($this -> _post('group'));
            if($tmp == 0 || $tmp == 1 || $tmp == 9) $data['group'] = $tmp;
            else $tmp = 0;
            srand((double)microtime()*1000000);
            $data['luckycode'] = substr(md5('goldbirds'.'_xzz'.$data['chsname'].rand()), 10, 16);
            
            $data['photo'] = strcmp(substr($this -> _post('face_fn'), 0, 7), 'upload/') == 0 ? $this -> _post('face_fn') : null;
            
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
            $uid = intval($this -> _post('nowuid'));
            if($uid == 9999 || $uid <= 0) $this -> ajaxReturn(null, '[错误]无效的UID。', 2);
            
            $data['chsname'] = $this -> _post('chsname');
            $data['engname'] = $this -> _post('engname') == '' ? null : $this -> _post('engname');
            $data['email'] = $this -> _post('email') == '' ? null : $this -> _post('email');
            $data['phone'] = $this -> _post('phone') == '' ? null : $this -> _post('phone');
            $data['address'] = $this -> _post('address') == '' ? null : $this -> _post('address');
            if(intval($this -> _post('sex')) == 1) $data['sex'] = 1;
            else $data['sex'] = 0;
            $tmp = intval($this -> _post('grade'));
            if($tmp > 1950 && $tmp < 2100) $data['grade'] = $tmp;
            else $data['grade'] = null;
            $data['introduce'] = $this -> _post('introduce') == '' ? null : $this -> _post('introduce');
            $data['detail'] = $this -> _post('detail') == '' ? null : $this -> _post('detail');
            $data['photo'] = null;
            $data['ojaccount'] = $this -> _post('ojaccount') == '' ? null : $this -> _post('ojaccount');
            $tmp = intval($this -> _post('group'));
            if($tmp == 0 || $tmp == 1 || $tmp == 2 || $tmp == 9) $data['group'] = $tmp;
            else $tmp = 0;
            
            $data['photo'] = strcmp(substr($this -> _post('face_fn'), 0, 7), 'upload/') == 0 ? $this -> _post('face_fn') : null;
            
            
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
            $data = $contestDB -> where('cid > 0') -> order('holdtime DESC, cid DESC') -> select();
            if($data === false) {
                $this -> ajaxReturn(null, '[错误]数据库错误。', 1);
            }
            else if($data === null) {
                $this -> ajaxReturn(null, '[错误]没有比赛信息。', 2);
            }
            else {
                $this -> ajaxReturn($data, '[成功]', 0);
            }
        }
    }

    public function ajax_get_contest() {  //获取一条比赛获奖记录信息
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $contestDB = D('Contest');
            $cid = intval($this -> _get('cid'));
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
                $data['id'] = intval($this -> _post('id'));

                echo json_encode(array('data' => $data, 'info' => '[成功]上传成功，文件大小'.sprintf("%.2lf", intval($fileinfo[0]['size'])/1024).'KB。', 'status' => 0));
            }
        }
    }
    
    public function ajax_add_contest() {  //添加获奖记录
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $tmp = intval($this -> _post('nowcid'));
            if($tmp != 9999) $this -> ajaxReturn(null, '[错误]无效的参数。', 2);
            
            if(false === strtotime($this -> _post('holdtime')))
                $this -> ajaxReturn(null, '[错误]日期格式不对！', 1);
            if(strtotime($this -> _post('holdtime')) >= strtotime('2037-12-31') 
                || strtotime($this -> _post('holdtime')) < strtotime('1960-1-1')) {
                $this -> ajaxReturn(null, '[错误]日期范围不太对！', 1);
            }
            $data['holdtime'] = $this -> _post('holdtime');
            
            $data['team'] = $this -> _post('team');
            $data['site'] = $this -> _post('site');
            $data['university'] = $this -> _post('university');
            $data['type'] = (intval($this -> _post('type')) >= 0 && intval($this -> _post('type')) <= 2) ? intval($this -> _post('type')) : 1;
            $data['medal'] = (intval($this -> _post('medal')) >= 0 && intval($this -> _post('medal')) <= 3) ? intval($this -> _post('medal')) : 3;
            $data['ranking'] = $this -> _post('ranking') == '' ? null : $this -> _post('ranking');
            $data['title'] = $this -> _post('title') == '' ? null : $this -> _post('title');
            
            $personDB = M('Person');
            $plist = explode('-', $this -> _post('leader'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> ajaxReturn(null, '[错误]Leader的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['leader'] = $c['uid'];
            
            $plist = explode('-', $this -> _post('teamer1'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> ajaxReturn(null, '[错误]Teamer1的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['teamer1'] = $c['uid'];
            
            $plist = explode('-', $this -> _post('teamer2'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> ajaxReturn(null, '[错误]Teamer2的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['teamer2'] = $c['uid'];
            
            $data['pic1'] = strcmp(substr($this -> _post('pic1_fn'), 0, 7), 'upload/') == 0 ? $this -> _post('pic1_fn') : null;
            $data['pic2'] = strcmp(substr($this -> _post('pic2_fn'), 0, 7), 'upload/') == 0 ? $this -> _post('pic2_fn') : null;
            
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
            $list = $this -> _get('cid');
            $delpic = intval($this -> _get('delpic'));
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
            $cid = intval($this -> _post('nowcid'));
            if($cid == 9999 || $cid <= 0) $this -> ajaxReturn(null, '[错误]无效的CID。', 2);
            
            if(false === strtotime($this -> _post('holdtime')))
                $this -> ajaxReturn(null, '[错误]日期格式不对！', 1);
            if(strtotime($this -> _post('holdtime')) >= strtotime('2037-12-31')
            || strtotime($this -> _post('holdtime')) < strtotime('1960-1-1')) {
                $this -> ajaxReturn(null, '[错误]日期范围不太对！', 1);
            }
            $data['holdtime'] = $this -> _post('holdtime');
            
            $data['team'] = $this -> _post('team');
            $data['site'] = $this -> _post('site');
            $data['university'] = $this -> _post('university');
            $data['type'] = (intval($this -> _post('type')) >= 0 && intval($this -> _post('type')) <= 2) ? intval($this -> _post('type')) : 1;
            $data['medal'] = (intval($this -> _post('medal')) >= 0 && intval($this -> _post('medal')) <= 3) ? intval($this -> _post('medal')) : 3;
            $data['ranking'] = $this -> _post('ranking') == '' ? null : $this -> _post('ranking');
            $data['title'] = $this -> _post('title') == '' ? null : $this -> _post('title');
            
            $personDB = M('Person');
            $plist = explode('-', $this -> _post('leader'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> ajaxReturn(null, '[错误]Leader的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['leader'] = $c['uid'];
            
            $plist = explode('-', $this -> _post('teamer1'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> ajaxReturn(null, '[错误]Teamer1的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['teamer1'] = $c['uid'];
            
            $plist = explode('-', $this -> _post('teamer2'));
            $c['uid'] = intval($plist[0]);
            $c['chsname'] = $plist[1];
            $res = $personDB -> where($c) -> find();
            if(!$res) $this -> ajaxReturn(null, '[错误]Teamer2的格式不对耶，请用“uid-中文姓名”酱紫的。', 1);
            else $data['teamer2'] = $c['uid'];
            
            $data['pic1'] = strcmp(substr($this -> _post('pic1_fn'), 0, 7), 'upload/') == 0 ? $this -> _post('pic1_fn') : null;
            $data['pic2'] = strcmp(substr($this -> _post('pic2_fn'), 0, 7), 'upload/') == 0 ? $this -> _post('pic2_fn') : null;
            
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
            $c['k'] = $this -> _post('k');
            $settingDB = M('Setting');
            $data = $settingDB -> where($c) -> find();
            if(!$data) $this -> ajaxReturn(null, '[错误]参数键值错误！', 1);  //无效的K值或者查询错误
            if($data['type'] == 0) {  //bool型，非0-是，0-否
                if(intval($this -> _post('v')) == 0) $dat = '0';
                else $dat = '1';
            }
            else if($data['type'] == 1) {  //文本-转义
                if(get_magic_quotes_gpc()) {  //如果get_magic_quotes_gpc()是打开的
                    $_POST['v'] = stripslashes($_POST['v']);
                }
                $dat = $this -> _post('v');
            }
            else {  //文本-不转义
                if(get_magic_quotes_gpc()) {  //如果get_magic_quotes_gpc()是打开的
                    $_POST['v'] = stripslashes($_POST['v']);
                }
                $dat = $this -> _post('v', false);
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
    
    public function ajax_upload_image() {
        
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
                if ($filename != "." && $filename != "..") {
                    $files[iconv('GBK', 'UTF-8', strtolower($filename))] = iconv('GBK', 'UTF-8', $filename);  //filename-无用，null-有关联，需要中文编码转换
                }
            }
            closedir($handler);
            
            //查找contest中的关联图片
            $contestDB = M('Contest');
            $data = $contestDB -> field('pic1, pic2') -> select();
            foreach($data as $d) {
                if($d['pic1'] && array_key_exists(strtolower(substr($d['pic1'], 7)))) $files[strtolower(substr($d['pic1'], 7))] = null;
                if($d['pic2'] && array_key_exists(strtolower(substr($d['pic2'], 7)))) $files[strtolower(substr($d['pic2'], 7))] = null;
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
                if($v === 1 && strpos($var, $k) !== false) $files[$k] = null;
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
            $photos = $this -> _post('photos', false);
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
    
    public function ojhistory() {
        
        $this -> commonassign();
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> profile();
        else {
            $this -> display('');
        }
    }
    
    public function ajax_get_oj_list() {
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $ojhistoryDB = M('Ojhistory');
            $data = $ojhistoryDB -> field('vid, mainname') -> order('sortid DESC') -> select();
            $this -> ajaxReturn($data, '[成功]', 0);
        }
    }
    
    public function ajax_get_oj_detail() {
    
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $vid = intval($this -> _get('vid'));
            if($vid <= 0) $this -> ajaxReturn(null, '[错误]无效的VID，请检查。', 1);
            else {
                $ojhistoryDB = M('Ojhistory');
                $data = $ojhistoryDB -> where('vid='.$vid) -> find();
                $data['mainname'] = htmlspecialchars_decode($data['mainname']);
                $data['devname'] = htmlspecialchars_decode($data['devname']);
                $data['introduce'] = htmlspecialchars_decode($data['introduce']);
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
            $vid = intval($this -> _get('vid'));
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
    
    public function ajax_upload_ojpic() {
        
        $this -> ajax_upload_contestpic();
    }
    
    public function ajax_modify_oj() {  //保存一条OJ历史的修改
        
        if(!session('goldbirds_islogin') || intval(session('goldbirds_group')) < 1)  //无权限处理
            $this -> ajaxReturn(null, '[错误]无权限。', 3);
        else {
            $vid = intval($this -> _post('vid'));
            if($vid <= 0) $this -> ajaxReturn(null, '[错误]无效的VID参数。', 1);
            else {
                $data['sortid'] = intval($this -> _post('sortid'));
                $data['mainname'] = $this -> _post('mainname');
                $data['devname'] = $this -> _post('devname') == '' ? null : $this -> _post('devname');
                $data['introduce'] = $this -> _post('introduce') == '' ? null : $this -> _post('introduce');
                $photos = $this -> _post('photos');
                $titles = $this -> _post('titles');
                $descs = $this -> _post('descs');
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
                        $this -> ajaxReturn(null, '[成功]', 0);
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
    
    
}
