<?php

/**
 * @file
 *
 * OrderEntityController 
 */

namespace Entity\Order;

use Pyramid\Component\Entity\EntityController;
use Pyramid\Component\Permission\Permission as Perm;
use Entity;

class OrderEntityController extends EntityController {
    
    //@inherited attachLoad
    protected function attachLoad(&$query_entities) {
        parent::attachLoad($query_entities);
        foreach ($query_entities as $entity_id => $entity) {
            //to do
            $this->assemblefood($entity);
        }
    }
    
    //菜单详情
    protected function assemblefood($entity) {
        if (!empty($entity->field_order_food)) {
            foreach($entity->field_order_food as $k=>$val) {
                $food = Entity\Food\Food::load(entity_request(array('fid'=>$val['fid'])));
                $entity->field_order_food[$k] = array_merge($entity->field_order_food[$k],(array)$food);
                $food = null;
            }
        }
        
    }
    

}
