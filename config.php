<?php

//配置: session
$sessions = array(
    'prefix' => 'diandan#berule',
);

//配置: 数据库
$databases = array(
    'default' => array(
        'host'      => '127.0.0.1',
        'port'      => '3306',
        'database'  => 'diandan',
        'username'  => 'diandan',
        'password'  => 'diandan',
        'prefix'    => '',
        'charset'   => 'utf8mb4',
    ),
);

//配置: 日志记录
$loggers = array(    
    'default' => array(
        'class' => 'Pyramid\Component\Logger\FileLogger',
        'level' => 'debug',
        //'file' => 'd:/www/logs/diandan_default',
		'file' => '/tmp/diandan_default',
    ),
    'system' => array(
        'class' => 'Pyramid\Component\Logger\FileLogger',
        'level' => 'debug',
        //'file' => 'd:/www/logs/diandan__system',
		'file' => '/tmp/diandan_system',
    ),
);

//配置: 模板引擎
$engines = array(
    'default' => array(
        'engine'      => 'Pyramid\Component\Templating\PhpEngine',
        'loader'      => 'Pyramid\Component\Templating\Php\Loader',
        'environment' => 'Pyramid\Component\Templating\Php\Environment',
        'loaderArgs' => array(),
        'envArgs'    => array(/*'cache'=>'/opt/wwwroot/pay.berule.com/Cache/Theme'*/),
    ),
);

//配置：api status
$api_status = array(
    '200'   => 'OK',
    '10000' => '服务维护中',
    '10001' => '请填写完整信息',
    '10002' => '密码输入有误',
    '10003' => '该用户不存在',
    '10004' => '请输入用户名',
    '10005' => '缺少appid或mkey',
    '10006' => '无效的Appid',
    '10007' => '非法的秘钥',
    '10008' => '获取Token已超过次数',
    '10009' => '无效的Token',
    '10010' => '无效的邮箱接口',
    '10011' => '待发送地址有误',
	'10012' => '待发送手机号有误',
	'10013' => '无效的短信接口',
);
config()->set('status',$api_status);

//餐厅分类
$shop_category = array(
    '1' =>  '火锅',
    '2' =>  '川菜',
    '3' =>  '粤菜',
    '4' =>  '湘菜',
    '5' =>  '东北菜',
    '6' =>  '本帮江浙菜',
    '7'=>   '素菜',
    '8' =>  '西餐',
    '9' =>  '自助餐',
    '10' =>  '小吃快餐',
    '11' => '面包甜点',
    '12' => '咖啡厅',
    '13' => '日本',
    '14' => '韩国料理',
    '15' => '烧烤',
    '16' => '新疆菜',
    '17' => '西北菜',
    '18' => '台湾菜',
    '19' => '清真菜',
    '20' => '东南亚菜',
    '21' => '其他',
);
config()->set('shop_category',$shop_category);

//百度api调用
config()->set('baidu_ak','rjlPWo7Cgjk6DKFBsv6ANW8G');
//腾讯api调用
config()->set('tengxun_key','ZIMBZ-2BJHQ-7OW54-G6RWQ-V6YIK-EEB3Z');
