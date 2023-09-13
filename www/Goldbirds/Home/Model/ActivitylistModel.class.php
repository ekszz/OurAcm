<?php
namespace Home\Model;
use Think\Model\RelationModel;

class ActivitylistModel extends RelationModel{
    protected $_link = array(
        'Admin' => array(
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'Person',
            'foreign_key' => 'adminuid',
            'mapping_name' => 'admin_detail',
        )
    );
    protected $_validate = array(
        array('ispublic', array(0,1), '是否公开注册信息参数异常:(', self::EXISTS_VALIDATE, 'in', self::MODEL_BOTH),
        array('isinner', array(0,1), '是否队内可见参数异常:(', self::EXISTS_VALIDATE, 'in', self::MODEL_BOTH),
        array('isneedreview', array(0,1), '是否需要审核参数异常:(', self::EXISTS_VALIDATE, 'in', self::MODEL_BOTH),
        array('title', '1,199', '标题也太不正常了吧= =<br />需要长度1-199。', self::MUST_VALIDATE, 'length', self::MODEL_BOTH),
    );
}
