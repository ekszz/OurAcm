<?php
class CoachAction extends BaseAction {
    public function index() {
        $personDB = M('Person');
        $this -> commonassign();
        $data = $personDB -> field('uid, chsname, engname, photo, introduce, detail, md5(luckycode) AS id') -> where('`group`=2') -> order('uid ASC') -> select();
        if($data) {  //有数据
            $this -> assign('data', $data);
            $this -> display();
        }
        else {  //无数据
            $this -> display('nodata');
        }
    }
}