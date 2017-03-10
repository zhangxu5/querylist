DROP TABLE IF EXISTS `wine_goods`;
CREATE TABLE `wine_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_id` varchar(50) NOT NULL COMMENT '商品唯一编号',
  `source` int(11) NOT NULL DEFAULT '1' COMMENT '来源,1为酒仙网，2为京东',
  `type` tinyint(4) DEFAULT NULL COMMENT '酒的种类',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '启用状态',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;