<?php
namespace Home\Model;
use Think\Model;

class OjhistoryModel extends Model{
    protected $_validate = array(
        array('mainname', '1,32', 'OJ版本描述长度不正确！', self::EXISTS_VALIDATE, 'length', self::MODEL_BOTH),
        array('devname', '1,32', '开发代号长度不正确！', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
    );
}