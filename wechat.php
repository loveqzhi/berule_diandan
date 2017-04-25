<?php

/**
 * @file
 *
 * index.php
 */

//include framework
require_once dirname(__DIR__) . '/Pyramid/Pyramid.php';

//include config
require_once __DIR__ . '/config.php';

//include langs
require_once __DIR__ . '/cache/langs.php';

//设置后台模板目录
define('THEMEFOLDER', __DIR__ . '/theme/wechat');
$engines['default']['loaderArgs'] = array(THEMEFOLDER);


//extends Kernel
class AppKernel extends Kernel{

    public function __construct() {
        require_once __DIR__ . '/Entity/Entity.php';
        require_once __DIR__ . '/Api/Api.php';
        require_once __DIR__ . '/Wechat/Wechat.php';
        
        $files = file_scan(__DIR__ . '/Wechat', "|(\w+)/\\1.php$|is", array('fullpath'=>true,'minDepth'=>2));
        foreach ($files as $f) {
            $module = explode('.', $f['basename'])[0];
            require_once $f['file'];
            $r = new Pyramid\Component\Reflection\ReflectionClass("Wechat\\$module\\$module");
            foreach ($r->getMethods() as $method=>$m) {
                if (!empty($m['comments']['route'])) {
                    route_register($m['comments']['route'], "Wechat\\$module\\$module::$method");
                }
            }
        }
    }
    
    public function afterRoute($request) {
        $matchroute = $request->route->get('matchroute');
        logger()->debug("Request Route uri is ".$_SERVER['REQUEST_URI']);
        switch($matchroute) {
            case 'wechat/oauth/verify':
            case 'wechat/unsubscribe':
            //case 'wechat/shop':
            //case 'wechat/index';
            break;
            default:
                $wx_authen = session()->get('wx_authentication');
                if ($wx_authen && isset($wx_authen['expires_time']) && time() < $wx_authen['expires_time']) {
                    //已经授权过并在有效期内 
                    $wxuser = Entity\Wxuser\Wxuser::loadByOpenid($wx_authen['openid']);
                    if (!$wxuser || $wxuser->subscribe!=1) {    //是否是关注会员
                        header("Location: /wechat/unsubscribe");
                        exit;
                    }
                    return true;
                }
                else {
                    $request->get->setParameter('state',str_replace(array('/','?','=','&'),array('AA','BB','CC','DD'),$_SERVER['REQUEST_URI']));
                    Wechat\Oauth\Oauth::verify($request);
                }
            break;
        }
        
    }
    
    function themePager($pager, $pageurl = '') {
        static $li = '<li><a href="%spage=%s">%s</a></li>';
        static $lt = '<li><span>%s</span></li>';
        static $hasjs;
        $unique   = uniqid();
        $pageurl .= strpos($pageurl,'?') ? '&' : '?';
        $return  = '<ul class="pagination">';
        $return .= sprintf($lt, $pager['page'].'/<strong>'.$pager['pages'].'</strong> 页');
        if ($pager['page'] > 1) {
            $return .= sprintf($li, $pageurl, $pager['page']-1, '上一页');
        }
        if ($pager['page'] < $pager['pages']) {
            $return .= sprintf($li, $pageurl, $pager['page']+1, '下一页');
        }
        $return .= "<li><a href='javascript:showjumpdiv(\"{$unique}\");'>跳转</a></li>"
            . "<form method='post' action='{$pageurl}'>"
            . "<div id='div{$unique}' class='jumpdiv'> <input id='input{$unique}' type='text' name='page' /> 页 "
            . "<button type='submit' class='btn btn-info btn-xs'>确定</button></div>"
            . "</form>";
        
        $return .= '</ul>';
        if (!$hasjs) {
            $return .= '<script>function showjumpdiv(unique) { $("#div"+unique).toggle();$("#input"+unique).focus();}</script>';
            $hasjs = true;
        }
        return $return;
    }

}

$kernel   = new AppKernel();
$request  = Pyramid\Component\HttpFoundation\Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();