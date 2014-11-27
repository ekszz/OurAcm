<?php
namespace Home\Controller;

class RegionalController extends BaseController {
    
    public function index() {
        if(intval($this -> getconfig('config_contest_default_show')) == 0) $this -> data();
        else $this -> cool();
    }
    
    public function cool() {  //酷炫版
        $year = intval(I('get.y'));
        $years = $this -> getYears();
        $this -> commonassign();
        if(!$years) $this -> display('nodata');
        else {
            $contestDB = D('Contest');
            if($year <= 1960 || $year > 2037) {
                $data = $contestDB -> field('MAX(YEAR(holdtime)) AS year') -> where('type = 1') -> find();
                $year = $data['year'];
            }
            $this -> assign('y', $years);
            $this -> assign('year', $year);
            if(intval($this -> getconfig('config_contest_sort'))) {  //按奖项优先排序
                $data = $contestDB -> relation(true) -> field('*, YEAR(holdtime) AS y, MONTH(holdtime) AS m') -> where('type = 1 AND YEAR(holdtime) ='.$year) -> order('medal ASC, holdtime DESC, team ASC') -> select();
            }
            else {  //按时间优先排序
                $data = $contestDB -> relation(true) -> field('*, YEAR(holdtime) AS y, MONTH(holdtime) AS m') -> where('type = 1 AND YEAR(holdtime) ='.$year) -> order('holdtime DESC, medal ASC, team ASC') -> select();
            }
            
            import('ORG.Util.Image');
            for($i = 0; $i < count($data); $i++) {
                $data[$i]['site'] = htmlspecialchars($data[$i]['site']);
                $data[$i]['university'] = htmlspecialchars($data[$i]['university']);
                $data[$i]['title'] = htmlspecialchars($data[$i]['title']);
                $data[$i]['team'] = htmlspecialchars($data[$i]['team']);
                $data[$i]['leader_detail']['chsname'] = htmlspecialchars($data[$i]['leader_detail']['chsname']);
                $data[$i]['leader_detail']['engname'] = htmlspecialchars($data[$i]['leader_detail']['engname']);
                $data[$i]['teamer1_detail']['chsname'] = htmlspecialchars($data[$i]['teamer1_detail']['chsname']);
                $data[$i]['teamer1_detail']['engname'] = htmlspecialchars($data[$i]['teamer1_detail']['engname']);
                $data[$i]['teamer2_detail']['chsname'] = htmlspecialchars($data[$i]['teamer2_detail']['chsname']);
                $data[$i]['teamer2_detail']['engname'] = htmlspecialchars($data[$i]['teamer2_detail']['engname']);
                
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
    
    private function getYears() { //获取年份信息
        $contest = M('contest');
        $years = $contest -> distinct(true) -> field('YEAR(holdtime) AS y') -> where('type = 1') -> group('y') -> order('y DESC') -> select();
        return $years;
    }
    
    public function data() {  //表单版
        $years = $this -> getYears();
        if(!$years) $this -> display('nodata');
        else {
            $this -> assign('y', $years);
            
            $contestDB = D('Contest');
            if(intval($this -> getconfig('config_contest_sort'))) {  //按奖项优先排序
                $oridata = $contestDB -> relation(true) -> field('*, YEAR(holdtime) AS y, MONTH(holdtime) AS m') -> where('type = 1') -> order('y DESC, medal ASC, holdtime DESC, team ASC') -> select();
            }
            else {  //按时间优先排序
                $oridata = $contestDB -> relation(true) -> field('*, YEAR(holdtime) AS y, MONTH(holdtime) AS m') -> where('type = 1') -> order('holdtime DESC, medal ASC, team ASC') -> select();
            }
            for($i = 0; $i < count($oridata); $i++) {
                $oridata[$i]['site'] = htmlspecialchars($oridata[$i]['site']);
                $oridata[$i]['university'] = htmlspecialchars($oridata[$i]['university']);
                $oridata[$i]['title'] = htmlspecialchars($oridata[$i]['title']);
                $oridata[$i]['team'] = htmlspecialchars($oridata[$i]['team']);
                $oridata[$i]['leader_detail']['chsname'] = htmlspecialchars($oridata[$i]['leader_detail']['chsname']);
                $oridata[$i]['leader_detail']['engname'] = htmlspecialchars($oridata[$i]['leader_detail']['engname']);
                $oridata[$i]['teamer1_detail']['chsname'] = htmlspecialchars($oridata[$i]['teamer1_detail']['chsname']);
                $oridata[$i]['teamer1_detail']['engname'] = htmlspecialchars($oridata[$i]['teamer1_detail']['engname']);
                $oridata[$i]['teamer2_detail']['chsname'] = htmlspecialchars($oridata[$i]['teamer2_detail']['chsname']);
                $oridata[$i]['teamer2_detail']['engname'] = htmlspecialchars($oridata[$i]['teamer2_detail']['engname']);
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