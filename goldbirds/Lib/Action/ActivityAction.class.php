<?php
class ActivityAction extends BaseAction {

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
            $myaid = $activitydataDB -> field('aid, state') -> where('ojaccount = "'.OJLoginInterface::getLoginUser().'"') -> select();
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
                    while($k < count($accept) && $accept[$k]['aid'] >= $data[$i]['aid']) {  //遍历获取通过审核参加活动人数
                        if($accept[$k]['aid'] == $data[$i]['aid']) { $data[$i]['accept'] = $accept[$k]['accept']; $k++; break; }
                        $k++;
                    }
                    foreach($myaid as $state) {  //遍历获取审核状态
                        if($state['aid'] == $data[$i]['aid']) {
                            $data[$i]['state'] = $state['state'];
                        }
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
        
        if($rule[0]['classname'] != null && class_exists($rule[0]['classname']) && method_exists($rule[0]['classname'], 'buildpage')) {  //使用自定义类中的buildpage生成页面
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

    public function ajax_load_contestants() {  //获取参赛人员名单
        
        $aid = intval(I('get.aid'));
        if($aid <= 0) $this -> ajaxReturn(null, '[错误]无效的AID参数。', 1);
        
        $activitylistDB = M('Activitylist');
        $activity = $activitylistDB -> field('title, form, adminuid, ispublic, isinner, isneedreview') -> where('aid = '.$aid) -> find();
        if(!$activity) $this -> ajaxReturn(null, '[错误]无效的AID参数。', 1);  //没有数据或数据库错误
        
        //权限检查
        if($activity['isinner'] == 1 && $this -> logincheck() < 2) $this -> ajaxReturn(null, '[错误]无效的AID参数', 1); //没有权限，返回无效AID参数
        if($activity['ispublic'] == 0 && ($this -> logincheck() < 2 || (session('goldbirds_group') < 1 && session('goldbirds_uid') != $activity['adminuid'])))
            $this -> ajaxReturn(null, '[错误]你没有权限查看已报名的人员名单。', 2);
        
        $rule = $this -> explain_reg_rule($activity['form']);
        if(null === $rule) $this -> ajaxReturn(null, '[错误]系统错误，无效的活动规则字符串。', 3);
        
        if($this -> logincheck() == 2 && (session('goldbirds_group') > 0 || session('goldbirds_uid') == $activity['adminuid'])) $ret['isadmin'] = 1;
        else $ret['isadmin'] = 0;
        $ret['title'] = htmlspecialchars($activity['title']);
        $ret['isneedreview'] = $activity['isneedreview'];
        $ret['contestants'] = array();
        $ret['titles'] = array();
        
        //获取报名者信息
        $dataid = array();  //公开字段序号-base64逗号隔开的注册信息中的序号
        $ruleid = array();  //公开字段序号-rule条目中对应的序号
        $k = 0;
        for($i = 1; $i < count($rule); $i++) {
            if($rule[$i]['type'] == 1 || $rule[$i]['type'] == 2 || $rule[$i]['type'] == 3 || $rule[$i]['type'] == 5 || $rule[$i]['type'] == 6) {  //只有这些才有数据
                if($rule[$i]['public'] == 1) {
                    array_push($ret['titles'], $rule[$i]['dis']);
                    array_push($ruleid, $i);
                    array_push($dataid, $k);
                }
                $k++;
            }
        }

        $activitydataDB = M('Activitydata');
        $regdata = $activitydataDB -> field('adid, ojaccount, data, state') -> where('aid = '.$aid) -> order('regtime DESC') -> select();
        if($regdata) {
            foreach($regdata as $r) {   //每一条用户注册信息$r
                $tmp['adid'] = $r['adid'];
                $tmp['ojaccount'] = $r['ojaccount'];
                $tmp['state'] = $r['state'];
                $tmp['data'] = array();
                $perunit = explode(',', $r['data']);
                for($j = 0; $j < count($ruleid); $j++) {
                    $rulei = $ruleid[$j];
                    $datai = $dataid[$j];
                    if($rule[$rulei]['type'] != 5) {
                        array_push($tmp['data'], htmlspecialchars(base64_decode($perunit[$datai])));
                    }
                    else {
                        $no = intval(base64_decode($perunit[$datai]));
                        array_push($tmp['data'], htmlspecialchars($rule[$rulei]['item'][$no]));
                    }
                }
                array_push($ret['contestants'], $tmp);
            }
        }
        $this -> ajaxReturn($ret, '[成功]', 0);
    }
    
    public function ajax_review_contestant() {  //更改报名者审核状态
        
        $adid = intval(I('get.adid'));
        $state = (intval(I('get.state')) == 2 ? 2 : 1);
        if($adid <= 0) $this -> ajaxReturn(null, '[错误]无效的ADID参数。', 1);
        if($this -> logincheck() < 2) $this -> ajaxReturn(null, '[错误]没有权限。', 2);
        
        $activitydataDB = M('Activitydata');
        $contestant = $activitydataDB -> where('adid = '.$adid) -> field('aid') -> find();
        if(!$contestant) $this -> ajaxReturn(null, '[错误]无效的ADID参数。', 1);
        
        $aid = $contestant['aid'];
        $activitylistDB = M('Activitylist');
        $activity = $activitylistDB -> field('isneedreview, adminuid') -> where('aid = '.$aid) -> find();
        if(!$activity) $this -> ajaxReturn(null, '[错误]未找到该场活动的报名信息，请检查。', 3);
        
        if($activity['isneedreview'] == 0) $this -> ajaxReturn(null, '[错误]本活动无需审核。', 4);
        if(session('goldbirds_group') < 1 && $activity['adminuid'] != session('goldbirds_uid')) $this -> ajaxReturn(null, '[错误]没有权限。', 2);
        
        $d['state'] = $state;
        $res = $activitydataDB -> where('adid = '. $adid) -> save($d);
        if($res === false) $this -> ajaxReturn(null, '[错误]审核该参与者失败，请重试。ADID='.$adid, 5);
        else $this -> ajaxReturn($adid, '[成功]', 0);
    }
    
    public function ajax_del_contestant() {  //删除某条注册信息
        
        $adid = intval(I('get.adid'));
        if($adid <= 0) $this -> ajaxReturn(null, '[错误]无效的ADID参数。', 1);
        if($this -> logincheck() < 2) $this -> ajaxReturn(null, '[错误]没有权限。', 2);
        
        $activitydataDB = M('Activitydata');
        $contestant = $activitydataDB -> where('adid = '.$adid) -> field('aid') -> find();
        if(!$contestant) $this -> ajaxReturn(null, '[错误]无效的ADID参数。', 1);
        
        $aid = $contestant['aid'];
        $activitylistDB = M('Activitylist');
        $activity = $activitylistDB -> field('adminuid') -> where('aid = '.$aid) -> find();
        if(!$activity) $this -> ajaxReturn(null, '[错误]未找到该场活动的报名信息，请检查。', 3);

        if(session('goldbirds_group') < 1 && $activity['adminuid'] != session('goldbirds_uid')) $this -> ajaxReturn(null, '[错误]没有权限。', 2);
        
        $res = $activitydataDB -> where('adid = '. $adid) -> delete();
        if(!$res) $this -> ajaxReturn(null, '[错误]审核该参与者失败，请重试。ADID='.$adid, 5);
        else $this -> ajaxReturn(null, '[成功]', 0);
    }
    
    public function export_contestants() {  //导出注册信息文件
        
        $aid = intval(I('get.aid'));
        if($aid <= 0) $this -> ajaxReturn(null, '[错误]无效的AID参数。', 1);
        if($this -> logincheck() < 2) echo '[错误]没有权限。';
        else {
            $activitylistDB = M('Activitylist');
            $activity = $activitylistDB -> field('adminuid, form') -> where('aid = '.$aid) -> find();
            if(!$activity) echo '[错误]查询比赛信息出错。';
            else {
                if(session('goldbirds_group') < 1 && $activity['adminuid'] != session('goldbirds_uid')) echo '[错误]没有权限。';
                else if(null === ($rule = $this -> explain_reg_rule($activity['form']))) echo '[错误]解析活动规则字符串失败。';
                else {
                    $file = ''; //文件内容
                    $activitydataDB = M('Activitydata');
                    $data = $activitydataDB -> where('aid = '.$aid) -> order('regtime ASC') -> select();
                    $file .= 'OJ账号,审核状态(2-通过，1-拒绝，0-未审核),注册时间,';
                    for($i = 0; $i < count($rule); $i++) {  //标题
                        if($rule[$i]['type'] == 1 || $rule[$i]['type'] == 2 || $rule[$i]['type'] == 3 || $rule[$i]['type'] == 6)
                            $file .= ($rule[$i]['dis'].',');
                        else if($rule[$i]['type'] == 5) {
                            $tmp = '';
                            for($a = 0; $a < count($rule[$i]['item']); $a++) {
                                if($a != 0) $tmp .= '|';
                                $tmp .= ($a.'-'.$rule[$i]['item'][$a]);
                            }
                            $file .= ($rule[$i]['dis'].'('.$tmp.'),');    
                        }
                    }
                    $file .= "\r\n";
                    foreach($data as $d) {
                        $file .= ($d['ojaccount'].',');
                        $file .= ($d['state'].',');
                        $file .= ($d['regtime'].',');
                        $list = explode(',', $d['data']);
                        foreach($list as $l) $file .= (base64_decode($l).',');
                        $file .= "\r\n";
                    }
                    
                    $file = iconv("UTF-8", "GBK", $file);  //UTF-8在EXCEL下乱码
                    Header("Content-type: application/octet-stream");
                    Header("Accept-Ranges: bytes");
                    Header("Accept-Length:".strlen($file));
                    Header("Content-Disposition: attachment; filename=Activity_".$aid.'.csv');
                    echo $file;
                }
            }
        }
    }
}
