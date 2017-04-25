<?php

/**
 * @ file
 *  
 * @ Taxonomy Food
 */

namespace Entity\TaxonomyFood;

use PDO;
use Exception;

entity_register('taxonomy_food', array(
    'primaryKey' => 'tid',
    'baseTable'  => 'taxonomy_food',
));

class TaxonomyFood {

    //根据主键读取
    public static function load($request) {
        $tid  = (int) $request->getParameter('tid');
        $data = entity_load('taxonomy_food', array($tid));
        return reset($data);
    }

    //根据主键读取多个
    public static function loadMulti($request) {
        $ids = $request->getParameter('ids');
        if ($ids && !is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $data = entity_load('taxonomy_food', $ids);
        return $data;
    }
    
    //新增
    public static function insert($request) {
        $taxonomy_food = (object) $request->getParameters();
        unset($taxonomy_food->tid);
        $taxonomy_food->status = 1;
        $taxonomy_food->created = $taxonomy_food->updated = time();
        entity_insert('taxonomy_food', $taxonomy_food);            
        logger()->info("新增分类成功: ".var_export((array)$taxonomy_food,true));
        return $taxonomy_food;
    }
    
    //更新
    public static function update($request) {
        $taxonomy_food = (object) $request->getParameters();
        unset($taxonomy_food->created);
        $taxonomy_food->updated = time();
        entity_update('taxonomy_food', $taxonomy_food);           
        return $taxonomy_food;
    }

    //列表
    public static function search($request) {
        $navi   = array('page'=> 1,'size' => 20, 'total'=> 0, 'pages' => 1);
        $page   = (int) $request->getParameter('page', 1);
        $size   = (int) $request->getParameter('size', 20);
        $query  = db_select('taxonomy_food', 't')
                        ->extend('Pager')->page($page)->size($size)
                        ->fields('t', array('tid'));     
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
        $query->orderBy('tid','DESC');
        $pager = array_merge($navi, $query->fetchPager());
        $ids   = $query->execute()->fetchCol();
        $data  = self::loadMulti(entity_request(array('ids'=>$ids)));

        return array('data'=>$data, 'pager'=>$pager);
    }
    
}
