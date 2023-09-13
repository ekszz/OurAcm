<?php
namespace Home\Controller;

class IndexController extends BaseController {
    
    protected $module_name = 'HOME';
    
    public function index() {
        $this -> assign('home_chs_header', $this -> getconfig('home_chs_header'));
        $this -> assign('home_eng_header', $this -> getconfig('home_eng_header'));
        $this -> assign('home_additional_title', $this -> getconfig('home_additional_title'));
        $this -> assign('home_mainarea', $this -> getconfig('home_mainarea'));
       
        $contestDB = M('Contest');
        $data['wf'] = $contestDB -> where('type = 0') -> getField('COUNT(*) AS c');
        $data['regional'][0] = $contestDB -> where('type = 1 AND medal = 0') -> getField('COUNT(*) AS c');
        $data['regional'][1] = $contestDB -> where('type = 1 AND medal = 1') -> getField('COUNT(*) AS c');
        $data['regional'][2] = $contestDB -> where('type = 1 AND medal = 2') -> getField('COUNT(*) AS c');
        
        if(intval($this -> getconfig('config_show_recent_contest'))) {  //显示最近获奖记录
            $recent = array();
            $res = $contestDB -> where('(type = 0 OR type = 1) AND TO_DAYS(NOW()) - TO_DAYS(holdtime) <= '.intval($this -> getconfig('config_recent_days'))) -> order('type ASC, holdtime DESC, medal ASC') -> select();
            if($res) {
                foreach($res as $r) {
                    $tmp['type'] = $r['type'];
                    $tmp['medal'] = $r['medal'];
                    $tmp['team'] = htmlspecialchars($r['team']);
                    $tmp['holdtime'] = $r['holdtime'];
                    $recent[] = $tmp;
                }
            }
            $this -> assign('recent', $recent);
        }
        
        $this -> commonassign();
        $this -> assign('number', $data);
	    $this -> display();
    }
}