-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net

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
('we_icpc_introduce', '我们-ICPC简介内容', '（介绍ACM-ICPC。具体内容可到后台更改。）', '设置“我们”页面中ACM-ICPC赛事简介的内容，可为空，HTML代码。', 2),
('we_team_introduce', '我们-ICPC集训队简介', '（介绍本校的ACM-ICPC集训队。具体内容可到后台更改。）', '设置“我们”页面中ACM-ICPC集训队简介的内容，可为空，HTML代码。', 2);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
