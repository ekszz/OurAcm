SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `activitydata` (
  `adid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `aid` int(11) NOT NULL COMMENT '活动ID',
  `ojaccount` varchar(128) NOT NULL COMMENT 'OJ用户名',
  `data` text COMMENT '注册信息',
  `state` smallint(6) NOT NULL DEFAULT '0' COMMENT '审核状态（0-待审核，1-拒绝，2-通过）',
  `regtime` datetime NOT NULL COMMENT '注册时间',
  PRIMARY KEY (`adid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='注册信息' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `activitylist` (
  `aid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '活动ID',
  `title` varchar(200) NOT NULL COMMENT '活动名称',
  `desc` text COMMENT '活动内容描述',
  `addtime` datetime NOT NULL COMMENT '添加时间',
  `deadline` datetime NOT NULL COMMENT '截止报名时间',
  `form` varchar(10000) DEFAULT NULL COMMENT '报名表单格式',
  `isinner` smallint(6) NOT NULL DEFAULT '0' COMMENT '是否只允许队员可见（1-是，0-否）',
  `ispublic` smallint(6) NOT NULL DEFAULT '0' COMMENT '注册信息是否公开（0-否，1-是）',
  `isneedreview` smallint(6) NOT NULL DEFAULT '0' COMMENT '是否需要审核（0-否 ,1-是）',
  `adminuid` int(11) NOT NULL DEFAULT '0' COMMENT '管理者UID',
  PRIMARY KEY (`aid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='活动列表' AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
