<?php

/**
 * @ file
 *  
 * @ Takeout
 */

namespace Entity\Takeout;

use PDO;
use Exception;

entity_register('takeout', array(
    'controller' => 'Entity\\Takeout\\TakeoutEntityController',
    'primaryKey' => 'id',
    'baseTable'  => 'takeout',
));

class Takeout {

    //根据主键读取
    public static function load($request) {
        $id  = (int) $request->getParameter('id');
        $data = entity_load('takeout', array($id));
        return reset($data);
    }

    //根据主键读取多个
    public static function loadMulti($request) {
        $ids = $request->getParameter('ids');
        if ($ids && !is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $data = entity_load('takeout', $ids);
        return $data;
    }
    
    //新增
    public static function insert($request) {
        $takeout = (object) $request->getParameters();
        unset($takeout->id);
        $takeout->status = 1;
        $takeout->created = $takeout->updated = time();
        entity_insert('takeout', $takeout);            
        logger()->info("新增预订成功: ".var_export((array)$takeout,true));
        return $takeout;
    }
    
    //更新
    public static function update($request) {
        $takeout = (object) $request->getParameters();
        unset($takeout->created);
        $takeout->updated = time();
        entity_update('takeout', $takeout);           
        return $takeout;
    }

    //列表
    public static function search($request) {
        $navi   = array('page'=> 1,'size' => 6, 'total'=> 0, 'pages' => 1);
        $page   = (int) $request->getParameter('page', 1);
        $size   = (int) $request->getParameter('size', 6);
        $query  = db_select('takeout', 't')
                        ->extend('Pager')->page($page)->size($size)
                        ->fields('t', array('id'));     
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
        $pager = array_merge($navi, $query->fetchPager());
        $ids   = $query->execute()->fetchCol();
        $data  = self::loadMulti(entity_request(array('ids'=>$ids)));

        return array('data'=>$data, 'pager'=>$pager);
    }
    
}
