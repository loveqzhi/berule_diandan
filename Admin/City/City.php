<?php

namespace Admin\City;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Entity;

class City {
    
    /**
     * 获取下一级地区数据
     * @route 
     * @access
     * @param  int $pid    父级ID
     * @return json
     */
    public static function getByPid($pid) {
        $array = array();
        if ($pid === null) return true;
        $data = db_select("city","c")
                ->fields("c",array('id','name'))
                ->condition("pid",$pid)
                ->condition("status",1)
                ->orderBy('sortrank','DESC')
                ->execute()
                ->fetchAll();
        foreach($data as $d) {
            $array[] = array('id'=>$d->id,'name'=>$d->name);
        }
        return $array;
    }
    
    /**
     * 列表
     * @route /admin/city/search
     * @access  admin_access
     * @param int $page     当前页数
     * @param int $size     每页显示数量
     * @return String
     */
    public static function search($request) {
        //conditions
        $request->setParameter('conditions',array(
            'level'  => array('value' => $request->get('level',1)),
            'pid'    => array('value' => $request->get('pid',null)),
        ));
        //orderby
        $request->setParameter('orderBys',array(
            'sortrank'  => array('value' => 'DESC'),
        ));
        
        $res = array(
            'city'  => Entity\City\City::load(entity_request(array('id'=>$request->get('pid')))),
            'menus' => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
            'list'  => Entity\City\City::search($request),
        );

        return new Response(theme()->render('city-search.html',$res));  
    }

    /**
     * 添加地区
     * @route /admin/city/add
     * @access  admin_access
     * @param  string name    标题
     * @param  string pinyin  链接
     * @param  string pid     父级id
     * @return String
     */
    public static function add ($request) {
        $post_data = $request->post->getParameters();           
        if ($post_data) {
            $array = $post_data;
            if (empty($array['name'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'地区名必须填写')),'200',
                            array('Content-Type'=>'application/json'));
            }
            Entity\City\City::insert(entity_request($array));
            return new Response(json_encode(
                            array('status'=>'success' ,'msg'=>'ok')),'200',
                            array('Content-Type'=>'application/json'));
        }
        else {
            $res['city']  = Entity\City\City::load(entity_request(array('id'=>$request->get('pid'))));
            $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0]; 
            return new Response(theme()->render('city-add.html',$res));
        }
    }

    /**
     * 修改
     * @route /admin/city/edit/{id}
     * @access  admin_access
     * @param int $id
     * @return String
     */
	public static function edit($request) {
        $id = (int)$request->route->getParameter('id');
        $res['city'] = Entity\City\City::load(entity_request(array('id'=>$id)));
        if (empty($res['city'])) {
            return new RedirectResponse($request->getUriForPath('/admin/city/search'),'2',
                        '非法操作',array('Content-type'=>'text/html; charset=utf-8'));
        }        
        $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0];
        return new Response(theme()->render('city-edit.html',$res));
    }
   
    /**
     * 更新
     * @route /admin/city/update
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
                            array('status'=>'error' ,'msg'=>'地区名必须填写')),'200',
                            array('Content-Type'=>'application/json'));
            }      
			Entity\City\City::update(entity_request($array));
            return new Response(json_encode(
                        array('status'=>'success','msg'=>'ok')),'200',
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
     * @route /admin/city/delete/{id}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function delete($request) {
        /*
		$id = (int)$request->route->getParameter('id');       
		if ($id) {
            $city = Entity\City\City::load(entity_request(array('id'=>$id)));
            db_update("city")
                ->fields(array('status'=>'0','updated'=>time()))
                ->condition("id",$id)
                ->execute();
            return new RedirectResponse($request->getUriForPath('/admin/city/search'),'0','成功');
        }
        else {
            return new RedirectResponse($request->getUriForPath('/admin/city/search'),'0',lang('非法操作'));
        }
        */

	}

    
}