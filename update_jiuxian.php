<?php 
/* 
* @Author: zhangxu
* @Date:   2016-1-4 14:25:10
* @Last Modified by:   anchen
* @Last Modified time: 2017-03-10 15:03:16
* 该脚本用于更新酒仙网产品,每次采集200条
*/
//header("Content-Type:text/html;charset=utf-8;");
set_time_limit(0);
require_once 'QueryList/vendor/autoload.php';
require_once 'db.class.php';
require_once 'cls.php';
require_once 'chinesespell.php';

use QL\QueryList;

//此处开始执行酒仙网获取商品id更新

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

//以下开始执行商品信息更新
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

    function post($url,$post_data = '', $timeout = 5){//curl
        $ch = curl_init();
 
        curl_setopt ($ch, CURLOPT_URL, $url);
 
        curl_setopt ($ch, CURLOPT_POST, 1);
 
        if($post_data != ''){
 
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
 
        }
 
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
 
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
 
        curl_setopt($ch, CURLOPT_HEADER, false);
 
        $res=json_decode(curl_exec($ch),true);//指定以数组形式返回

        curl_close($ch);

        return $res;
 
    }

//$start_time = time();
$detaile_table = "wine_detail";
$brand_table = "wine_brand";
$log_table = "wine_collect_log";
$rules = array(
    'type' => array('.dGuide > a','html'),//获取分组信息
    'name' => array('.dGuide > span','html'),//获取酒名
    'score' => array('li.comScore > em','html'),//获取评分
    'chandi' => array('ul.intrList','html'),//获取

);
$pattern_bigpic = "/(lbarr.*')(.*?)('];)/";
$rules_pic = array(
    'pic' => array('div.show-list-con img','src'),//获取小图
);
//资源服务器路径
$param_url = "";


//获取goods_id表酒仙网商品编号的最大id
$max_brandid_sql = "select id from wine_goods where source = 1 and status = 1 order by id desc limit 1";
$max_brandid_result = $dbObj->getOne($max_brandid_sql);
$max_brandid = $max_brandid_result['id'];


$cl_sql = "select cl_goods_id from wine_collect_log where cl_source = 1 order by cl_time desc limit 1";
$cl_result = $dbObj->getOne($cl_sql);

if($cl_result){
    $strat_goodsid = $cl_result['cl_goods_id'];
    //获取id在source=1查询条件下的行数
    $num_sql = "select count(1) as num from wine_goods where  source = 1 and status = 1 and id < $strat_goodsid  order by id ";
    $num_result = $dbObj->getOne($num_sql);

    $strat = $num_result['num'] + 1;

    //获取的最后一条酒仙网抓取数据id为酒仙网goods_id表的最大id，则重新开始采集
    if($strat_goodsid >= $max_brandid){
        $sql = "select id,goods_id,type from wine_goods where source = 1 and status = 1 limit 0,1000";
    }else{
        $sql = "select id,goods_id,type from wine_goods where source = 1 and status = 1 limit $strat,1000";
    }
    
}else{
    $sql = "select id,goods_id,type from wine_goods where source = 1 and status = 1 limit 0,1000";
}


//查询酒仙网的产品id
$result = $dbObj->fetchAll($sql);

//循环产品id
foreach ($result as $key => $value) {
    //每次采集的200条达到goods_id表的最大id时，停止本次采集
    if($value['id'] > $max_brandid){
        exit;
    }
    $good = array(
            'brand_id'=>'',//商品品牌编号
            'name'=>'',//主要商品名
            'price'=>'',//主要价格
            'name_jiuxian'=>'',//商品酒仙网名
            'price_jiuxian'=>'',//商品酒仙网价格
            'origin'=>'',//产地
            'company'=>'',//酒厂
            'content'=>'',//净含量
            'alcohol'=>'',//酒精度
            'specifications'=>'',//规格
            'conditions'=>'',//储藏条件
            'xianggui'=>'',//箱规
            'material'=>'',//原料
            'scent'=>'',//香型
            'score_jiuxian'=>'',//酒仙网评分
            'change_time'=>'',//修改时间戳
            'createtime'=>'',//创建时间
            'id_jiuxian'=>'',//酒仙网产品唯一编号
            'wine_type'=>'',//产品类型
            'chanqu'=>'',//产区
            'pic_jiuxian'=>'',//酒仙网小图
            'bigpic_jiuxian'=>'',//酒仙大图
            'species'=>'',//种类
            'brand'=>'',//品牌

    );
    $nowtime = date("Y-m-d H:i:s",time());
    $goods_id = $value['goods_id'];
    $log_goods_id = $value['id'];
    $url = "http://www.jiuxian.com/goods-".$goods_id.".html";
    $content = curl_get($url);

    if(empty($content)){
        $log_text = "file_get_contents未获取到内容";
        $log = array(
            'cl_source'=>1,
            'cl_goods_id'=>$log_goods_id,
            'cl_goods'=>$goods_id,
            'cl_status'=>0,
            'cl_content'=>$log_text,
            'cl_time'=>time(),
            'cl_createtime'=>$nowtime
        );
        $res = $dbObj->insert($log_table,$log);
        //file_put_contents("log.txt", $log_text.PHP_EOL, FILE_APPEND);
        continue;
    }

    $data = QueryList::Query($content,$rules)->data;

    if(!$data){
        $log_text = "QueryList未获取到内容";
        $log = array(
            'cl_source'=>1,
            'cl_goods_id'=>$log_goods_id,
            'cl_goods'=>$goods_id,
            'cl_status'=>0,
            'cl_content'=>$log_text,
            'cl_time'=>time(),
            'cl_createtime'=>$nowtime
        );
        $res = $dbObj->insert($log_table,$log);
        //file_put_contents("log.txt", $log_text.PHP_EOL, FILE_APPEND);
        continue;
    }


    //php模拟post请求获取动态加载的数据
    
    $post_data = array(  
        'proId' => $goods_id,  
        'resId' => 2  
    );
    $data_url = "http://www.jiuxian.com/pro/selectProActByProId.htm";
    $result = post($data_url,$post_data);

    if(!$result){
        $log_text = "模拟post请求未获取到数据";
        $log = array(
            'cl_source'=>1,
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

    if(!isset($result['act']['nowPrice'])){
        $log_text = "未获取到商品价格";
        $log = array(
            'cl_source'=>1,
            'cl_goods_id'=>$log_goods_id,
            'cl_goods'=>$goods_id,
            'cl_status'=>0,
            'cl_content'=>$log_text,
            'cl_time'=>time(),
            'cl_createtime'=>$nowtime
        );
        $res = $dbObj->insert($log_table,$log);
        //file_put_contents("log.txt", $log_text.PHP_EOL, FILE_APPEND);
        continue;
    }

    if(!isset($data[0]['name'])){
        $log_text = "未获取到商品名称";
        $log = array(
            'cl_source'=>1,
            'cl_goods_id'=>$log_goods_id,
            'cl_goods'=>$goods_id,
            'cl_status'=>0,
            'cl_content'=>$log_text,
            'cl_time'=>time(),
            'cl_createtime'=>$nowtime
        );
        $res = $dbObj->insert($log_table,$log);
        //file_put_contents("log.txt", $log_text.PHP_EOL, FILE_APPEND);
        continue;
    }

    // if(!isset($data[1]['type'])){
    //     $log_text = "未获取到商品种类";
    //     $log = array(
    //         'cl_source'=>1,
    //         'cl_goods_id'=>$log_goods_id,
    //         'cl_goods'=>$goods_id,
    //         'cl_status'=>0,
    //         'cl_content'=>$log_text,
    //         'cl_time'=>time(),
    //         'cl_createtime'=>$nowtime
    //     );
    //     $res = $dbObj->insert($log_table,$log);
    //     //file_put_contents("log.txt", $log_text.PHP_EOL, FILE_APPEND);
    //     continue;
    // }

    if(!isset($data[2]['type'])){
        $log_text = "未获取到商品品牌";
        $log = array(
            'cl_source'=>1,
            'cl_goods_id'=>$log_goods_id,
            'cl_goods'=>$goods_id,
            'cl_status'=>0,
            'cl_content'=>$log_text,
            'cl_time'=>time(),
            'cl_createtime'=>$nowtime
        );
        $res = $dbObj->insert($log_table,$log);
        //file_put_contents("log.txt", $log_text.PHP_EOL, FILE_APPEND);
        continue;
    }

    if(!isset($data[0]['score'])){
        $log_text = "未获取到商品评分";
        $log = array(
            'cl_source'=>1,
            'cl_goods_id'=>$log_goods_id,
            'cl_goods'=>$goods_id,
            'cl_status'=>0,
            'cl_content'=>$log_text,
            'cl_time'=>time(),
            'cl_createtime'=>$nowtime
        );
        $res = $dbObj->insert($log_table,$log);
        //file_put_contents("log.txt", $log_text.PHP_EOL, FILE_APPEND);
        continue;
    }


        //定义商品详细信息数组

        $good['price_jiuxian'] = $result['act']['nowPrice'];//商品价格
        $good['name_jiuxian'] = $data[0]['name'];//商品名称
        //抓取的名字中含有'号导致sql报错
        $good['name_jiuxian'] = str_replace("'","\'",$good['name_jiuxian']);

        $good['species'] = $value['type'];//种类

        $good['brand'] = $data[2]['type'];//品牌
        $good['brand'] = preg_replace('/\（.*?\）/', '', $good['brand']);
        $good['brand'] = preg_replace('/\(.*?\)/', '', $good['brand']);
        $good['brand'] = preg_replace('/\（.*?\)/', '', $good['brand']);
        $good['brand'] = preg_replace('/\(.*?\）/', '', $good['brand']);
        $good['score_jiuxian'] = $data[0]['score'];//评分

        $arr = trim(strip_tags($data[0]['chandi']));
        $product_data = explode(PHP_EOL,$arr);


        if(!$product_data){
            $log_text = "未获取到详细信息";
            $log = array(
                'cl_source'=>1,
                'cl_goods_id'=>$log_goods_id,
                'cl_goods'=>$goods_id,
                'cl_status'=>0,
                'cl_content'=>$log_text,
                'cl_time'=>time(),
                'cl_createtime'=>$nowtime
            );
            $res = $dbObj->insert($log_table,$log);
            //file_put_contents("log.txt", $log_text.PHP_EOL, FILE_APPEND);
            continue;
        }

        foreach ($product_data as $key => $value) {
            $str1 = explode('：',trim($value));
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
                case '香型': //洋酒,白酒酒厂字段
                    $good['scent'] = $str1[1];
                    break;         
            }
        }

        
        preg_match_all($pattern_bigpic, $content, $matches);

        if(!$matches[2]){
            $log_text = "未获取到大图";
            $log = array(
                'cl_source'=>1,
                'cl_goods_id'=>$log_goods_id,
                'cl_goods'=>$goods_id,
                'cl_status'=>0,
                'cl_content'=>$log_text,
                'cl_time'=>time(),
                'cl_createtime'=>$nowtime
            );
            $res = $dbObj->insert($log_table,$log);
            //file_put_contents("log.txt", $log_text.PHP_EOL, FILE_APPEND);
        }

        $bigpic = '';

        foreach ($matches[2] as $key => $value) {
            if($bigpic == ''){
                $bigpic = $value;
            }else{
                $bigpic = $bigpic.";".$value;
            }
        }
        //获取小图
        $data_pic = QueryList::Query($content,$rules_pic)->data;

        if(!$data_pic){
            $log_text = "未获取到小图";
            $log = array(
                'cl_source'=>1,
                'cl_goods_id'=>$log_goods_id,
                'cl_goods'=>$goods_id,
                'cl_status'=>0,
                'cl_content'=>$log_text,
                'cl_time'=>time(),
                'cl_createtime'=>$nowtime
            );
            $res = $dbObj->insert($log_table,$log);
            //file_put_contents("log.txt", $log_text.PHP_EOL, FILE_APPEND);
        }

        $pic = '';
        foreach ($data_pic as $key => $value) {
            if($pic == ''){
                $pic = $value['pic'];
            }else{
                $pic = $pic.";".$value['pic'];
            }
        }
        //产品信息采集完毕end
        
        //判定goods_id是否已存在
        $sql = "select id from wine_detail where id_jiuxian='$goods_id'";
        $detail_id = $dbObj->getOne($sql);
        if($detail_id){
            //执行更新
            $update_data = array(
                'name'=>$good['name_jiuxian'],//主要商品名
                'price'=>$good['price_jiuxian'],//主要价格
                'name_jiuxian'=>$good['name_jiuxian'],//商品酒仙网名称
                'price_jiuxian'=>$good['price_jiuxian'],//商品酒仙网价格
                'origin'=>$good['origin'],//产地
                'company'=>$good['company'],//酒厂
                'content'=>$good['content'],//净含量
                'alcohol'=>$good['alcohol'],//酒精度
                'specifications'=>$good['specifications'],//规格
                'conditions'=>$good['conditions'],//储藏条件
                'xianggui'=>$good['xianggui'],//箱规
                'material'=>$good['material'],//原料
                'scent'=>$good['scent'],//香型
                'score_jiuxian'=>$good['score_jiuxian'],//酒仙网评分
                'change_time'=>time(),//时间戳
                'wine_type'=>$good['wine_type'],//产品类型
                'chanqu'=>$good['chanqu'],//产区
                //'pic_jiuxian'=>$pic,//酒仙网小图
                //'bigpic_jiuxian'=>$bigpic,//酒仙大图
            );
            $where = " id_jiuxian = '$goods_id'";
            $res = $dbObj->update($detaile_table,$update_data,$where);

        }else{
            //不存在，为新抓取的商品id，执行插入
            //抓取的数据中含有'号导致sql语句报错
            $good['brand'] = str_replace("'","\'",$good['brand']);
            $species = $good['species'];
            $brand = $good['brand'];
    
            $brand_data = array('species'=>$species,'brand'=>$brand,'country'=>$good['origin']);

         
            //检查需要插入的商品品牌是否已存在，不存在插入，已存在更新产地
            $sql = "select id,country from wine_brand where species='$species' and brand='$brand'";
            $result = $dbObj->getOne($sql);

            if(!$result){
                //为新增的品牌

                $spell = new ChineseSpell();
                $brand_spell = iconv("UTF-8","gb2312", $good['brand']);
                $quanpin = $spell->getFullSpell($brand_spell);
                $firstpin = $spell->getChineseSpells($brand_spell,'',1);

                if(empty($firstpin) || empty($quanpin)){
                    $log_text = "未获取拼音";
                    $log = array(
                        'cl_source'=>1,
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
                $brand_data['source'] = 1;
                $res = $dbObj->insert($brand_table,$brand_data);
                $id = $dbObj->insertId();
            }else{
                $id = $result['id'];
                $change_time = time();
                if(!empty($good['origin'])){
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
            }//品牌检测end
            $good['brand_id']=$id;
            $good['name'] =  $good['name_jiuxian'];//主要商品名
            $good['price'] =$good['price_jiuxian'];//主要价格
            $good['change_time']=time();
            $good['createtime']=date("Y-m-d H:i:s");
            $good['id_jiuxian']=$goods_id;
            $pic_jiuxian_ifeng = '';
            $pic_jiuxian_ifengbig = '';

            $res_jiuxian = explode(';',$pic);
            foreach ($res_jiuxian as $val) {
                $img_res = getImage($val,'','',$type=1);
                $post_data = array (
                    'width'=>54,
                    'height'=>54,
                    'controller'=>'wineapp',
                    'fileName'=>'icon',
                    "icon" => "@".$img_res['file_name'],
                );
                
                $res_param = post($param_url,$post_data);

                if($res_param['statu'] == 1){
                    //upload成功
                    if(empty($pic_jiuxian_ifeng)){
                        $pic_jiuxian_ifeng = $res_param['msg'];
                    }else{
                        $pic_jiuxian_ifeng .= ";".$res_param['msg'];
                    }
                }else{
                    //上传资源服务器失败,记录
                    $log_text = "图片上传资源服务器失败";
                    $log = array(
                        'cl_source'=>1,
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

            $res_jiuxian_big = explode(';',$bigpic);
            foreach ($res_jiuxian_big as $val) {
                $img_res_big = getImage($val,'','',$type=1);
                $post_data_big = array (
                    'width'=>440,
                    'height'=>440,
                    'controller'=>'wineapp',
                    'fileName'=>'icon',
                    "icon" => "@".$img_res_big['file_name'],
                );
                
                $res_param_big = post($param_url,$post_data_big);

                if($res_param_big['statu'] == 1){
                    //upload成功
                    if(empty($pic_jiuxian_ifengbig)){
                        $pic_jiuxian_ifengbig = $res_param_big['msg'];
                    }else{
                        $pic_jiuxian_ifengbig .= ";".$res_param_big['msg'];
                    }
                }else{
                    //上传资源服务器失败,记录
                    $log_text = "图片上传资源服务器失败";
                    $log = array(
                        'cl_source'=>1,
                        'cl_goods_id'=>$log_goods_id,
                        'cl_goods'=>$goods_id,
                        'cl_status'=>0,
                        'cl_content'=>$log_text,
                        'cl_time'=>time(),
                        'cl_createtime'=>$nowtime
                    );
                    $res = $dbObj->insert($log_table,$log);
                }
                unlink($img_res_big['file_name']);
                //sleep(1);
            }
            
            $good['pic_jiuxian']=$pic_jiuxian_ifeng;//酒仙网小图
            $good['bigpic_jiuxian']=$pic_jiuxian_ifengbig;//酒仙大图
            unset($good['species']);
            unset($good['brand']);
            $res = $dbObj->insert($detaile_table,$good);
        }
        $log_text = "成功";
        $log = array(
            'cl_source'=>1,
            'cl_goods_id'=>$log_goods_id,
            'cl_goods'=>$goods_id,
            'cl_status'=>1,
            'cl_content'=>$log_text,
            'cl_time'=>time(),
            'cl_createtime'=>$nowtime
        );
        $res = $dbObj->insert($log_table,$log);
    }
    //$end_time = time();
    //$time_cha = ($end_time - $start_time)/60;
    //echo $time_cha."\n";
?>