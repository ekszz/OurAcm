-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `ouracm`
--

-- --------------------------------------------------------

--
-- 表的结构 `setting`
--

CREATE TABLE IF NOT EXISTS `setting` (
  `k` varchar(64) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT '后台显示的名称',
  `v` text,
  `desc` varchar(255) DEFAULT NULL COMMENT '描述',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '类型，0-布尔，1-文本，2-文本（不转义）',
  UNIQUE KEY `k` (`k`) USING BTREE,
  UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='参数表';

--
-- 转存表中的数据 `setting`
--

INSERT INTO `setting` (`k`, `name`, `v`, `desc`, `type`) VALUES
('config_smtp_account', '设置-SMTP邮箱账号', 'username@ekszz.com', '用来发送邮件的SMTP邮箱账号，全称，如：acmicpc@ekszz.com', 2),
('config_smtp_fromname', '设置-SMTP发送者名称', 'OurACM', '设置SMTP发送的邮件中的发送者描述', 2),
('config_smtp_host', '设置-SMTP服务器', 'smtp.ekszz.com', '设置SMTP服务器', 2),
('config_smtp_needauth', '设置-SMTP服务器是否需要认证', '1', 'SMTP服务器是否需要认证才能使用，true-是，false-否', 0),
('config_smtp_username', '设置-SMTP服务器用户名', 'username', '在SMTP需要认证的情况下有效，SMTP服务器认证用户名。', 2),
('config_smtp_password', '设置-SMTP服务器密码', 'password', '在SMTP服务器需要认证的情况下有效，SMTP服务器密码。', 2),
('config_invite_title', '设置-邀请邮件标题', 'OurACM邀请你注册', '设置邀请邮件的标题。', 1),
('config_invite_content', '设置-邀请邮件正文内容', '亲爱的{chsname}：\nOurACM邀请你前来注册，快回来看看ACM/ICPC队的变化吧~~\n\n你的邀请码：{code}\n注册网址：{url}\n\n期待你的加入！\n\n\n自动发送的邮件，请勿直接回复。\nPowered by OurACM.\n', '设置邀请邮件的正文内容，其中{chsname}转义成队员中文名字，{engname}转义为队员英文姓名，{code}转义为相应的邀请码，{url}自动转义为注册网址。', 2);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
