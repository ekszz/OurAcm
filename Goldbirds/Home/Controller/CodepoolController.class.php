<?php
namespace Home\Controller;

class CodepoolController extends BaseController {
    
    public function index() {
        
        if(IS_GET) {  //查看代码
            
            $k = I('get.key');
            $k = strtolower($k);
            if(strlen($k) != 8 || !ctype_xdigit($k)) $this -> index_show(null);
            else $this -> index_show($k);
        }
        else if(IS_POST) {  //提交代码
            
            $k = I('get.key');
            $k = strtolower($k);
            if(strlen($k) != 8 || !ctype_xdigit($k)) $this -> index_show(null);
            else {
                $dat['k'] = $k;
                $dat['exptime'] = date('Y-m-d H:i:s', time() + intval($this -> getconfig('codepool_exptime')));  //过期时间
                $dat['tag'] = I('post.tag', '', false) == '' ? null : I('post.tag', false, '');
                $dat['code'] = I('post.code', '', false);
                $dat['ip'] = get_client_ip();

                $verify = new \Think\Verify();
                if(!($verify -> check(I('post.verify', '', false)))) $this -> index_show($k, '[错误]验证码错误。');
                else if(strlen($dat['code']) > intval($this -> getconfig('codepool_maxlength'))) $this -> index_show($k, '[错误]你提交的代码长度太长了。');
                else if(!$dat['code']) $this -> index_show($k, '[错误]你提交的代码是空的-__-||');
                else {
                    $codepoolDB = M('Codepool');
                    $codepoolDB -> add($dat);
                    $this -> index_show($k);
                }
            }
        }
    }
    
    private function index_show($k, $err = '') {  //传入的$k需保证合法，8位十六进制字符串
        
        $data = array();
        if($k) {
            $c['k'] = $k;
            $codepoolDB = M('Codepool');
            $res = $codepoolDB -> where($c) -> order('codeid ASC') -> select();
            
            foreach($res as $r) {
                $data[$r['codeid']]['tag'] = ($r['tag'] == null ? '代码'.sprintf('%06d', $r['codeid']) : htmlspecialchars($r['tag']));
                $data[$r['codeid']]['tag_id'] = 'c'.sprintf('%06d', $r['codeid']);
                $data[$r['codeid']]['code'] = htmlspecialchars($r['code']);
            }
        }
        
        $this -> assign('data', $data);
        if($data) $this -> assign('url', 'http://'.$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"].'?z=codepool-index-key-'.$c['k']);
        if($data) $this -> assign('key', $c['k']);
        else $this -> assign('key', substr(md5('goldbirds_seed'.time().rand(1, 65536)), 2, 8));
        if($err) $this -> assign('err', $err);
        $this -> commonassign();
        $this -> display();
    }
    
    public function verify() {  //生成验证码
        
        $config =    array(
            'fontSize'    =>    15,    // 验证码字体大小
            'length'      =>    4,     // 验证码位数
            'useCurve'    =>    false, // 关闭验证码杂点
            'codeSet'     =>    '01', //字符集
        );
        
        $Verify = new \Think\Verify($config);
        $Verify->entry();
    }
    
    public function _clean() {  //删除过期代码，计划任务调用
        
        if(strcmp(CODECLEANTOKEN, 'goldbirds') == 0) {
            $codepoolDB = M('Codepool');
            $codepoolDB -> where('exptime < "'.date('Y-m-d H:i:s').'"') -> delete();
        }
    }
}
