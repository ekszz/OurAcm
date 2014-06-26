<?php
class ContestModel extends RelationModel{
    protected $_link = array(
        'Leader' => array(
            'mapping_type' => BELONGS_TO,
            'class_name' => 'Person',
            'foreign_key' => 'leader',
            'mapping_name' => 'leader_detail',
        ),
        'Teamer1' => array(
            'mapping_type' => BELONGS_TO,
            'class_name' => 'Person',
            'foreign_key' => 'teamer1',
            'mapping_name' => 'teamer1_detail',
        ),
        'Teamer2' => array(
            'mapping_type' => BELONGS_TO,
            'class_name' => 'Person',
            'foreign_key' => 'teamer2',
            'mapping_name' => 'teamer2_detail',
        ),
    );
    protected $_validate = array(
        array('site', '1,32', '[错误]地点太长了，试试简写！', Model::EXISTS_VALIDATE, 'length', Model::MODEL_BOTH),
        array('university', '1,32', '[错误]举办单位太长了，试试简写！', Model::EXISTS_VALIDATE, 'length', Model::MODEL_BOTH),
        array('type', array(0,1,2), '[错误]类型错误:(', Model::EXISTS_VALIDATE, 'in', Model::MODEL_BOTH),
        array('medal', array(0,1,2,3), '[错误]哪有这个奖牌啊:(', Model::EXISTS_VALIDATE, 'in', Model::MODEL_BOTH),
        array('title', '1,32', '[错误]附加奖项太长了，请试试简写。', Model::VALUE_VALIDATE, 'length', Model::MODEL_BOTH),
        array('ranking', '1,300', '[错误]排名好像不太对。', Model::VALUE_VALIDATE, 'between', Model::MODEL_BOTH),
        array('team', '1,16', '[错误]队伍名称暴长啊:(', Model::EXISTS_VALIDATE, 'length', Model::MODEL_BOTH),
        array('pic1', '1,255', '[错误]照片1的路径暴长啊:(', Model::EXISTS_VALIDATE, 'length', Model::MODEL_BOTH),
        array('pic2', '1,255', '[错误]照片1的路径暴长啊:(', Model::EXISTS_VALIDATE, 'length', Model::MODEL_BOTH),
    );
}
