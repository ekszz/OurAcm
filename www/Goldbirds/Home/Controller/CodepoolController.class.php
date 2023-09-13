<?php
namespace Home\Controller;

class CodepoolController extends BaseController {
    
    protected $module_name = 'CODEPOOL';
    
    private function safe_check($ip) {  //检测IP是否提交量过大
        
        $codepoolDB = M('Codepool');
        $c['ip'] = array('EQ', $ip);
        $count = $codepoolDB 
            -> field('COUNT(codeid) AS c') 
            -> where($c)
            -> where('submittime < "'.date('Y-m-d H:i:s').'" AND submittime > "'.date('Y-m-d H:i:s', time() - 86400).'"')
            -> select();
        
        if($count === false || intval($count[0]['c']) >= intval($this -> getconfig('codepool_maxperip'))) return false;  //提交量太大
        else return true;  //正常
    }
    
    private function k_check($key) {  //验证KEY是否合法，合法返回代码集
        
        $k = strtolower($key);
        if(strlen($k) != 8 || !ctype_xdigit($k))  //KEY本身不合法
            return false;
        else {
            $codepoolDB = M('Codepool');
            $c['k'] = $key;
            $res = $codepoolDB -> field('codeid, tag, code, ojaccount') -> where($c) -> order('codeid ASC') -> select();
            if(!$res) return false;
            else return $res;
        }
    }
    
    public function index() {  //提交页面
        
        if($this -> logincheck() > 0) {     //已登录，显示历史提交
            $ojaccount = \OJLoginInterface::getLoginUser();
            $this -> assign('url', '');
            
            $Codepool = M('Codepool');
            $c['ojaccount'] = $ojaccount;
            $codes = $Codepool -> field('codeid, k, submittime, tag') -> where($c) -> order('submittime DESC') -> select();
            $res = array();
            $hash = array();
            foreach ($codes as $code) {
                if(array_key_exists($code['k'], $hash)) {   //已经出现过了
                    $res[$hash[$code['k']]]['tag'] .= (' / '.htmlspecialchars($code['tag']));
                } else {
                    $id = count($res);
                    $hash[$code['k']] = $id;
                    $res[$id]['k'] = $code['k'];
                    $res[$id]['tag'] = ($code['tag'] == null ? '代码'.sprintf('%06d', $code['codeid']) : htmlspecialchars($code['tag']));
                    $res[$id]['submittime'] = $code['submittime'];
                }
            }
            $this -> assign('codes', $res);
        }
        else $this -> assign('url', \OJLoginInterface::getLoginURL());
        $this -> commonassign();
        $this -> display('index');
    }
    
    public function ajax_submit() {  //提交代码
        
        $k = I('post.k');
        if(!$k) {  //新提交
            $codepoolDB = M('Codepool');
            do {  //查询KEY是否已存在
                $k = substr(md5('goldbirds_seed'.time().rand(1, 65536)), 2, 8);
                $c['k'] = $k;
            }while($codepoolDB -> field('k') -> where($c) -> find());
            
            $dat['k'] = $k;
            $dat['submittime'] = date('Y-m-d H:i:s', time());
            $dat['tag'] = I('post.tag', '', false) == '' ? null : I('post.tag', '', false);
            $dat['code'] = I('post.code', '', false);
            $dat['ip'] = get_client_ip();
            $dat['ojaccount'] = ($this -> logincheck() == 0 ? null : session('goldbirds_oj'));
            
            $verify = new \Think\Verify();
            if(!($verify -> check(I('post.verify', '', false)))) $this -> myajaxReturn(null, '验证码错误。', 1);
            else if(strlen($dat['tag']) > 20) $this -> myajaxReturn(null, '你提交的标签长度太长了。最长20字节。', 2);
            else if(!$this -> safe_check(get_client_ip())) $this -> myajaxReturn(null, '你今日提交的代码太多啦>.<', 4);
            else if(strlen($dat['code']) > intval($this -> getconfig('codepool_maxlength'))) $this -> myajaxReturn(null, '你提交的代码长度太长了。', 2);
            else if(!$dat['code']) $this -> myajaxReturn(null, '你提交的代码是空的-__-||', 3);
            else {
                $codepoolDB = M('Codepool');
                $codepoolDB -> add($dat);
                $this -> myajaxReturn('http://'.$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"].'?z=codepool-x-k-'.$c['k'], '[成功]', 0);
            }
        }
        else {  //追加
            $codepoolDB = M('Codepool');
            //查询KEY是否已存在
            $c['k'] = $k;
            $ret = $this -> k_check($k);
            if(!$ret) $this -> myajaxReturn(null, '无效的KEY。', 5);
            
            $dat['k'] = $k;
            $dat['submittime'] = date('Y-m-d H:i:s', time());
            $dat['tag'] = I('post.tag', '', false) == '' ? null : I('post.tag', '', false);
            $dat['code'] = I('post.code', '', false);
            $dat['ip'] = get_client_ip();
            
            if($this -> logincheck() == 0 || strcmp(session('goldbirds_oj'), $ret[0]['ojaccount']) != 0) $this -> myajaxReturn(null, '该代码不是你提交的。', 6);
            $dat['ojaccount'] = session('goldbirds_oj');
            
            $verify = new \Think\Verify();
            if(!($verify -> check(I('post.verify', '', false)))) $this -> myajaxReturn(null, '验证码错误。', 1);
            else if(strlen($dat['tag']) > 20) $this -> myajaxReturn(null, '你提交的标签长度太长了。最长20字节。', 2);
            else if(!$this -> safe_check(get_client_ip())) $this -> myajaxReturn(null, '你今日提交的代码太多啦>.<', 4);
            else if(strlen($dat['code']) > intval($this -> getconfig('codepool_maxlength'))) $this -> myajaxReturn(null, '你提交的代码长度太长了。', 2);
            else if(!$dat['code']) $this -> myajaxReturn(null, '你提交的代码是空的-__-||', 3);
            else {
                $codepoolDB = M('Codepool');
                $id = $codepoolDB -> add($dat);
                $topage['tag_id'] = 'c'.sprintf('%06d', $id);
                $topage['tag'] = ($dat['tag'] == null ? '代码'.sprintf('%06d', $id) : htmlspecialchars($dat['tag']));
                $topage['code'] = htmlspecialchars($dat['code']); 
                $this -> myajaxReturn($topage, '[成功]', 0);
            }
        }
    }
    
    public function x() {  //提取代码
        
        $k = I('get.k');
        $res = $this -> k_check($k);
        if(false === $res) {
            $this -> assign('invalidkey', 'alert("Oh, NO !", "该URL无效，你需要的资源可能已从地球消失了~~", "error");');
            $this -> index();
        }
        else {
            $data = array();
            foreach($res as $r) {
                $data[$r['codeid']]['tag'] = ($r['tag'] == null ? '代码'.sprintf('%06d', $r['codeid']) : htmlspecialchars($r['tag']));
                $data[$r['codeid']]['tag_id'] = 'c'.sprintf('%06d', $r['codeid']);
                $data[$r['codeid']]['code'] = htmlspecialchars($r['code']);
            }
            
            $this -> assign('data', $data);
            $this -> assign('url', 'http://'.$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"].'?z=codepool-x-k-'.$k);
            $this -> assign('key', $k);
            if($this -> logincheck() > 0 && strcmp($res[0]['ojaccount'], session('goldbirds_oj')) == 0) $this -> assign('issamelogin', 1);
            else $this -> assign('issamelogin', 0);
            $this -> commonassign();
            $this -> display('x');
        }
    }
 
    public function _clean() {  //删除过期代码，计划任务调用
        
        if(strcmp(CODECLEANTOKEN, 'goldbirds') == 0) {
            $codepoolDB = M('Codepool');
            $codepoolDB -> where('submittime < "'.date('Y-m-d H:i:s', time() - intval($this -> getconfig('codepool_exptime'))).'"') -> delete();
        }
    }
}
