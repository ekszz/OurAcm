<?php
class OjhistoryModel extends Model{
    protected $_validate = array(
        array('mainname', '1,32', '[错误]OJ版本描述长度不正确！', Model::EXISTS_VALIDATE, 'length', Model::MODEL_BOTH),
        array('devname', '1,32', '[错误]开发代号长度不正确！', Model::VALUE_VALIDATE, 'length', Model::MODEL_BOTH),
    );
}