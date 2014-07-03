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
            $myaid = $activitydataDB -> field('aid') -> where('ojaccount = "'.session('goldbirds_oj').'"') -> select();
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

    public function ajax_get_registeform() {  //未完工
        
        $aid = intval(I('get.aid'));
        $activitylistDB = M('Activitylist');
        
        $settingAction = A('Setting');
        
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
