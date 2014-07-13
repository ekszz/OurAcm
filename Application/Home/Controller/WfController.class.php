<?php
namespace Home\Controller;

class WfController extends BaseController {
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
            for($i = 0; $i < count($data); $i++) {
                $data[$i]['site'] = htmlspecialchars($data[$i]['site']);
                $data[$i]['university'] = htmlspecialchars($data[$i]['university']);
                $data[$i]['title'] = htmlspecialchars($data[$i]['title']);
                $data[$i]['team'] = htmlspecialchars($data[$i]['team']);
                
                //如果不存在缩略图，则生成
                if($data[$i]['pic1'] && !file_exists('upload/thumb/'.substr($data[$i]['pic1'], 7))) {
                    $image = new \Think\Image();
                    $image -> open($data[$i]['pic1']) -> thumb(576, 360) -> save('upload/thumb/'.substr($data[$i]['pic1'], 7));
                }
                if($data[$i]['pic2'] && !file_exists('upload/thumb/'.substr($data[$i]['pic2'], 7))) {
                    $image = new \Think\Image();
                    $image -> open($data[$i]['pic2']) -> thumb(576, 360) -> save('upload/thumb/'.substr($data[$i]['pic2'], 7));
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
		    for($i = 0; $i < count($data); $i++) {
		        $data[$i]['site'] = htmlspecialchars($data[$i]['site']);
                $data[$i]['university'] = htmlspecialchars($data[$i]['university']);
                $data[$i]['title'] = htmlspecialchars($data[$i]['title']);
                $data[$i]['team'] = htmlspecialchars($data[$i]['team']);
		    }
			$this -> assign('data', $data);
			$this -> commonassign();
			$this -> display('data');
		}
    }
}