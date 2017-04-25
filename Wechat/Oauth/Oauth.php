<?php
/**
 * @ file Oauth.php
 */
namespace Wechat\Oauth;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Entity;

class Oauth {

    /**
     * Oauth2.0 授权
     * @route /wechat/oauth/verify
     * @access
     * @return json 
     */
    public static function verify($request) {
        $state = str_replace(array('AA','BB','CC','DD'),array('/','?','=','&'),$request->get->getParameter('state'));
        //初始化相对应的授权对象
        $provider = Entity\Oauth2\Oauth2::provider('wechat',
            array(
                'appid'     => variable()->get('wxconfig')['appid'],
                'appsecret' => variable()->get('wxconfig')['appsecret'],
                'state'     => $request->get('state',1),
                'redirect_uri' => 'http://diandan.berule.com/wechat/oauth/verify',
            )
        );
        $code  = $request->get->getParameter('code');
        if ($code) {
            logger()->debug("Get Oauth2 Code is:".$code); 
            $tokens = $provider->access($code); //通过code获取access_token
            $tokens['expires_time'] = time() + $tokens['expires_in'] - 200;
            $wxuser = Entity\Wxuser\Wxuser::loadByOpenid($tokens['openid']);
            $tokens['userinfo'] = $wxuser;
            session()->set('wx_authentication',$tokens);//设置session
            logger()->debug("set tokens data is:".var_export($tokens,true)); 
            header("Location: ".$state);
            exit;
        }
        else {
            $provider->authorize();//请求code            
        }
    }
    
}