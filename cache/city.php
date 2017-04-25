<?php 
/**
 * 生成城市javascript数据
 */
set_time_limit(0);
ini_set('memory_limit', '128M');

//include framework
require_once dirname(dirname(__DIR__)) . '/Pyramid/Pyramid.php';
//include config
require_once dirname(__DIR__) . '/config.php';

$citys = db_select("city")
            ->fields("city")
            ->condition("pid",0)
            ->orderBy("sortrank","DESC")
            ->execute()
            ->fetchAll();
$array = array();
foreach($citys as $city) {
    $array[$city->id] = array(
        'id'    =>  $city->id,
        'name'  =>  $city->name,
        'child' =>  array(),
    );
    $childDist = db_select("city")
                    ->fields("city")
                    ->condition("pid",$city->id)
                    ->orderBy("sortrank","DESC")
                    ->execute()
                    ->fetchAll();
    foreach($childDist as $child) {
        $array[$city->id]['child'][$child->id] = array(
            'id'    =>  $child->id,
            'name'  =>  $child->name,
            'child' =>  array(),
        );
        $childStreet = db_select("city")
                    ->fields("city")
                    ->condition("pid",$child->id)
                    ->orderBy("sortrank","DESC")
                    ->execute()
                    ->fetchAll();
        foreach ($childStreet as $street) {
            $array[$city->id]['child'][$child->id]['child'][$street->id] = array(
                'id'    =>  $street->id,
                'name'  =>  $street->name,
            );
        }
    }
   
}
echo "var cityData = ".json_encode($array);