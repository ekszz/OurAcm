<?php
class OtherAction extends BaseAction {
    
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
            if(intval($this -> getconfig('config_contest_sort'))) {  //按奖项优先排序
                $data = $contestDB -> relation(true) -> field('*, YEAR(holdtime) AS y, MONTH(holdtime) AS m') -> where('type = 2 AND YEAR(holdtime) ='.$year) -> order('medal ASC, holdtime DESC, team ASC') -> select();
            }
            else {  //按时间优先排序
                $data = $contestDB -> relation(true) -> field('*, YEAR(holdtime) AS y, MONTH(holdtime) AS m') -> where('type = 2 AND YEAR(holdtime) ='.$year) -> order('holdtime DESC, medal ASC, team ASC') -> select();
            }
            
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
    
    private function getYears() { //获取年份信息
        $contest = M('contest');
        $years = $contest -> distinct(true) -> field('YEAR(holdtime) AS y') -> where('type = 2') -> group('y') -> order('y DESC') -> select();
        return $years;
    }
    
    public function data() {  //表单版
        
        $years = $this -> getYears();
        if(!$years) $this -> display('nodata');
        else {
            $this -> assign('y', $years);
            
            $contestDB = D('Contest');
            if(intval($this -> getconfig('config_contest_sort'))) {  //按奖项优先排序
                $oridata = $contestDB -> relation(true) -> field('*, YEAR(holdtime) AS y, MONTH(holdtime) AS m') -> where('type = 2') -> order('medal ASC, holdtime DESC, team ASC') -> select();
            }
            else {  //按时间优先排序
                $oridata = $contestDB -> relation(true) -> field('*, YEAR(holdtime) AS y, MONTH(holdtime) AS m') -> where('type = 2') -> order('holdtime DESC, medal ASC, team ASC') -> select();
            }
            $data = array();
            foreach ($oridata as $v) {
                $data[$v['y']][] = $v;
            }
            $this -> assign('data', $data);
            $this -> commonassign();
            $this -> display('data');
        }
    }
}