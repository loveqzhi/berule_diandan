<?php

/**
 * @ file
 *  
 * @ Order
 */

namespace Entity\Order;

use PDO;
use Exception;

entity_register('orders', array(
    'controller' => 'Entity\\Order\\OrderEntityController',
    'primaryKey' => 'id',
    'baseTable'  => 'orders',
));

class Order {

    //根据主键读取
    public static function load($request) {
        $id  = (int) $request->getParameter('id');
        $data = entity_load('orders', array($id));
        return reset($data);
    }

    //根据主键读取多个
    public static function loadMulti($request) {
        $ids = $request->getParameter('ids');
        if ($ids && !is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $data = entity_load('orders', $ids);
        return $data;
    }
    
    //新增
    public static function insert($request) {
        $orders = (object) $request->getParameters();
        unset($orders->id);
        $orders->status = 0;
        $orders->created = $orders->updated = time();
        entity_insert('orders', $orders);            
        logger()->info("新增订单生成: ".var_export((array)$orders,true));
        return $orders;
    }
    
    //更新
    public static function update($request) {
        $orders = (object) $request->getParameters();
        unset($orders->created);
        $orders->updated = time();
        entity_update('orders', $orders);           
        return $orders;
    }

    //列表
    public static function search($request) {
        $navi   = array('page'=> 1,'size' => 10, 'total'=> 0, 'pages' => 1);
        $page   = (int) $request->getParameter('page', 1);
        $size   = (int) $request->getParameter('size', 10);
        $query  = db_select('orders', 'o')
                        ->extend('Pager')->page($page)->size($size)
                        ->fields('o', array('id'));     
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
        foreach ($request->getParameter('ordersBys',array()) as $key=>$val) {
            if (!is_null($val['value'])) {
                $query->ordersBy($key,$val['value']);
            }
        }
        $pager = array_merge($navi, $query->fetchPager());
        $ids   = $query->execute()->fetchCol();
        $data  = self::loadMulti(entity_request(array('ids'=>$ids)));

        return array('data'=>$data, 'pager'=>$pager);
    }
    
}
