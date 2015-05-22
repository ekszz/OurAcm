<?php
namespace Home\Model;
use Think\Model;

class PersonModel extends Model{
    protected $_validate = array(
        array('chsname', '1,20', '必须输入中文姓名！', self::EXISTS_VALIDATE, 'length', self::MODEL_BOTH),
        array('engname', '2,64', '英文姓名必须小于64位！', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
        array('email', 'email', 'Email格式不正确！', self::VALUE_VALIDATE),
        array('phone', '7,20', '联系电话长度不正确', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
        array('address', '1,64', '你住在火星么，地址这么长……', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
        array('sex', array(0,1), '没有你这个性别:(', self::VALUE_VALIDATE, 'in', self::MODEL_BOTH),
        array('grade', '2000,2099', '年级不太对。', self::VALUE_VALIDATE, 'between', self::MODEL_BOTH),
        array('introduce', '1,512', '自我介绍长度过长。', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
        array('ojaccount', '1,32', 'OJ账号太长了吧。', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
        array('luckycode', '16', '邀请码格式不对。', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
    );
}