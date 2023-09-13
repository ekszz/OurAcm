<?php
namespace Home\Controller;

class WeController extends BaseController {
    
    protected $module_name = 'WE';
    
    public function index() {
        
        $empty = true;
        $data = array();
        
        if($this -> getconfig('we_team_introduce')) {
            $empty = false;
            $data['team']['title'] = 'ACM-ICPC集训队简介';
            $data['team']['content'] = $this -> getconfig('we_team_introduce');
        }
        if($this -> getconfig('we_icpc_introduce')) {
            $empty = false;
            $data['icpc']['title'] = 'ACM-ICPC赛事简介';
            $data['icpc']['content'] = $this -> getconfig('we_icpc_introduce');
        }

        $this -> commonassign();
        
        if($empty) {
            $data['team']['title'] = 'ACM-ICPC集训队简介';
            $data['team']['content'] = '<div class="alert alert-success alert-block">
                <p>队长还没有添加神秘的ACM-ICPC集训队的介绍，你可以发个邮件催他赶紧添加 o(-"-)o</p>
                <p>或者先来 <a href="?z=coach" class="btn btn-small btn-success">教练团队</a> 和 <a href="?z=oj" class="btn btn-small btn-success">OnlineJudge历史</a> 看看吧~</p>
                </div>';
        }
	    $this -> assign('data', $data);
	    $this -> display('index');
    }
}