OurAcm  
======  
  
优雅简洁的ACM-ICPC队伍风采展示系统，基于Bootstrap和ThinkPHP！  
  
*简洁优雅的界面  
*支持WF、Regional等类型的ACM-ICPC比赛展示  
*酷炫版与表单版两种展示模式  
*支持教练团队展示  
*支持OnlineJudge展示  
*操作简易、功能强大的后台管理功能  
*用户系统OnlineJudge关联模式  
*支持邀请码机制，快速添加新队员  
  
  
系统要求  
========  
  
建议使用PHP 5.3+环境，MySQL 5.1+数据库，  
浏览器支持Chrome、Firefox、IE10+等，IE低版本显示不友好，会有提示信息。  
  
  
部署说明  
========  
  
1、将所有文件拷贝到web目录下；  
2、修改\goldbirds\Common\common.php中的三个OnlineJudge用户关联接口；  
2、使用phpmyadmin或其它工具，导入db目录下的数据库文件；  
3、修改\goldbirds\Conf\config.php中的数据库配置选项；  
4、删除\db\目录；  
5、请确保\upload、\goldbirds\Runtime具有写权限；  
6、访问index.php尝试能否正常访问；  
7、登录OnlineJudge，并访问index.php?z=setting，以邀请码"iloveacmiloveacm"关联带管理权的OnlineJudge账户；  
8、关联后，点击"个人中心"，可在里面进行相应配置管理。  
