<?php
return array(
	'MODULE_ALLOW_LIST'     =>  array('Home'), // 配置你原来的分组列表

	'MULTI_MODULE'          =>  false, // 单模块访问
	'DEFAULT_MODULE'        =>  'Home', // 默认访问模块

	//URL设置
    'URL_CASE_INSENSITIVE'    => true,	// URL是否不区分大小写
    'URL_MODEL'			=>  3,			// 更改该项请务必更改JS中的相应路径
    'URL_PATHINFO_DEPR' =>  '-',
    'VAR_PATHINFO'      =>  'z',

	//CHARSET
    'OUTPUT_CHARSET'	=>	'utf-8',	// 输出编码设置   
    'DB_CHARSET'		=>	'utf8',	    // 数据库编码设置   
    'TEMPLATE_CHARSET' 	=>	'utf-8',	// 模板编码设置  
    'DEFAULT_CHARSET'	=>	'utf-8',	// 默认编码

	//Debug
    'SHOW_PAGE_TRACE'	=>	false,		// 显示页面Trace信息

    //SESSION
    'SESSION_AUTO_START'=>	true,		// 是否自动开启Session
);
