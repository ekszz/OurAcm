<?php
namespace Home\Controller;

class TalkController extends BaseController {
    
    private $talk_idx;  //递规生成Talk列表用
    private $talk_arr;
    
    static private $var_title_minlen = 5;  //标题最短值
    static private $var_title_maxlen = 100;  //标题最长值
    static private $var_content_maxlen = 4096;  //内容最长值
    static private $var_num_perpage = 10;  //每页数量
    static private $var_newlogo_second = 7200;  //显示新回复标志的时间差（秒）
    
    public function index() {
        
        $page = intval(I('get.p', '', false));
        if($page <= 1) $page = 1;
        
        $pid = intval(I('get.pid', '', false));
        if($pid > 9999 || $pid < 1000) $pid = 0;
        
        $talkDB = M('Talk');
        if($pid == 0) $res = $talkDB -> field('ptid, MAX(createtime) AS t') -> distinct('ptid') -> order('t DESC') -> group('ptid') -> limit(($page - 1 ) * TalkController::$var_num_perpage, TalkController::$var_num_perpage) -> select();
        else $res = $talkDB -> field('ptid, MAX(createtime) AS t') -> where('problemid = '.$pid) -> distinct('ptid') -> order('t DESC') -> group('ptid') -> limit(($page - 1 ) * TalkController::$var_num_perpage, TalkController::$var_num_perpage) -> select();
        
        if(count($res) == 0) {
            $page = 1;  //没有记录了，置为第一页
            if($pid == 0) $res = $talkDB -> field('ptid, MAX(createtime) AS t') -> distinct('ptid') -> order('t DESC') -> group('ptid') -> limit(($page - 1 ) * TalkController::$var_num_perpage, TalkController::$var_num_perpage) -> select();
            else $res = $talkDB -> field('ptid, MAX(createtime) AS t') -> where('problemid = '.$pid) -> distinct('ptid') -> order('t DESC') -> group('ptid') -> limit(($page - 1 ) * TalkController::$var_num_perpage, TalkController::$var_num_perpage) -> select();
        }
       
        $dat = '';
        foreach ($res as $r) {
            $this -> talk_arr = $this -> gettalk(intval($r['ptid']));
            $this -> talk_idx = 0;
            $dat .= $this -> buildtalk(0);
        }
        $this -> assign('talk', $dat ? $dat : '<center><li>暂时没有相关的Talk T_T</li></center>');
        $this -> assign('page', $page);
        $this -> assign('pid', $pid);
        if($this -> logincheck() > 0) $this -> assign('ojaccount', \OJLoginInterface::getLoginUser());
        else $this -> assign('url', \OJLoginInterface::getLoginURL());
        $this -> commonassign();
        $this -> display('index');
    }
    
    public function msg() {  //单条显示页面
        
        $tid = intval(I('get.tid', '', false));
        
        $talkDB = M('Talk');
        $res = $talkDB -> where('tid = '.$tid) -> find();
        
        if(!$res) {
            $this -> index();
        }
        else {
            $this -> commonassign();
            if($this -> logincheck() > 0) $this -> assign('ojaccount', \OJLoginInterface::getLoginUser());
            else $this -> assign('url', \OJLoginInterface::getLoginURL());
            $this -> assign('tid', $tid);
            $this -> assign('title', htmlspecialchars($res['title']));
            $this -> assign('introduce', '<p><span class="text-warning"><strong>'
                .strlen($res['content']).'B</strong></span> BY <strong>'
                .(\OJLoginInterface::getUserUrl($res['ojaccount']) == null ? '<span class="text-primary">'.htmlspecialchars($res['ojaccount']).'</span>' : '<a href="'.\OJLoginInterface::getUserUrl($res['ojaccount']).'"><span class="text-primary">'.htmlspecialchars($res['ojaccount']).'</span></a>')
                .'</strong></span> @ <span class="text-muted">'
                .$res['createtime'].'</span>'.
                ($res['problemid'] ? ' IN <strong>'.(\OJLoginInterface::getProblemUrl($res['problemid']) == null ? '<span class="text-danger">Problem '.$res['problemid'].'</span>' : '<a href="'.\OJLoginInterface::getProblemUrl($res['problemid']).'"><span class="text-danger">Problem '.$res['problemid'].'</span></a>').'</strong>' : '')
                .(time() - strtotime($res['createtime']) <= TalkController::$var_newlogo_second ? ' <span class="label label-warning">New</span>' : '')
                .'</p>');
            if($res['content']) $this -> assign('content', '<pre style="font-size:16px">'.htmlspecialchars($res['content']).'</pre>');
            if(intval($res['lft']) + 1 < intval($res['rgt'])) {
                $this -> talk_arr = $this -> gettalk(intval($res['tid']));
                $this -> talk_idx = 0;
                $son = $this -> buildtalk(0);
                $this -> assign('son', $son);
            }
            $this -> display('msg');
        }
    }
    
    public function ajax_postnewtalk() {  //AJAX提交新talk
        
        if($this -> logincheck() == 0) $this -> myajaxReturn(null, '你还未登录。', 1);
        else {  //已登录
            $verify = new \Think\Verify();
            if(!($verify -> check(I('post.verify', '', false))))
                $this -> myajaxReturn(null, '验证码错误。', 1);
            
            $title = I('post.title', '', false);
            if(strlen($title) < TalkController::$var_title_minlen) $this -> myajaxReturn(null, '标题长度不能小于'.TalkController::$var_title_minlen.'。', 2);
            if(strlen($title) > TalkController::$var_title_maxlen) $this -> myajaxReturn(null, '标题长度不能大于'.TalkController::$var_title_maxlen.'。', 2);
            
            $content = I('post.content', '', false);
            if(strlen($content) > TalkController::$var_content_maxlen) $this -> myajaxReturn(null, '内容太长了-_-', 2);
            if(strlen($content) == 0) $content = null;
            
            $pid = intval(I('post.pid', '', false));
            if($pid == 0 || 1000 > $pid || $pid > 9999) $pid = null;
            
            if($this -> addtalk($title, \OJLoginInterface::getLoginUser(), $content, $pid))
                $this -> myajaxReturn($this -> loadlastesttalk(), '[成功]', 0);
            else $this -> myajaxReturn(null, '提交失败。', 3);
        }
    }
    
    public function ajax_replytalk() {  //回复talk
        
        if($this -> logincheck() == 0) $this -> myajaxReturn(null, '你还未登录。', 1);
        else {  //已登录
            $verify = new \Think\Verify();
            if(!($verify -> check(I('post.verify', '', false))))
                $this -> myajaxReturn(null, '验证码错误。', 1);
        
            $title = I('post.title', '', false);
            if(strlen($title) < TalkController::$var_title_minlen) $this -> myajaxReturn(null, '标题长度不能小于'.TalkController::$var_title_minlen.'。', 2);
            if(strlen($title) > TalkController::$var_title_maxlen) $this -> myajaxReturn(null, '标题长度不能大于'.TalkController::$var_title_maxlen.'。', 2);
        
            $content = I('post.content', '', false);
            if(strlen($content) > TalkController::$var_content_maxlen) $this -> myajaxReturn(null, '内容太长了-_-', 2);
            if(strlen($content) == 0) $content = null;
        
            $tid = intval(I('post.tid', '', false));
        
            if($this -> addson($tid, $title, \OJLoginInterface::getLoginUser(), $content))
                $this -> myajaxReturn(null, '[成功]', 0);
            else $this -> myajaxReturn(null, '回复失败。', 3);
        }
    }
    
    private function loadlastesttalk() {
        $talkDB = M('Talk');
        $res = $talkDB -> field('ptid, MAX(createtime) AS t') -> distinct('ptid') -> order('t DESC') -> group('ptid') -> limit(TalkController::$var_num_perpage) -> select();
        $dat = '';
        foreach ($res as $r) {
            $this -> talk_arr = $this -> gettalk(intval($r['ptid']));
            $this -> talk_idx = 0;
            $dat .= $this -> buildtalk(0);
        }
        return ($dat ? $dat : '<center><li>暂时没有相关的Talk T_T</li></center>');
    }
    
    private function addtalk($title, $ojaccount, $content = null, $pid = null) {  //添加一条顶级条目，函数不检查传入参数
        
        $talkDB = M('Talk');
        $talkDB -> execute('BEGIN');  //事务开始
        
        $data['ptid'] = 0;
        $data['ojaccount'] = $ojaccount;
        $data['title'] = $title;
        $data['content'] = $content;
        $data['createtime'] = date("Y-m-d H:i:s");
        $data['problemid'] = (intval($pid) == 0 ? null : intval($pid));
        $data['lft'] = 1;
        $data['rgt'] = 2;
        $data['ip'] = get_client_ip();
        
        $id = $talkDB -> add($data);
        if(!$id) {
            $talkDB -> execute('ROLLBACK');
            return false;
        }

        $id = $talkDB -> where('tid = '.intval($id)) -> setField('ptid', $id);

        if($id) {
            $talkDB -> execute('COMMIT');
            return true;
        }
        else {
            $talkDB -> execute('ROLLBACK');
            return false;
        }
    }
    
    private function addson($nowid, $title, $ojaccount, $content = null) {  //添加回复（子节点），函数不检查传入参数
        
        $talkDB = M('Talk');
        $talkDB -> execute('BEGIN');  //事务开始
        
        $p = $talkDB -> field('ptid, lft, problemid') -> where('tid = '.intval($nowid)) -> find();
        if(!$p) return false;
        
        $talkDB -> where('ptid = '.intval($p['ptid'])) -> lock(true) -> select();  //锁定同主题条目
        
        $talkDB -> where('ptid = '.intval($p['ptid']).' AND rgt > '.intval($p['lft'])) -> setInc('rgt', 2);
        $talkDB -> where('ptid = '.intval($p['ptid']).' AND lft > '.intval($p['lft'])) -> setInc('lft', 2);
        
        $data['ptid'] = intval($p['ptid']);
        $data['ojaccount'] = $ojaccount;
        $data['title'] = $title;
        $data['content'] = $content;
        $data['createtime'] = date("Y-m-d H:i:s");
        $data['problemid'] = (intval($p['problemid']) == 0 ? null : intval($p['problemid']));
        $data['lft'] = intval($p['lft']) + 1;
        $data['rgt'] = intval($p['lft']) + 2;
        $data['ip'] = get_client_ip();
        
        $res = $talkDB -> add($data);
        if($res) {
            $talkDB -> execute('COMMIT');
            return true;
        }
        else {
            $talkDB -> execute('ROLLBACK');
            return false;
        }
    }
    
    private function gettalk($nowid) {  //根据$nowid获取该节点及所有子节点的树状结构数组
        
        $talkDB = M('Talk');
        $p = $talkDB -> field('ptid, lft, rgt') -> where('tid = '.intval($nowid)) -> find();
        $res = $talkDB -> query("SELECT (COUNT(parent.tid) - 1) AS level, node.tid, node.title, node.problemid, LENGTH(node.content) AS l, node.ojaccount, node.createtime 
            FROM talk AS node,
            talk AS parent
            WHERE node.ptid = %d 
            AND parent.ptid = %d 
            AND (parent.lft BETWEEN %d AND %d) 
            AND (parent.rgt BETWEEN %d AND %d) 
            AND (node.lft BETWEEN parent.lft AND parent.rgt) 
            GROUP BY node.tid
            ORDER BY node.lft", intval($p['ptid']), intval($p['ptid']), intval($p['lft']), intval($p['rgt']), intval($p['lft']), intval($p['rgt']));
        return $res;
    }
    
    private function buildtalk($level) {  //根据全局变量归生成最终HTML
        
        //<ul>标签
        if($level == 0) {
            $str = '<blockquote>';
        }
        else {
            $str = '<ul>';
        }
        
        while(true) {
            if($this -> talk_idx + 1 <= count($this -> talk_arr) - 1 
            && intval($this -> talk_arr[$this -> talk_idx + 1]['level']) == $level) {  //下一条还是当前层
                $str .= '<li>'.$this -> buildtalk_perhtml($this -> talk_idx).'</li>';
                $this -> talk_idx ++;
            }
            else if($this -> talk_idx + 1 <= count($this -> talk_arr) - 1 
            && intval($this -> talk_arr[$this -> talk_idx + 1]['level']) > $level) {  //下一条是下一层
                $str .= '<li>'.$this -> buildtalk_perhtml($this -> talk_idx);
                $this -> talk_idx ++;
                $str .= $this -> buildtalk($level + 1);
                $str .= '</li>';
            }
            else if($this -> talk_idx + 1 <= count($this -> talk_arr) - 1 
            && intval($this -> talk_arr[$this -> talk_idx + 1]['level']) < $level) {  //下一条是上一层
                $str .= '<li>'.$this -> buildtalk_perhtml($this -> talk_idx).'</li>';
                $this -> talk_idx ++;
                if($level == 0) {
                    $str .= '</blockquote>';
                }
                else {
                    $str .= '</ul>';
                }
                return $str;
            }
            else if($this -> talk_idx + 1 == count($this -> talk_arr)) {  //最后一个
                $str .= '<li>'.$this -> buildtalk_perhtml($this -> talk_idx).'</li>';
                $this -> talk_idx ++;
                if($level == 0) {
                    $str .= '</blockquote>';
                }
                else {
                    $str .= '</ul>';
                }
                return $str;
            }
            else if($this -> talk_idx + 1 > count($this -> talk_arr)) {  //已结束，补全</ul>
                if($level == 0) {
                    $str .= '</blockquote>';
                }
                else {
                    $str .= '</ul>';
                }
                return $str;
            }
        }
    }
    
    private function buildtalk_perhtml($idx) {  //生成每条talk的html
        return '<a style="color:black" href="?z=talk-msg-tid-'.$this -> talk_arr[$idx]['tid'].'">'.htmlspecialchars($this -> talk_arr[$idx]['title'])
        .'</a> <small style="display:inline">(<span class="text-warning"><strong>'
        .intval($this -> talk_arr[$idx]['l']).'B</strong></span>) BY <strong>'
        .(\OJLoginInterface::getUserUrl($this -> talk_arr[$idx]['ojaccount']) == null ? '<span class="text-primary">'.htmlspecialchars($this -> talk_arr[$idx]['ojaccount']).'</span>' : '<a href="'.\OJLoginInterface::getUserUrl($this -> talk_arr[$idx]['ojaccount']).'"><span class="text-primary">'.htmlspecialchars($this -> talk_arr[$idx]['ojaccount']).'</span></a>')
        .'</strong> @ <span class="text-muted">'
        .$this -> talk_arr[$idx]['createtime'].'</span>'.
        ($this -> talk_arr[$idx]['problemid'] ? ' IN <strong>'.(\OJLoginInterface::getProblemUrl($this -> talk_arr[$idx]['problemid']) == null ? '<span class="text-danger">Problem '.$this -> talk_arr[$idx]['problemid'].'</span>' : '<a href="'.\OJLoginInterface::getProblemUrl($this -> talk_arr[$idx]['problemid']).'"><span class="text-danger">Problem '.$this -> talk_arr[$idx]['problemid'].'</span></a>').'</strong>' : '')
        .(time() - strtotime($this -> talk_arr[$idx]['createtime']) <= TalkController::$var_newlogo_second ? ' <span class="label label-warning">New</span>' : '')
        .'</small>';
    }
}