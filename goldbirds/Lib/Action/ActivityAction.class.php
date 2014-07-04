<?php
class ActivityAction extends BaseAction {
    
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
    
    private function explain_reg_rule($string) {  //解析注册字段字符串，成功返回Array，失败返回null
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
    
    public function index() {
        
        $this -> commonassign();
        if($this -> logincheck() == 0) $this -> assign('notlogin', true);
        
        $this -> display();
    }
    
    public function ajax_load_activity() {  //加载活动列表，type=0所有，type!=0自己参加的
        
        $type = intval(I('get.type'));
        if($type == 0) {  //所有活动
            
            $activitylistDB = M('Activitylist');
            if($this -> logincheck() == 2) {  //队员已登录，可看isinner=1的比赛
                $data = $activitylistDB -> field('aid, title, deadline, isinner, ispublic, isneedreview, desc, adminuid') -> order('aid DESC') -> select();
            }
            else {  //未登录，仅可看公开比赛
                $data = $activitylistDB -> field('aid, title, deadline, isinner, ispublic, isneedreview, desc, adminuid') -> where('isinner = 0') -> order('aid DESC') -> select();
            }
            
            if($data === false) {
                $this -> ajaxReturn(null, '[错误]数据库错误。', 1);
            }
            else if($data === null) {
                $this -> ajaxReturn(null, '[成功]暂时没有活动报名信息。', 0);
            }
            else {
                $activitydataDB = M('Activitydata');
                $accept = $activitydataDB -> field('aid, count(*) AS accept') -> group('aid') -> where('state = 2') -> order('aid DESC') -> select();
                $k = 0;
                for($i = 0; $i < count($data); $i++) {
                    if(session('goldbirds_islogin') && (session('goldbirds_group') > 0 || session('goldbirds_uid') == $data[$i]['adminuid'])) $data[$i]['adminuid'] = 1;
                    else $data[$i]['adminuid'] = 0;
                    if($data[$i]['desc']) $data[$i]['desc'] = '_'; else $data[$i]['desc'] = null;
                    $data[$i]['title'] = htmlspecialchars($data[$i]['title']);
                    $data[$i]['accept'] = 0;
                    while($k < count($accept) && $accept[$k]['aid'] >= $data[$i]['aid']) {
                        if($accept[$k]['aid'] == $data[$i]['aid']) { $data[$i]['accept'] = $accept[$k]['accept']; $k++; break; }
                        $k++;
                    }
                }
                $this -> ajaxReturn($data, '[成功]', 0);
            }
        }
        else {  //仅自己报名的
            
            if($this -> logincheck() == 0) $this -> ajaxReturn(null, '[错误]你在OJ上还未登录，请先登录。', 1);  //OJ未登录
            
            $activitydataDB = M('Activitydata');
            $myaid = $activitydataDB -> field('aid') -> where('ojaccount = "'.OJLoginInterface::getLoginUser().'"') -> select();
            if(!$myaid) $this -> ajaxReturn(null, '[成功]无数据。', 0);

            $aidstr = '';
            $aidstr .= $myaid[0]['aid'];
            for($i = 1; $i < count($myaid); $i++) {
                $aidstr = $aidstr.','.$myaid[$i]['aid'];
            }
            
            $activitylistDB = M('Activitylist');
            $data = $activitylistDB -> field('aid, title, deadline, isinner, ispublic, isneedreview, desc, adminuid') -> where('aid IN ('.$aidstr.')') -> order('aid DESC') -> select();
            
            if($data === false) {
                $this -> ajaxReturn(null, '[错误]数据库错误。', 1);
            }
            else if($data === null) {
                $this -> ajaxReturn(null, '[提示]暂时没有活动报名信息。', 2);
            }
            else {
                $activitydataDB = M('Activitydata');
                $accept = $activitydataDB -> field('aid, count(*) AS accept') -> group('aid') -> where('state = 2') -> order('aid DESC') -> select();
                $k = 0;
                for($i = 0; $i < count($data); $i++) {
                    if(session('goldbirds_islogin') && (session('goldbirds_group') > 0 || session('goldbirds_uid') == $data[$i]['adminuid'])) $data[$i]['adminuid'] = 1;
                    else $data[$i]['adminuid'] = 0;
                    if($data[$i]['desc']) $data[$i]['desc'] = '_'; else $data[$i]['desc'] = null;
                    $data[$i]['title'] = htmlspecialchars($data[$i]['title']);
                    $data[$i]['accept'] = 0;
                    while($k < count($accept) && $accept[$k]['aid'] >= $data[$i]['aid']) {
                        if($accept[$k]['aid'] == $data[$i]['aid']) { $data[$i]['accept'] = $accept[$k]['accept']; $k++; break; }
                        $k++;
                    }
                }
                $this -> ajaxReturn($data, '[成功]', 0);
            }
        }
    }

    public function ajax_get_registeform() {  //生成注册表单（如已注册，则附带返回注册信息）
        
        if($this -> logincheck() == 0) $this -> ajaxReturn(null, '[错误]请先登录OJ。', 5);  //未登录，非法操作
        
        $aid = intval(I('get.aid'));
        if($aid <= 0) $this -> ajaxReturn(null, '[错误]无效的AID参数。', 1);
        
        //获取活动信息
        $activitylistDB = M('Activitylist');
        $data = $activitylistDB -> field('title, form, deadline, isinner, isneedreview') -> where('aid = '.$aid) -> find();
        if(!$data) $this -> ajaxReturn(null, '[错误]获取注册表单格式出错:(', 2);
        if($data['isinner'] == 1 && $this -> logincheck() != 2) $this -> ajaxReturn(null, '[错误]没有权限。', 6);  //内部活动，非法操作
        
        //加载自定义类
        Vendor('ActivityFormClass.activity');
        $rule = $this -> explain_reg_rule($data['form']);
        $ret['title'] = htmlspecialchars($data['title']);
        
        if($rule[0]['classname'] != null && method_exists($rule[0]['classname'], 'buildpage')) {  //使用自定义类中的buildpage生成页面
            if(false === eval('$ret["form"] = '.$rule[0]['classname'].'::buildpage();'))
                $this -> ajaxReturn(null, '[错误]生成注册表单出错:(', 4);
            else {
                //获取注册信息
                $activitydataDB = M('Activitydata');
                $regdata = $activitydataDB -> field('data, state') -> where('aid = '.$aid.' AND ojaccount = "'.OJLoginInterface::getLoginUser().'"') -> order('adid DESC') -> find();
                if(!$regdata) {  //未注册
                    if(time() > strtotime($data['deadline'])) $this -> ajaxReturn(null, '[错误]你已经错过了报名时间了 -__-', 3);  //超过deadline
                    $ret['data'] = null;
                    $retstr = '[成功]';
                }
                else {  //已注册
                    $ret['data'] = array();
                    $perunit = explode(',', $regdata['data']);
                    foreach($perunit as $p) {
                        array_push($ret['data'], base64_decode($p));
                    }
                    //是否已通过审核
                    if($data['isneedreview'] == 1 && $regdata['state'] == 2) {
                        $ret['readonly'] = 1;
                        $retstr = '[提示]你已通过审核，不能修改报名信息。<br />如果你确实需要修改报名信息，请联系比赛组织者 : )';
                    }
                    else {
                        if(time() > strtotime($data['deadline'])) {  //已注册，且超过报名时间
                            $ret['readonly'] = 1;
                            $retstr = '[提示]活动报名已截止啦~ 不能修改信息了。 : )';
                        }
                        else {
                            $ret['readonly'] = 0;
                            $retstr = '[成功]';
                        }
                    }
                }
                $this -> ajaxReturn($ret, $retstr, 0);
            }
        }
        else {
            //生成表单HTML字符串
            $htmlstr = '';
            for($i = 1; $i < count($rule); $i++) {
                switch($rule[$i]['type']) {
                    case 1:
                        $htmlstr .= ('<label for="d'.$i.'">'.$rule[$i]['dis'].'</label><input type="text" class="'.$rule[$i]['class'].'" id="d'.$i.'" name="regdata[]"><br />');
                        break;
                    case 2:
                        $htmlstr .= ('<label for="d'.$i.'">'.$rule[$i]['dis'].'</label><input type="text" value="'.$rule[$i]['item'].'" class="'.$rule[$i]['class'].'" id="d'.$i.'" name="regdata[]" readonly="readonly"><br />');
                        break;
                    case 3:
                        $htmlstr .= ('<label for="d'.$i.'">'.$rule[$i]['dis'].'</label><input type="password" class="'.$rule[$i]['class'].'" id="d'.$i.'" name="regdata[]"><br />');
                        break;
                    case 4:
                        $htmlstr .= $rule[$i]['dis'];
                        break;
                    case 5:
                        $htmlstr .= ('<label for="d'.$i.'">'.$rule[$i]['dis'].'</label><select class="'.$rule[$i]['class'].'" id="d'.$i.'" name="regdata[]">');
                        for($j = 0; $j < count($rule[$i]['item']); $j++) {
                            $htmlstr .= ('<option value="'.$j.'">'.$rule[$i]['item'][$j].'</option>');
                        }
                        $htmlstr .= '</select>';
                        break;
                    case 6:
                        $htmlstr .= ('<label for="d'.$i.'">'.$rule[$i]['dis'].'</label><textarea rows="5" class="'.$rule[$i]['class'].'" name="regdata[]" id="d'.$i.'"></textarea>');
                        break;
                    case 7:
                        $htmlstr .= ('<span class="'.$rule[$i]['class'].'">'.$rule[$i]['dis'].'</span>');
                        break;
                    default:
                        $this -> ajaxReturn(null, '[错误]系统错误，无效的活动表单规则字符串。', 6);
                }
            }
            $ret['form'] = $htmlstr;
            
            //获取注册信息
            $activitydataDB = M('Activitydata');
            $regdata = $activitydataDB -> field('data, state') -> where('aid = '.$aid.' AND ojaccount = "'.OJLoginInterface::getLoginUser().'"') -> order('adid DESC') -> find();
            if(!$regdata) {  //未注册
                if(time() > strtotime($data['deadline'])) $this -> ajaxReturn(null, '[错误]你已经错过了报名时间了 -__-', 3);  //超过deadline
                $ret['data'] = null;
                $retstr = '[成功]';
            }
            else {  //已注册
                $ret['data'] = array();
                $perunit = explode(',', $regdata['data']);
                foreach($perunit as $p) {
                    array_push($ret['data'], base64_decode($p));
                }
                //是否已通过审核
                if($data['isneedreview'] == 1 && $regdata['state'] == 2) {
                    $ret['readonly'] = 1;
                    $retstr = '[提示]你已通过审核，不能修改报名信息。<br />如果你确实需要修改报名信息，请联系比赛组织者 : )';
                }
                else {
                    if(time() > strtotime($data['deadline'])) {  //已注册，且超过报名时间
                        $ret['readonly'] = 1;
                        $retstr = '[提示]活动报名已截止啦~ 不能修改信息了。 : )';
                    }
                    else {
                        $ret['readonly'] = 0;
                        $retstr = '[成功]';
                    }
                }
            }
            $this -> ajaxReturn($ret, $retstr, 0);
        }
    }
    
    public function ajax_save_regdata() {  //提交注册信息
        
        if($this -> logincheck() == 0) $this -> ajaxReturn(null, '[错误]请先登录OJ。', 5);  //未登录，非法操作
        
        $aid = I('post.aid');
        if($aid <= 0) $this -> ajaxReturn(null, '[错误]无效的AID参数。', 1);
        
        $activitylistDB = M('Activitylist');
        $activity = $activitylistDB -> where('aid = '.$aid) -> find();
        
        //合法性检查
        if(!$activity) $this -> ajaxReturn(null, '[错误]无效的AID参数。', 1);
        if($activity['isinner'] == 1 && $this -> logincheck() != 2) $this -> ajaxReturn(null, '[错误]没有权限。', 6);  //内部活动，非法操作
        if(time() > strtotime($activity['deadline'])) $this -> ajaxReturn(null, '[错误]报名时间已截止。', 2);  //时间已截止
        
        $activitydataDB = M('Activitydata');
        $regdata = $activitydataDB -> where('aid = '.$aid.' AND ojaccount = "'.OJLoginInterface::getLoginUser().'"') -> find();
        if(!$regdata) {  //注册
            $postdata = I('post.regdata', false, '');  //传进的是数组
            $datastr = '';
            if(count($postdata) < 1) $this -> ajaxReturn(null, '[错误]无效的请求数据。', 3);
            
            //调用自定义类进行输入数据合法性校验
            $rule = $this -> explain_reg_rule($activity['form']);
            if($rule === null) $this -> ajaxReturn(null, '[错误]系统错误，无效的活动规则字符串。', 9);
            if($rule[0]['classname'] != null) {
                Vendor('ActivityFormClass.activity');
                $checkres = null;
                if(!class_exists($rule[0]['classname']) || !method_exists($rule[0]['classname'], 'checkdata') 
                || false === eval('$checkres = '.$rule[0]['classname'].'::checkdata($postdata);') 
                || (!(is_string($checkres)) && !is_array($checkres)) )
                    $this -> ajaxReturn(null, '[错误]系统错误，无效的自定义活动类。', 7);  //自定义类不合法
                
                if(is_string($checkres)) $this -> ajaxReturn(null, $checkres, 8);
                else $postdata = $checkres;
            }
            
            for($i = 0; $i < count($postdata); $i++) {
                if($i == 0) $datastr .= base64_encode($postdata[$i]);
                else $datastr = $datastr.','.base64_encode($postdata[$i]);
            }
            $d['aid'] = $aid;
            $d['ojaccount'] = OJLoginInterface::getLoginUser();
            $d['data'] = $datastr;
            if($activity['isneedreview'] == 1) $d['state'] = 0;
            else $d['state'] = 2;
            $d['regtime'] = date('Y-m-d H:i:s',time());
            if($activitydataDB -> add($d)) {
                $this -> ajaxReturn(null, '[成功]报名活动成功！', 0);
            }
            else {
                $this -> ajaxReturn(null, '[错误]报名活动失败！', 4);
            }
         }
        else {  //修改
            if($activity['isneedreview'] == 1 && $regdata['state'] == 2) 
                $this -> ajaxReturn(null, '[错误]你已通过审核，无法修改报名信息。如果确实需要修改，请联系管理员。', 7);
            
            $adid = $regdata['adid'];
            
            $postdata = I('post.regdata', false, '');  //传进的是数组
            $datastr = '';
            if(count($postdata) < 1) $this -> ajaxReturn(null, '[错误]无效的请求数据。', 3);
            
            //调用自定义类进行输入数据合法性校验
            $rule = $this -> explain_reg_rule($activity['form']);
            if($rule === null) $this -> ajaxReturn(null, '[错误]系统错误，无效的活动规则字符串。', 9);
            if($rule[0]['classname'] != null) {
                Vendor('ActivityFormClass.activity');
                $checkres = null;
                if(!class_exists($rule[0]['classname']) || !method_exists($rule[0]['classname'], 'checkdata')
                || false === eval('$checkres = '.$rule[0]['classname'].'::checkdata($postdata);')
                || (!(is_string($checkres)) && !is_array($checkres)) )
                    $this -> ajaxReturn(null, '[错误]系统错误，无效的自定义活动类。', 7);  //自定义类不合法
            
                if(is_string($checkres)) $this -> ajaxReturn(null, $checkres, 8);
                else $postdata = $checkres;
            }
            
            for($i = 0; $i < count($postdata); $i++) {
                if($i == 0) $datastr .= base64_encode($postdata[$i]);
                else $datastr = $datastr.','.base64_encode($postdata[$i]);
            }

            $d['data'] = $datastr;
            if($activity['isneedreview'] == 1) $d['state'] = 0;
            else $d['state'] = 2;
            $result = $activitydataDB -> where('adid = '.$adid.' AND ojaccount = "'.OJLoginInterface::getLoginUser().'"') -> save($d);
            if($result !== false) {
                $this -> ajaxReturn(null, '[成功]修改活动报名信息成功！', 0);
            }
            else {
                $this -> ajaxReturn(null, '[错误]修改活动报名信息失败！', 4);
            }
        }
        
    }
    
    public function ajax_get_desc() {  //获取活动详情
        
        $aid = intval(I('get.aid'));
        if($aid <= 0) $this -> ajaxReturn(null, '[错误]无效的AID参数。', 1);
        
        $activitylistDB = M('Activitylist');
        if($this -> logincheck() == 2) {  //队员，已登录
            $data = $activitylistDB -> field('title, desc') -> where('aid = '.$aid) -> find();
        }
        else {
            $data = $activitylistDB -> field('title, desc') -> where('aid = '.$aid.' AND isinner = 0') -> find();
        }
        
        if($data === false) {
            $this -> ajaxReturn(null, '[错误]数据库错误。', 2);
        }
        else if($data === null) {
            $this -> ajaxReturn(null, '[错误]无效的AID参数。', 1);
        }
        else {
            $data['title'] = htmlspecialchars($data['title']);
            $this -> ajaxReturn($data, '[成功]', 0);
        }
    }
}
