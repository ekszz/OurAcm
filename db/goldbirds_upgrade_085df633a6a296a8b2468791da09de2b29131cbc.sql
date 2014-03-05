-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2013 年 12 月 11 日 08:43
-- 服务器版本: 5.5.24
-- PHP 版本: 5.3.13

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `goldbirds`
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
  UNIQUE KEY `k` (`k`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='参数表';

--
-- 转存表中的数据 `setting`
--

INSERT INTO `setting` (`k`, `name`, `v`, `desc`, `type`) VALUES
('config_contest_sort', '设置-获奖记录排序方法', '1', '设置每个赛季中比赛获奖记录（除World Fianl外）中的排序方法，Yes-按奖项，比赛时间依次排序，No-按比赛时间，奖项依次排序', 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
