<?php 
/* 
* @Author: zhangxu
* @Date:   2016-1-4 14:25:10
* @Last Modified by:   anchen
* @Last Modified time: 2017-03-10 15:01:53
* 该脚本用于更新1919产品
*/
//header("Content-Type:text/html;charset=utf-8;");
require_once 'QueryList/vendor/autoload.php';
require_once 'db.class.php';
require_once 'cls.php';
require_once 'chinesespell.php';

use QL\QueryList;

//此处开始获取商品id
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
    //解决https无法获取问题
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //不验证证书下同
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    //解决301重定向问题
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); 
    //执行命令
    $data = curl_exec($curl);
    //关闭URL请求
    curl_close($curl);
    //显示获得的数据
    return $data;
}

//此处开始执行商品信息更新
function getImage($url,$save_dir='',$filename='',$type=0){ 
    if(trim($url)==''){ 
        return array('file_name'=>'','save_path'=>'','error'=>1); 
    } 
    if(trim($save_dir)==''){ 
        $save_dir='./'; 
    } 
    if(trim($filename)==''){//保存文件名 
        $ext=strrchr($url,'.'); 
        $ext = str_replace(';', '', $ext);
        if($ext!='.gif'&& $ext!='.jpg' && $ext!='.png'){ 
            return array('file_name'=>'','save_path'=>'','error'=>3); 
        } 
        $filename=time().$ext; 
    } 
    if(0!==strrpos($save_dir,'/')){ 
        $save_dir.='/'; 
    } 
    //创建保存目录 
    if(!file_exists($save_dir)&&!mkdir($save_dir,0777,true)){ 
        return array('file_name'=>'','save_path'=>'','error'=>5); 
    } 
    //获取远程文件所采用的方法  
    if($type){ 
        $ch=curl_init(); 
        $timeout=5; 
        curl_setopt($ch,CURLOPT_URL,$url); 
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); 
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout); 
                    //curl https无法获取网页内容
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书下同
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $img=curl_exec($ch); 
        curl_close($ch); 
    }else{ 
        ob_start();  
        readfile($url); 
        $img=ob_get_contents();  
        ob_end_clean();  
    } 
    //$size=strlen($img); 
    //文件大小  
    $fp2=@fopen($save_dir.$filename,'a'); 
    fwrite($fp2,$img); 
    fclose($fp2); 
    unset($img,$url); 
    return array('file_name'=>$filename,'save_path'=>$save_dir.$filename,'error'=>0); 
} 

    function post($url, $post_data = '', $timeout = 5){//curl
        $ch = curl_init();
 
        curl_setopt ($ch, CURLOPT_URL, $url);
 
        curl_setopt ($ch, CURLOPT_POST, 1);
 
        if($post_data != ''){
 
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
 
        }
 
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
 
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
 
        curl_setopt($ch, CURLOPT_HEADER, false);


            //curl https无法获取网页内容
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书下同
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        

        
        $res=curl_exec($ch);//指定以数组形式返回

        curl_close($ch);

        return $res;
 
    }

//$start_time = time();
$detaile_table = "wine_detail";
$brand_table = "wine_brand";
$log_table = "wine_collect_log";

//抓取的商品图片分发到自己的资源服务器路径
$paramimg_url = "";

//$species_array = array('白酒','葡萄酒','洋酒','啤酒','黄酒/保健酒','果酒/饮料','收藏酒/陈年老酒');

//获取wine_goods表1919商品编号的最大id
$max_brandid_sql = "select id from wine_goods where source = 2 and status = 1 order by id desc limit 1";
$max_brandid_result = $dbObj->getOne($max_brandid_sql);
$max_brandid = $max_brandid_result['id'];

//获取日志记录里最后一次操作的1919商品id(对应wine_goods表的id)
$cl_sql = "select cl_goods_id from wine_collect_log where cl_source = 2 order by cl_time desc limit 1";
$cl_result = $dbObj->getOne($cl_sql);
if($cl_result){
    $strat_goodsid = $cl_result['cl_goods_id'];
    //获取id在source=2查询条件下的行数
    $num_sql = "select count(1) as num from wine_goods where  source = 2 and status = 1 and id < $strat_goodsid  order by id ";
    $num_result = $dbObj->getOne($num_sql);
    $strat = $num_result['num'] + 1;
    //获取的最后一条1919抓取数据id为酒仙网goods_id表的最大id，则重新开始采集
    if($strat_goodsid >= $max_brandid){
        $sql = "select id,goods_id,type from wine_goods where source = 2 and status = 1 limit 0,200";
    }else{
        $sql = "select id,goods_id,type from wine_goods where source = 2 and status = 1 limit $strat,200";
    }
    
}else{
    $sql = "select id,goods_id,type from wine_goods where source = 2 and status = 1 limit 0,200";
}
//查询1919的产品id
$result = $dbObj->fetchAll($sql);
//循环产品id
foreach ($result as $key => $value) {
    if($value['id'] > $max_brandid){
        exit;
    }
    $good = array(
            'brand_id'=>'',//商品品牌编号
            'name'=>'',//主要商品名
            'price'=>'',//主要价格
            'name_1919'=>'',//商品酒仙网名称
            'price_1919'=>'',//商品酒仙网价格
            'origin'=>'',//产地
            'company'=>'',//酒厂
            'content'=>'',//净含量
            'alcohol'=>'',//酒精度
            'specifications'=>'',//规格
            'conditions'=>'',//储藏条件
            'xianggui'=>'',//箱规
            'material'=>'',//原料
            'scent'=>'',//香型
            'score_1919'=>'',//酒仙网评分
            'change_time'=>'',//修改时间戳
            'createtime'=>'',//创建时间
            'id_1919'=>'',//酒仙网产品唯一编号
            'wine_type'=>'',//产品类型
            'chanqu'=>'',//产区
            'pic_1919'=>'',//酒仙网小图
            'bigpic_1919'=>'',//酒仙大图
            'species'=>'',//种类
            'brand'=>'',//品牌

    );
    $nowtime = date("Y-m-d H:i:s",time());
    $goods_id = $value['goods_id'];
    $log_goods_id = $value['id'];
    $url = "http://www.1919.cn/product-".$goods_id.".html";
    $content = curl_get($url);

    if(!$content || empty($content)){
        $log_text = "file_get_contents未获取到内容";
        $log = array(
            'cl_source'=>2,
            'cl_goods_id'=>$log_goods_id,
            'cl_goods'=>$goods_id,
            'cl_status'=>0,
            'cl_content'=>$log_text,
            'cl_time'=>time(),
            'cl_createtime'=>$nowtime
        );
        $res = $dbObj->insert($log_table,$log);
        continue;
    }
    //获取种类和品牌
    $rules = array(
        'species' => array('div.share_site > span:eq(2)','text'),
        'brand' => array('div#intro_basic_info > li:eq(2)','text')
    );

    $data = QueryList::Query($content,$rules)->data;
    $arr_brand = explode('：', $data[0]['brand']);


    if($arr_brand[0] != '品牌'){
        $log_text = "商品品牌未匹配到";
        $log = array(
            'cl_source'=>2,
            'cl_goods_id'=>$log_goods_id,
            'cl_goods'=>$goods_id,
            'cl_status'=>0,
            'cl_content'=>$log_text,
            'cl_time'=>time(),
            'cl_createtime'=>$nowtime
        );
        $res = $dbObj->insert($log_table,$log);
        continue;
    }

    $good['species'] = $value['type'];

    $good['brand'] = $arr_brand[1];
    $good['brand'] = preg_replace('/\（.*?\）/', '', $good['brand']);
    $good['brand'] = preg_replace('/\(.*?\)/', '', $good['brand']);
    $good['brand'] = preg_replace('/\（.*?\)/', '', $good['brand']);
    $good['brand'] = preg_replace('/\(.*?\）/', '', $good['brand']);
    //商品和种类获取结束

    //php模拟post请求获取商品详情
    $param_url = "https://www.1919.cn/product-goodsParams-".$goods_id.".html";
    //$param_url = "https://www.1919.cn/product-goodsParams-816.html";
    $post_data = array('invalid_post_data'=>1);
    $res_param = post($param_url,$post_data);

    $rule_param = array(
        'param' => array('ul.goodsStandard > li','text'),
    );
    $param = QueryList::Query($res_param,$rule_param)->data;

    if(!$param){
        $log_text = "未获取到商品详情";
        $log = array(
            'cl_source'=>2,
            'cl_goods_id'=>$log_goods_id,
            'cl_goods'=>$goods_id,
            'cl_status'=>0,
            'cl_content'=>$log_text,
            'cl_time'=>time(),
            'cl_createtime'=>$nowtime
        );
        $res = $dbObj->insert($log_table,$log);
        continue;
    }

    foreach ($param as $key => $value) {
        $str1 = explode('：', $value['param']);
        $str1[0]=trim($str1[0]);
        $str1[1]=trim($str1[1]);

        switch ($str1[0]) {
            case '产地':
                $good['origin'] = $str1[1];
                break;
            case '酒庄':
                $good['company'] = $str1[1];
                break;
            case '净含量':
                $good['content'] = $str1[1];
                break;
            case '酒精度':
                $good['alcohol'] = $str1[1];
                break;
            case '规格':
                $good['specifications'] = $str1[1];
                break;
            case '贮藏条件':
                $good['conditions'] = $str1[1];
                break;
            case '储藏条件':
                $good['conditions'] = $str1[1];
                break;
            case '箱规':
                $good['xianggui'] = $str1[1];
                break;
            case '产区':
                $good['chanqu'] = $str1[1];
                break;
            case '产品类型'://葡萄酒类型字段
                $good['wine_type'] = $str1[1];
                break; 
            case '类型': //洋酒类型字段
                $good['wine_type'] = $str1[1];
                break;
            case '原料': //洋酒,白酒原料字段
                $good['material'] = $str1[1];
                break; 
            case '酒厂': //洋酒,白酒酒厂字段
                $good['company'] = $str1[1];
                break;  
            case '香型': //
                $good['scent'] = $str1[1];
                break; 
            case '整箱规格': //
                $good['xianggui'] = $str1[1];
                break; 
            case '贮存条件': //
                $good['conditions'] = $str1[1];
                break; 
            case '国家': //
                $good['origin'] = $str1[1];
                break;
            case '葡萄酒分类': //
                $good['wine_type'] = $str1[1];
                break;
            case '口味类型': //
                if($good['wine_type']){
                    $good['wine_type'] = $good['wine_type'].";".$str1[1];
                }else{
                    $good['wine_type'] = $str1[1];
                }
                break;       
        }
    }//获取商品详情结束

    //获取商品名
    $rules_goodsname = array(
        'param' => array('h2.goodsname > div','text'),
    );
    $param_goodsname = QueryList::Query($content,$rules_goodsname)->data;
    if(!$param_goodsname){
        $log_text = "未获取到商品名";
        $log = array(
            'cl_source'=>2,
            'cl_goods_id'=>$log_goods_id,
            'cl_goods'=>$goods_id,
            'cl_status'=>0,
            'cl_content'=>$log_text,
            'cl_time'=>time(),
            'cl_createtime'=>$nowtime
        );
        $res = $dbObj->insert($log_table,$log);
        continue;
    }
    $good['name_1919'] = $param_goodsname[0]['param']; 
    //抓取的名字中含有'号导致sql报错
    $good['name_1919'] = str_replace("'","\'",$good['name_1919']);
    //获取商品名结束

    //模拟post获取价格
    $url_price = "https://www.1919.cn/passport-getPriceById.html";
    $post_price = array('id'=>$goods_id);
    $res_price = post($url_price,$post_price);

    if(!$res_price){
        $log_text = "未获取到价格";
        $log = array(
            'cl_source'=>2,
            'cl_goods_id'=>$log_goods_id,
            'cl_goods'=>$goods_id,
            'cl_status'=>0,
            'cl_content'=>$log_text,
            'cl_time'=>time(),
            'cl_createtime'=>$nowtime
        );
        $res = $dbObj->insert($log_table,$log);
        continue;
    }

    $partten = "/\"price_promotion\":(.*?)\,/";
    preg_match($partten, $res_price, $match);
    $good['price_1919'] = str_replace('"','', $match[1]);  

    if($good['price_1919'] == 'null'){
        //获取促销价失败,获取销售价
        $partten1 = "/\"price_mlv\":(.*?)\,/";
        preg_match($partten1, $res_price, $match);
        $good['price_1919'] = str_replace('"','', $match[1]);
        if($good['price_1919'] == 'null'){
            //获取销售价失败,获取零售价
            $partten1 = "/\"price_mkt\":(.*?)\,/";
            preg_match($partten1, $res_price, $match);
            $good['price_1919'] = str_replace('"','', $match[1]);
        } 
    }

    //获取价格end

    //模拟获取评分
    $url_score = "https://www.1919.cn/product-goodsDiscuss-".$goods_id.".html";
    $post_score = array('invalid_post_data'=>1);
    $res_score = post($url_score,$post_score);

    $rule_score = array(
        'discuss' => array('ul.out > li > strong','text'),
    );
    $score = QueryList::Query($res_score,$rule_score)->data;
    $good['score_1919'] = $score[0]['discuss']; //获取评分结束

    //获取图集
    $rules_pic = array(
        'pic_url' => array('ul.goods-detail-pic-thumbnail>li>a>img','src'),//获取分组信息
    );

    $data_pic = QueryList::Query($content,$rules_pic)->data;

    $pic = '';
    foreach ($data_pic as $key => $value) {
        $arr = explode('?',$value['pic_url']);
        if($pic == ''){
            $pic = $arr[0];
        }else{
            $pic = $pic.";".$arr[0];
        }
    }
    $good['pic_1919'] = $pic;  //1919小图和大图一样，获取图集结束


    //判定goods_id是否已存在
    $sql_goodsid = "select id,id_jiuxian from wine_detail where id_1919='$goods_id'";
    $detail_id = $dbObj->getOne($sql_goodsid);
    if($detail_id){
        //执行更新
        //更新操作前需判定此商品是否已和酒仙网商品匹配，若匹配，则更新1919专有字段，反之更新所有详情字段
        if($detail_id['id_jiuxian']){
            $update_data = array(
                'name_1919'=>$good['name_1919'],//商品1919名称
                'price_1919'=>$good['price_1919'],//商品1919价格
                'score_1919'=>$good['score_1919'],//1919评分
                'change_time'=>time(),//时间戳
                //'pic_1919'=>$good['pic_1919']//1919图
            );

        }else{
            $update_data = array(
                'name'=>$good['name_1919'],//商品1919名称
                'price'=>$good['price_1919'],//商品1919价格
                'name_1919'=>$good['name_1919'],//商品1919名称
                'price_1919'=>$good['price_1919'],//商品1919价格
                'score_1919'=>$good['score_1919'],//1919评分
                'change_time'=>time(),//时间戳
                //'pic_1919'=>$good['pic_1919'],//1919图
                'origin'=>$good['origin'],//产地
                'company'=>$good['company'],//酒厂
                'content'=>$good['content'],//净含量
                'alcohol'=>$good['alcohol'],//酒精度
                'specifications'=>$good['specifications'],//规格
                'conditions'=>$good['conditions'],//储藏条件
                'xianggui'=>$good['xianggui'],//箱规
                'material'=>$good['material'],//原料
                'scent'=>$good['scent'],//香型
                'wine_type'=>$good['wine_type'],//产品类型
                'chanqu'=>$good['chanqu'],//产区
            );
        }
        $where = " id_1919 = '$goods_id'";
        $res = $dbObj->update($detaile_table,$update_data,$where);

        $log_text = "成功-更新";
        $log = array(
            'cl_source'=>2,
            'cl_goods_id'=>$log_goods_id,
            'cl_goods'=>$goods_id,
            'cl_status'=>1,
            'cl_content'=>$log_text,
            'cl_time'=>time(),
            'cl_createtime'=>$nowtime
        );
        $res = $dbObj->insert($log_table,$log);
    }else{
        //不存在，为新抓取的商品id，需要执行匹配
        $good['brand'] = str_replace("'","\'",$good['brand']);
        $species = $good['species'];
        $brand = $good['brand'];
    
        $brand_data = array('species'=>$species,'brand'=>$brand,'country'=>$good['origin']);
     
        //检查新的商品品牌是否已存在，不存在插入，已存在更新产地
        $sql = "select id,country from wine_brand where species='$species' and brand='$brand'";
        $result = $dbObj->getOne($sql);

        if(!$result){
            //新增品牌
            $spell = new ChineseSpell();
            $brand_spell = iconv("UTF-8","gb2312", $brand);
            $quanpin = $spell->getFullSpell($brand_spell);
            $firstpin = $spell->getChineseSpells($brand_spell,'',1);

            if(empty($firstpin) || empty($quanpin)){
                $log_text = "未获取拼音";
                $log = array(
                    'cl_source'=>2,
                    'cl_goods_id'=>$log_goods_id,
                    'cl_goods'=>$goods_id,
                    'cl_status'=>0,
                    'cl_content'=>$log_text,
                    'cl_time'=>time(),
                    'cl_createtime'=>$nowtime
                );
                $res = $dbObj->insert($log_table,$log);
                //file_put_contents("log.txt", $log_text.PHP_EOL, FILE_APPEND);
                $firstpin = '';

            }else{
                $firstpin = strtoupper(substr($quanpin,0,1));
            }//获取拼音结束，开始执行插入

            $brand_data['quanpin'] = $quanpin;
            $brand_data['firstpin'] = $firstpin;
            $brand_data['change_time'] = time();
            $brand_data['source'] = 2;

            $res = $dbObj->insert($brand_table,$brand_data);
            $id = $dbObj->insertId();
            $good['brand_id']=$id;
            $good['change_time']=time();
            $good['createtime']=date("Y-m-d H:i:s");
            $good['id_1919']=$goods_id;
            $good['name'] = $good['name_1919'];//商品1919名称
            $good['price'] = $good['price_1919'];//商品1919价格
            
            $pic_1919_ifeng = '';
            $res_1919 = explode(';',$good['pic_1919']);
            foreach ($res_1919 as $val) {
                $img_res = getImage($val,'','',$type=1);
                $post_data = array (
                    'width'=>430,
                    'height'=>430,
                    'controller'=>'wineapp',
                    'fileName'=>'icon',
                    "icon" => "@".$img_res['file_name'],
                );
                
                $res_param = post($paramimg_url,$post_data);
                $res_param = json_decode($res_param,true);

                if($res_param['statu'] == 1){
                    //upload成功
                    if(empty($pic_jiuxian_ifeng)){
                        $pic_1919_ifeng = $res_param['msg'];
                    }else{
                        $pic_1919_ifeng .= ";".$res_param['msg'];
                    }
                }else{
                    //上传资源服务器失败,记录
                    $log_text = "图片上传资源服务器失败";
                    $log = array(
                        'cl_source'=>2,
                        'cl_goods_id'=>$log_goods_id,
                        'cl_goods'=>$goods_id,
                        'cl_status'=>0,
                        'cl_content'=>$log_text,
                        'cl_time'=>time(),
                        'cl_createtime'=>$nowtime
                    );
                    $res = $dbObj->insert($log_table,$log);
                }
                unlink($img_res['file_name']);
                //sleep(1);
            }

            $good['pic_1919'] = $pic_1919_ifeng;

            unset($good['species']);
            unset($good['brand']);
            $res = $dbObj->insert($detaile_table,$good);

            $log_text = "成功-插入";
            $log = array(
                'cl_source'=>2,
                'cl_goods_id'=>$log_goods_id,
                'cl_goods'=>$goods_id,
                'cl_status'=>1,
                'cl_content'=>$log_text,
                'cl_time'=>time(),
                'cl_createtime'=>$nowtime
            );
            $res = $dbObj->insert($log_table,$log);
        }else{
            $id = $result['id'];
            $change_time = time();
            //更新brand表的country字段
            if(!empty($good['origin'])){
                $change_time = time();
                if(empty($result['country'])){
                    $new_country = $good['origin'];
                    $u_sql = "update wine_brand set country='$new_country',change_time = '$change_time' where id='$id'";
                    $dbObj->query($u_sql);
                }else if(strpos($result['country'],$good['origin'])===false){
                    $new_country = $result['country'].";".$good['origin'];
                    $u_sql = "update wine_brand set country='$new_country',change_time = '$change_time' where id='$id'";
                    $dbObj->query($u_sql);
                }
            }

            //开始执行匹配
            //获取到品牌id,根据相关信息匹配商品是否一致
            $wql = "select id,name,content,alcohol,scent,price from wine_detail where brand_id='$id'";
            $pipei_result = $dbObj->fetchAll($wql);
            foreach ($pipei_result as $key => $val) {
                $where_id = $val['id'];
                //净含量判定
                if(!empty($val['content']) && !empty($good['content'])){
                    $content_val = str_replace(array("ml","L","l","ML","毫升","升"),'',$val['content']);
                    $content_1919 = str_replace(array("ml","L","l","ML","毫升","升"),'',$good['content']);
                    if($content_val != $content_1919){
                        continue;
                    }
                }

                //酒精度判定
                if(!empty($val['alcohol']) && !empty($good['alcohol'])){
                    
                    $alcohol_val = str_replace(array("度","°","vol","%vol","%Vol","% vol","%VOL","%"),'',$val['alcohol']);
                    $alcohol_1919 = str_replace(array("度","°","vol","%vol","%Vol","% vol","%VOL","%"),'',$good['alcohol']);

                    $alcohol_val = trim($alcohol_val);
                    $alcohol_val = preg_replace('/\（.*?\）/', '', $alcohol_val);
                    $alcohol_val = preg_replace('/\(.*?\)/', '', $alcohol_val);
                    $alcohol_val = preg_replace('/\（.*?\)/', '', $alcohol_val);
                    $alcohol_val = preg_replace('/\(.*?\）/', '', $alcohol_val);

                    $alcohol_1919 = trim($alcohol_1919);
                    $alcohol_1919 = preg_replace('/\（.*?\）/', '', $alcohol_1919);
                    $alcohol_1919 = preg_replace('/\(.*?\)/', '', $alcohol_1919);
                    $alcohol_1919 = preg_replace('/\（.*?\)/', '', $alcohol_1919);
                    $alcohol_1919 = preg_replace('/\(.*?\）/', '', $alcohol_1919);

                    if($alcohol_val != $alcohol_1919){
                        continue;
                    }
                }

                //香型判定
                if(!empty($val['scent']) && !empty($good['scent'])){
                    if($val['scent'] != $good['scent']){
                        continue;
                    }
                }

                //价格判定，两者价格相差不超过200
                if(!empty($val['price']) && !empty($good['price_1919'])){
                    $price = intval($val['price']);
                    $price_1919 = intval($good['price_1919']);
                    if(abs($price-$price_1919) > 200){
                        continue;
                    }
                }

                if($val['name'] != '' && $good['name_1919'] != '' && empty($val['id_1919'])){
                    $name = str_replace('度', "°", $val['name']);
                    $name = preg_replace('/【(.*?)\】/','',$name);
                    $name_1919 = str_replace(' ', "", $good['name_1919']);
                    $name_1919 = str_replace('度', "°", $name_1919);

                    $xiangsidu = $lcs->getSimilar($name,$name_1919);//匹配名字相似度
                    if($xiangsidu > 0.5){
                        $pipei = true;
                        //匹配相似,执行更新操作
                        
                        $pic_1919_ifeng = '';
                        $res_1919 = explode(';',$good['pic_1919']);
                        foreach ($res_1919 as $val) {
                            $img_res = getImage($val,'','',$type=1);
                            $post_data = array (
                                'width'=>430,
                                'height'=>430,
                                'controller'=>'wineapp',
                                'fileName'=>'icon',
                                "icon" => "@".$img_res['file_name'],
                            );
                            
                            $res_param = post($paramimg_url,$post_data);
                            $res_param = json_decode($res_param,true);
                            if($res_param['statu'] == 1){
                                //upload成功
                                if(empty($pic_jiuxian_ifeng)){
                                    $pic_1919_ifeng = $res_param['msg'];
                                }else{
                                    $pic_1919_ifeng .= ";".$res_param['msg'];
                                }
                            }else{
                                //上传资源服务器失败,记录
                                $log_text = "图片上传资源服务器失败";
                                $log = array(
                                    'cl_source'=>2,
                                    'cl_goods_id'=>$log_goods_id,
                                    'cl_goods'=>$goods_id,
                                    'cl_status'=>0,
                                    'cl_content'=>$log_text,
                                    'cl_time'=>time(),
                                    'cl_createtime'=>$nowtime
                                );
                                $res = $dbObj->insert($log_table,$log);
                            }
                            unlink($img_res['file_name']);
                            //sleep(1);
                        }
                        $good['pic_1919'] = $pic_1919_ifeng;
                        $info = array(
                            'change_time'=>time(),
                            'name_1919'=>$good['name_1919'],
                            'score_1919'=>$good['score_1919'],
                            'price_1919'=>$good['price_1919'],
                            'pic_1919'=>$good['pic_1919'],
                            'id_1919'=>$goods_id
                            );

                        $where = "id=$where_id";
                        $dbObj->update($detaile_table,$info,$where);
                        $log_text = "成功-匹配插入";
                        $log = array(
                            'cl_source'=>2,
                            'cl_goods_id'=>$log_goods_id,
                            'cl_goods'=>$goods_id,
                            'cl_status'=>1,
                            'cl_content'=>$log_text,
                            'cl_time'=>time(),
                            'cl_createtime'=>$nowtime
                        );
                        $res = $dbObj->insert($log_table,$log);
                        break;
                    }
                }
            }

            //没有匹配到，插入新商品
            if(!isset($pipei)){
                $good['brand_id']=$id;
                $good['change_time']=time();
                $good['createtime']=date("Y-m-d H:i:s");
                $good['id_1919']=$goods_id;
                $good['name'] = $good['name_1919'];//商品1919名称
                $good['price'] = $good['price_1919'];//商品1919价格
                
                $pic_1919_ifeng = '';
                $res_1919 = explode(';',$good['pic_1919']);
                
                foreach ($res_1919 as $val) {
                    $img_res = getImage($val,'','',$type=1);
                    $post_data = array (
                        'width'=>430,
                        'height'=>430,
                        'controller'=>'wineapp',
                        'fileName'=>'icon',
                        "icon" => "@".$img_res['file_name'],
                    );

                    $res_param = post($paramimg_url,$post_data);

                    $res_param = json_decode($res_param,true);

                    if($res_param['statu'] == 1){
                        //upload成功
                        if(empty($pic_jiuxian_ifeng)){
                            $pic_1919_ifeng = $res_param['msg'];
                        }else{
                            $pic_1919_ifeng .= ";".$res_param['msg'];
                        }
                    }else{
                        //上传资源服务器失败,记录
                        $log_text = "图片上传资源服务器失败";
                        $log = array(
                            'cl_source'=>2,
                            'cl_goods_id'=>$log_goods_id,
                            'cl_goods'=>$goods_id,
                            'cl_status'=>0,
                            'cl_content'=>$log_text,
                            'cl_time'=>time(),
                            'cl_createtime'=>$nowtime
                        );
                        $res = $dbObj->insert($log_table,$log);
                    }
                    unlink($img_res['file_name']);
                    //sleep(1);
                }

                $good['pic_1919'] = $pic_1919_ifeng;
                unset($good['species']);
                unset($good['brand']);
                $res = $dbObj->insert($detaile_table,$good);
                $log_text = "成功-无匹配插入";
                $log = array(
                    'cl_source'=>2,
                    'cl_goods_id'=>$log_goods_id,
                    'cl_goods'=>$goods_id,
                    'cl_status'=>1,
                    'cl_content'=>$log_text,
                    'cl_time'=>time(),
                    'cl_createtime'=>$nowtime
                );
                $res = $dbObj->insert($log_table,$log);        
            }
        }//新商品id处理结束
    }
}
    exit;
    //$end_time = time();
    //$time_cha = ($end_time - $start_time)/60;
    //echo $time_cha."\n";
?>