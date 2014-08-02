SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `codepool` (
  `codeid` int(11) NOT NULL AUTO_INCREMENT COMMENT '代码ID',
  `k` varchar(8) NOT NULL COMMENT 'key键',
  `exptime` datetime NOT NULL COMMENT '过期时间',
  `tag` varchar(128) DEFAULT NULL COMMENT '左侧标签',
  `code` text NOT NULL COMMENT '代码内容',
  `ip` varchar(32) DEFAULT NULL COMMENT '客户端IP',
  PRIMARY KEY (`codeid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='码池表' AUTO_INCREMENT=1 ;

INSERT INTO `setting` (`k`, `name`, `v`, `desc`, `type`) VALUES
('codepool_exptime', '码池-代码保留时间', '2592000', '码池功能中用户提交的代码保留的时间，单位为秒', 2);
