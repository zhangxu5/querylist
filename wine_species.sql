DROP TABLE IF EXISTS `wine_species`;
CREATE TABLE `wine_species` (
  `s_id` int(11) NOT NULL AUTO_INCREMENT,
  `s_type` varchar(50) NOT NULL COMMENT '种类',
  `s_status` int(11) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`s_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;