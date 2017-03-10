DROP TABLE IF EXISTS `wine_brand`;
CREATE TABLE `wine_brand` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `species` varchar(20) NOT NULL COMMENT '种类',
  `brand` varchar(50) NOT NULL COMMENT '品牌',
  `country` varchar(50) NOT NULL COMMENT '国家',
  `source` tinyint(4) NOT NULL DEFAULT '0' COMMENT '来源',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `change_time` int(11) NOT NULL COMMENT '修改时间',
  `quanpin` varchar(200) NOT NULL COMMENT '全拼',
  `firstpin` char(4) NOT NULL COMMENT '首字母',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;