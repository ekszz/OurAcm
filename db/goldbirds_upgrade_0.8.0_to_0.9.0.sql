ALTER TABLE `setting` CHANGE `v` `v` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

INSERT INTO `setting` (`k`, `name`, `v`, `desc`, `type`) VALUES
('module_disable_we', '模块_禁用我们模块', '0', '禁用“我们”模块', 0),
('module_disable_coach', '模块-禁用教练模块', '0', '是否需要停用“教练”模块', 0),
('module_disable_ojhistory', '模块-禁用OJ历史模块', '0', '是否需要停用“OnlineJudge历史”模块', 0),
('module_disable_news', '模块-禁用新闻模块', '0', '是否需要停用“新闻”模块', 0),
('module_disable_wf', '模块-禁用全球总决赛（WF）模块', '0', '是否需要停用“全球总决赛（WF）”模块', 0),
('module_disable_regional', '模块-禁用区域赛（Regional）模块', '0', '是否需要停用“区域赛（Regional）”模块', 0),
('module_disable_codepool', '模块-禁用码池模块', '0', '是否需要停用“码池”模块', 0),
('module_disable_activity', '模块-禁用活动中心模块', '0', '是否需要停用“活动中心”模块', 0),
('module_disable_talk', '模块-禁用谈资模块', '0', '是否需要停用“谈资”模块', 0);
