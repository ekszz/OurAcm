<?php
return array(

    //Database
    'DB_TYPE'   		=> 	'mysql', 	    // 数据库类型
    'DB_HOST'   		=> 	'localhost',    // 服务器地址
    'DB_NAME'   		=> 	'ouracm',		// 数据库名
    'DB_USER'   		=> 	'root', 	    // 用户名
    'DB_PWD'    		=> 	'', 	        // 密码
    'DB_PORT'   		=> 	3306, 		    // 端口
    'DB_PREFIX' 		=> 	'', 		    // 数据库表前缀
    
    'LIMIT_REFLESH_TIME'=>  '1',			//浏览器防刷新检测
    
    //CHARSET
    'OUTPUT_CHARSET'	=>	'utf-8',	// 输出编码设置   
    'DB_CHARSET'		=>	'utf8',	    // 数据库编码设置   
    'TEMPLATE_CHARSET' 	=>	'utf-8',	// 模板编码设置  
    'DEFAULT_CHARSET'	=>	'utf-8',	// 默认编码

    //Debug
    'SHOW_PAGE_TRACE'	=>	false,		// 显示页面Trace信息

    //SESSION
    'SESSION_AUTO_START'=>	true,		// 是否自动开启Session
    
    //URL设置
    'URL_CASE_INSENSITIVE'    => true,	// URL是否不区分大小写
    'URL_MODEL'			=>  3,			// 更改该项请务必更改JS中的相应路径
    'URL_PATHINFO_DEPR' =>  '-',
    'VAR_PATHINFO'      =>  'z',

    'TMPL_STRIP_SPACE'  => false       // 是否去除模板文件里面的html空格与换行
);
?>
