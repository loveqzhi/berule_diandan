<?php

/**
 * @ file Provider.php
 */

namespace Entity\Oauth2;
use Entity;

abstract class Provider {

    //$name 实例化认证模块名称
    public $name;
    
    //$callback 回调
    public $callback;
    
    //$client_secret
    public $client_secret;
    
    //$redirect_uri
    public $redirect_uri;

    //$params 参数
    public $params = array();
    
    //$method 请求方法
    public $method = 'GET';
    
    //$scope
    public $scope;
    
    //$scope_seperator 分割符
    public $scope_seperator;
    
    //构造函数
    /**
     * @param options = array('id'=>'','secret'=>'','callback'=>'','scope'=>'')
     */
    public function __construct(array $options = array()) {
        $this->params = $options;
		if (empty($options['id'])) {
			throw new \Exception('Required option not provided: id');
		}
        
		$this->client_id = $options['id'];
		isset($options['callback']) and $this->callback = $options['callback'];
		isset($options['secret'])   and $this->client_secret = $options['secret'];
		isset($options['scope'])    and $this->scope = $options['scope'];
        isset($options['state'])    and $this->state = $options['state'];
		isset($options['redirect_uri']) and $this->redirect_uri = $options['redirect_uri'];
    }
    
    public function __get($key) {
		return $this->$key;
	}
    
    //Returns the authorization URL for the provider.
    abstract public function url_authorize();

    //Returns the access token endpoint for the provider.
    abstract public function url_access_token();
    
    /**
	 * @param OAuth2_Token_Access $token
	 * @return array basic user info
	 */
    //abstract public function get_user_info(OAuth2_Token_Access $token);
    
    /*
	* Get an authorization code from provider.
	*/	
	public function authorize($options = array())
	{
		$params = array(
			'client_id' 		=> $this->client_id,
			'redirect_uri' 		=> isset($options['redirect_uri']) ? $options['redirect_uri'] : $this->redirect_uri,
			'state' 			=> isset($options['state']) ? $options['state'] : $this->state,
			'scope'				=> is_array($this->scope) ? implode($this->scope_seperator, $this->scope) : $this->scope,
			'response_type' 	=> 'code',
		);
        
		$params = array_merge($params, $this->params);
        if ($this->name == 'wechat') {
            //临时用微信的测试        
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=%s&scope=%s&state=%s&#wechat_redirect=';
            $query = array(
                'appid'         => $params['appid'],
                'redirect_uri'  => urlencode($params['redirect_uri']),
                'response_type' => 'code',
                'scope'         => $params['scope'],
                'state'         => urlencode($params['state']),
            );
            $url = vsprintf($url, $query);
        }
        else {
            $url = $this->url_authorize().'?'.http_build_query($params);
        }
              
        header('Location: '.$url);
        exit;
	}
    
    /*
	* Get access token
	*
	* @param	string	The access code
	* @return	object	Success or failure along with the response details
	*/	
	public function access($code, $options = array()) {
		$params = array(
			'client_id' 	=> $this->client_id,
			'client_secret' => $this->client_secret,
			'grant_type' 	=> isset($options['grant_type']) ? $options['grant_type'] : 'authorization_code',
		);
		
		$params = array_merge($params, $this->params);
		switch ($params['grant_type']) {
			case 'authorization_code':
				$params['code'] = $code;
				$params['redirect_uri'] = isset($options['redirect_uri']) ? $options['redirect_uri'] : $this->redirect_uri;
			break;
			case 'refresh_token':
				$params['refresh_token'] = $code;
			break;
		}
		$response = null;
		$url = $this->url_access_token();
		switch ($this->method) {
			case 'GET':
				// Need to switch to Request library, but need to test it on one that works
				$url .= '?'.http_build_query($params);
				$response = file_get_contents($url);
				parse_str($response, $return);
			break;
			case 'POST':             
				$opts = array(
					'http' => array(
						'method'  => 'POST',
						'header'  => 'Content-type: application/x-www-form-urlencoded',
						'content' => http_build_query($params),
					)
				);
				$_default_opts = stream_context_get_params(stream_context_get_default());
				$context = stream_context_create(array_merge_recursive($_default_opts['options'], $opts));
				$response = file_get_contents($url, false, $context);
				$return = json_decode($response, true);
			break;
			default:
				throw new \Exception("Method '{$this->method}' must be either GET or POST");
		}
        
        return $return;    		
	}
    
}
