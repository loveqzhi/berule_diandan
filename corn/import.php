<?php 

set_time_limit(0);
ini_set('memory_limit', '128M');

//include framework
require_once dirname(dirname(__DIR__)) . '/Pyramid/Pyramid.php';
//include config
require_once dirname(__DIR__) . '/config.php';

require_once dirname(__DIR__) . '/Entity/Entity.php';

use Entity\Shop\Shop as Shop;

$cities = array();
$citys = db_select("city","c")
            ->fields("c")
            ->condition("level",2)
            ->execute()
            ->fetchAll();
foreach ($citys as $city) {
    $childs = db_select("city","c")
                ->fields("c",array("id","name"))
                ->condition("pid",$city->id)
                ->execute()
                ->fetchAllKeyed(1,0);
    $cities[$city->name] = array(
                'id' => $city->id,
                'child' => $childs
            );
    $childs = null;
}
$category = array_flip(config()->get('shop_category'));

for($i=1;$i<=25542;$i++) {
    $shop = db_select("shop_shanghai")
                ->fields("shop_shanghai")
                ->condition("id",$i)
                ->execute()
                ->fetchAssoc();
    unset($shop['data'],$shop['id']);
    if (isset($category[$shop['category']])) {
        $shop['category'] = $category[$shop['category']];
    } else {
        $shop['category'] = 0;
    }
    
    if (isset($cities[$shop['dist']])) {
        $shop['street'] = $cities[$shop['dist']]['child'][$shop['street']];
        $shop['dist'] = $cities[$shop['dist']]['id'];
    } else {
        $shop['dist'] = 0;
        $shop['street'] = 0;
    }
    $shop['city'] = 101;
    
    Shop::insert(entity_request($shop)); 
    echo "写入成功 ".$shop['name']."\n";
    $shop = null;
}