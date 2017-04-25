<?php
/**
 * @ file Takeout.php 外卖订单
 */
namespace Wechat\Takeout;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Entity;

class Takeout {

    /**
     * 我的预订
     * @route /wechat/mytakeout
     * @access
     * @param int status  状态
     * @param int page    当前页
     * @param str format  是否json
     * @return json 
     */
    public static function search($request) {
        $format = $request->get('format');
        if ($format && $format=='json') {
            $appuser = session()->get('wx_authentication');
            $request->setParameter('size',6);
            //conditions
            $request->setParameter('conditions',array(
                'openid'    => array('value' => $appuser['openid']),
                'status'    => array('value' => explode(",",$request->get('status')),'flag'=>'in')
            ));
            //orderBys
            $request->setParameter('orderBys',array(
                'id'      => array('value' => 'DESC')
            ));
            
            $res = array(
                'list' => Entity\Takeout\Takeout::search($request)  
            );
            if (($res['list']['data'])) {
                //简化输出
                foreach ($res['list']['data'] as $k=>$data) {
                    unset( $res['list']['data'][$k]);
                    logger()->debug("data is:".var_export($data->field_takeout_order,true));
                    if (isset($data->field_takeout_order[0])) {
                        $food_number = 0;
                        foreach ($data->order->field_order_food as $f) {
                            $food_number += $f['number'];
                        }
                        $res['list']['data'][] = (object) array(
                            'id'        => $data->id,
                            'shop_id'   => $data->shop_id,
                            'shop_name' => $data->shop->name,
                            'shop_image'=> $data->shop->image,
                            'order_id'  => $data->field_takeout_order[0]['id'],
                            'order_price'  => $data->order->total,
                            'ordernumber'  => $data->order->ordernumber,
                            'food_number'  => $food_number,
                            'status'    => $data->status,
                            'created'   => $data->created,
                        );
                    }
                }
            }
            return new Response(json_encode(
                                array('status'=>'success','msg'=>'ok','data'=>$res['list'])),'200',
                                array('Content-Type'=>'application/json'));
        } 
        else {
            return new Response(theme()->render('takeout-list.html',array()));
        }
    }
    
    /**
	 * 订单详情
     * @route /wechat/takeout/detail/{id}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function detail($request) {
        $appuser = session()->get('wx_authentication');
		$id = (int)$request->route->getParameter('id');
		if ($id) {
            $res['takeout'] = $takeout = Entity\Takeout\Takeout::load(entity_request(array('id'=>$id)));
            if ($takeout->field_takeout_order && !empty($takeout->field_takeout_order)
                && $appuser['openid'] == $takeout->openid) {
                $res['order'] = Entity\Order\Order::load(entity_request(array('id'=>current($takeout->field_takeout_order)['id'])));
                //logger()->debug("order data:".var_export($res['order'],true));
                $res['shop']  = Entity\Shop\Shop::load(entity_request(array('id'=>$takeout->shop_id)));
            }
            else {
                return new RedirectResponse($request->getUriForPath('/wechat/index'),'2','非法调用');
            }
            return new Response(theme()->render('takeout-detail.html',$res));  
        }
        else {
            return new RedirectResponse($request->getUriForPath('/wechat/index'),'2','非法调用');
        }
	}
    
    /**
	 * 订单更新
     * @route /wechat/takeout/update
     * @access  admin_access
     * @param int id   
     * @param int status
	 * @return redirect
	 */
	public static function delete($request) {
        $appuser = session()->get('wx_authentication');
        $id = (int)$request->get('id');
        $status = $request->get('status');
        logger()->debug("post data id: ".$id." status:".$status);
        $takeout = Entity\Takeout\Takeout::load(entity_request(array('id'=>$id)));
        if ($takeout->openid != $appuser['openid'] || !in_array($status,array(0,4))) {
            return new Response(json_encode(
                                array('status'=>'error','msg'=>'非法操作')),'200',
                                array('Content-Type'=>'application/json'));
            return new RedirectResponse($request->getUriForPath('/wechat/index'),'2','非法操作');
        }
        
        if ($status==0 && in_array($takeout->status,array(2,3,4))) {
            return new Response(json_encode(
                                array('status'=>'error','msg'=>'当前状态无法删除')),'200',
                                array('Content-Type'=>'application/json'));
        }
        if ($status==4 && $takeout->status != 3) {
            return new Response(json_encode(
                                array('status'=>'error','msg'=>'外卖还没送出，不能确认收货')),'200',
                                array('Content-Type'=>'application/json'));
        }
        db_update("takeout")
                ->fields(array("status"=>$status))
                ->condition("id",$id)
                ->execute();
        return new Response(json_encode(
                                array('status'=>'success','msg'=>'ok')),'200',
                                array('Content-Type'=>'application/json'));
    }
    /**
     * 外卖提交
     * @route /wechat/takeout/register
     * @access
     * @return json 
     */
    public static function register($request) {
        if ($request->post->getParameters()) {
            $post_data = $request->post->getParameters();
            if (empty($post_data['telephone'])) {
                return new Response(json_encode(
                                array('status'=>'error','msg'=>'请输入联系方式')),'200',
                                array('Content-Type'=>'application/json'));
            }
            if (empty($post_data['name'])) {
                return new Response(json_encode(
                                array('status'=>'error','msg'=>'请输入姓名')),'200',
                                array('Content-Type'=>'application/json'));
            }
            if (empty($post_data['address'])) {
                return new Response(json_encode(
                                array('status'=>'error','msg'=>'请填送餐地址')),'200',
                                array('Content-Type'=>'application/json'));
            }

            $appuser = Entity\Wxuser\Wxuser::loadByOpenid(session()->get('wx_authentication')['openid']);
            $post_data['openid']     = $appuser->openid;
            $post_data['headimgurl'] = $appuser->headimgurl;
            $post_data['nickname']   = $appuser->nickname;
            logger()->debug("post data is:".var_export($post_data,true));
            Entity\Takeout\Takeout::insert(entity_request($post_data));
            return new Response(json_encode(
                                array('status'=>'success','msg'=>'ok')),'200',
                                array('Content-Type'=>'application/json'));
        }
        else {
            $res['shop'] = Entity\Shop\Shop::load(entity_request(array('id'=>$request->get('shop_id'))));
            return new Response(theme()->render('takeout-register.html',$res));
        }
    }
    
     /**
     * 预定订单
     * @route /wechat/takeout/order
     * @access
     * @return json 
     */
    public static function order($request) {
        $shop_id = $request->get('shop_id');
        $order   = $request->get('order');
        if (empty($shop_id) || empty($order)) {
            header("Location: /wechat/index");
            exit;
        }
        $res['shop']  = Entity\Shop\Shop::load(entity_request(array('id'=>$shop_id)));
        $res['order'] = Entity\Order\Order::load(entity_request(array('id'=>$order)));
        return new Response(theme()->render('takeout-order.html',$res));
    }
}