<?php

namespace Admin\Book;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Api\Wechat\WechatAPI as WechatAPI;
use Entity;

class Book {
    
    /**
     * 列表
     * @route /admin/book/search
     * @access  admin_access
     * @param int $page     当前页数
     * @param int $size     每页显示数量
     * @param int $status   状态
     * @return String
     */
    public static function search($request) {
        //conditions
        $request->setParameter('size',10);
        $request->setParameter('conditions',array(
            'shop_id' => array('value' => array_column(session()->get('user')->field_user_shop,'shop_id'),'flag'=>'in'),
            'status'  => array('value' => $request->get('status',1)),
        ));
        //orderBys
        $request->setParameter('orderBys',array(
            'id'      => array('value' => 'DESC')
        ));
        
        $res = array(
            'status'=> $request->get('status',1),
            'menus' => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
            'list'  => Entity\Book\Book::search($request),
        );

        return new Response(theme()->render('book-search.html',$res));  
    }

    /**
	 * 更新预订
     * @route /admin/book/update
     * @access  admin_access
     * @param int $id
     * @param str $status
	 * @return redirect
	 */
	public static function update($request) {
        $shop_ids = array_column(session()->get('user')->field_user_shop,'shop_id');
		$id = (int)$request->get('id');
        $status = $request->get('status');
		if ($id) {
            $book = Entity\Book\Book::load(entity_request(array('id'=>$id)));
            if ($book && in_array($book->shop_id,$shop_ids)) {
                db_update("book")
                    ->fields(array('status'=>$status,'updated'=>time()))
                    ->condition("id",$id)
                    ->execute();
            }
            switch ($status) {
                case 2:
                    $updates = array('status'=>2,'processingtime'=>time());
                    $output = array(
                        "touser"    => $book->openid,
                        "msgtype"   => "text",
                        "text"      => array(
                           "content" => '你的预订店家已经受理了。',
                        ),
                    );
                    $res = WechatAPI::send($output);
                break;
                case 3:
                    $updates = array('status'=>3,'outfotime'=>time());
                    $output = array(
                        "touser"    => $book->openid,
                        "msgtype"   => "text",
                        "text"      => array(
                           "content" => '感谢你的光临。有任何问题可电邮我们\n61726776 hanson@berule.com',
                        ),
                    );
                    $res = WechatAPI::send($output);
                break;
                case 4:
                    $updates = array('status'=>4,'finishtime'=>time());
                    $output = array(
                        "touser"    => $book->openid,
                        "msgtype"   => "text",
                        "text"      => array(
                           "content" => "你的预订店家已经拒绝，注意查看信息。",
                        ),
                    );
                    $res = WechatAPI::send($output);
                break;
            }
            return new RedirectResponse($request->getUriForPath('/admin/book/search'),'0','成功');
            
        }
        else {
            return new RedirectResponse($request->getUriForPath('/admin/book/search'),'0',lang('非法操作'));
        }
	}
    
    /**
	 * 审核通过
     * @route /admin/book/success/{id}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function success($request) {
		$id = (int)$request->route->getParameter('id');
		if ($id) {
            $book = Entity\Book\Book::load(entity_request(array('id'=>$id)));
            if ($book) {
                db_update("book")
                    ->fields(array('status'=>'2','updated'=>time()))
                    ->condition("id",$id)
                    ->execute();
            }
            return new RedirectResponse($request->getUriForPath('/admin/book/search'),'0','成功');
        }
        else {
            return new RedirectResponse($request->getUriForPath('/admin/book/search'),'0',lang('非法操作'));
        }
	}
    
    /**
	 * 审核失败
     * @route /admin/book/fail/{id}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function fail($request) {
		$id = (int)$request->route->getParameter('id');
		if ($id) {
            $book = Entity\Book\Book::load(entity_request(array('id'=>$id)));
            if ($book) {
                db_update("book")
                    ->fields(array('status'=>'4','updated'=>time()))
                    ->condition("id",$id)
                    ->execute();
            }
            return new RedirectResponse($request->getUriForPath('/admin/book/search'),'0','成功');
        }
        else {
            return new RedirectResponse($request->getUriForPath('/admin/book/search'),'0',lang('非法操作'));
        }
	}
    
    /**
	 * 订单详情
     * @route /admin/book/detail/{id}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function detail($request) {
		$id = (int)$request->route->getParameter('id');
		if ($id) {
            $res['book'] = $book = Entity\Book\Book::load(entity_request(array('id'=>$id)));
            if ($book->field_book_order && !empty($book->field_book_order)) {
                $res['order'] = Entity\Order\Order::load(entity_request(array('id'=>current($book->field_book_order)['id'])));
            }
            $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0];
            return new Response(theme()->render('book-detail.html',$res));  
        }
	}
    
}