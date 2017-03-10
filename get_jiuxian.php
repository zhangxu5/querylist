<?php
/* 
* @Author: zhangxu
* @Date:   2016-12-29 14:25:10
* @Last Modified by:   anchen
* @Last Modified time: 2017-03-10 14:49:15
* 该脚本用于定时抓取酒仙网产品数据
*/


set_time_limit(0);
require_once 'QueryList/vendor/autoload.php';
require_once 'db.class.php';
require_once 'cls.php';

use QL\QueryList;


function curl_get($url,$timeout=5){
   //初始化
    $curl = curl_init();
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $url);
    //设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, false);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    //执行命令
    $data = curl_exec($curl);
    //关闭URL请求
    curl_close($curl);
    //显示获得的数据
    return $data;
}

//获取每个品种的总页数
function getallnum($content){
    $rules = array(
        'href' => array('div.fanye>a','text'),
    );
    //先获取所有a标签
    $data = QueryList::Query($content,$rules,'','GB2312','UTF-8')
          ->getData(function($item){
            return $item['href'];
        });
    $last = count($data)-0-2;//总页数为倒数第二个元素
    return $data[$last];
}

function getnewid($allnum,$jiuurl,$dbObj,$type){
    $table_name = 'wine_goods';
    for($i=1;$i<$allnum+1;$i++){
        $url = $jiuurl.$i;
        $content = curl_get($url);
        $rules = array(
            'href' => array('div.proListSearch a.proName','href'),
        );

        $data = QueryList::Query($content,$rules,'','GB2312','UTF-8')
              ->getData(function($item){
                return $item['href'];
            });

        foreach ($data as $key => $value) {
            $str = "goods-";
            if(strpos($value,$str) === false){     //使用绝对等于判定url是否包含goods-
                continue;
            }else{
                $patterns = "/\d+/";
                preg_match($patterns,$value,$match);
                //取出goods的唯一编号,和数据库里做匹对，看是否存在这个编号
                $sql = "select id from wine_goods where source = 1 and goods_id=$match[0]";
                $result = $dbObj->getOne($sql);
                //表示不存在这个编号的数据，插入
                if(!$result){
                    $data = array('goods_id'=>$match[0],'source'=>1,'type'=>$type);
                    $res = $dbObj->insert($table_name,$data);
                }
            }
        }   
    }
    //echo $j;
}

//白酒品种
$jiuxian_baijiu = "http://list.jiuxian.com/1-0-0-0-0-0-0-0-0-0-0-0.htm?pageNum=";
$jiuxian_baijiu_1 = $jiuxian_baijiu."1";
$content_baijiu_1 = curl_get($jiuxian_baijiu_1);
$all_num_baijiu = getallnum($content_baijiu_1);
$type = 1;
getnewid($all_num_baijiu,$jiuxian_baijiu,$dbObj,$type);

//葡萄酒品种
$jiuxian_putaojiu = "http://list.jiuxian.com/2-0-0-0-0-0-0-0-0-0-0-0.htm?pageNum=";
$jiuxian_putaojiu_1 = $jiuxian_putaojiu."1";
$content_putaojiu_1 = curl_get($jiuxian_putaojiu_1);
$all_num_putaojiu = getallnum($content_putaojiu_1);
$type = 2;
getnewid($all_num_putaojiu,$jiuxian_putaojiu,$dbObj,$type);


//洋酒品种
$jiuxian_yangjiu = "http://list.jiuxian.com/4-0-0-0-0-0-0-0-0-0-0-0.htm?pageNum=";
$jiuxian_yangjiu_1 = $jiuxian_yangjiu."1";
$content_yangjiu_1 = curl_get($jiuxian_yangjiu_1);
$all_num_yangjiu = getallnum($content_yangjiu_1);
$type = 3;
getnewid($all_num_yangjiu,$jiuxian_yangjiu,$dbObj,$type);



//啤酒品种
$jiuxian_pijiu = "http://list.jiuxian.com/151-0-0-0-0-0-0-0-0-0-0-0.htm?pageNum=";
$jiuxian_pijiu_1 = $jiuxian_pijiu."1";
$content_pijiu_1 = curl_get($jiuxian_pijiu_1);
$all_num_pijiu = getallnum($content_pijiu_1);
$type = 4;
getnewid($all_num_pijiu,$jiuxian_pijiu,$dbObj,$type);


//保健酒品种
$jiuxian_baojian = "http://list.jiuxian.com/6-0-0-0-0-0-0-0-0-0-0-0.htm?pageNum=";
$jiuxian_baojian_1 = $jiuxian_baojian."1";
$content_baojian_1 = curl_get($jiuxian_baojian_1);
$all_num_baojian = getallnum($content_baojian_1);
$type = 5;
getnewid($all_num_baojian,$jiuxian_baojian,$dbObj,$type);


//黄酒品种
$jiuxian_huangjiu = "http://list.jiuxian.com/95-0-0-0-0-0-0-0-0-0-0-0.htm?pageNum=";
$jiuxian_huangjiu_1 = $jiuxian_huangjiu."1";
$content_huangjiu_1 = curl_get($jiuxian_huangjiu_1);
$all_num_huangjiu = getallnum($content_huangjiu_1);
$type = 5;
getnewid($all_num_huangjiu,$jiuxian_huangjiu,$dbObj,$type);


//果酒品种
$jiuxian_huangjiu = "http://list.jiuxian.com/179-0-0-0-0-0-0-0-0-0-0-0.htm?pageNum=";
$jiuxian_huangjiu_1 = $jiuxian_huangjiu."1";
$content_huangjiu_1 = curl_get($jiuxian_huangjiu_1);
$all_num_huangjiu = getallnum($content_huangjiu_1);
$type = 6;
getnewid($all_num_huangjiu,$jiuxian_huangjiu,$dbObj,$type);
//商品id更新结束

exit;
?>
