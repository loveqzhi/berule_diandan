<?php

/**
 * @ file
 *  
 * @ Shop
 */

namespace Entity\Shop;

use PDO;
use Exception;

entity_register('shop', array(
    'controller' => 'Entity\\Shop\\ShopEntityController',
    'primaryKey' => 'id',
    'baseTable'  => 'shop',
));

class Shop {

    //根据主键读取
    public static function load($request) {
        $id  = (int) $request->getParameter('id');
        $data = entity_load('shop', array($id));
        return reset($data);
    }

    //根据主键读取多个
    public static function loadMulti($request) {
        $ids = $request->getParameter('ids');
        if ($ids && !is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $data = entity_load('shop', $ids);
        return $data;
    }
    
    //新增
    public static function insert($request) {
        $shop = (object) $request->getParameters();
        unset($shop->id);
        $shop->status = 1;
        $shop->created = $shop->updated = time();
        entity_insert('shop', $shop);            
        logger()->info("新增店铺成功: ".var_export((array)$shop,true));
        return $shop;
    }
    
    //更新
    public static function update($request) {
        $shop = (object) $request->getParameters();
        unset($shop->created);
        $shop->updated = time();
        entity_update('shop', $shop);           
        return $shop;
    }

    //列表
    public static function search($request) {
        $navi   = array('page'=> 1,'size' => 10, 'total'=> 0, 'pages' => 1);
        $page   = (int) $request->getParameter('page', 1);
        $size   = (int) $request->getParameter('size', 10);
        $query  = db_select('shop', 's')
                        ->extend('Pager')->page($page)->size($size)
                        ->fields('s', array('id'));     
        foreach ($request->getParameter('conditions',array()) as $key=>$val) {
            $flag = isset($val['flag'])? $val['flag'] : '=';
            if (!is_null($val['value'])) {
                $query->condition($key,$val['value'],$flag);
            }
        }
        foreach($request->getParameter('leftJoin',array()) as $tb=>$val) {
            $query->leftJoin($tb,$tb,$tb.".entity_id=c.id");
            foreach ($val as $kk=>$vv) {
                $flag = isset($vv['flag'])? $vv['flag'] : '=';
                if (!is_null($vv['value'])) {
                    $query->condition($tb.".".$kk,$vv['value'],$flag);
                }
            }
        }
        foreach ($request->getParameter('orderBys',array()) as $key=>$val) {
            if (!is_null($val['value'])) {
                $query->orderBy($key,$val['value']);
            }
        }
        $query->orderBy('id','DESC');
        $pager = array_merge($navi, $query->fetchPager());
        $ids   = $query->execute()->fetchCol();
        $data  = self::loadMulti(entity_request(array('ids'=>$ids)));

        return array('data'=>$data, 'pager'=>$pager);
    }
    
}
