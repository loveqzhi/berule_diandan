<?php

namespace Admin\Picture;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Entity;
use Admin;

class Picture {
    
    /**
     * 列表
     * @route /admin/picture/{type}
     * @access  admin_access
     * @param int $page     当前页数
     * @param int $size     每页显示数量
     * @param int $status   状态
     * @return String
     */
    public static function search($request) {
        $type = $request->route->getParameter('type','home');
        //conditions
        $request->setParameter('conditions',array(
            'type'      => array('value' => $type),
            'status'    => array('value' => $request->get('status',1)),
        ));
        $res = array(
            'type'  => $type,
            'menus' => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
            'list'  => Entity\Picture\Picture::search($request),
        );

        return new Response(theme()->render('picture-search.html',$res));  
    }

    /**
     * 添加图片
     * @route /admin/picture/add
     * @access  admin_access
     * @param  string title    标题
     * @param  string source   链接
     * @param  string image    图片路径
     * @return String
     */
    public static function add ($request) {     
        $post_data = $request->post->getParameters();           
        if ($post_data) {
            $array = $post_data;
            $array['adminid'] = session()->get('admin')->id;
            if (empty($array['title'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'标题必须填写')),'200',
                            array('Content-Type'=>'application/json'));
            }
            
            if (empty($array['source'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'链接必须填写')),'200',
                            array('Content-Type'=>'application/json'));
            }
            
            if (empty($array['image'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'图片必须上传')),'200',
                            array('Content-Type'=>'application/json'));
            }
            if (empty($array['source'])) {
                $array['source'] = '#';
            }

            Entity\Picture\Picture::insert(entity_request($array));
            return new Response(json_encode(
                            array('status'=>'success' ,'msg'=>'ok','type'=>$array['type'])),'200',
                            array('Content-Type'=>'application/json'));
        }
        else {
            $res['type']  = $request->get('type','home');
            $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0]; 
            return new Response(theme()->render('picture-add.html',$res));
        }
    }

    /**
     * 修改
     * @route /admin/picture/edit/{id}
     * @access  admin_access
     * @param int $id
     * @return String
     */
	public static function edit($request) {
        $id = (int)$request->route->getParameter('id');
        $res['picture'] = Entity\Picture\Picture::load(entity_request(array('id'=>$id)));
        if (empty($res['picture'])) {
            return new RedirectResponse($request->getUriForPath('/admin/picture/home'),'2',
                        '非法操作',array('Content-type'=>'text/html; charset=utf-8'));
        }        
        $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0];
        return new Response(theme()->render('picture-edit.html',$res));
    }
   
    /**
     * 更新
     * @route /admin/picture/update
     * @access  admin_access
     * @param array $request
     * @return json
     */
	public static function update($request) {
		$post_data = $request->getParameters();
		if (!empty($post_data['id'])) {
            $array = $post_data;
            $array['id']   = (int)$post_data['id'];
            
            if (empty($array['title'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'标题必须填写')),'200',
                            array('Content-Type'=>'application/json'));
            }      
            if (empty($array['source'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'链接必须填写')),'200',
                            array('Content-Type'=>'application/json'));
            }            
            if (empty($array['image'])) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'图片必须上传')),'200',
                            array('Content-Type'=>'application/json'));
            }
            if (empty($array['source'])) {
                $array['source'] = '#';
            }
			Entity\Picture\Picture::update(entity_request($array));
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
     * @route /admin/picture/delete/{id}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function delete($request) {       
		$id = (int)$request->route->getParameter('id');       
		if ($id) {
            $picture = Entity\Picture\Picture::load(entity_request(array('id'=>$id)));
            db_update("picture")
                ->fields(array('status'=>'0','updated'=>time()))
                ->condition("id",$id)
                ->execute();
            return new RedirectResponse($request->getUriForPath('/admin/picture/'.$picture->type),'0','成功');
        }
        else {
            return new RedirectResponse($request->getUriForPath('/admin/picture/home'),'0',lang('非法操作'));
        }

	}
    
}