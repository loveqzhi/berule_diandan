<?php

/**
 * @file
 *
 * TakeoutEntityController 
 */

namespace Entity\Book;

use Pyramid\Component\Entity\EntityController;
use Pyramid\Component\Permission\Permission as Perm;
use Entity;

class BookEntityController extends EntityController {
    
    //@inherited attachLoad
    protected function attachLoad(&$query_entities) {
        parent::attachLoad($query_entities);
        foreach ($query_entities as $entity_id => $entity) {
            //to do
            $this->assemblefood($entity);
        }
    }
    
    //店铺详情
    protected function assemblefood($entity) {
        if (!empty($entity->shop_id)) {
            $entity->shop = Entity\Shop\Shop::load(entity_request(array('id'=>$entity->shop_id)));
        }
        if (!empty($entity->field_book_order)) {
            $entity->order = true;
        } else {
            $entity->order = false;
        }
        
    }
    

}
