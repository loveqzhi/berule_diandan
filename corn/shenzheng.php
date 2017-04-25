<?php
/**
 * 大众点评 深圳 店铺信息采集
 */
set_time_limit(0);
ini_set('memory_limit', '128M');

//include framework
require_once dirname(dirname(__DIR__)) . '/Pyramid/Pyramid.php';
//include config
require_once dirname(__DIR__) . '/config.php';

require_once 'curl.php';

$dists = array(
    '福田区'   => array('target' => 'r29', ),
    '南山区'   => array('target' => 'r31', ),
    '罗湖区'   => array('target' => 'r30', ),
    '宝安区'   => array('target' => 'r33', ),
    '龙岗区'   => array('target' => 'r34', ),
    '龙华新区' => array('target' => 'r12033', ),
    '盐田区'   => array('target' => 'r32', ),
    '大鹏新区' => array('target' => 'r12036', ),
    '光明新区' => array('target' => 'r12034', ),
    '坪山新区' => array('target' => 'r12035', ),
);

$dist = trim($argv[1]);
$target = $dists[$dist]['target'];
$curl = new Curl(getcwd()."/".$dist."_cookie.txt");
$firsthtml = $curl->get("http://www.dianping.com/search/category/7/10/".$target);
if (strpos($firsthtml,"您使用的IP访问网站过于") !== false) {
        logger()->debug("@@@@@@访问过于频繁了@@@@@@@@@@");
        sleep(3);
        $firsthtml = $curl->get("http://www.dianping.com/search/category/7/10/".$target);
}
getRegion_nav_sub($firsthtml);

function getRegion_nav_sub($firsthtml) {
    global $dist;
    $preg = "#<div\sid=\"region-nav-sub\"\sclass=\"nc-items\snc-sub\">(?<list>.*?)<\/div>#ixsu";
    $preg_a = "#<a\shref=\"(?<urls>.*?)\#nav-tab\|0\|1\"\s+><span>(?<names>.*?)<\/span><\/a>#iu";
    preg_match($preg,$firsthtml,$match);
    if (!empty($match) && isset($match['list'])) {
        preg_match_all($preg_a,$match['list'],$matchs);
        if (!empty($matchs)) {
            foreach($matchs['urls'] as $k=>$url) {
                getStreet(array(
                    'url'       => $url,
                    'street'    => $matchs['names'][$k],
                    'street_id' => str_replace('/search/category/7/10/r','',$url),
                    'page'      => '1',));
            }
        }
    }
    else {
        echo "FIRST:".$firsthtml;
    }
}

//店铺列表url
function getStreet($param) {
    global $curl;
    //$url = "http://www.dianping.com".$param['url'];
    $url = "http://m.dianping.com/shoplist/7/r/".$param['street_id']."/c/10/s/s_-1?reqType=ajax&page=".$param['page'];
    $html = $curl->get($url);
    if (strpos($html,"您使用的IP访问网站过于") !== false) {
        logger()->debug("@@@@@@访问过于频繁了@@@@@@@@@@");
        sleep(3);
        $html = $curl->get($url);
    }
    $preg_urls = "#href=\"(?<urls>\/shop\/\d{1,})\"#iu";
    preg_match_all($preg_urls,$html,$matches);
    if (!empty($matches) && !empty($matches['urls'])) {
        foreach($matches['urls'] as $url) { //循环店铺          
            getShop(array(
                'url' => $url,
                'street' => $param['street'],
                'shopid' => str_replace("/shop/","",$url)
            ));
        }
        getStreet(array('page'=>$param['page']+1) + $param);
        sleep(3);
    }
    else {
        echo "SECOND:".$html;
    }
}

//店铺详情
function getShop($param) {
    global $dist,$curl;
    $url  = "http://www.dianping.com".$param['url'];
    //更新数据不采集
    $done_shop = db_select("shop_shenzheng")
                        ->fields("shop_shenzheng")
                        ->condition("shop_id",$param['shopid'])
                        ->execute()
                        ->fetch();
    if ($done_shop) {
        return true;
    }
                        
    $oldhtml = $curl->get($url);
    $html = preg_replace("#.+<div\s+id=\"body\"\s+class=\"body\">(.*?)<div\s+class=\"action\">(?:.+)#ius","$1",$oldhtml);
    $preg_category = "#<a\s+href=\"http://www.dianping.com/search/category/(.*?)\"\s+itemprop=\"url\">\s+".$param['street']."\s+</a>\s+&gt;\s+<a\s+href=\"http://www.dianping.com/search/category/(.*?)\"\s+itemprop=\"url\">\s+(?<category>.*?)\s+</a>\s+&gt;#isu";   //分类
    $preg_tel = "#<span\s+class=\"item\"\s+itemprop=\"tel\">(?<tel>.*?)<\/span>#iu";
    preg_match($preg_category,$html,$category);
    preg_match($preg_tel,$html,$tel);
    $shopJson = $curl->get("http://www.dianping.com/ajax/json/shop/wizard/BasicHideInfoAjaxFP?shopId=".$param['shopid']);
    $shopJson = json_decode($shopJson,true);
    if (!empty($shopJson) && !empty($category) && !empty($tel)) {
        $content = !empty($shopJson['msg']['shopInfo']['writeUp'])?$shopJson['msg']['shopInfo']['writeUp']:$shopJson['msg']['shopInfo']['searchKeyWord'];
        $shop = array(
            'name'      => $shopJson['msg']['shopInfo']['shopName'],
            'shop_id'   => $param['shopid'],
            'category'  => $category['category'],
            'tel'       => $tel['tel'],
            'address'   => $shopJson['msg']['shopInfo']['address'],
            'avgprice'  => !empty($shopJson['msg']['shopInfo']['avgPrice'])?$shopJson['msg']['shopInfo']['avgPrice']:0,
            'lng'       => !empty($shopJson['msg']['shopInfo']['glng'])?$shopJson['msg']['shopInfo']['glng']:0,
            'lat'       => !empty($shopJson['msg']['shopInfo']['glat'])?$shopJson['msg']['shopInfo']['glat']:0,
            'province'  => '0',
            'city'      => '深圳',
            'dist'      => $dist,
            'street'    => $param['street'],
            'star'      => (int) ($shopJson['msg']['shopInfo']['shopPower']/10),
            'wifi'      => '0',
            'park'      => !empty($shopJson['msg']['parkInfo'])?'1':0,
            'hotdish'   => $shopJson['msg']['shopInfo']['dishTags'],
            'traffic'   => strip_tags($shopJson['msg']['shopInfo']['publicTransit']),
            'businesstime' => $shopJson['msg']['shopInfo']['businessHours'],
            'description' => mb_substr($content,0,50,'UTF-8'),
            'content'   => "营业时间：".$shopJson['msg']['shopInfo']['businessHours']."<br>".
                           "公交线路：".strip_tags($shopJson['msg']['shopInfo']['publicTransit'])."<br>".
                           $content,
            'status'    => '1',
            'data'      => serialize(json_encode($shopJson)),
            'updated'   => time(),            
        );
        $tmp_shop = db_select("shop_shenzheng")
                        ->fields("shop_shenzheng")
                        ->condition("shop_id",$param['shopid'])
                        ->execute()
                        ->fetch();
        if (!empty($tmp_shop)) {
            db_update("shop_shenzheng")
                    ->fields($shop)
                    ->condition("id",$tmp_shop->id)
                    ->execute();
            logger()->debug("更新成功 ".$dist." ".$param['street']." ".$shop['name']." ".date('Y-m-d H:i:s'));
        }
        else {
            $shop['image'] = getImage($shopJson['msg']['shopInfo']['defaultPic'],$param['shopid']);
			$shop['created'] = time();
            db_insert("shop_shenzheng")
                    ->fields($shop)
                    ->execute();
            logger()->debug("采集成功 ".$dist." ".$param['street']." ".$shop['name']." ".date('Y-m-d H:i:s'));
        }
        
    }
    
    $oldhtml = $html = $shop = $tmp_shop = null;
    sleep(2);
}

//获取街道ID
function getStreetId($pid,$name) {
    $street = db_select("city")
                ->fields("city")
                ->condition("pid",$pid)
                ->condition("name",$name)
                ->execute()
                ->fetch();
    if (!empty($street)) {
        return $street->id;
    }
    else {
        return 0;
    }
}

//分类ID
function getCategory($name) {
    static $shop_categorys;
    if (empty($shop_categorys)) {
        $shop_categorys = config()->get('shop_category');
    }
    foreach($shop_categorys as $k => $v) {
        if ($v == $name) {
            return $k;
        }
    }
    return 0;
}

//下载图片
function getImage($url,$shop_id) {
    $url = str_replace("_s.","_m.",$url);
    $fopen = @fopen($url,"rb");
    $dirname = "/cache/upload/images/dianping_shenzheng/".time()."_".$shop_id.".jpg";
    $saveFile = @fopen("/opt/wwwroot/diandan".$dirname,"wb");
    if ($fopen) {
        while (!feof($fopen)) {          
            fwrite($saveFile, fread($fopen, 1024), 1024); 
        }
        fclose($saveFile);
        fclose($fopen);
    }
    return $dirname;
}