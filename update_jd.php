<?php 
/* 
* @Author: zhangxu
* @Date:   2016-1-4 14:25:10
* @Last Modified by:   anchen
* @Last Modified time: 2017-03-10 15:03:01
* 该脚本用于更新1919产品
*/
//header("Content-Type:text/html;charset=utf-8;");
require_once 'QueryList/vendor/autoload.php';
require_once 'db.class.php';
require_once 'cls.php';
require_once 'chinesespell.php';

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
$species_array = array('白酒','葡萄酒','洋酒','啤酒','黄酒/保健酒','果酒/饮料','收藏酒/陈年老酒');

//资源服务器路径
$paramimg_url = "";

//获取wine_goods表京东商品编号的最大id
$max_brandid_sql = "select id from wine_goods where source = 3 and status = 1 order by id desc limit 1";
$max_brandid_result = $dbObj->getOne($max_brandid_sql);
$max_brandid = $max_brandid_result['id'];

//获取日志记录里最后一次操作的京东商品id(对应wine_goods表的id)
$cl_sql = "select cl_goods_id from wine_collect_log where cl_source = 3 order by cl_time desc limit 1";
$cl_result = $dbObj->getOne($cl_sql);
if($cl_result){
    $strat_goodsid = $cl_result['cl_goods_id'];
    //获取id在source=3查询条件下的行数
    $num_sql = "select count(1) as num from wine_goods where  source = 3 and status = 1 and id < $strat_goodsid  order by id ";
    $num_result = $dbObj->getOne($num_sql);
    $strat = $num_result['num'] + 1;
    //获取的最后一条京东抓取数据id为酒仙网goods_id表的最大id，则重新开始采集
    if($strat_goodsid >= $max_brandid){
        $sql = "select id,goods_id,type from wine_goods where source = 3 and status = 1 limit 0,5000";
    }else{
        $sql = "select id,goods_id,type from wine_goods where source = 3 and status = 1 limit $strat,5000";
    }
    
}else{
    $sql = "select id,goods_id,type from wine_goods where source = 3 and status = 1 limit 0,5000";
}

//查询京东的产品id
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
            'name_jd'=>'',//商品酒仙网名称
            'price_jd'=>'',//商品酒仙网价格
            'origin'=>'',//产地
            'company'=>'',//酒厂
            'content'=>'',//净含量
            'alcohol'=>'',//酒精度
            'specifications'=>'',//规格
            'conditions'=>'',//储藏条件
            'xianggui'=>'',//箱规
            'material'=>'',//原料
            'scent'=>'',//香型
            'goodRate_jd'=>'',//京东好评
            'generalRate_jd'=>'',//京东中评
            'poorRate_jd'=>'',//京东差评
            'change_time'=>'',//修改时间戳
            'createtime'=>'',//创建时间
            'id_jd'=>'',//酒仙网产品唯一编号
            'wine_type'=>'',//产品类型
            'chanqu'=>'',//产区
            'pic_jd'=>'',//酒仙网小图
            'bigpic_jd'=>'',//酒仙大图
            'species'=>'',//种类
            'brand'=>'',//品牌

    );
    $nowtime = date("Y-m-d H:i:s",time());
    $goods_id = $value['goods_id'];
    $log_goods_id = $value['id'];
    $url = "https://item.jd.com/".$goods_id.".html";
    $content = curl_get($url);

    if(empty($content)){
        $log_text = "file_get_contents未获取到内容";
        $log = array(
            'cl_source'=>3,
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

    //获取种类品牌和商品名
    $rules = array(
        'name' => array('div#name > h1','text'),
        'zhonglei' => array('div.breadcrumb span a','text'),
    );

    $data = QueryList::Query($content,$rules,'','UTF-8','GB2312')->data;
    if(count($data) != 4){
        $log_text = "获取到的种类品牌信息不全";
        $log = array(
            'cl_source'=>3,
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

//京东种类 黄酒/养生酒 替换为 黄酒/保健酒
    if($data[1]['zhonglei'] == '黄酒/养生酒'){
        $data[1]['zhonglei'] = '黄酒/保健酒';
    }

    if(!in_array($data[1]['zhonglei'],$species_array)){
        $log_text = "商品种类未匹配到";
        $log = array(
            'cl_source'=>3,
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

    if($data[2]['zhonglei'] == ''){
        $log_text = "商品品牌未匹配到";
        $log = array(
            'cl_source'=>3,
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

    $good['species'] = $data[1]['zhonglei'];
    if($good['species'] == '黄酒' || $good['species'] == '保健酒' || $good['species'] == '黄酒/保健酒/其他'){
        $good['species'] = '黄酒/保健酒';
    }
    $good['brand'] = $data[2]['zhonglei'];
    $good['name_jd'] = $data[0]['name'];
    $good['name_jd'] = str_replace("'","\'",$good['name_jd']);
    //商品种类品牌和商品名获取结束

    //php模拟post请求获取商品详情
    $param = array(
        'param' => array('table.Ptable td','text'),
    );
    $data_param = QueryList::Query($content,$param)->data;
    if(!$data_param){
        $log_text = "未获取到商品详情";
        $log = array(
            'cl_source'=>3,
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

    foreach ($data_param as $key => $value) {
        if($key%2==0){
            switch ($value['param']) {
                case '酒精度':
                    $good['alcohol'] = $data_param[$key+1]['param'];
                    break;
                case '香型':
                    $good['scent'] = $data_param[$key+1]['param'];
                    break;
                case '省份':
                    $good['origin'] = $data_param[$key+1]['param'];
                    break;
                case '储存方法':
                    $good['conditions'] = $data_param[$key+1]['param'];
                    break;
                case '规格':
                    $good['specifications'] = $data_param[$key+1]['param'];
                    break;
                case '净含量':
                    $good['content'] = $data_param[$key+1]['param'];
                    break; 
                case '品牌':
                    $good['brand'] = $data_param[$key+1]['param'];
                    break; 
                case '原料':
                    $good['material'] = $data_param[$key+1]['param'];
                    break;  
                case '原产国':
                    $good['origin'] = $data_param[$key+1]['param'];
                    break;
                case '产区':
                    $good['chanqu'] = $data_param[$key+1]['param'];
                    break;  
                case '类型':
                    $good['wine_type'] = $data_param[$key+1]['param'];
                    break; 
                case '类别':
                    $good['wine_type'] = $data_param[$key+1]['param'];
                    break;
                case '等级':
                    break; 
                case '葡萄品种':
                    break;
                case '特性':
                    break; 
                case '年份':
                    break; 
                case '类别':
                    break; 
                case '适用人群':
                    break; 
                case '保质期':
                    break;              
                default:
                    break;
            }
        }
    }

    if(empty($good['alcohol'])){
        preg_match('/(\d+)(度|°)/i', $good['name_jd'], $n);
        if(empty($n)){
            $good['alcohol'] = '';
        }else{
            $good['alcohol'] = $n[0];
        }
    }

    if(empty($good['content'])){
        preg_match('/(\d+)(ml|L|ML|l|毫升|升)/i', $good['name_jd'], $m); 

        if(empty($m)){
            $good['content'] = '';
        }else{
            $good['content'] = $m[0];
        }
    }

    $good['brand'] = preg_replace('/\（.*?\）/', '', $good['brand']);
    $good['brand'] = preg_replace('/\(.*?\)/', '', $good['brand']);
    $good['brand'] = preg_replace('/\（.*?\)/', '', $good['brand']);
    $good['brand'] = preg_replace('/\(.*?\）/', '', $good['brand']);
    $good['brand'] = str_replace("'","\'",$good['brand']);
    //获取商品详情结束
    
    //获取评论
    $comment_url = "https://sclub.jd.com/comment/productPageComments.action?productId=".$goods_id."&score=0&sortType=3&page=0&pageSize=10&isShadowSku=0&callback=fetchJSON_comment98vv2649";
    $comment = curl_get($comment_url);

    if(!$comment || empty($comment)){
        $log_text = "未获取到商品评论";
        $log = array(
            'cl_source'=>3,
            'cl_goods_id'=>$log_goods_id,
            'cl_goods'=>$goods_id,
            'cl_status'=>0,
            'cl_content'=>$log_text,
            'cl_time'=>time(),
            'cl_createtime'=>$nowtime
        );
        $res = $dbObj->insert($log_table,$log);
    }
    //好评
    $partten = "/\"goodRateShow\":(.*?)\,/";
    preg_match($partten, $comment, $match);
    $good['goodRate_jd'] = $match[1];

    //中评
    $partten = "/\"generalRateShow\":(.*?)\}/";
    preg_match($partten, $comment, $match);
    $good['generalRate_jd'] = $match[1];

    //差评
    $partten = "/\"poorRateShow\":(.*?)\,/";
    preg_match($partten, $comment, $match);
    $good['poorRate_jd'] = $match[1];

    //获取评分结束

    //获取价格
    $price_url = "https://p.3.cn/prices/get?type=1&area=1_72_&pdtk=&pduid=&pdpin=&pdbp=0&skuid=J_".$goods_id."&callback=cnp";
    $res = curl_get($price_url);
    $price_pattern = "/\"p\":\"(.*?)\",/";
    preg_match($price_pattern, $res, $match);
    if(!$match[1] || empty($match[1])){
        $log_text = "未获取到商品价格";
        $log = array(
            'cl_source'=>3,
            'cl_goods_id'=>$log_goods_id,
            'cl_goods'=>$goods_id,
            'cl_status'=>0,
            'cl_content'=>$log_text,
            'cl_time'=>time(),
            'cl_createtime'=>$nowtime
        );
        $res = $dbObj->insert($log_table,$log);
    }
    $good['price_jd'] = $match[1];
    //获取价格end
    
    //获取图集
    $rules_pic = array(
            'pic_url' => array('div.spec-items>ul.lh>li>img','src'),//获取图片信息
        );

    $data_pic = QueryList::Query($content,$rules_pic)->data;

    $pic = '';
    foreach ($data_pic as $key => $value) {
        $value['pic_url'] = "https:".$value['pic_url'];
        if($pic == ''){
            $pic = $value['pic_url'];
        }else{
            $pic = $pic.";".$value['pic_url'];
        }
    }

    $good['pic_jd'] = $pic;
    $good['bigpic_jd'] = str_replace('/n5/', '/n1/', $pic);
    //获取图集结束
    //种类判定
    switch ($good['species']) {
        case '白酒':
            $good['species'] = 1;
            break;
        case '葡萄酒':
            $good['species'] = 2;
            break;
        case '洋酒':
            $good['species'] = 3;
            break;
        case '啤酒':
            $good['species'] = 4;
            break;
        case '黄酒/保健酒':
            $good['species'] = 5;
            break;
        case '果酒':
            $good['species'] = 6;
            break;
        case '收藏酒/陈年老酒':
            $good['species'] = 7;
            break;
        default:
            echo $good['species'];
            break;
    }
    //更新wine_goods表的type字段
    $tableName_goods = 'wine_goods';
    $info = array(
        'type'=>$good['species']
    );
    $where = " id = '$log_goods_id'";
    $dbObj->update($tableName_goods, $info, $where);

    //判定goods_id是否已存在
    $sql_goodsid = "select id,id_jiuxian,id_1919 from wine_detail where id_jd='$goods_id'";
    $detail_id = $dbObj->getOne($sql_goodsid);
    if($detail_id){
        //执行更新
        //更新操作前需判定此商品是否已和现有商品匹配，若匹配，则更新京东专有字段，反之更新所有详情字段
        if($detail_id['id_jiuxian'] || $detail_id['id_1919']){
            $update_data = array(
                'name_jd'=>$good['name_jd'],//商品京东名称
                'price_jd'=>$good['price_jd'],//商品京东价格
                'goodRate_jd'=>$good['goodRate_jd'],
                'generalRate_jd'=>$good['generalRate_jd'],
                'poorRate_jd'=>$good['poorRate_jd'],
                'change_time'=>time(),//时间戳
                //'pic_jd'=>$good['pic_jd'],//京东小图
                //'bigpic_jd'=>$good['bigpic_jd']//京东大图
            );

        }else{
            $update_data = array(
                'name'=>$good['name_jd'],//商品1919名称
                'price'=>$good['price_jd'],//商品1919价格
                'name_jd'=>$good['name_jd'],//商品1919名称
                'price_jd'=>$good['price_jd'],//商品1919价格
                'goodRate_jd'=>$good['goodRate_jd'],
                'generalRate_jd'=>$good['generalRate_jd'],
                'poorRate_jd'=>$good['poorRate_jd'],
                'change_time'=>time(),//时间戳
                //'pic_jd'=>$good['pic_jd'],//京东小图
                //'bigpic_jd'=>$good['bigpic_jd'],//京东大图
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
        $where = " id_jd = '$goods_id'";
        $res = $dbObj->update($detaile_table,$update_data,$where);

        $log_text = "成功-更新";
        $log = array(
            'cl_source'=>3,
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
        $pic_jd_ifeng = '';
        $pic_jd_ifeng_big = '';
        
        $res_jd = explode(';',$good['pic_jd']);
        foreach ($res_jd as $val) {
            $img_res = getImage($val,'','',$type=1);
            $post_data = array (
                'width'=>50,
                'height'=>50,
                'controller'=>'wineapp',
                'fileName'=>'icon',
                "icon" => "@".$img_res['file_name'],
            );
            
            $res_param = post($paramimg_url,$post_data);
            $res_param = json_decode($res_param,true);

            if($res_param['statu'] == 1){
                //upload成功
                if(empty($pic_jd_ifeng)){
                    $pic_jd_ifeng = $res_param['msg'];
                }else{
                    $pic_jd_ifeng .= ";".$res_param['msg'];
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

        $res_jd_big = explode(';',$good['bigpic_jd']);
        foreach ($res_jd_big as $val) {
            $img_res = getImage($val,'','',$type=1);
            $post_data = array (
                'width'=>350,
                'height'=>350,
                'controller'=>'wineapp',
                'fileName'=>'icon',
                "icon" => "@".$img_res['file_name'],
            );
            
            $res_param = post($paramimg_url,$post_data);
            $res_param = json_decode($res_param,true);

            if($res_param['statu'] == 1){
                //upload成功
                if(empty($pic_jd_ifeng_big)){
                    $pic_jd_ifeng_big = $res_param['msg'];
                }else{
                    $pic_jd_ifeng_big .= ";".$res_param['msg'];
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

        $good['pic_jd'] = $pic_jd_ifeng;
        $good['bigpic_jd'] = $pic_jd_ifeng_big;

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
                    'cl_source'=>3,
                    'cl_goods_id'=>$log_goods_id,
                    'cl_goods'=>$goods_id,
                    'cl_status'=>0,
                    'cl_content'=>$log_text,
                    'cl_time'=>time(),
                    'cl_createtime'=>$nowtime
                );
                $res = $dbObj->insert($log_table,$log);
                $firstpin = '';
            }else{
                $firstpin = strtoupper(substr($quanpin,0,1));
            }//获取拼音结束，开始执行插入

            $brand_data['quanpin'] = $quanpin;
            $brand_data['firstpin'] = $firstpin;
            $brand_data['change_time'] = time();
            $brand_data['source'] = 3;

            $res = $dbObj->insert($brand_table,$brand_data);
            $id = $dbObj->insertId();
            $good['brand_id']=$id;
            $good['change_time']=time();
            $good['createtime']=date("Y-m-d H:i:s");
            $good['id_jd']=$goods_id;
            $good['name'] = $good['name_jd'];//商品1919名称
            $good['price'] = $good['price_jd'];//商品1919价格
            unset($good['species']);
            unset($good['brand']);
            $res = $dbObj->insert($detaile_table,$good);

            $log_text = "成功-插入";
            $log = array(
                'cl_source'=>3,
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
            $wql = "select * from wine_detail where brand_id='$id'";
            $pipei_result = $dbObj->fetchAll($wql);
            foreach ($pipei_result as $key => $val) {
                //净含量判定
                if(!empty($val['content']) && !empty($good['content'])){
                    $content_val = str_replace(array("ml","L","l","ML","毫升","升"),'',$val['content']);
                    $content_jd= str_replace(array("ml","L","l","ML","毫升","升"),'',$good['content']);
                    if($content_val != $content_jd){
                        continue;
                    }
                }

                //酒精度判定
                if(!empty($val['alcohol']) && !empty($good['alcohol'])){
                    
                    $alcohol_val = str_replace(array("度","°","vol","%vol","%Vol","% vol","%VOL","%"),'',$val['alcohol']);
                    $alcohol_jd = str_replace(array("度","°","vol","%vol","%Vol","% vol","%VOL","%"),'',$good['alcohol']);

                    $alcohol_val = trim($alcohol_val);
                    $alcohol_val = preg_replace('/\（.*?\）/', '', $alcohol_val);
                    $alcohol_val = preg_replace('/\(.*?\)/', '', $alcohol_val);
                    $alcohol_val = preg_replace('/\（.*?\)/', '', $alcohol_val);
                    $alcohol_val = preg_replace('/\(.*?\）/', '', $alcohol_val);

                    $alcohol_jd = trim($alcohol_jd);
                    $alcohol_jd = preg_replace('/\（.*?\）/', '', $alcohol_jd);
                    $alcohol_jd = preg_replace('/\(.*?\)/', '', $alcohol_jd);
                    $alcohol_jd = preg_replace('/\（.*?\)/', '', $alcohol_jd);
                    $alcohol_jd = preg_replace('/\(.*?\）/', '', $alcohol_jd);

                    if($alcohol_val != $alcohol_jd){
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
                if(!empty($val['price']) && !empty($good['price_jd'])){
                    $price = intval($val['price']);
                    $price_jd = intval($good['price_jd']);
                    if(abs($price-$price_jd) > 200){
                        continue;
                    }
                }

                if($val['name'] != '' && $good['name_jd'] != '' && empty($val['id_jd'])){
                    $name = str_replace('度', "°", $val['name']);
                    $name = preg_replace('/【(.*?)\】/','',$name);
                    $name_jd = str_replace(' ', "", $good['name_jd']);
                    $name_jd = str_replace('度', "°", $name_jd);

                    $xiangsidu = $lcs->getSimilar($name,$name_jd);//匹配名字相似度
                    if($xiangsidu > 0.5){
                        $pipei = true;
                        //匹配相似,执行更新操作
                        $info = array(
                            'change_time'=>time(),
                            'name_jd'=>$good['name_jd'],
                            'goodRate_jd'=>$good['goodRate_jd'],
                            'generalRate_jd'=>$good['generalRate_jd'],
                            'poorRate_jd'=>$good['poorRate_jd'],
                            'price_jd'=>$good['price_jd'],
                            'pic_jd'=>$good['pic_jd'],
                            'bigpic_jd'=>$good['bigpic_jd'],
                            'id_jd'=>$goods_id
                            );
                        $where = "id='{$val['id']}'";
                        $dbObj->update($detaile_table,$info,$where);
                        $log_text = "成功-匹配插入";
                        $log = array(
                            'cl_source'=>3,
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
                $good['id_jd']=$goods_id;
                $good['name'] = $good['name_jd'];//商品1919名称
                $good['price'] = $good['price_jd'];//商品1919价格
                unset($good['species']);
                unset($good['brand']);
                $res = $dbObj->insert($detaile_table,$good);
                $log_text = "成功-无匹配插入";
                $log = array(
                    'cl_source'=>3,
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
    //$end_time = time();
    //$time_cha = ($end_time - $start_time)/60;
    //echo $time_cha."\n";
?>