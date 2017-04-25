<?php 

/**
 * @ file menu
 * @ 返回用户可见权限菜单
 * @ return authentication menu
 */
namespace Entity\Menu;
use Entity;

class Menu { 
    /**
     * 获取菜单
     * @route
     * @access 
     * @param 
     * @return array 
     */
    public static function getMenu($route) {
        $path = '/' . $route;
        $menus = self::setMenus();
        $admin = Entity\User\User::load(entity_request(array('uid'=>session()->get('user')->uid)));
        foreach ($menus as $k=>$v) {
			/*
            if(strpos($path,dirname($v['href'])) === 0) {
                $menus[$k]['active'] = true;
            }
			*/
            foreach($v['childs'] as $kk => $vv) {
				if(strpos($path,dirname($vv['href'])) === 0) {
					$menus[$k]['active'] = true;
				}
                if (!isset($admin->permissions[$vv['href']])) {
                    unset($menus[$k]['childs'][$kk]);
                }
            }
            
            if (empty($menus[$k]['childs']))
                unset($menus[$k]);

        }
        //print_r($menus);exit;
        foreach ($menus as $k => $v) {
			if($v['active'] == true) {
				foreach ($v['childs'] as $kk => $vv) {
					if ($vv['href'] == $path || strpos($path,$vv['href'])!==false) {
						$menus[$k]['childs'][$kk]['active'] = true;						
					}
				}
                return array($menus, $menus[$k]);
			}
        }
        $route = dirname($route);
        if ($route == '.' || $route == '/' || $route == '\\') {
            return array($menus, array());
        } else {
            return self::getMenu($route);
        }
    }
    /**
     * 设置路由
     * @route 
     * @access 
     * @param 
     * @return array 
     */
    public static function setMenus(){
        return array(
                '0'  => array(
                    'name'   => '我的店铺',
                    'href'   => '/admin/manager/shop',
                    'icon'   => 'fa-th-large',
                    'active' => false,
					'module' => 'manager',
                    'childs' => array(
                        array(
                            'name'   => '店铺列表',
                            'href'   => '/admin/manager/shop',
                            'icon'   => 'fa-list',
                            'active' => false,
                        ),
                    ),
                ),
                '1'  => array(
                    'name'   => '外卖订单',
                    'href'   => '/admin/takeout/search',
                    'icon'   => 'fa-tty',
                    'active' => false,
					'module' => 'book',
                    'childs' => array(
                        array(
                            'name'   => '订单列表',
                            'href'   => '/admin/takeout/search',
                            'icon'   => 'fa-user',
                            'active' => false,
                        ),
                    ),
                ),
                '2'  => array(
                    'name'   => '预订位子',
                    'href'   => '/admin/book/search',
                    'icon'   => 'fa-tasks',
                    'active' => false,
					'module' => 'book',
                    'childs' => array(
                        array(
                            'name'   => '预订列表',
                            'href'   => '/admin/book/search',
                            'icon'   => 'fa-gear',
                            'active' => false,
                        ),
                        
					),
                ),
                '3'  => array(
                    'name'   => '微信排号',
                    'href'   => '/admin/queue/search',
                    'icon'   => 'fa-weixin',
                    'active' => false,
					'module' => 'book',
                    'childs' => array(
                        array(
                            'name'   => '排号列表',
                            'href'   => '/admin/queue/search',
                            'icon'   => 'fa-user',
                            'active' => false,
                        ),
                    ),
                ),
                '4'  => array(
                    'name'   => '菜谱管理',
                    'href'   => '/admin/food/search',
                    'icon'   => 'fa-fire',
                    'active' => false,
					'module' => 'food',
                    'childs' => array(
                        array(
                            'name'   => '菜谱列表',
                            'href'   => '/admin/food/search',
                            'icon'   => 'fa-list',
                            'active' => false,
                        ),
                        array(
                            'name'   => '食谱分类',
                            'href'   => '/admin/food/taxonomy/search',
                            'icon'   => 'fa-list-alt',
                            'active' => false,
                        ),
                        
					),
                ),
                '5'  => array(
                    'name'   => '微信服务号',
                    'href'   => '/admin/wxuser/search',
                    'icon'   => 'fa-weixin',
                    'active' => false,
					'module' => 'wechat',
                    'childs' => array(
                        array(
                            'name'   => '会员列表',
                            'href'   => '/admin/wxuser/search',
                            'icon'   => 'fa-user',
                            'active' => false,
                        ),
                        array(
                            'name'   => '用户留言',
                            'href'   => '/admin/wxuser/message',
                            'icon'   => 'fa-user',
                            'active' => false,
                        ),
                        array(
                            'name'   => '账号配置',
                            'href'   => '/admin/wechat/setting',
                            'icon'   => 'fa-gear',
                            'active' => false,
                        ),
                        
					),
                ),
                '6' => array(
                    'name'   => '图片管理',
                    'href'   => '/admin/picture/home',
                    'icon'   => 'fa-file-image-o',
                    'active' => false,
				    'module' => 'picture',
                    'childs' => array(
                        array(
                            'name'   => '首页横幅',
                            'href'   => '/admin/picture/home',
                            'icon'   => 'fa-user',
                            'active' => false,
                        ),
                    ),
                ),
                '7' => array(
                    'name'   => '地区管理',
                    'href'   => '/admin/city/search',
                    'icon'   => 'fa-globe',
                    'active' => false,
				    'module' => 'city',
                    'childs' => array(
                        array(
                            'name'   => '地区列表',
                            'href'   => '/admin/city/search',
                            'icon'   => 'fa-map-marker',
                            'active' => false,
                        ),
                    ),
                ),
                '97'  => array(
                    'name'   => '店铺管理',
                    'href'   => '/admin/shop/search',
                    'icon'   => 'fa-th-large',
                    'active' => false,
					'module' => 'shop',
                    'childs' => array(
                        array(
                            'name'   => '店铺列表',
                            'href'   => '/admin/shop/search',
                            'icon'   => 'fa-list',
                            'active' => false,
                        ),
                        array(
                            'name'   => '添加店铺',
                            'href'   => '/admin/shop/add',
                            'icon'   => 'fa-pencil',
                            'active' => false,
                        ),
					),
                ),
                '98' => array(
                    'name'   => '管理员管理',
                    'href'   => '/admin/user/search',
                    'icon'   => 'fa-users',
                    'active' => false,
					'module' => 'user',
                    'childs' => array(
                        array(
                            'name'   => '管理员列表',
                            'href'   => '/admin/user/search',
                            'icon'   => 'fa-user',
                            'active' => false,
                        ),
						array(
                            'name'   => '新增用户',
                            'href'   => '/admin/user/add',
                            'icon'   => 'fa-pencil',
                            'active' => false,
                        ),
					),
				),
                '99' => array(
                    'name'   => '角色管理',
                    'href'   => '/admin/role/search',
                    'icon'   => 'fa-wrench',
                    'active' => false,
					'module' => 'role',
                    'childs' => array(                       
                        array(
                            'name'   => '角色列表',
                            'href'   => '/admin/role/search',
                            'icon'   => 'fa-reorder',
                            'active' => false,
                        ),
						array(
                            'name'   => '添加角色',
                            'href'   => '/admin/role/add',
                            'icon'   => 'fa-pencil',
                            'active' => false,
                        ),
                    ),
                ),
        
        );
    }
}