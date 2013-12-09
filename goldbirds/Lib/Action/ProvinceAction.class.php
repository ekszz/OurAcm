<?php
class ProvinceAction extends BaseAction {
    
    public function index() {
        if(intval($this -> getconfig('config_contest_default_show')) == 0) $this -> data();
        else $this -> cool();
    }
    
    public function cool() {  //酷炫版
        $year = intval($this -> _get('y'));
        $years = $this -> getYears();
        $this -> commonassign();
        if(!$years) $this -> display('nodata');
        else {
            $contestDB = D('Contest');
            if($year <= 1960 || $year > 2037) {
                $data = $contestDB -> field('MAX(YEAR(holdtime)) AS year') -> where('type = 2') -> find();
                $year = $data['year'];
            }
            $this -> assign('y', $years);
            $this -> assign('year', $year);
            $data = $contestDB -> relation(true) -> field('*, YEAR(holdtime) AS y, MONTH(holdtime) AS m') -> where('type = 2 AND YEAR(holdtime) ='.$year) -> order('holdtime DESC') -> select();
            $this -> assign('data', $data);
            $this -> display('cool');
        }
    }
    
    private function getYears() { //获取年份信息
        $contest = M('contest');
        $years = $contest -> distinct(true) -> field('YEAR(holdtime) AS y') -> where('type = 2') -> group('y') -> order('y DESC') -> select();
        return $years;
    }
    
    public function data() {  //表单版
        $years = $this -> getYears();
        $this -> assign('y', $years);
        
        $contestDB = D('Contest');
        $oridata = $contestDB -> relation(true) -> field('*, YEAR(holdtime) AS y, MONTH(holdtime) AS m') -> where('type = 2') -> order('holdtime DESC') -> select();
        $data = array();
        foreach ($oridata as $v) {
            $data[$v['y']][] = $v;
        }
        $this -> assign('data', $data);
        $this -> commonassign();
        $this -> display('data');
    }
}