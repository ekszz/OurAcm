<?php
namespace Home\Model;
use Think\Model;

class PersonModel extends Model{
    protected $_validate = array(
        array('chsname', '1,20', '[错误]必须输入中文姓名！', self::EXISTS_VALIDATE, 'length', self::MODEL_BOTH),
        array('engname', '2,64', '[错误]英文姓名必须小于64位！', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
        array('email', 'email', '[错误]Email格式不正确！', self::VALUE_VALIDATE),
        array('phone', '7,20', '[错误]联系电话长度不正确', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
        array('address', '1,64', '[错误]你住在火星么，地址这么长……', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
        array('sex', array(0,1), '[错误]没有你这个性别:(', self::VALUE_VALIDATE, 'in', self::MODEL_BOTH),
        array('grade', '2000,2099', '[错误]年级不太对。', self::VALUE_VALIDATE, 'between', self::MODEL_BOTH),
        array('introduce', '1,512', '[错误]自我介绍长度过长。', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
        array('ojaccount', '1,32', '[错误]OJ账号太长了吧。', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
        array('luckycode', '16', '[错误]邀请码格式不对。', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
    );
}