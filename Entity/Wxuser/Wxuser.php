<?php

/**
 * @ file
 *  
 * @ Wxuser
 */

namespace Entity\Wxuser;

use PDO;
use Exception;

entity_register('wxuser', array(
    'primaryKey' => 'wid',
    'baseTable'  => 'wxuser',
));

class Wxuser {

    //根据主键读取
    public static function load($request) {
        $wid  = (int) $request->getParameter('wid');
        $data = entity_load('wxuser', array($wid));
        return reset($data);
    }

    //根据主键读取多个
    public static function loadMulti($request) {
        $ids = $request->getParameter('ids');
        if ($ids && !is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $data = entity_load('wxuser', $ids);
        return $data;
    }
    
    //loadByOpenid
    public static function loadByOpenid($openid=null) {
        $wids = db_select("wxuser","w")
                ->fields("w",array('wid'))
                ->condition("openid",$openid)
                ->execute()
                ->fetchCol();
        return self::load(entity_request(array('wid'=>current($wids))));
    }
    //新增
    public static function insert($request) {
        $wxuser = (object) $request->getParameters();
        unset($wxuser->wid);
        $wxuser->status = 1;
        $wxuser->created = $wxuser->accessed = $wxuser->updated = time();
        entity_insert('wxuser', $wxuser);            
        //logger()->info("微信关注用户写入成功: ".var_export((array)$wxuser,true));
        return $wxuser;
    }
    
    //更新
    public static function update($request) {
        $wxuser = (object) $request->getParameters();
        unset($wxuser->created);
        $wxuser->updated = time();
        entity_update('wxuser', $wxuser);           
        return $wxuser;
    }

    //列表
    public static function search($request) {
        $navi   = array('page'=> 1,'size' => 10, 'total'=> 0, 'pages' => 1);
        $page   = (int) $request->getParameter('page', 1);
        $size   = (int) $request->getParameter('size', 10);
        $query  = db_select('wxuser', 'w')
                        ->extend('Pager')->page($page)->size($size)
                        ->fields('w', array('wid'));     
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
        $query->orderBy('wid','DESC');
        $pager = array_merge($navi, $query->fetchPager());
        $ids   = $query->execute()->fetchCol();
        $data  = self::loadMulti(entity_request(array('ids'=>$ids)));

        return array('data'=>$data, 'pager'=>$pager);
    }
    
}
