<?php

namespace Admin\Takeout;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Api\Wechat\WechatAPI as WechatAPI;
use Entity;

class Takeout {
    
    /**
     * 列表
     * @route /admin/takeout/search
     * @access  admin_access
     * @param int $page     当前页数
     * @param int $size     每页显示数量
     * @param int $status   状态
     * @return String
     */
    public static function search($request) {
        //conditions
        $request->setParameter('size',10);
        $status = $request->get('status',null);
        $array = array(
            'shop_id' => array('value' => session()->get('user')->field_user_shop[0]['shop_id']),
            'status'  => array('value' => 0, 'flag'=>'<>'),
        );
        if ($status) {
            $array['status'] = array('value' => $status);
        }
        $request->setParameter('conditions',$array);
        //orderBys
        $request->setParameter('orderBys',array(
            'id'      => array('value' => 'DESC')
        ));
        
        $res = array(
            'status'=> $status,
            'menus' => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
            'list'  => Entity\Takeout\Takeout::search($request),
        );

        return new Response(theme()->render('takeout-search.html',$res));  
    }

    /**
	 * 删除
     * @route /admin/takeout/delete/{id}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function delete($request) {
		$id = (int)$request->route->getParameter('id');
		if ($id) {
            $takeout = Entity\Takeout\Takeout::load(entity_request(array('id'=>$id)));
            if ($takeout) {
                db_update("takeout")
                    ->fields(array('status'=>'0','updated'=>time()))
                    ->condition("id",$id)
                    ->execute();
            }
            return new RedirectResponse($request->getUriForPath('/admin/takeout/search'),'0','成功');
        }
        else {
            return new RedirectResponse($request->getUriForPath('/admin/takeout/search'),'0',lang('非法操作'));
        }
	}
    
    /**
	 * 审核通过
     * @route /admin/takeout/success/{id}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function success($request) {
		$id = (int)$request->route->getParameter('id');
		if ($id) {
            $takeout = Entity\Takeout\Takeout::load(entity_request(array('id'=>$id)));
            if ($takeout) {
                db_update("takeout")
                    ->fields(array('status'=>'2','updated'=>time()))
                    ->condition("id",$id)
                    ->execute();
            }
            return new RedirectResponse($request->getUriForPath('/admin/takeout/search'),'0','成功');
        }
        else {
            return new RedirectResponse($request->getUriForPath('/admin/takeout/search'),'0',lang('非法操作'));
        }
	}
    

    
    /**
	 * 订单详情
     * @route /admin/takeout/detail/{id}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function detail($request) {
		$id = (int)$request->route->getParameter('id');
		if ($id) {
            $res['takeout'] = $takeout = Entity\Takeout\Takeout::load(entity_request(array('id'=>$id)));
            if ($takeout->field_takeout_order && !empty($takeout->field_takeout_order)) {
                $res['order'] = Entity\Order\Order::load(entity_request(array('id'=>current($takeout->field_takeout_order)['id'])));
            }
            $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0];
            return new Response(theme()->render('takeout-detail.html',$res));  
        }
	}
    
    /**
     * 更新订单状态
     * @route /admin/takeout/{id}/update
     * @access  admin_access
     * @param int id      订单ID
     * @param int status  状态
	 * @return redirect
     */
    public static function update($request) {
        $id = (int)$request->route->getParameter('id');
        $status = (int) $request->get('status');
        
        $takeout = Entity\Takeout\Takeout::load(entity_request(array('id'=>$id)));
        $shop_id = session()->get('user')->field_user_shop[0]['shop_id'];
        if ($takeout->shop_id != $shop_id) {
            return new RedirectResponse($request->getUriForPath('/admin/takeout/search'),'2','非法操作');
        }
        if ($takeout && $takeout->status == 0) {
            return new RedirectResponse($request->getUriForPath('/admin/takeout/search'),'2','用户已经删除该订单');
        }
        switch ($status) {
            case 2:
                $updates = array('status'=>2,'processingtime'=>time());
                $output = array(
                    "touser"    => $takeout->openid,
                    "msgtype"   => "text",
                    "text"      => array(
                       "content" => '你的订单店家已经受理了。厨房正在烹饪中，请耐心等待.',
                    ),
                );
                $res = WechatAPI::send($output);
            break;
            case 3:
                $updates = array('status'=>3,'outfotime'=>time());
                $output = array(
                    "touser"    => $takeout->openid,
                    "msgtype"   => "text",
                    "text"      => array(
                       "content" => '你的订单店家已经送出。马上就能吃上。',
                    ),
                );
                $res = WechatAPI::send($output);
            break;
            case 4:
                $updates = array('status'=>4,'finishtime'=>time());
                $output = array(
                    "touser"    => $takeout->openid,
                    "msgtype"   => "text",
                    "text"      => array(
                       "content" => "感谢你参与贝螺订餐，有任何问题可电邮我们\n61726776 hanson@berule.com",
                    ),
                );
                $res = WechatAPI::send($output);
            break;
            case 5:
                $updates = array('status'=>5);
                $output = array(
                    "touser"    => $takeout->openid,
                    "msgtype"   => "text",
                    "text"      => array(
                       "content" => '你的订单店家已经删除。请及时关注。',
                    ),
                );
                $res = WechatAPI::send($output);
            
            break;
        }
        db_update("takeout")
                ->fields($updates)
                ->condition("id",$id)
                ->execute();
        return new RedirectResponse($request->getUriForPath('/admin/takeout/search'),'0','ok');
    }
    
}