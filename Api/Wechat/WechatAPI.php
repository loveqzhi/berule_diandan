<?php 

namespace Api\Wechat;

use Api\Wechat\WechatHttp as WechatHttp;

class WechatAPI {
    
    //公众号配置
    public static $wxconfig;
    
    //公众号回调的post数据中的密文encrypt
    public static $encrypt;
    
    //公众号回调 xml明文
    public static $requestxml;
    
    //公众号回调的签名signature
    public static $signature;
    
    //公众号回调的 timestamp
    public static $timestamp;
    
    //公众号回调的 nonce
    public static $nonce;
    
    
    /*
     * 授权
     */
    public static function checkAccess() {
        self::$signature = isset($_GET['signature']) ? $_GET['signature'] : '';
        self::$timestamp = isset($_GET['timestamp']) ? $_GET['timestamp'] : time();
        self::$nonce     = isset($_GET['nonce']) ? $_GET['nonce'] : '';
        $hash = self::getSha1(self::$wxconfig['token'], self::$timestamp, self::$nonce);
        logger()->debug("calculate hash is: ".$hash." sign is: ".self::$signature);
        if (isset($_GET['echostr'])) {  //验证url
            if ($hash != self::$signature) {
                return false;
            }
            logger()->debug("check URL success");
            return $_GET['echostr'];
        }
        else {//验证签名
            self::$requestxml = file_get_contents('php://input');
            if ($hash != self::$signature) {
                return false;
            }
            return true;
        }
    }
    
    
    /*
     * 发送客服消息
     */
    public static function send($msg) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=%s';
        $token = self::getToken();
        return WechatHttp::curl(sprintf($url, $token), array(), self::json_encode($msg));        
    }
    /**
     * 发送模板消息
     */
    public static function sendTemplate($msg) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=%s';
        $token = self::getToken();
        logger()->debug("json data is ".self::json_encode($msg));
        return WechatHttp::curl(sprintf($url, $token), array(), self::json_encode($msg));
    }
    /*
     * 获取access_token
     *
     * @return string
     */
    public  static function getToken($setwxconfg=FALSE) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
        if (empty(self::$wxconfig)) {
            self::$wxconfig = variable()->get('wxconfig');
            if ($setwxconfg==true) {
                return true;
            }
        }
        //从数据库读取
        $tokens = variable()->get('wxtoken');
        if (empty($tokens) || time()>$tokens['expired']) {
            list($header, $body) = WechatHttp::curl(sprintf($url, self::$wxconfig['appid'], self::$wxconfig['appsecret']));
            $json = json_decode($body, true);
            if (!$json || isset($json['errcode'])) {
                throw new Exception('can not access token.');            
            } else {
                $tokens['token']   = $json['access_token'];
                $tokens['expired'] = time() + $json['expires_in'] - 200;
                variable()->set('wxtoken',$tokens);
            }
        }
        return $tokens['token'];
    }
    
    /**
     *  获取jsapi 签名包
     * 
     *
     */
    public static function getSignPackage(){
        self::getToken(true);
        $jsapiTicket = self::getJsapiTicket();
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $timestamp = time();
        $nonceStr = random();
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
          "appId"     => self::$wxconfig['appid'],
          "nonceStr"  => $nonceStr,
          "timestamp" => $timestamp,
          "url"       => $url,
          "signature" => $signature,
          "rawString" => $string
        );
        logger()->debug("Get Jsapi signpackage is:".var_export($signPackage,true));
        return $signPackage; 
    }
    public static function getJsapiTicket(){
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=%s";
        //从数据库读取
        $tickets = variable()->get('jsapi_ticket');
        if (empty($tickets) || time()>$tickets['expire_time']) {
            $token = self::getToken();
            list($header, $body) = WechatHttp::curl(sprintf($url, $token));
            $json = json_decode($body, true);
            
            $tickets = array(
                'jsapi_ticket' => $json['ticket'],
                'expire_time'  => time() + 7000,
            );
            variable()->set('jsapi_ticket',$tickets);           
        }
        return $tickets['jsapi_ticket'];
    }
    /*
     * 上传多媒体文件
     *
     * image 128K, voice 256K, video 1M, thumb 64K     
     * @return array(type => , media_id => , created_at => )
     */
    public function uploadFile($type, $file) {
        static $url = 'http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=%s&type=%s';
        $token = $this->getToken();
        list($header, $body) = WechatHttp::curl(sprintf($url, $token, $type), array(), array('media' => '@'.realpath($file)));
        $json = json_decode($body, true);
        if (!$json || isset($json['errcode'])) {
            throw new Exception('failed to upload file.');
        } else {
            return $json;
        }
    }

    /*
     * 下载多媒体文件
     *
     * @return binary
     */
    public function downloadFile($media_id) {
        static $url = 'http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=%s&media_id=%s';
        $token = self::getToken();
        list($header, $body) = WechatHttp::curl(sprintf($url, $token, $media_id));
        if (substr($body,0,1) == '{') {
            throw new Exception('can not download media file.');
        } else {
            return $body;
        }
    }

    /*
     * 获取用户信息
     @return array(subscribe=>1, openid=>, nickname=>, sex=>, language=>, province=>, city=>, country=>, headimgurl=>, subscribe_time=>,)
     */
    public static function getUserInfo($openid) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=%s&openid=%s&lang=zh_CN';
        $token  = self::getToken();
        list($header, $body) = WechatHttp::curl(sprintf($url, $token, $openid));
        $json = json_decode($body, true);
        if (!$json || isset($json['errcode'])) {
            throw new Exception('failed to get user info.');
        } else {
            return $json;
        }
    }

    /*
     * 获取关注者列表
     * 
     */
    public function getUserList($request) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token=%s&next_openid=%s';
        $token  = self::getToken();
        $next_openid = $request->get('next_openid','');
        list($header, $body) = WechatHttp::curl(sprintf($url, $token, $next_openid));
        $json = json_decode($body, true);//print_r($json);exit;
        $users = array();
        if ($json && !isset($json['errcode'])) {
            //$users['data']['openid'] = isset($json['data']['openid']) ? $json['data']['openid'] : array();
            $users['total'] = $json['total'];
            $users['count'] = $json['count'];
			$users['next_openid'] = $json['next_openid'];
			$users['data'] = array();
            if (!empty($json['data']['openid'])) {
                foreach($json['data']['openid'] as $openid) {
                     $users['data'][] = self::getUserInfo($openid);
                }
            }
        }

        return $users;
    }

    /*
     * 创建自定义菜单
     */
    public static function createMenu($menu) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=%s';
        $token = self::getToken();
        list($header, $body) = WechatHttp::curl(sprintf($url, $token), array(), self::json_encode($menu));
        $json = json_decode($body, true);
        logger()->debug("create menu result ".var_export($json,true));
        if (!$json || $json['errcode'] != 0) {
            return false;
        } else {
            return true;
        }
    }

    /*
     * 查询自定义菜单
     */
    public function getMenu() {
        static $url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token=%s';
        $token = self::getToken();
        list($header, $body) = WechatHttp::curl(sprintf($url, $token));
        $json = json_decode($body, true);
        if (!$json || isset($json['errcode'])) {
            return false;
        } else {
            return $json;
        }
    }

    /*
     * 删除自定义菜单
     */
    public function deleteMenu() {
        static $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=%s';
        $token = self::getToken();
        list($header, $body) = WechatHttp::curl(sprintf($url, $token));
        $json = json_decode($body, true);
        if (!$json || $json['errcode'] != 0) {
            return false;
        } else {
            return true;
        }
    }

    /*
     * 用户同意授权，获取code
     */
    public static function getWebCodeUrl($param){
        static $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=%s&scope=%s&state=%s#wechat_redirect';
        self::getToken(true);
        $query = array(
            'appid'         => self::$wxconfig['appid'],
            'redirect_uri'  => $param['redirect_uri'],
            'response_type' => 'code',
            'scope'         => $param['scope'],
            'state'         => $param['state'],
        );
        return vsprintf($url, $query);
    }
    
    /*
     * 通过code换取网页授权access_token
     */
    public static function getWebToken($code) {
        static $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?%s';
        self::getToken(true);
        $query = array(
            'appid'      => self::$wxconfig['appid'],
            'secret'     => self::$wxconfig['appsecret'],
            'code'       => $code,
            'grant_type' => 'authorization_code',
        );
        list($header, $body) = WechatHttp::curl(sprintf($url, http_build_query($query)));
        $json = json_decode($body, true);
        if (!$json || isset($json['errcode'])) {
            throw new Exception('failed to upload file.');
        } else {
            return $json;
        }
    }

    /*
     * 刷新access_token
     */
    public static function refreshWebToken($request,$token) {
        static $url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?%s';
        $query = array(
            'appid'         => $request->params->getParameter('wxconfig')['appid'],
            'grant_type'    => 'refresh_token',
            'refresh_token' => $token,
        );
        list($header, $body) = WechatHttp::curl(sprintf($url, http_build_query($query)));
        $json = json_decode($body, true);
        if (!$json || isset($json['errcode'])) {
            throw new Exception('failed to upload file.');
        } else {
            return $json;
        }
    }
    
    /*
     * 判断access_token
     */
    public static function authWebToken($token,$openid) {
        static $url = 'https://api.weixin.qq.com/sns/auth?access_token=%s&openid=%s';
        list($header, $body) = WechatHttp::curl(sprintf($url,$token,$openid));
        $json = json_decode($body, true);
        if (!$json || $json['errcode'] != 0) {
            return false;
        } else {
            return true;
        }
    }
    
    /*
     * 获取用户信息(需scope为 snsapi_userinfo)
     * @return array(openid=>, nickname=>, sex=>, province=>, city=>, country=>, headimgurl=>, privilege:[])
     */
    public static function getWebUserInfo($openid, $token) {
        static $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN';
        list($header, $body) = WechatHttp::curl(sprintf($url, $token, $openid));
        $json = json_decode($body, true);
        logger()->debug("userinfo data is:".var_export($json,true));
        if (!$json || isset($json['errcode'])) {
            throw new Exception('failed to get user info.');
        } else {
            return $json;
        }
    }

    //生成json数据
    protected static function json_encode($data) {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            return urldecode(json_encode($this->urlencode($data)));
        } else {
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }
    
    //转码数组
    protected function urlencode($data) {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[urlencode($k)] = $this->urlencode($v);
            }
        } else {
            $data = urlencode($data);
        }
        
        return $data;
    }
    
    public static function getSha1($token, $timestamp, $nonce)
	{
		//排序
		try {
			$array = array($token, $timestamp, $nonce);
			sort($array, SORT_STRING);
			$str = implode($array);
			return  sha1($str);
		} catch (Exception $e) {
			print $e . "\n";
			return null;
		}
	}
}