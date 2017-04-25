<?php

/**
 * file api.php
 */
//include framework
require_once dirname(__DIR__) . '/Pyramid/Pyramid.php';

//include config
require_once __DIR__ . '/config.php';


class ApiKernel extends Kernel{

    public function __construct() {
        require_once __DIR__ . '/Entity/Entity.php';
        require_once __DIR__ . '/Api/Api.php';
        file_include(__DIR__ . '/Entity', "|(\w+)/\\1.php$|is", array('fullpath'=>true,'minDepth'=>2));
        $files = file_scan(__DIR__ . '/Api', "|(\w+)/\\1.php$|is", array('fullpath'=>true,'minDepth'=>2));
        foreach ($files as $f) {
            $module = explode('.', $f['basename'])[0];
            $r = new Pyramid\Component\Reflection\ReflectionClass("Api\\$module\\$module");
            foreach ($r->getMethods() as $method=>$m) {
                if (!empty($m['comments']['route'])) {
                    route_register($m['comments']['route'], "Api\\$module\\$module::$method");
                }
            }
        }
    }

}

$kernel = new ApiKernel();
$request = Pyramid\Component\HttpFoundation\Request::createFromGlobals();
$response = $kernel->handle($request);
$response->setStatusTexts(config()->get('status'));
//临时处理下微信接口
$matchroute = $request->route->get('matchroute');
if (strrpos($matchroute,'wechat') !== false) {
    $response->send();
}
else if ($request->hasParameter('gzip')) {
    $response->sendJson(0, 'gzencode');
} 
else {
    $response->sendJson();
}
