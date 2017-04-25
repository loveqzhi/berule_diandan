<?php
/**
 * @ file Order.php
 */
namespace Wechat\Order;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Entity;

class Order {

    /**
     * 生成订单
     * @route /wechat/order/create
     * @access
     * @return string
     */
    public static function add($request) {
        $post_data = $request->post->getParameters();
        if ($post_data) {
            logger()->debug("Order post data is:".var_export($post_data,true));
            $wx_authen = session()->get('wx_authentication');
            $array = array(
                'shop_id'   => $post_data['shop_id'],
                'wid'       => $wx_authen['userinfo']->wid,
                'openid'    => $wx_authen['userinfo']->openid,               
                'total'       => '0.00',
                'prepayment'  => '0.00',
                'field_order_food' => $post_data['field_order_food']
            );
            foreach ($post_data['field_order_food'] as $f) {
                $food = Entity\Food\Food::load(entity_request(array('fid'=>$f['fid'])));
                $array['total'] += ($food->price * $f['number']);
                $array['prepayment'] += ($food->advance * $f['number']);
                $food = null;
            }
            if (isset($post_data['id'])) {
                $array['id'] = $post_data['id'];
                $order = Entity\Order\Order::update(entity_request($array));
            }
            else {
                $array['ordernumber'] = self::getOrderId($wx_authen['userinfo']->wid);
                $order = Entity\Order\Order::insert(entity_request($array));
            }
            //logger()->debug("Order prepare data is:".var_export($array,true));
            return new Response(json_encode(
                            array('status'=>'success' ,'msg'=>'ok','data'=>(array)$order)),'200',
                            array('Content-Type'=>'application/json'));
        }
    }
    
    /**
     * get OrderId
     */
    protected static function getOrderId($uid) {
        list($usec, $sec) = explode(' ', microtime());
        return strtoupper(substr(md5($uid),0,2)) . date('YmdHis', $sec) . (int) ($usec * 1000000);
    }
    
    /**
     * 订单详情
     * @route /wechat/order/detail/{id}
     * @access
     * @param int id
     * @return string
     */
    public static function detail($request) {
        
        $id = $request->route->getParameter('id');
        $res['order'] = Entity\Order\Order::load(entity_request(array('id'=>$id)));
        
        
        return new Response(theme()->render('order-detail.html',$res));
    }
}