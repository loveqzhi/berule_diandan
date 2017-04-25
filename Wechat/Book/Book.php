<?php
/**
 * @ file Book.php 餐厅位子预订
 */
namespace Wechat\Book;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Api\Wechat\WechatAPI as WechatAPI;
use Entity;

class Book {

    /**
     * 我的预订
     * @route /wechat/mybook
     * @access
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
                'id'        => array('value' => 'DESC'),
            ));
            
            $res = array(
                'list' => Entity\Book\Book::search($request)
            );
            if (($res['list']['data'])) {
                //简化输出
                foreach ($res['list']['data'] as $k=>$data) {
                    unset( $res['list']['data'][$k]);
                    $res['list']['data'][] = (object) array(
                        'id'        => $data->id,
                        'shop_id'   => $data->shop_id,
                        'shop_name' => $data->shop->name,
                        'shop_image'=> $data->shop->image,
                        'human'     => $data->human,
                        'bookdate'  => $data->bookdate,
                        'booktime'  => $data->booktime,
                        'order'     => $data->order,
                        'status'    => $data->status,
                        'created'   => $data->created,
                    );
                }
            }
            return new Response(json_encode(
                                array('status'=>'success','msg'=>'ok','data'=>$res['list'])),'200',
                                array('Content-Type'=>'application/json'));
            
        }
        else {
            return new Response(theme()->render('book-list.html',array()));
        }
    }
    
    /**
     * 预订提交
     * @route /wechat/book/register
     * @access
     * @return json 
     */
    public static function register($request) {
        if ($request->post->getParameters()) {
            $post_data = $request->post->getParameters();
            //logger()->debug("post data is:".var_export($post_data,true));
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
            if (empty($post_data['bookdate'])) {
                return new Response(json_encode(
                                array('status'=>'error','msg'=>'请选择预订日期')),'200',
                                array('Content-Type'=>'application/json'));
            }
            if (empty($post_data['human'])) {
                return new Response(json_encode(
                                array('status'=>'error','msg'=>'请选择用餐人数')),'200',
                                array('Content-Type'=>'application/json'));
            }
            $appuser = Entity\Wxuser\Wxuser::loadByOpenid(session()->get('wx_authentication')['openid']);
            $post_data['openid']     = $appuser->openid;
            $post_data['headimgurl'] = $appuser->headimgurl;
            $post_data['nickname']   = $appuser->nickname;
            logger()->debug("post data is:".var_export($post_data,true));
            Entity\Book\Book::insert(entity_request($post_data));
            return new Response(json_encode(
                                array('status'=>'success','msg'=>'ok')),'200',
                                array('Content-Type'=>'application/json'));
        }
        else {
            $res['shop'] = Entity\Shop\Shop::load(entity_request(array('id'=>$request->get('shop_id'))));
            return new Response(theme()->render('book-register.html',$res));
        }
    }
    
     /**
     * 预定订单
     * @route /wechat/book/order
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
        return new Response(theme()->render('book-order.html',$res));
    }
    
    /**
	 * 预订更新
     * @route /wechat/book/update
     * @access  admin_access
     * @param int id   
     * @param int status
	 * @return redirect
	 */
	public static function update($request) {
        $appuser = session()->get('wx_authentication');
        $id = (int)$request->get('id');
        $status = $request->get('status');
        $takeout = Entity\Book\Book::load(entity_request(array('id'=>$id)));
        if ($takeout->openid != $appuser['openid']) {
            return new Response(json_encode(
                                array('status'=>'error','msg'=>'非法操作')),'200',
                                array('Content-Type'=>'application/json'));
            return new RedirectResponse($request->getUriForPath('/wechat/index'),'2','非法操作');
        }
        /*
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
        */
        db_update("book")
                ->fields(array("status"=>$status))
                ->condition("id",$id)
                ->execute();
        return new Response(json_encode(
                                array('status'=>'success','msg'=>'ok')),'200',
                                array('Content-Type'=>'application/json'));
    }
    
    /**
	 * 预订详情
     * @route /wechat/book/detail/{id}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function detail($request) {
        $appuser = session()->get('wx_authentication');
		$id = (int)$request->route->getParameter('id');
		if ($id) {
            $res['book'] = $book = Entity\Book\Book::load(entity_request(array('id'=>$id)));
            if ($appuser['openid'] != $book->openid) {
                return new RedirectResponse($request->getUriForPath('/wechat/index'),'2','非法调用');
            }
            if ($book->field_book_order && !empty($book->field_book_order)) {
                $res['order'] = Entity\Order\Order::load(entity_request(array('id'=>current($book->field_book_order)['id'])));
            }
            $res['shop']  = Entity\Shop\Shop::load(entity_request(array('id'=>$book->shop_id)));
            $res['signPackage'] = WechatAPI::getSignPackage();
            return new Response(theme()->render('book-detail.html',$res));  
        }
        else {
            return new RedirectResponse($request->getUriForPath('/wechat/index'),'2','非法调用');
        }
	}
}