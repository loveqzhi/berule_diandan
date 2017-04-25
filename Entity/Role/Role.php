<?php

/**
 * @file
 *
 * Role 角色组
 *
 */

namespace Entity\Role;

use PDO;
use Exception;

entity_register('role', array(
    'primaryKey' => 'rid',
    'baseTable'  => 'roles',
));

class Role {

    //根据主键读取
    public static function load($request) {
        $rid  = (int) $request->getParameter('rid');
        $data = entity_load('role', array($rid));
        return reset($data);
    }

    //根据主键读取多个
    public static function loadMulti($request) {
        $rids = $request->getParameter('rids');
        if ($rids && !is_array($rids)) {
            $rids = explode(',', $rids);
        }
        $data = entity_load('role', $rids);
        return $data;
    }
    
    //新增
    public static function insert($request) {
        $role = (object) $request->getParameters();
        unset($role->rid);
        if ($exists = db_select('roles', 'r')
                        ->fields('r')
                        ->condition('name', $role->name)
                        ->execute()
                        ->fetchObject()) {
            return entity_response()->setStatus(10101);
        }
        entity_insert('role', $role);            

        return $role;
    }
    
    //修改
    public static function update($request) {
        $role = (object) $request->getParameters();
        entity_update('role', $role);
        return $role;
    }

    //删除
    public static function delete($request) {
        $rid  = (int) $request->getParameter('rid');
        return entity_delete('role', $rid);
    }
    
    //列表
    public static function search($request) {
        $navi = array('page'=> 1,'size' => 10, 'total'=> 0, 'pages' => 1);
        $page = (int) $request->getParameter('page', 1);
        $size = (int) $request->getParameter('size', 10);
        $query = db_select('roles', 'r')
                    ->extend('Pager')->page($page)->size($size)
                    ->fields('r', array('rid'))
                    ->orderBy("r.rid","ASC");
        $rids = $query->execute()->fetchCol();
        $data = self::loadMulti(entity_request(array('rids'=>$rids)));
        $pager= array_merge($navi,$query->fetchPager());
        
        return array('data'=>$data, 'pager'=>$pager);
    }
    
    /** 
     * @ function getPermissionByRid
     * @ 根据角色Rid获取权限
     * @ param $rid
     * @ return array
     */
    public static function getPermissionByRid($rid) {
        $permission = array();
        $data = self::load(entity_request(array('rid'=>$rid)))->field_role_permission;
        foreach ($data as  $val) {
            $permission[$val['permission']] = unserialize($val['data']); 
        }
        return $permission;
    }
    
    /**
     * 根据用户ID 返回用户角色ID
     * @route
     * @param $id
     * @return array
     */
    public static function loadByUid ($uid) {
        $roles = db_select("relation_user_roles","u")
                    ->leftJoin("roles","r","r.rid=u.rid")
                    ->fields("r",array('rid','name'))
                    ->condition("u.uid",$uid)
                    ->execute()
                    ->fetchAll(PDO::FETCH_ASSOC);
        return $roles;
    }
    
}
