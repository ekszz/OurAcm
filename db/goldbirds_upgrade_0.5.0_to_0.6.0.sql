SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `codepool` (
  `codeid` int(11) NOT NULL AUTO_INCREMENT COMMENT '代码ID',
  `k` varchar(8) NOT NULL COMMENT 'key键',
  `submittime` datetime NOT NULL COMMENT '提交时间',
  `tag` varchar(32) DEFAULT NULL COMMENT '左侧标签',
  `code` text NOT NULL COMMENT '代码内容',
  `ojaccount` varchar(32) DEFAULT NULL COMMENT '提交时的OJ账号', 
  `ip` varchar(32) DEFAULT NULL COMMENT '客户端IP',
  PRIMARY KEY (`codeid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='码池表' AUTO_INCREMENT=1 ;

INSERT INTO `setting` (`k`, `name`, `v`, `desc`, `type`) VALUES
('codepool_exptime', '码池-代码保留时间', '2592000', '码池功能中用户提交的代码保留的时间，单位为秒。', 2);
INSERT INTO `setting` (`k`, `name`, `v`, `desc`, `type`) VALUES
('codepool_maxlength', '码池-单个代码最大长度', '32768', '码池功能中单个代码允许提交的最大长度，单位字节。', '2');
INSERT INTO `setting` (`k`, `name`, `v`, `desc`, `type`) VALUES 
('codepool_maxperip', '码池-单IP每24小时最大提交量', '200', '码池功能中，每个IP地址24小时内最大提交的代码数量。该值用予防止机器人提交。', '2');

ALTER TABLE `contest` CHANGE `medal` `medal` INT(2) NOT NULL COMMENT '奖牌，0-金牌，1-银牌，2-铜牌，3-鼓励奖，4-旅游队'; 
