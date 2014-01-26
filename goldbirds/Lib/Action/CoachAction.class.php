<?php
class CoachAction extends BaseAction {
    public function index() {
        $personDB = M('Person');
        $this -> commonassign();
        $data = $personDB -> field('uid, chsname, engname, photo, introduce, detail, md5(luckycode) AS id') -> where('`group`=2') -> order('uid ASC') -> select();
        if($data) {  //有数据
            for($i = 0; $i < count($data); $i++) {
                $data[$i]['introduce'] = htmlspecialchars($data[$i]['introduce']);
                $data[$i]['detail'] = htmlspecialchars($data[$i]['detail']);
            }
            $this -> assign('data', $data);
            $this -> display();
        }
        else {  //无数据
            $this -> display('nodata');
        }
    }
}