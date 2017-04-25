<?php

/**
 * @ file
 *  
 * @ City 信息
 */

namespace Entity\City;

use PDO;
use Exception;

entity_register('city', array(
    'primaryKey' => 'id',
    'baseTable'  => 'city',
));

class City {

    //根据主键读取
    public static function load($request) {
        $id  = (int) $request->getParameter('id');
        $data = entity_load('city', array($id));
        return reset($data);
    }

    //根据主键读取多个
    public static function loadMulti($request) {
        $ids = $request->getParameter('ids');
        if ($ids && !is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $data = entity_load('city', $ids);
        return $data;
    }
    
    public static function getNameById($id) {
        $city = self::load(entity_request(array('id'=>$id)));
        return $city->name;
    }
    //新增
    public static function insert($request) {
        $city = (object) $request->getParameters();
        unset($city->id);
        $city->status  = 1;
        $city->created = $city->updated = time();
        entity_insert('city', $city);            
        logger()->info("新增地区成功: ".var_export((array)$city,true));
        return $city;
    }
    
    //更新
    public static function update($request) {
        $city = (object) $request->getParameters();
        unset($city->created);
        $city->updated = time();
        entity_update('city', $city);           
        return $city;
    }
    
    //getByPid
    public static function getByPid($pid) {
        $data = db_select("city","c")
                    ->fields("c")
                    ->condition("pid",$pid)
                    ->orderBy("sortrank","DESC")
                    ->execute()
                    ->fetchAll();
        
        foreach ($data as $k => $dist) {
            $data[$k]->child =  db_select("city","c")
                                    ->fields("c")
                                    ->condition("pid",$dist->id)
                                    ->orderBy("sortrank","DESC")
                                    ->execute()
                                    ->fetchAll();
        }
     
        return $data;
    }
    //列表
    public static function search($request) {
        $navi   = array('page'=> 1,'size' => 30, 'total'=> 0, 'pages' => 1);
        $page   = (int) $request->getParameter('page', 1);
        $size   = (int) $request->getParameter('size', 30);
        $query  = db_select('city', 'c')
                        ->extend('Pager')->page($page)->size($size)
                        ->fields('c', array('id'));     
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
