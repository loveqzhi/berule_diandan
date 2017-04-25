<?php

/**
 * @ file
 *  
 * @ User App用户中心
 */

namespace Entity\User {

    entity_register('user', array(
        'controller' => 'Entity\\User\\UserEntityController',
        'primaryKey' => 'uid',
        'baseTable'  => 'user',
    ));

    class User {
    
        //根据主键读取
        public static function load($request) {
            $uid  = (int) $request->getParameter('uid');
            $data = entity_load('user', array($uid));
            return reset($data);
        }

        //根据主键读取多个
        public static function loadMulti($request) {
            $uids = $request->getParameter('uids');
            if ($uids && !is_array($uids)) {
                $uids = explode(',', $uids);
            }
            $data = entity_load('user', $uids);
            return $data;
        }

        //根据Uuid读取
        public static function loadByUuid($request) {
            $uuid = $request->getParameter('uuid');
            $data  = entity_load('user', array(), array('uuid'=>$uuid));
            return reset($data);
        }

        //根据username读取
        public static function loadByUsername($request) {
            $username = $request->getParameter('username');
            $data  = entity_load('user', array(), array('username'=>$username));
            return reset($data);
        }

		//根据UID读取用户名
		public static function loadUsernameByUid($uid) {

			return self::load(entity_request(array('uid'=>$uid)))->username;
		}

        //修改密码
        public static function changePassword($request) {
            $oldpassword = $request->getParameter('oldpassword');
            $newpassword = $request->getParameter('newpassword');
            $cfmpass     = $request->getParameter('cfmpass');
            if (!$oldpassword || !$newpassword || !$cfmpass) {
                return entity_response()->setStatus(10001);
            }
            if (mb_strlen($newpassword,'UTF-8') < 6) {
                return entity_response()->setStatus(10010);
            }
            if ($cfmpass !== $newpassword) {
                 return entity_response()->setStatus(10009);
            }
            if ($user = self::load($request)) {
                if (pyramid_password_verify($oldpassword, $user->password)) {
                    self::update(entity_request(array('uid'=>$user->uid,'password'=>$newpassword)));
                    return entity_response()->setStatus(0);
                } else {
                    return entity_response()->setStatus(10002);
                }
            } else {
                return entity_response()->setStatus(10003);
            }
        }

        //检测用户是否可以登录
        public static function check($request) {
            $username = $request->getParameter('username');
            $password = $request->getParameter('password');
            if (!$username) {
                return entity_response()->setStatus(10001);
            }
            $query = db_select('user', 'u')->fields('u', array('uid'));
            if ($username) {
                $query->condition('username', $username);
            }
            if ($uid = $query->execute()->fetchField()) {
                $request->setParameter('uid',$uid);
                $user = self::load($request);
                if($user->password == '') {
                   return entity_response()->setStatus(10007);
                }
                if (pyramid_password_verify($password, $user->password)) {
                    self::updateAccessedByUid($user->uid);
                    return entity_response($user);
                } else {
                    return entity_response()->setStatus(10002);
                }
            } else {
                return entity_response()->setStatus(10003);
            }
        }
    
        //更新登录时间 
        public static function updateAccessedByUid($uid) {
            return db_update("user")
                    ->fields(array('accessed'=>time(),'status'=>1))
                    ->condition("uid",$uid)
                    ->execute();
        }      
        //新增
        public static function insert($request) {
            $user = (object) $request->getParameters();
            unset($user->uid);
            foreach (array('username', 'password') as $key) {
                if (empty($user->$key)) {
                    return entity_response()->setStatus(10001);
                }
            }
            if ($exists = db_select('user', 'u')
                            ->fields('u')
                            ->condition('username', $user->username)
                            ->execute()
                            ->fetchObject()) {
                return entity_response()->setStatus(10005);
            }
            $user->uuid = uuid();
            $user->status = 1;
            $user->created = $user->updated = time();
            $user->nickname = $user->username;
            $user->password = pyramid_password_hash($user->password);
            entity_insert('user', $user);
            logger()->info("register a user success: ".var_export((array)$user,true));
            return $user;
        }
        
        //修改
        public static function update($request) {
            $user = (object) $request->getParameters();
            unset($user->username, $user->created);
            if (!empty($user->password)) {
                $user->password = pyramid_password_hash($user->password);
            }
            entity_update('user', $user);
            return $user;
        }

        //用户列表
        public static function search($request) {
            $navi   = array('page'=> 1,'size' => 15, 'total'=> 0, 'pages' => 1);
            $page   = (int) $request->getParameter('page', 1);
            $size   = (int) $request->getParameter('size', 15);
            $status = $request->get('status',null);
            $query  = db_select('user', 'u')
                            ->extend('Pager')->page($page)->size($size)
                            ->fields('u', array('uid'))
                            ->orderBy('uid', 'DESC');
            if (!is_null($status)) {
                $query->condition('status', $status);
            }
            $uids     = $query->execute()->fetchCol();
            $pager    = array_merge($navi,$query->fetchPager());
            $data     = self::loadMulti(entity_request(array('uids'=>$uids)));

            return array('data'=>$data, 'pager'=>$pager);
        }
        //检查是否登录
        public static function checkLogin(){
            
            return session()->get('user') ? session()->get('user') : null;
        }
		//返回用户UID
        public static function getUid(){
            
            return session()->get('user') ? session()->get('user')->uid : null;
        }
        
        //获取用户邮箱配置
        public static function getUserMailOption($uid,$boxid) {
            $user = self::load(entity_request(array('uid'=>$uid)));
            if(empty($user) || empty($user->field_user_mail) 
                || !isset($user->field_user_mail[$boxid])
                || $user->field_user_mail[$boxid]['status'] != 1) {
                return false;
            }
            else {
                return $user->field_user_mail[$boxid] + unserialize($user->field_user_mail[$boxid]['options']);
            }
        }
         //获取用户邮箱配置
        public static function getUserMailOptionByName($uid,$boxname) {
            $user = self::load(entity_request(array('uid'=>$uid)));
            foreach($user->field_user_mail as $k=>$mail) {
                if($mail['name'] == $boxname) {
                    return $mail + unserialize($mail['options']);
                }
            }
            return false;
        }
		//获取用户短信接口配置getUserSmsOption
        public static function getUserSmsOption($uid,$boxid) {
            $user = self::load(entity_request(array('uid'=>$uid)));
            if(empty($user) || empty($user->field_user_sms) 
                || !isset($user->field_user_sms[$boxid])) {
                return false;
            }
            else {
                return $user->field_user_sms[$boxid] + unserialize($user->field_user_sms[$boxid]['options']);
            }
        }
		//获取用户短信接口配置getUserSmsOptionByName
        public static function getUserSmsOptionByName($uid,$boxname) {
            $user = self::load(entity_request(array('uid'=>$uid)));
            foreach($user->field_user_sms as $k=>$sms) {
                if($sms['name'] == $boxname) {
                    return $sms + unserialize($sms['options']);
                }
            }
            return false;
        }
    }
}