# querylist
querylist采集器采集酒
1.get_jiuxian.php 用于采集酒仙网的商品id
2.get_1919.php 用于采集1919自营的商品id
3.chinesespell.php 用于获取品牌全拼及首字母，部分汉字无法获取
4.cls.php 用于匹配同一品牌下商品名的相似度
5.db.class.php 用于连接mysql
6.update_*.php 更新商品数据


注意:
1.采集到的商品图片最好存到本地或者提交到资源服务器，我这边采用的是POST提交到资源服务器.$paramimg_url为图片服务器的地址
2.考虑到服务器压力，update_*.php没有一次性全部抓取，分批更新数据
3.采集程序最好在命令行模式下运行
