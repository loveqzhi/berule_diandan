<?php

/**
 * @ Admin file Wechat.php
 */
namespace Admin\Wechat;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Api\Wechat\WechatAPI as WechatAPI;

use Entity;

class Wechat {
    
    /**
     * 微信配置
     * @route /admin/wechat/setting
     * @access admin_access
     * @return string 
     */
    public static function setting($request) {
        if (($user = Entity\User\User::checkLogin()) == null) {
            return new RedirectResponse($request->getUriForPath('/admin/user/login'),'2',
                        '请先登录系统',array('Content-type'=>'text/html; charset=utf-8'));
        }
        $res = array(
            'HOST'   => $request->getSchemeAndHttpHost(),
            'wechat' => variable()->get('wxconfig'),
            'menus'  => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
        );
        return new Response(theme()->render('weixin-setting.html',$res));
    }
    
    /**
     * 账号配置保存
     * @route /admin/wechat/setting/save
     * @access admin_access
     * @return string 
     */
    public static function setting_save($request) {
        if (($user = Entity\User\User::checkLogin()) == null) {
            return new RedirectResponse($request->getUriForPath('/user/login'),'2',
                        '请先登录系统',array('Content-type'=>'text/html; charset=utf-8'));
        }
       
        if ($post_data = $request->post->getParameters()) {
            variable()->set('wxconfig',$post_data);
            if(isset($post_data['options']['menu'])) { //更新微信自定义菜单
                WechatAPI::createMenu(json_decode($post_data['options']['menu'],true));
            }
            return new Response(json_encode(
                                array('status'=>'success','msg'=>'ok')),'200',
                                array('Content-Type'=>'application/json'));
        }
        else {
            return new RedirectResponse($request->getUriForPath('/admin/wechat/setting'),'0');       
        }
    }
    
    /**
     * 发送客服消息
     * @route /admin/wechat/sendmessage
     * @param str scene_id
     * @param str scene_str
     * @access
     */
    public static function sendmessage($request) {
        if (($user = Entity\User\User::checkLogin()) == null) {
            return new RedirectResponse($request->getUriForPath('/admin/user/login'),'2',
                        '请先登录系统',array('Content-type'=>'text/html; charset=utf-8'));
        }
        if ($post_data = $request->post->getParameters()) {
            if (!empty($post_data['openid']) && !empty($post_data['msg'])) {
                $output = array(
                    "touser"    => $post_data['openid'],
                    "msgtype"   => "text",
                    "text"      => array(
                       "content" => $post_data['msg'],
                    ),
                );
                $res = WechatAPI::send($output);
            }
        }
		return new Response(json_encode(
                                array('status'=>'success','msg'=>'Ok')),'200',
                                array('Content-Type'=>'application/json'));

    }
    
}
