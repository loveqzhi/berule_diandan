<?php
/**
 * @ file Food.php
 */
namespace Wechat\Food;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Entity;

class Food {

    /**
     * 菜单列表
     * @route /wechat/food/{shop_id}/list
     * @access
     * @return string
     */
    public static function search($request) {
        $shop_id = $request->route->getParameter('shop_id');
        $request->setParameter('size',30);
        $request->setParameter('conditions',array(
            'tid'     => array('value' => $request->get('tid',null)),
            'shop_id' => array('value' => $shop_id),
        ));
       
        $res = array(
            'list' => Entity\Food\Food::search($request),
            'shop' => Entity\Shop\Shop::load(entity_request(array('id'=>$shop_id))),
            'from' => $request->get('from','book'),
        );
        $request->setParameter('conditions',array(
            'shop_id'=> array('value' => $request->route->getParameter('shop_id')),
            'status' => array('value' => 1),
        ));
        $request->setParameter('orderBys',array(
            'sortrank'  => array('value' => 'DESC'),
        ));
        $res['taxonomy_food'] = Entity\TaxonomyFood\TaxonomyFood::search($request)['data'];
        if (null != $request->get('order')) {
            $res['order']  = Entity\Order\Order::load(entity_request(array('id'=>$request->get('order'))));
        }
        else {
            $res['order'] = false;
        }
        return new Response(theme()->render('food-list.html',$res));
    }
    
    /**
     * 菜式详情
     * @route /wechat/food/detail/{fid}
     * @access
     * @return string
     */
    public static function detail($request) {
        $fid = $request->route->getParameter('fid');
        
        $res = array(
            'food' => Entity\Shop\Shop::load(entity_request(array('fid'=>$fid))),
        );
        
        return new Response(theme()->render('food-detail.html',$res));
    }
    
}