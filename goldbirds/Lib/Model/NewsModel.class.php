<?php
class NewsModel extends RelationModel{
    protected $_link = array(
        'Author' => array(
            'mapping_type' => BELONGS_TO,
            'class_name' => 'Person',
            'foreign_key' => 'author',
            'mapping_name' => 'author_detail',
        )
    );
    protected $_validate = array(
        
    );
}
