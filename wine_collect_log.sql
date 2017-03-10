DROP TABLE IF EXISTS `wine_collect_log`;
CREATE TABLE `wine_collect_log` (
  `cl_id` int(11) NOT NULL AUTO_INCREMENT,
  `cl_source` int(11) NOT NULL COMMENT '来源',
  `cl_goods_id` int(11) NOT NULL COMMENT '对应goods表id字段',
  `cl_goods` varchar(100) NOT NULL COMMENT '对应goods表goods_id字段',
  `cl_status` tinyint(4) NOT NULL COMMENT '采集成功1 失败0',
  `cl_content` varchar(200) NOT NULL COMMENT '采集错误原因',
  `cl_time` int(11) NOT NULL COMMENT '采集时间戳',
  `cl_createtime` datetime NOT NULL COMMENT '采集时间',
  PRIMARY KEY (`cl_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;