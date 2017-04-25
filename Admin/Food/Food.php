<?php

namespace Admin\Food;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Entity;
use Admin;

class Food {
    
    /**
     * 列表
     * @route /admin/food/search
     * @access  admin_access
     * @param int $page     当前页数
     * @param int $size     每页显示数量
     * @param int $tid      食谱分类
     * @param int $status   状态
     * @return String
     */
    public static function search($request) {
        //conditions
        $request->setParameter('conditions',array(
            'shop_id'=> array('value' => session()->get('user')->field_user_shop[0]['shop_id']),
            'tid'    => array('value' => $request->get('tid',null)),
            //'status' => array('value' => $request->get('status',1)),
        ));
        $res = array(
            'tid'   => $request->get('tid',''),
            'list'  => Entity\Food\Food::search($request),          
            'menus' => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
            
        );
        $request->setParameter('conditions',array(
            'shop_id'=> array('value' => session()->get('user')->field_user_shop[0]['shop_id']),
            'status' => array('value' => 1),
        ));
        $res['taxonomy_food'] = Entity\TaxonomyFood\TaxonomyFood::search($request)['data'];

        return new Response(theme()->render('food-search.html',$res));  
    }

    /**
     * 添加
     * @route /admin/food/add
     * @access  admin_access
     * @param  string name     菜谱名
     * @param  string image    图片
     * @param  float  price    价格
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
                            array('status'=>'error' ,'msg'=>'菜名必须填写')),'200',
                            array('Content-Type'=>'application/json'));
            }            
            if (empty($array['image'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'图片必须上传')),'200',
                            array('Content-Type'=>'application/json'));
            }
            Entity\Food\Food::insert(entity_request($array));
            return new Response(json_encode(
                            array('status'=>'success' ,'msg'=>'ok')),'200',
                            array('Content-Type'=>'application/json'));
        }
        else {
            $request->setParameter('conditions',array(
                'status'  => array('value' => 1),
                'shop_id' => array('value' => (isset($_GET['shop_id']) && !empty($_GET['shop_id']))?$_GET['shop_id']:session()->get('user')->field_user_shop[0]['shop_id'])
            ));
            $res['taxonomy_food'] = Entity\TaxonomyFood\TaxonomyFood::search($request)['data'];
            $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0]; 
            return new Response(theme()->render('food-add.html',$res));
        }
    }

    /**
     * 修改
     * @route /admin/food/edit/{fid}
     * @access  admin_access
     * @param int $fid
     * @return String
     */
	public static function edit($request) {
        $fid = (int)$request->route->getParameter('fid');
        $shop_id = $request->get('shop_id',null);
        if (empty($shop_id)) {
            $shop_id = session()->get('user')->field_user_shop[0]['shop_id'];
        }
        $res['food'] = Entity\Food\Food::load(entity_request(array('fid'=>$fid)));
        if (empty($res['food'])) {
            return new RedirectResponse($request->getUriForPath('/admin/food/search'),'2',
                        '非法操作',array('Content-type'=>'text/html; charset=utf-8'));
        }
        $request->setParameter('conditions',array(
            'status'  => array('value' => 1),
            'shop_id' => array('value' => $shop_id)
        ));
        $res['taxonomy_food'] = Entity\TaxonomyFood\TaxonomyFood::search($request)['data'];
        $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0];
        return new Response(theme()->render('food-edit.html',$res));
    }
   
    /**
     * 更新
     * @route /admin/food/update
     * @access  admin_access
     * @param array $request
     * @return json
     */
	public static function update($request) {
		$post_data = $request->getParameters();
		if (!empty($post_data['fid'])) {
            $array = $post_data;
            $array['fid']   = (int)$post_data['fid'];
            $array['shop_id'] = (isset($post_data['shop_id']) && !empty($post_data['shop_id']))?$post_data['shop_id']:session()->get('user')->field_user_shop[0]['shop_id'];
            if (empty($array['name'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'分类名必须填写')),'200',
                            array('Content-Type'=>'application/json'));
            }            
            if (empty($array['image'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'Icon必须上传')),'200',
                            array('Content-Type'=>'application/json'));
            }
			Entity\Food\Food::update(entity_request($array));
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
	 * 下架
     * @route /admin/food/delete/{fid}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function delete($request) {
		$fid = (int)$request->route->getParameter('fid');
		if ($fid) {
            $food = Entity\Food\Food::load(entity_request(array('fid'=>$fid)));
            db_update("food")
                ->fields(array('status'=>'3','updated'=>time()))
                ->condition("fid",$fid)
                ->execute();
            return new RedirectResponse($request->getUriForPath('/admin/food/search'),'0','成功');
        }
        else {
            return new RedirectResponse($request->getUriForPath('/admin/food/search'),'0',lang('非法操作'));
        }

	}
    
     /**
	 * 售罄
     * @route /admin/food/soldout/{fid}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function soldout($request) {
		$fid = (int)$request->route->getParameter('fid');
		if ($fid) {
            $food = Entity\Food\Food::load(entity_request(array('fid'=>$fid)));
            db_update("food")
                ->fields(array('status'=>'2','updated'=>time()))
                ->condition("fid",$fid)
                ->execute();
            return new RedirectResponse($request->getUriForPath('/admin/food/search'),'0','成功');
        }
        else {
            return new RedirectResponse($request->getUriForPath('/admin/food/search'),'0',lang('非法操作'));
        }

	}
    
}