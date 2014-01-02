<?php
class WfAction extends BaseAction {
    public function index() {  //酷炫版视图
        if(intval($this -> getconfig('config_contest_default_show')) == 0) $this -> data();
        else $this -> cool();
    }
    
    private function getYears() { //获取年份信息
        $contest = M('contest');
        $years = $contest -> distinct(true) -> field('YEAR(holdtime) AS y') -> where('type = 0') -> group('y') -> order('y DESC') -> select();
        return $years;
    }
    
    public function cool() {  //酷炫版
        
        $years = $this -> getYears();
        $this -> commonassign();
        if(!$years) $this -> display('nodata');
        else {
            $this -> assign('y', $years);
            $this -> assign('nowyear', 9999);
            $contest = D('Contest');
            $data = $contest -> relation(true) -> field('*, YEAR(holdtime) AS y, MONTH(holdtime) AS m') -> where('type=0') -> order('holdtime DESC') -> select();
            
            //如果不存在缩略图，则生成
            import('ORG.Util.Image');
            foreach($data as $d) {
                if($d['pic1'] && !file_exists('upload/thumb/'.substr($d['pic1'], 7))) {
                    Image::thumb($d['pic1'], 'upload/thumb/'.substr($d['pic1'], 7), '', 576, 360, false);
                }
                if($d['pic2'] && !file_exists('upload/thumb/'.substr($d['pic2'], 7))) {
                    Image::thumb($d['pic2'], 'upload/thumb/'.substr($d['pic2'], 7), '', 576, 360, false);
                }
            }
            
            $this -> assign('data', $data);
            $this -> display('cool');
        }
    }
    
    public function data() {  //表单版视图
        
        $contest = D('Contest');
        $data = $contest -> relation(true) -> field('*, YEAR(holdtime) AS y, MONTH(holdtime) AS m') -> where('type=0') -> order('holdtime DESC') -> select();
		if(!$data) $this -> display('nodata');
		else {
			$this -> assign('data', $data);
			$this -> commonassign();
			$this -> display('data');
		}
    }
}