<?php

/**
 * @file
 *
 * UserEntityController 
 */

namespace Entity\Shop;

use Pyramid\Component\Entity\EntityController;
use Pyramid\Component\Permission\Permission as Perm;
use Entity;

class ShopEntityController extends EntityController {
    
    //@inherited attachLoad
    protected function attachLoad(&$query_entities) {
        parent::attachLoad($query_entities);
        foreach ($query_entities as $entity_id => $entity) {
            //to do
            $this->assemblearea($entity);
        }
    }
    
    //地区名
    protected function assemblearea($entity) {
        //$entity->province_name = Entity\City\City::getNameById($entity->province);
        $entity->city_name = Entity\City\City::getNameById($entity->city);
        $entity->dist_name = Entity\City\City::getNameById($entity->dist);
        $entity->street_name = Entity\City\City::getNameById($entity->street);
    }
    

}
