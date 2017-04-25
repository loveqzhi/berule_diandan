<?php

/**
 * @ Api file admin.php 
 * @ public for Back-stage management
 * @ 
 */
namespace Admin\User;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Entity;
use Admin;

class User {
    
    /**
     * @route /
     * @return string
     */
    public static function loginPage($request){
        return new Response(theme()->render('user-login.html',array()));
    }

    /**
     * 后台用户登录
     * @route /admin/user/login
     * @access 
     * @param string $username
     * @param string $password
     * @return json 
     */
    public static function login($request) {
        $uid = session()->get('user')->uid;
        if(!empty($uid)) {
            return new RedirectResponse($request->getUriForPath('/admin/food/search'));
        }
        
        if ($request->post->getParameters()) {
            $res = Entity\User\User::check($request);
            if ($res->getStatus() == 0) {
                if ($res->getData()->status=='0') {
                    return new Response(json_encode(
                        array('status'=>'error','msg'=>'账号被锁定')),'200',
                        array('Content-Type'=>'application/json'));
                }
                
                $user = $res->getData();
                unset($user->password);
                session()->set('user',$user);
                return new Response(json_encode(
                        array('status'=>'success','msg'=>'登录成功')),'200',
                        array('Content-Type'=>'application/json'));
            } else {
                $msg = config()->get('status')[$res->getStatus()];
                return new Response(json_encode(
                        array('status'=>'error','msg'=>$msg)),'200',
                        array('Content-Type'=>'application/json'));

            }
        } 
        else {
            return new Response(theme()->render('user-login.html',array()));
        }
    }
    
    /**
     * 编辑用户
     * @route /admin/user/edit/{uid}
     * @access admin_access
     * @param int $id
     * @return string 
     */
    public static function edit($request) {
        //判断权限 todo
        
        $uid = (int)$request->route->getParameter('uid');
        $res['user'] = Entity\User\User::load(entity_request(array('uid'=>$uid)));
        if (empty($res['user'])) {
            return new RedirectResponse($request->getUriForPath('/admin/user/search'),'2',
                        '非法操作',array('Content-type'=>'text/html; charset=utf-8'));
        }

        $res['roles'] = Entity\Role\Role::search(entity_request(array('size'=>100,'page'=>1)));
        $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0];
        if (!empty($res['user']->field_user_shop)) {
            $res['myshop'] = Entity\Shop\Shop::load(entity_request(array('id'=>$res['user']->field_user_shop[0]['shop_id'])));
        }
        //$res['shops'] = Admin\Shop\Shop::allData($request);
        return new Response(theme()->render('user-edit.html',$res));
    }
    
    /**
     * 添加管理用户
     * @route /admin/user/add
     * @access admin_access
     * @param string $username
     * @param string $password
     * @return string 
     */
    public static function add($request){
        if ($request->post->getParameters()) {
            $res = Entity\User\User::loadByUsername(entity_request(array('username'=>$request->get('username'))));
            if ($res != null) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'用户已经存在')),'200',
                            array('Content-Type'=>'application/json'));
            } 
            if (!preg_match('/^[\\~!@#$%^&*()-_=+|{}\[\],.?\/:;\'\"\d\w]{6,20}$/',
                    $request->get('password'))) {
                return new Response(json_encode(
                                array('status'=>'error','msg'=>'密码格式不正确')),'200',
                                array('Content-Type'=>'application/json'));
            }

            $res = Entity\User\User::insert($request);
            if (isset($res) && isset($res->uid)) {
                return new Response(json_encode(
                                array('status'=>'success','msg'=>'ok')),'200',
                                array('Content-Type'=>'application/json'));
            } else {
                return new Response(json_encode(
                    array('status'=>'error','code'=>10004,'msg'=>'注册失败')));
            }
        }
        else {
            $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0];
			//$res['shops'] = Admin\Shop\Shop::allData($request);
            return new Response(theme()->render('user-add.html',$res));
        }
    }   
    
    /**
     * 更新用户信息
     * @route /admin/user/update
     * @access admin_access
     * @param string $username
     * @param string $email
     * @param string $telephone
     * @return string 
     */
    public static function update($request) {
        $tempPerms = Admin\Role\Role::getAllPermissions();
        $post_data = $request->getParameters();
        $uid = $request->getParameter('uid');
        $username = $request->getParameter('username');
        if (empty($username)) {
            return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'用户名必须填写')),'200',
                            array('Content-Type'=>'application/json'));
        }
        if (!$uid) {
            return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'非法操作')),'200',
                            array('Content-Type'=>'application/json'));
        }
        $user = Entity\User\User::loadByUsername(entity_request(array('username'=>$username)));
        if(!empty($user) && $user->uid!=$uid) {
            return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>'用户名已被使用')),'200',
                            array('Content-Type'=>'application/json'));
        }
        if (!empty($uid) && isset($post_data['relation_admin_roles']) && !empty($post_data['relation_admin_roles'])) {
            Admin\Role\Role::updateUserRoleByUid($post_data['relation_admin_roles'],$uid);
            unset($post_data['relation_admin_roles']);
        }
        else {
            Admin\Role\Role::updateUserRoleByUid(array(),$uid);
        }
        //更新用户信息
        Entity\User\User::update(entity_request($post_data));    
        
        return new Response(json_encode(
                            array('status'=>'success' ,'msg'=>'ok')),'200',
                            array('Content-Type'=>'application/json'));
    }    
    
    /**
     * 管理员列表
     * @route /admin/user/search
     * @access admin_access
     * @param int $page
     * @param int $size
     * @param int $status
     * @return string 
     */
    public static function search($request) {
        if (Entity\User\User::checkLogin() == null) {
            return new RedirectResponse($request->getUriForPath('/user/login'),'2',
                        '请先登录系统',array('Content-type'=>'text/html; charset=utf-8'));
        }
		if (Entity\User\User::getUid() != 1) {
			 return new RedirectResponse($request->getUriForPath('/user/login'),'2',
                        '非法操作',array('Content-type'=>'text/html; charset=utf-8'));
		}
        $res = array(
            'list'  => Entity\User\User::search($request),
            'menus' => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
        );
        return new Response(theme()->render('user-search.html',$res));
    }
    
    /**
     * 退出登录
     * @route /admin/user/logout
     * @access 
     * @return mixd 
     */

    public static function logout($request) {
        session()->delete('user');
        return new RedirectResponse($request->getUriForPath('/'),'0','退出成功');
    }
    
    /**
     * @route /admin/user/password/{uid}
     * @access admin_access
     * @param int  $id
     * @param string $password
     * @param string $re_password
     * @return json
     */
    public static function password($request){
        if ($request->post->getParameters()) {
            $uid = $request->get('uid');
            $password = $request->get('password');
            $re_password = $request->get('re_password');
            if (!$uid) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>lang('非法操作'))),'200',
                            array('Content-Type'=>'application/json'));
            }
            
            if ($password != $re_password) {
                return new Response(json_encode(
                            array('status'=>'error' ,'msg'=>lang('两次输入不一致'))),'200',
                            array('Content-Type'=>'application/json'));
            } 
            
            $array = array(
                'uid' => $uid,
                'password' => $password,
            );
            Entity\User\User::update(entity_request($array));
            return new Response(json_encode(
                            array('status'=>'success' ,'msg'=>'成功')),'200',
                            array('Content-Type'=>'application/json'));
        }
        else {
            $uid = $request->route->getParameter('uid');
            if (!$uid) {
                return new RedirectResponse($request->getUriForPath('/uesr/search'),'2',
                        lang('非法操作'),array('Content-type'=>'text/html; charset=utf-8'));
            }
            $res['user']  = Entity\User\User::load(entity_request(array('uid'=>$uid)));
            $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0];
            return new Response(theme()->render('user-password.html',$res));
        }
    }
    
    /**
     * @route /admin/upload
     * @access 
     * @param object  $Filedata
     * @return string
     */
    public static function upload($request,$filename='Filedata') {
        $savePath = dirname(dirname(__DIR__ )). '/cache/upload/images/' . date('Ymd');
        mkdir($savePath,0700,true);
        $savePath = $savePath . "/" . date('Ymd') . "_" . random() . ".jpg";
        $file = $request->files->getParameter($filename);
        if (!empty($file) && !empty($file['tmp_name'])) {
            if(!move_uploaded_file($file['tmp_name'], $savePath)) {
              copy($file['tmp_name'], $savePath);
            }
            return new Response(json_encode(
                        array('status'=>'success','msg'=>'ok','image'=>str_replace(dirname(dirname(__DIR__)),'',$savePath))),'200',
                        array('Content-Type'=>'application/json'));
        }
        return new Response(json_encode(
                        array('status'=>'error','msg'=>'ok','image'=>'')),'200',
                        array('Content-Type'=>'application/json'));
    }
     /**
     * 百度编辑器使用
     * @route /admin/upfile
     * @access 
     * @param object  $upfile
     * @return string
     */
    public static function upfile($request,$filename='upfile') {
        $savePath = dirname(dirname(__DIR__ )). '/cache/upload/images/' . date('Ymd');
        mkdir($savePath,0700,true);
        $savePath = $savePath . "/" . date('Ymd') . "_" . random() . ".jpg";
        $file = $request->files->getParameter($filename);
        if (!empty($file) && !empty($file['tmp_name'])) {
            if(!move_uploaded_file($file['tmp_name'], $savePath)) {
              copy($file['tmp_name'], $savePath);
            }
            $lastPath = str_replace(dirname(dirname(__DIR__)),'',$savePath);
            return new Response(json_encode(
                         array(                        
                            "state"     => 'SUCCESS',
                            "url"       => $lastPath,
                            "name"     => substr($lastPath, strrpos($lastPath, '/') + 1),
                            "originalName"  => $file['name'],
                            "type"      => strtolower(strrchr($file['name'], '.')),
                            "size"      => $file['size'],
                        )),'200',
                        array('Content-Type'=>'application/json'));
        }
        return new Response(json_encode(
                        array('status'=>'error','msg'=>'ok','image'=>'')),'200',
                        array('Content-Type'=>'application/json'));
    }
    
}
