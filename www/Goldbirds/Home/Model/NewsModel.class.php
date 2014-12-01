<?php
namespace Home\Model;
use Think\Model\RelationModel;

class NewsModel extends RelationModel{
    protected $_link = array(
        'Author' => array(
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'Person',
            'foreign_key' => 'author',
            'mapping_name' => 'author_detail',
        )
    );
    protected $_validate = array(
        array('title', '1,100', '[错误]标题长度不对！不能为空，不超过100字节。', self::EXISTS_VALIDATE, 'length', self::MODEL_BOTH),
        array('category', '1,20', '[错误]分类不正确！不能为空，不超过20字节。', self::EXISTS_VALIDATE, 'length', self::MODEL_BOTH),
    );
}
