<?php
namespace Home\Controller;

class WeController extends BaseController {
    
    public function index() {
        
        $empty = true;
        $data = array();
        
        if($this -> getconfig('we_icpc_introduce')) {
            $empty = false;
            $data['icpc']['title'] = 'ACM-ICPC赛事简介';
            $data['icpc']['content'] = $this -> getconfig('we_icpc_introduce');
        }
        if($this -> getconfig('we_team_introduce')) {
            $empty = false;
            $data['team']['title'] = 'ACM-ICPC集训队简介';
            $data['team']['content'] = $this -> getconfig('we_team_introduce');
        }

        $this -> commonassign();
        
        if($empty) $this -> display('nodata');
	    else {
	        $this -> assign('data', $data);
	        $this -> display('index');
	    }
    }
}