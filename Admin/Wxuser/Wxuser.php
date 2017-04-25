<?php

/**
 * @ file Wxuser.php
 */
namespace Admin\Wxuser;

use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RedirectResponse;
use Api\Wechat\WechatAPI as WechatAPI;

use Entity;

class Wxuser {
    /**
     * 关注会员
     * @route /admin/wxuser/search
     * @access
     */
    public static function search($request) {
        if (($user = Entity\User\User::checkLogin()) == null) {
            return new RedirectResponse($request->getUriForPath('/admin/user/login'),'2',
                        '请先登录系统',array('Content-type'=>'text/html; charset=utf-8'));
        }
        //conditions
        $request->setParameter('conditions',array(
            'subscribe'     => array('value' => $request->get('subscribe',1)),
        ));
		$refresh_token = md5(time());
		variable()->set('wx_user_refresh_token',$refresh_token);
        $res = array(
			'refresh_token' => $refresh_token,
            'list'  => Entity\Wxuser\Wxuser::search($request),
            'menus' => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
        );

        return new Response(theme()->render('wxuser-search.html',$res));
    }
    
    /**
	 * 删除
     * @route /admin/wxuser/delete/{wid}
     * @access  admin_access
     * @param int $id
	 * @return redirect
	 */
	public static function delete($request) {
		if (($user = Entity\User\User::checkLogin()) == null) {
            return new RedirectResponse($request->getUriForPath('/admin/user/login'),'2',
                        '请先登录系统',array('Content-type'=>'text/html; charset=utf-8'));
        }
		$wid = (int)$request->route->getParameter('wid');       
		if ($wid) {
            $wxuser = Entity\Wxuser\Wxuser::load(entity_request(array('wid'=>$wid)));
            db_delete("wxuser")
                ->condition("wid",$wid)
                ->execute();
            return new RedirectResponse($request->getUriForPath('/admin/wxuser/search'),'0','成功');
        }
        else {
            return new RedirectResponse($request->getUriForPath('/admin/wxuser/search'),'0',lang('非法操作'));
        }

	}

    /**
     * 用户留言
     * @route /admin/wxuser/message
     * @access
     */
    public static function message($request) {
                
        if (($user = Entity\User\User::checkLogin()) == null) {
            return new RedirectResponse($request->getUriForPath('/admin/user/login'),'2',
                        '请先登录系统',array('Content-type'=>'text/html; charset=utf-8'));
        }
        $list = Entity\Message\Message::search($request);
        foreach($list['data'] as $k=>$message) {
            $wxuser = Entity\Wxuser\Wxuser::load(entity_request(array('wid'=>$message->wid)));
            $list['data'][$k]->openid = $wxuser->openid;
            $list['data'][$k]->content = self::emoji_html($message->content);
            $list['data'][$k]->headimgurl = $wxuser->headimgurl;
            $wxuser = null;
        }
        $res = array(
            'list'  => $list,
            'menus' => Entity\Menu\Menu::getMenu($request->route->get('path'))[0],
        );

        return new Response(theme()->render('wxuser-message.html',$res));
    }
    
    public static function emoji_html($string) {
        logger()->debug("微信原内容：".urlencode($string));
        $weixinEmoji = array(
            "/::)" => "0",
            "/::~" => "1",
            "/::B" => "2",
            "/::|" => "3",
            "/:8-)" => "4",
            "/::<" => "5",
            "/::$" => "6",
            "/::X" => "7",
            "/::Z" => "8",
            "/::(" => "9",
            "/::'(" => "9",
            "/::-|" => "10",
            "/::@" => "11",
            "/::P" => "12",
            "/::D" => "13",
            "/::O" => "14",
            "/::(" => "15",
            "/::+" => "16",
            "/:--b" => "17",
            "/::Q" => "18",
            "/::T" => "19",
            "/:,@P" => "20",
            "/:,@-D" => "21",
            "/::d" => "22",
            "/:,@o" => "23",
            "/::g" => "24",
            "/:|-)" => "25",
            "/::!" => "26",
            "/::L" => "27",
            "/::>" => "28",
            "/::,@" => "29",
            "/:,@f" => "30",
            "/::-S" => "31",
            "/:?" => "32",
            "/:,@x" => "33",
            "/:,@@" => "34",
            "/::8" => "35",
            "/:,@!" => "36",
            "/:!!!" => "37",
            "/:xx" => "38",
            "/:bye" => "39",
            "/:wipe" => "40",
            "/:dig" => "41",
            "/:handclap" => "42",
            "/:&-(" => "43",
            "/:B-)" => "44",
            "/:<@" => "45",
            "/:@>" => "46",
            "/::-O" => "47",
            "/:>-|" => "48",
            "/:P-(" => "49",
            "/::'|" => "50",
            "/:X-)" => "51",
            "/::*" => "52",
            "/:@x" => "53",
            "/:8*" => "54",
            "/:pd" => "55",
            "/:<W>" => "56",
            "/:beer" => "57",
            "/:basketb" => "58",
            "/:oo" => "59",
            "/:coffee" => "60",
            "/:eat" => "61",
            "/:pig" => "62",
            "/:rose" => "63",
            "/:fade" => "64",
            "/:showlove" => "65",
            "/:heart" => "66",
            "/:break" => "67",
            "/:cake" => "68",
            "/:li" => "69",
            "/:bome" => "70",
            "/:kn" => "71",
            "/:footb" => "72",
            "/:ladybug" => "73",
            "/:shit" => "74",
            "/:moon" => "75",
            "/:sun" => "76",
            "/:gift" => "77",
            "/:hug" => "78",
            "/:strong" => "79",
            "/:weak" => "80",
            "/:share" => "81",
            "/:v" => "82",
            "/:@)" => "83",
            "/:jj" => "84",
            "/:@@" => "85",
            "/:bad" => "86",
            "/:lvu" => "87",
            "/:no" => "88",
            "/:ok" => "89",
            "/:love" => "90",
            "/:<L>" => "91",
            "/:jump" => "92",
            "/:shake" => "93",
            "/:<O>" => "94",
            "/:circle" => "95",
            "/:kotow" => "96",
            "/:turn" => "97",
            "/:skip" => "98",
            "/:oY" => "99",
            "/:#-0" => "100",
            "/:hiphot" => "101",
            "/:kiss" => "102",
            "/:<&" => "103",
            "/:&>" => "104",
        );
        
        $emotionUrl = 'https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/';
        $emotionStringA = '<img src="'.$emotionUrl;
        $emotionStringB = '.gif" width="24" height="24">';
        
        foreach ($weixinEmoji as $k => $v) {           
            if (strpos($string,$k) !== false) {
                $img = $emotionStringA.$v.$emotionStringB;
                $string = str_replace($k,$img,$string);
            }        
        }
        return $string;
    }
	/**
	 * 更新所有用户信息
     * @route /admin/wxuser/refresh
     * @access  admin_access
     * @param  string refres_token
	 * @return json
	 */
	public static function refresh($request) {
		if (($user = Entity\User\User::checkLogin()) == null) {
            return new RedirectResponse($request->getUriForPath('/admin/user/login'),'2',
                        '请先登录系统',array('Content-type'=>'text/html; charset=utf-8'));
        }
		$post_data = $request->post->getParameters();    
		if (isset($post_data['refresh_token']) 
			&& $post_data['refresh_token']==variable()->get('wx_user_refresh_token')) {
           
			while(true) {	//循环取用户
				$users = WechatAPI::getUserList($request);
				foreach($users['data'] as $wxuser) {
					$array = array(
						'username'  => $wxuser['nickname'],
                        'nickname'  => $wxuser['nickname'],
						'openid'    => $wxuser['openid'],
						'city'      => $wxuser['city'],
						'province'  => $wxuser['province'],
						'country'   => $wxuser['country'],
						'headimgurl' => $wxuser['headimgurl'],
						'subscribe_time' => $wxuser['subscribe_time'],
						'language'  => $wxuser['language'],
						'sex'       => $wxuser['sex'],
						'subscribe' => $wxuser['subscribe'],
						'password'  => '',
						'unionid'   => '',
					);
					$olduser = db_select("wxuser","w")
						->fields("w",array('wid'))
						->condition("openid",$wxuser['openid'])
						->execute()
						->fetch();
					if ($olduser) {
						$array['wid'] = $olduser->wid;
						Entity\Wxuser\Wxuser::update(entity_request($array));
						logger()->debug("##### 更新会员 is:".$wxuser['nickname']." @ ".$wxuser['openid']);
					}
					else {
						Entity\Wxuser\Wxuser::insert(entity_request($array));
						logger()->debug("##### 有新的微信会员关注 is:".$wxuser['nickname']." @ ".$wxuser['openid']);
					}
					$array = $olduser = null;
				}
				if (!empty($users['next_openid'])) {
					break;
				}
				else {
					$request->setParameter('next_openid',$users['next_openid']);
				}
				$users = null;

			}
        }

		return new Response(json_encode(
                            array('status'=>'success' ,'msg'=>'ok')),'200',
                            array('Content-Type'=>'application/json'));
	}
    
}
