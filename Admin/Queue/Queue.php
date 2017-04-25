<?php

namespace Admin\Queue;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Entity;
use Admin;

class Queue {
    
    /**
     * 列表
     * @route /admin/queue/search
     * @access  admin_access
     * @param int $page     当前页数
     * @param int $size     每页显示数量
     * @param str $date     日期
     * @param int $status   状态
     * @return String
     */
    public static function search($request) {
        //conditions
        $request->setParameter('size',30);
        $request->setParameter('conditions',array(
            'date'    => array('value' => $request->get('date',date('Ymd'))),
            'shop_id' => array('value' => session()->get('user')->field_user_shop[0]['shop_id']),
            'status'  => array('value' => $request->get('status',1)),
        ));
        $res = array(
            'menus' => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
            'list'  => Entity\Queue\Queue::search($request),
        );
        //print_r($res['list']);exit;
        return new Response(theme()->render('queue-search.html',$res));  
    }

    /**
	 * 删除
     * @route /admin/queue/delete/{id}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function delete($request) {
		$id = (int)$request->route->getParameter('id');
		if ($id) {
            $queue = Entity\Queue\Queue::load(entity_request(array('id'=>$id)));
            db_update("queue")
                ->fields(array('status'=>'0','updated'=>time()))
                ->condition("id",$id)
                ->execute();
            return new RedirectResponse($request->getUriForPath('/admin/queue/search'),'0','成功');
        }
        else {
            return new RedirectResponse($request->getUriForPath('/admin/queue/search'),'0',lang('非法操作'));
        }
	}
    
    /**
	 * 入座
     * @route /admin/queue/seat/{id}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function seat($request) {
		$id = (int)$request->route->getParameter('id');
		if ($id) {
            $queue = Entity\Queue\Queue::load(entity_request(array('id'=>$id)));
            db_update("queue")
                ->fields(array('status'=>'2','updated'=>time()))
                ->condition("id",$id)
                ->execute();
            return new RedirectResponse($request->getUriForPath('/admin/queue/search'),'0','成功');
        }
        else {
            return new RedirectResponse($request->getUriForPath('/admin/queue/search'),'0',lang('非法操作'));
        }
	}
    
}