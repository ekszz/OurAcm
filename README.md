OurAcm  
======  

> 青春散场，记忆永恒。

  
优雅简洁的ACM-ICPC队伍风采展示系统，基于Bootstrap和ThinkPHP！  
  
* 简洁优雅的界面  
* 支持WF、Regional等类型的ACM-ICPC比赛展示  
* 酷炫版与表单版两种展示模式  
* 支持教练团队展示  
* 简易强大的报名系统，支持内部活动、注册字段自定义、审核、注册信息指导出等功能  
* “码池”功能，保存代码，分享代码  
* 支持“我们”展示模块  
* 支持OnlineJudge展示  
* 操作简易、功能强大的后台管理功能  
* 用户系统OnlineJudge关联模式  
* 支持邀请码机制，快速添加新队员  
* “谈资”模块，一套精致的Web Board系统  
  
系统要求  
========  
  
建议使用PHP 5.3+环境，MySQL 5.1+数据库。  
浏览器支持Chrome、Firefox、IE10+等；不支持IE低版本，使用这些旧版本浏览器访问，会跳转至提示页面。  

  
部署说明  
========  
  
1. 使用phpmyadmin或其它工具，导入/db目录下对应版本的数据库文件；  
2. 将www文件夹下所有文件拷贝到服务器的HTTP服务目录下；  
3. 修改服务器上/Goldbirds/Home/Common/function.php中的7个OnlineJudge接口；  
4. 修改服务器上/Goldbirds/Home/Conf/config.php中的数据库配置选项；  
5. 请确保服务器上/upload、/Goldbirds/Runtime（该文件夹首次访问时会创建）这2个目录具有写权限；  
6. 访问index.php尝试能否正常访问；  
7. 登录OnlineJudge，并访问index.php?z=setting，以邀请码"iloveacmiloveacm"关联带管理权的OnlineJudge账户；  
8. 关联后，点击"个人中心"，可在里面进行相应配置管理。  
