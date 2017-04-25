<?php

namespace Admin\Shop;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Entity;
use Admin;

class Shop {
    /**
     * 自动完成
     * @route /admin/shop/suggest
     * @access  admin_access
     * @return String
     */
    public static function suggest($request) {
        //conditions
        $name = $request->get('name','');
        //print_r($name);exit;
        $request->setParameter('size',30);
        $request->setParameter('conditions',array(
            'name'   => array('value' => "%".db_like($name)."%",'flag'=>'LIKE'),
            'status' => array('value' => $request->get('status',1)),
        ));
        $data = Entity\Shop\Shop::search($request);
        $array = array();
        if (!empty($data['data'])) {           
            foreach ($data['data'] as $d) {
                $array[] = array(
                            'id' => $d->id,
                            'name' => $d->name." ".$d->dist_name." ".$d->street_name." ".mb_substr($d->address,0,16,'UTF-8').".."
                            );
            }
            return new Response(json_encode(
                            array('status'=>'success' ,'msg'=>'ok','data'=>$array)),'200',
                            array('Content-Type'=>'application/json'));
        } 
        else {
            return new Response(json_encode(
                            array('status'=>'success' ,'msg'=>'ok','data'=>$array)),'200',
                            array('Content-Type'=>'application/json'));
        }

    }
    
    /**
     * 列表
     * @route /admin/shop/search
     * @access  admin_access
     * @param int $page     当前页数
     * @param int $size     每页显示数量
     * @param str $name     搜索关键词
     * @param int $status   状态
     * @return String
     */
    public static function search($request) {
        $name = $request->get('name',null);
        $conditions = array(
            'name'   => array('value' => ($name)? "%".db_like($name)."%" : null,'flag'=>'LIKE'),
            'status' => array('value' => $request->get('status',1)),
        );
        //conditions
        $request->setParameter('conditions',$conditions);
        $res = array(
            'menus' => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
            'list'  => Entity\Shop\Shop::search($request),
            'name'  => $name,
        );
        //print_r($res['list']);exit;
        return new Response(theme()->render('shop-search.html',$res));  
    }
    
    /**
     * 列表
     * @route /admin/manager/shop
     * @access  admin_access
     * @param int $page     当前页数
     * @param int $size     每页显示数量
     * @param str $name     搜索关键词
     * @param int $status   状态
     * @return String
     */
    public static function manager_search($request) {
        $admins = session()->get('user');
        $name = $request->get('name',null);
        $conditions = array(
            'id'     => array('value' => array_column($admins->field_user_shop,'shop_id'),'flag'=>'in'),
            'name'   => array('value' => ($name)? "%".db_like($name)."%" : null,'flag'=>'LIKE'),
            'status' => array('value' => $request->get('status',1)),
        );
        //conditions
        $request->setParameter('conditions',$conditions);
        $res = array(
            'menus' => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
            'list'  => Entity\Shop\Shop::search($request),
            'name'  => $name,
        );
        //print_r($res['list']);exit;
        return new Response(theme()->render('manager-shop-search.html',$res));  
    }

    /**
     * 添加
     * @route /admin/shop/add
     * @access  admin_access
     * @param  string name     店铺名
     * @param  string image    图片
     * @return String
     */
    public static function add ($request) {     
        $post_data = $request->post->getParameters();           
        if ($post_data) {
            $array = $post_data;
            if (empty($array['name'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'店铺必须填写')),'200',
                            array('Content-Type'=>'application/json'));
            }            
            if (empty($array['image'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'店铺Logo必须上传')),'200',
                            array('Content-Type'=>'application/json'));
            }
            if (empty($array['lng'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'请选择店铺坐标')),'200',
                            array('Content-Type'=>'application/json'));
            }
            if (empty($array['lat'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'请选择店铺坐标')),'200',
                            array('Content-Type'=>'application/json'));
            }
            if (empty($array['tel'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'请填写店铺联系电话')),'200',
                            array('Content-Type'=>'application/json'));
            }
            foreach(array('wifi','park','traffic') as $k) {
                if (!isset($array[$k])) {
                    $array[$k] = 0;
                }
            }
            Entity\Shop\Shop::insert(entity_request($array));
            return new Response(json_encode(
                            array('status'=>'success' ,'msg'=>'ok','type'=>$array['type'])),'200',
                            array('Content-Type'=>'application/json'));
        }
        else {
            //$res['province'] = Admin\District\District::getByLevel($request);
            $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0];
            return new Response(theme()->render('shop-add.html',$res));
        }
    }

    /**
     * 修改
     * @route /admin/shop/edit/{id}
     * @access  admin_access
     * @param int $id
     * @return String
     */
	public static function edit($request) {
        $id = (int)$request->route->getParameter('id');
        $res['shop'] = Entity\Shop\Shop::load(entity_request(array('id'=>$id)));
        if (empty($res['shop'])) {
            return new RedirectResponse($request->getUriForPath('/admin/shop/search'),'2',
                        '非法操作',array('Content-type'=>'text/html; charset=utf-8'));
        }
        $res['city'] = Admin\City\City::getByPid(0);
        $res['dist'] = Admin\City\City::getByPid($res['shop']->city);
        $res['street'] = Admin\City\City::getByPid($res['shop']->dist);
        $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0];
        return new Response(theme()->render('shop-edit.html',$res));
    }
   
    /**
     * 更新
     * @route /admin/shop/update
     * @access  admin_access
     * @param array $request
     * @return json
     */
	public static function update($request) {
		$post_data = $request->getParameters();
		if (!empty($post_data['id'])) {
            $array = $post_data;
            $array['id']   = (int)$post_data['id'];
            if (empty($array['name'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'店铺必须填写')),'200',
                            array('Content-Type'=>'application/json'));
            }            
            if (empty($array['image'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'店铺Logo必须上传')),'200',
                            array('Content-Type'=>'application/json'));
            }
            if (empty($array['lng'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'请选择店铺坐标')),'200',
                            array('Content-Type'=>'application/json'));
            }
            if (empty($array['lat'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'请选择店铺坐标')),'200',
                            array('Content-Type'=>'application/json'));
            }
            if (empty($array['tel'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'请填写店铺联系电话')),'200',
                            array('Content-Type'=>'application/json'));
            }
            foreach(array('wifi','park','traffic') as $k) {
                if (!isset($array[$k])) {
                    $array[$k] = 0;
                }
            }
			Entity\Shop\Shop::update(entity_request($array));
            return new Response(json_encode(
                        array('status'=>'success','msg'=>'ok','tid'=>$array['tid'])),'200',
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
     * @route /admin/shop/delete/{id}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function delete($request) {
        $admins = session()->get('user');
		$id = (int)$request->route->getParameter('id');
        if (!in_array('1',array_column($admins->roles,'rid'))) {
            return new RedirectResponse($request->getUriForPath('/admin/shop/search'),'2',' illegal operation');
        }
		if ($id) {
            $shop = Entity\Shop\Shop::load(entity_request(array('id'=>$id)));
            db_update("shop")
                ->fields(array('status'=>'0','updated'=>time()))
                ->condition("id",$id)
                ->execute();
            return new RedirectResponse($request->getUriForPath('/admin/shop/search'),'0','成功');
        }
        else {
            return new RedirectResponse($request->getUriForPath('/admin/shop/search'),'2',' illegal operation');
        }

	}
    
     /**
     * 某店铺食谱分类列表
     * @route /admin/shop/{id}/taxonomy_food
     * @access  admin_access
     * @param int $page     当前页数
     * @param int $size     每页显示数量
     * @return String
     */
    public static function taxonomy_food($request) {
        $id = $request->route->getParameter('id');
        //conditions
        $request->setParameter('conditions',array(
            'shop_id'   => array('value' => $id),
            'status'    => array('value' => $request->get('status',1)),
        ));
        $request->setParameter('orderBys',array(
            'sortrank'  => array('value' => 'DESC'),
        ));
        $res = array(
            'menus' => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
            'list'  => Entity\TaxonomyFood\TaxonomyFood::search($request),
            'shop'  => Entity\Shop\Shop::load(entity_request(array('id'=>$id)))
        );

        return new Response(theme()->render('shop-taxonomy-food-search.html',$res));  
    }
    
    /**
     * 某店铺菜谱列表
     * @route /admin/shop/{id}/food
     * @access  admin_access
     * @param int $page     当前页数
     * @param int $size     每页显示数量
     * @param int $tid      食谱分类
     * @param int $status   状态
     * @return String
     */
    public static function shop_food($request) {
        $shop_id = $request->route->getParameter('id');
        //conditions
        $request->setParameter('conditions',array(
            'shop_id'=> array('value' => $shop_id),
            'tid'    => array('value' => $request->get('tid',null)),
            //'status' => array('value' => $request->get('status',1)),
        ));
        $res = array(
            'tid'   => $request->get('tid',''),
            'list'  => Entity\Food\Food::search($request),          
            'menus' => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
            
        );
        $request->setParameter('conditions',array(
            'shop_id'=> array('value' => $shop_id),
            'status' => array('value' => 1),
        ));
        $res['taxonomy_food'] = Entity\TaxonomyFood\TaxonomyFood::search($request)['data'];
        $res['shop']  = Entity\Shop\Shop::load(entity_request(array('id'=>$shop_id)));
        
        return new Response(theme()->render('shop-food-search.html',$res));  
    }
    
}