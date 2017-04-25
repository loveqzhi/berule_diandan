<?php

/**
 * @ Api file City.php
 */
namespace Api\City;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Entity;

class City {

    /**
     * 城市JSON数据
     * @route   /api/city/jsondata
     * @access
     * @return json
     */
    public static function getCityJson($request) {
        $array = array();
        $cities = db_select("city","c")
                ->fields("c")
                ->condition("pid",0)
                ->condition("status",1)
                ->execute()
                ->fetchAll();
        foreach($cities as $c) {
            $array[$c->id] = array('id'=>$c->id,'name'=>$c->name,'child'=>array());
            $dists = db_select("city","c")
                        ->fields("c")
                        ->condition("c.pid",$c->id)
                        ->condition("c.status",1)
                        ->orderBy("c.sortrank","DESC")
                        ->execute()
                        ->fetchAll();
            foreach ($dists as $dist) {
                $array[$c->id]['child'][$dist->id] = array(
                    'id'    => $dist->id,
                    'name'  => $dist->name,
                    'child' => array()
                );
                $streets = db_select("city","c")
                            ->fields("c")
                            ->condition("c.pid",$dist->id)
                            ->condition("c.status",1)
                            ->orderBy("c.sortrank","DESC")
                            ->execute()
                            ->fetchAll();
                foreach ($streets as $street) {
                    $array[$c->id]['child'][$dist->id]['child'][$street->id] = array(
                        'id'    => $street->id,
                        'name'  => $street->name,
                    );
                }
            }
        }
        return new Response($array);
    }
    /**
     * 获取下一级地区数据
     * @route   /api/city/getbypid
     * @access
     * @param  int $pid    父级ID
     * @return json
     */
    public static function getByPid($request) {
        $array = array();
        $pid = $request->get('pid');
        if ($pid === null) return true;
        $data = db_select("city","c")
                ->fields("c",array('id','name'))
                ->condition("pid",$pid)
                ->condition("status",1)
                ->orderBy('sortrank','DESC')
                ->execute()
                ->fetchAll();
        foreach($data as $d) {
            $array[] = array('id'=>$d->id,'name'=>$d->name);
        }
        return new Response($array);
    }

}
