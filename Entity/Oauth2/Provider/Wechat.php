<?php 
/**
 * @ file Wechat.php
 */

namespace Entity\Oauth2\Provider;
use Entity;

class Wechat extends Entity\Oauth2\Provider{
    
    public $method = 'POST';
    public $scope_seperator = ',';

    public function __construct(array $options = array()) {
        if (empty($options['scope'])) {
          $options['scope'] = 'snsapi_userinfo';    //  snsapi_base
        }
        $options['id']      = $options['appid'];
        $options['secret']  = $options['appsecret'];
        $options['#wechat_redirect'] = '';
        $this->name = 'wechat';
        parent::__construct($options);
    }
    
    public function url_authorize() {
        return 'https://open.weixin.qq.com/connect/oauth2/authorize';
    }
    
    public function url_access_token() {
        return 'https://api.weixin.qq.com/sns/oauth2/access_token';
    }
    /*
    public function get_user_info(OAuth2_Token_Access $token) {
        return true;
    }
    */

}