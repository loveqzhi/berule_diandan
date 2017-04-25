<?php
namespace Admin\Role;
use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Entity;

class Role {

    /**
     * 返回权限配置数组
     * @route 
     * @access
     * @param 
     * @return array
     */
    protected static function getPermissions(){
        return  array(
            '我的店铺' => array(
				'/admin/manager/shop' => array(
                        'title'         => '店铺列表',
                        'description'   => '店铺列表',
                        'module'        => 'manager',
                        'quantity'      => '1',
                        'inherited'     => '',
                        'warning'       => '',
                ),
            ),
            '外卖订单' => array(
				'/admin/takeout/search' => array(
                        'title'         => '订单列表',
                        'description'   => '订单列表',
                        'module'        => 'takeout',
                        'quantity'      => '1',
                        'inherited'     => '',
                        'warning'       => '',
                ),
            ),
            '微信排号' => array(
				'/admin/queue/search' => array(
                        'title'         => '排号列表',
                        'description'   => '微信排号',
                        'module'        => 'queue',
                        'quantity'      => '1',
                        'inherited'     => '',
                        'warning'       => '',
                ),
            ),
            '预订位子' => array(
                '/admin/book/search' => array(
                        'title'         => '预订列表',
                        'description'   => '预订位子',
                        'module'        => 'book',
                        'quantity'      => '1',
                        'inherited'     => '',
                        'warning'       => '',
                ),
			),
            '菜谱管理' => array(
				'/admin/food/search' => array(
                        'title'         => '菜谱列表',
                        'description'   => '菜谱列表',
                        'module'        => 'food',
                        'quantity'      => '1',
                        'inherited'     => '',
                        'warning'       => '',
                ),
                '/admin/food/taxonomy/search' => array(
                        'title'         => '食谱分类',
                        'description'   => '',
                        'module'        => 'food',
                        'quantity'      => '1',
                        'inherited'     => '',
                        'warning'       => '',
                ),
			),
            '微信服务号' => array(
				'/admin/wxuser/search' => array(
                        'title'         => '用户管理',
                        'description'   => '更新地区',
                        'module'        => 'wechat',
                        'quantity'      => '1',
                        'inherited'     => '',
                        'warning'       => '',
                ),
                '/admin/wxuser/message' => array(
                        'title'         => '用户留言',
                        'description'   => '用户留言',
                        'module'        => 'wechat',
                        'quantity'      => '1',
                        'inherited'     => '',
                        'warning'       => '',
                ),
                '/admin/wechat/setting' => array(
                        'title'         => '账号配置',
                        'description'   => '',
                        'module'        => 'wechat',
                        'quantity'      => '1',
                        'inherited'     => '',
                        'warning'       => '',
                ),
			),
            '店铺管理' => array(
				'/admin/shop/search' => array(
                        'title'         => '店铺员列表',
                        'description'   => '店铺员列表',
                        'module'        => 'shop',
                        'quantity'      => '1',
                        'inherited'     => '',
                        'warning'       => '',
                ),
				'/admin/shop/add' => array(
                        'title'         => '添加店铺',
                        'description'   => '添加店铺',
                        'module'        => 'shop',
                        'quantity'      => '2',
                        'inherited'     => '',
                        'warning'       => '',
                ),
			),
            '图片管理' => array(
                '/admin/picture/home' => array(
                        'title'         => '首页横幅',
                        'description'   => '首页横幅',
                        'module'        => 'picture',
                        'quantity'      => '3',
                        'inherited'     => '',
                        'warning'       => '',
                ),
            ),
            '地区管理' => array(
                '/admin/city/search' => array(
                        'title'         => '地区列表',
                        'description'   => '地区列表',
                        'module'        => 'city',
                        'quantity'      => '3',
                        'inherited'     => '',
                        'warning'       => '',
                ),
            ),
			'管理员管理' => array(
				'/admin/user/search' => array(
                        'title'         => '管理员列表',
                        'description'   => '管理员',
                        'module'        => 'user',
                        'quantity'      => '1',
                        'inherited'     => '',
                        'warning'       => '',
                ),
				'/admin/user/add' => array(
                        'title'         => '添加管理员',
                        'description'   => '添加管理员',
                        'module'        => 'user',
                        'quantity'      => '2',
                        'inherited'     => '',
                        'warning'       => '',
                ),
				'/admin/user/edit' => array(
                        'title'         => '编辑管理员',
                        'description'   => '编辑管理员',
                        'module'        => 'user',
                        'quantity'      => '3',
                        'inherited'     => '',
                        'warning'       => '',
                ),
			),
            '角色管理' => array(                
                '/admin/role/search' => array(
                        'title'         => '角色列表',
                        'description'   => '更新角色',
                        'module'        => 'role',
                        'quantity'      => '1',
                        'inherited'     => '',
                        'warning'       => '',
                ),
				'/admin/role/add' => array(
                        'title'         => '添加角色',
                        'description'   => '更新角色',
                        'module'        => 'role',
                        'quantity'      => '1',
                        'inherited'     => '',
                        'warning'       => '',
                ),
				'/admin/role/edit' => array(
                        'title'         => '编辑角色',
                        'description'   => '更新角色',
                        'module'        => 'role',
                        'quantity'      => '1',
                        'inherited'     => '',
                        'warning'       => '',
                ),
            ),
        );
    }
    
    
    /**
     * 获取扁平化权限数组
     * @route 
     * @access 
     * @return array
     */
    public static function getAllPermissions() {
        $tempPermissions = array();
		foreach (self::getPermissions() as $v) {
			$tempPermissions = array_merge($tempPermissions,$v);
		}
        return $tempPermissions;
    }
    
    /**
     * 更新用户角色
     * @route
     * @access
     * @param $param 角色ID数组
     * @param $id   用户ID
     * @return boolean
     */
    public static function updateUserRoleByUid($param,$uid) {
        //删除原角色
        $thisdb = db_transaction(); //开启事务
        try {
            db_delete("relation_user_roles")
                ->condition("uid",$uid)
                ->execute();
            foreach($param as $v) {
                db_insert("relation_user_roles")
                    ->fields(array('uid'=>$uid,'rid'=>$v))
                    ->execute();
            }
        } catch (Exception $e) {
            $thisdb->rollback(); //回滚
            logger()->warn("更新用户角色失败了: ".$e->getMessage());
        }
        
        return true;
    }
    
    /**
     * 角色列表
     * @route /admin/role/search
     * @param int $page
     * @param int $size
     * @ return array
     */
    public static function search($request) {
        
        $res = array(
            'menus' => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
            'list'  => Entity\Role\Role::search($request),
        );
		
        return new Response(theme()->render('admin-role.html',$res));  
    }
    
    /**
     * 添加角色权限
     * @route /admin/role/add
     * @param $request
     * @return mixed
     */
    public static function add ($request) {
        //判断权限 todo
                
        $temproles = self::getAllPermissions();       
        if ($request->post->getParameters()) {
            $post_data = $request->getParameters();
            if (empty($post_data['name'])) {
                return new Response(json_encode(
                                array('status'=>'error','msg'=>'角色名称必须填写')),'200',
                                array('Content-Type'=>'application/json'));
            }
            if (!empty($post_data['field_role_permission'])) {
                foreach ($post_data['field_role_permission'] as $k => $v) {
                    if (isset($temproles[$v])) {
                        $post_data['field_role_permission'][$k] = array('permission'=>$v,'data'=>serialize($temproles[$v]));
                    } else {
                        unset($post_data['field_role_permission'][$k]);
                    }
                }
            }
            Entity\Role\Role::insert(entity_request($post_data));
            return new Response(json_encode(
                                array('status'=>'success','msg'=>'ok')),'200',
                                array('Content-Type'=>'application/json'));
        }
        else {
            $res['temproles'] = self::getPermissions();
            $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0]; 
            return new Response(theme()->render('admin-role-add.html',$res));
        }
    }
    /**
     * 编辑角色权限
	 * @route /admin/role/edit/{rid}
	 * @access
	 * @param $request 
	 * @return redirect
	 */
	public static function edit($request) {
        //判断权限 todo
        
        $rid = (int)$request->route->getParameter('rid');
        $res['role'] = Entity\Role\Role::load(entity_request(array('rid'=>$rid)));
        if (empty($res['role'])) {
            return new RedirectResponse($request->getUriForPath('/admin/role/search'),'2',
                        lang('非法操作'),array('Content-type'=>'text/html; charset=utf-8'));
        }
        //print_r($res['role']);exit;
        $res['temproles'] = self::getPermissions();
        $res['menus'] = Entity\Menu\Menu::getMenu($request->route->get('path'))[0];
        return new Response(theme()->render('admin-role-edit.html',$res));
    
    }
    
    /**
     * 更新角色权限
	 * @route /admin/role/update
	 * @access
	 * @param $request 
	 * @return redirect
	 */
	public static function update($request) {
		$tempPerms = self::getAllPermissions();
		$post_data = $request->getParameters();
		if (!empty($post_data['rid'])) {
			$array['rid'] = (int) $post_data['rid'];
            $array['name'] = trim($post_data['name']);
            $array['weight'] = (int)$post_data['weight'];
			foreach ($post_data['field_role_permission'] as $key) {
				if (isset($tempPerms[$key])) {
					$array['field_role_permission'][] = array('permission'=>$key,'data'=>serialize($tempPerms[$key]));
				}
			}
			Entity\Role\Role::update(entity_request($array));
            return new Response(json_encode(
                                array('status'=>'success','msg'=>'ok')),'200',
                                array('Content-Type'=>'application/json'));
		}
        else {
            return new Response(json_encode(
                                array('status'=>'error','msg'=>lang('非法操作'))),'200',
                                array('Content-Type'=>'application/json'));
        }

	}
    
}