<?php

namespace Admin\TaxonomyFood;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Entity;
use Admin;

class TaxonomyFood {
    
    /**
     * 列表
     * @route /admin/food/taxonomy/search
     * @access  admin_access
     * @param int $page     当前页数
     * @param int $size     每页显示数量
     * @param int $status   状态
     * @return String
     */
    public static function search($request) {
        //conditions
        $request->setParameter('conditions',array(
            'shop_id'   => array('value' => session()->get('user')->field_user_shop[0]['shop_id']),
            'status'    => array('value' => $request->get('status',1)),
        ));
        $request->setParameter('orderBys',array(
            'sortrank'  => array('value' => 'DESC'),
        ));
        $res = array(
            'menus' => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
            'list'  => Entity\TaxonomyFood\TaxonomyFood::search($request),
        );

        return new Response(theme()->render('food-taxonomy-search.html',$res));  
    }

    /**
     * 添加
     * @route /admin/food/taxonomy/add
     * @access  admin_access
     * @param  string name     分类名
     * @param  string image    Icon
     * @param  string sortrank 排序
     * @return String
     */
    public static function add ($request) {     
        $post_data = $request->post->getParameters();      
        if ($post_data) {
            $array = $post_data;
            $array['shop_id'] = (isset($post_data['shop_id']) && !empty($post_data['shop_id']))?$post_data['shop_id']:session()->get('user')->field_user_shop[0]['shop_id'];
            if (empty($array['name'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'分类名必须填写')),'200',
                            array('Content-Type'=>'application/json'));
            }
            /*
            if (empty($array['image'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'Icon必须上传')),'200',
                            array('Content-Type'=>'application/json'));
            }
            */
            Entity\TaxonomyFood\TaxonomyFood::insert(entity_request($array));
            return new Response(json_encode(
                            array('status'=>'success' ,'msg'=>'ok','type'=>$array['type'])),'200',
                            array('Content-Type'=>'application/json'));
        }
        else {
            $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0]; 
            return new Response(theme()->render('food-taxonomy-add.html',$res));
        }
    }

    /**
     * 修改
     * @route /admin/food/taxonomy/edit/{tid}
     * @access  admin_access
     * @param int $id
     * @return String
     */
	public static function edit($request) {
        $tid = (int)$request->route->getParameter('tid');
        $res['taxonomy_food'] = Entity\TaxonomyFood\TaxonomyFood::load(entity_request(array('tid'=>$tid)));
        if (empty($res['taxonomy_food'])) {
            return new RedirectResponse($request->getUriForPath('/admin/food/taxonomy/search'),'2',
                        '非法操作',array('Content-type'=>'text/html; charset=utf-8'));
        }
        $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0];
        return new Response(theme()->render('food-taxonomy-edit.html',$res));
    }
   
    /**
     * 更新
     * @route /admin/food/taxonomy/update
     * @access  admin_access
     * @param array $request
     * @return json
     */
	public static function update($request) {
		$post_data = $request->getParameters();
		if (!empty($post_data['tid'])) {
            $array = $post_data;
            $array['tid']   = (int)$post_data['tid'];
            $array['shop_id'] = (isset($post_data['shop_id']) && !empty($post_data['shop_id']))?$post_data['shop_id']:session()->get('user')->field_user_shop[0]['shop_id'];
            if (empty($array['name'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'分类名必须填写')),'200',
                            array('Content-Type'=>'application/json'));
            }
            /*
            if (empty($array['image'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'Icon必须上传')),'200',
                            array('Content-Type'=>'application/json'));
            }
            */
            
			Entity\TaxonomyFood\TaxonomyFood::update(entity_request($array));
            return new Response(json_encode(
                        array('status'=>'success','msg'=>'ok','type'=>$array['type'])),'200',
                        array('Content-Type'=>'application/json'));
		}
        else {
            return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'非法操作')),'200',
                            array('Content-Type'=>'application/json'));
        }
	}
    /**
	 * 删除
     * @route /admin/food/taxonomy/delete/{tid}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function delete($request) {   
		$tid = (int)$request->route->getParameter('tid');
        $shop_id = (int)$request->get('shop_id');
		if ($tid) {
            $taxonomy = Entity\TaxonomyFood\TaxonomyFood::load(entity_request(array('tid'=>$tid)));
            db_update("taxonomy_food")
                ->fields(array('status'=>'0','updated'=>time()))
                ->condition("tid",$tid)
                ->execute();
            if ($shop_id) {
                return new RedirectResponse($request->getUriForPath('/admin/shop/'.$shop_id.'/taxonomy_food'),'0','成功');
            } else {
                return new RedirectResponse($request->getUriForPath('/admin/food/taxonomy/search'),'0','成功');
            }
        }
        else {
            return new RedirectResponse($request->getUriForPath('/admin/food/taxonomy/search'),'0','非法操作');
        }

	}
    
}