<?php

/**
 * @ file
 *  
 * @ Food
 */

namespace Entity\Food;

use PDO;
use Exception;

entity_register('food', array(
    'primaryKey' => 'fid',
    'baseTable'  => 'food',
));

class Food {

    //根据主键读取
    public static function load($request) {
        $fid  = (int) $request->getParameter('fid');
        $data = entity_load('food', array($fid));
        return reset($data);
    }

    //根据主键读取多个
    public static function loadMulti($request) {
        $ids = $request->getParameter('ids');
        if ($ids && !is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $data = entity_load('food', $ids);
        return $data;
    }
    
    //新增
    public static function insert($request) {
        $food = (object) $request->getParameters();
        unset($food->fid);
        $food->status = 1;
        $food->created = $food->updated = time();
        entity_insert('food', $food);            
        logger()->info("新增菜式成功: ".var_export((array)$food,true));
        return $food;
    }
    
    //更新
    public static function update($request) {
        $food = (object) $request->getParameters();
        unset($food->created);
        $food->updated = time();
        entity_update('food', $food);           
        return $food;
    }

    //列表
    public static function search($request) {
        $navi   = array('page'=> 1,'size' => 10, 'total'=> 0, 'pages' => 1);
        $page   = (int) $request->getParameter('page', 1);
        $size   = (int) $request->getParameter('size', 10);
        $query  = db_select('food', 't')
                        ->extend('Pager')->page($page)->size($size)
                        ->fields('t', array('fid'));     
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
        $query->orderBy('fid','DESC');
        $pager = array_merge($navi, $query->fetchPager());
        $ids   = $query->execute()->fetchCol();
        $data  = self::loadMulti(entity_request(array('ids'=>$ids)));

        return array('data'=>$data, 'pager'=>$pager);
    }
    
}
