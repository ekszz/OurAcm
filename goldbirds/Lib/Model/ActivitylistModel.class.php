<?php
class ActivitylistModel extends RelationModel{
    protected $_link = array(
        'Admin' => array(
            'mapping_type' => BELONGS_TO,
            'class_name' => 'Person',
            'foreign_key' => 'adminuid',
            'mapping_name' => 'admin_detail',
        )
    );
    protected $_validate = array(
        //array('deadline', '1950-1-1,2099-12-31', '[错误]日期不是正常的地球历！你确定你来自地球么？', Model::EXISTS_VALIDATE, 'expire', Model::MODEL_BOTH),  //THINKPHP的BUG，该行验证为当前时间的验证
        array('ispublic', array(0,1), '[错误]是否公开注册信息参数异常:(', Model::EXISTS_VALIDATE, 'in', Model::MODEL_BOTH),
        array('isinner', array(0,1), '[错误]是否队内可见参数异常:(', Model::EXISTS_VALIDATE, 'in', Model::MODEL_BOTH),
        array('isneedreview', array(0,1), '[错误]是否需要审核参数异常:(', Model::EXISTS_VALIDATE, 'in', Model::MODEL_BOTH),
        array('title', '1,199', '[错误]标题也太不正常了吧= =', Model::VALUE_VALIDATE, 'length', Model::MODEL_BOTH),
    );
}
