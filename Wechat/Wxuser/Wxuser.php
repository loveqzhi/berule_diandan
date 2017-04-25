<?php

/*
 * @ file Wxuser.php
 */

namespace Wechat\Wxuser;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Entity;

class Wxuser {

    /**
     * 微信用户地址
     * @route /wechat/myaddress
     * @return json
     */
    public static function myaddress($request) {
        $wx_authen = session()->get('wx_authentication');
        $wxuser  = Entity\Wxuser\Wxuser::loadByOpenid($wx_authen['openid']);
        $format  = $request->get('format');
        $address = array();
        if ($wxuser && !empty($wxuser->field_wxuser_address)) {
            $address = array_column($wxuser->field_wxuser_address,'value');
        }
        if ($format == 'json') {
            return new Response(json_encode(
                            array('status'=>'success','msg'=>'ok','data'=>$address)),'200',
                            array('Content-Type'=>'application/json'));
        } else {
            return new Response(theme()->render('address-list.html',array()));
        }

    }
    
    /**
     * 微信用户地址更新
     * @route /wechat/wxuser/address/update
     * @param  array address
     * @param  json
     */
    public static function addressUpdate($request) {
        $wx_authen = session()->get('wx_authentication');
        if (!$wx_authen) {
            return new RedirectResponse($request->getUriForPath('/wechat/index'),'2','非法调用');
        }
        $post_data = $request->post->getParameters();
        logger()->debug("post data is:".var_export($post_data,true));       
        $wxuser  = Entity\Wxuser\Wxuser::loadByOpenid($wx_authen['openid']);
        $array = array(
            'wid' => $wxuser->wid,          
        ) + $post_data;
        Entity\Wxuser\Wxuser::update(entity_request($array));//更新地址
        
        return new Response(json_encode(
                            array('status'=>'success','msg'=>'ok',)),'200',
                            array('Content-Type'=>'application/json'));

    }
    
}

