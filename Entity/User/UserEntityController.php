<?php

/**
 * @file
 *
 * UserEntityController 
 */

namespace Entity\User;

use Pyramid\Component\Entity\EntityController;
use Pyramid\Component\Permission\Permission as Perm;

class UserEntityController extends EntityController {
    
    //@inherited attachLoad
    protected function attachLoad(&$query_entities) {
        parent::attachLoad($query_entities);
        foreach ($query_entities as $entity_id => $entity) {
            //to do
            $this->assembleRoles($entity);
            $this->assembleRolesPermissions($entity);
        }
    }
    
    //取角色
    protected function assembleRoles($entity) {       
        $entity->roles = \Entity\Role\Role::loadByUid($entity->uid);
    }
    
    //取权限
    protected function assembleRolesPermissions($entity) {
        if (!isset($entity->permissions)) {
            $entity->permissions = array();
        }
        // 拼装角色权限
        if (!empty($entity->roles)) {
            $roles = \Entity\Role\Role::loadMulti(entity_request(array('rids'=>array_column($entity->roles,"rid"))));
            foreach ($roles as $rid => $role) {
                foreach ($role->field_role_permission as $v) {
                    $entity->permissions[$v['permission']] = $v['data'] ? unserialize($v['data']) : array();
                }
            }
        }
        // 用户特殊权限
        /*
        if ($entity->field_role_permission) {
            foreach ($entity->field_role_permission as $perm) {
                if (!isset($entity->permission[$perm['permission']])) {
                    $entity->permissions[$perm['permission']] = $perm['data'] ? unserialize($perm['data']) : array();
                }
            }
        }
        */
    }

}
