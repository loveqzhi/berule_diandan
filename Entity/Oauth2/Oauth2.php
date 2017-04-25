<?php

/**
 * @ file Oauth2.php
 */
namespace Entity\Oauth2;
use Entity;

class Oauth2 {

    /**
	 * Create a new provider.
	 *
	 * $provider = Entity\Oauth2\Oauth2::provider('wechat',$options);
	 *
	 * @param   string $name    provider name
	 * @param   array  $options provider options
	 * @return  OAuth2_Provider
	 */
	public static function provider($name, array $options = NULL) {
		$name = ucfirst(strtolower($name));
		$class = "Entity\\Oauth2\\Provider\\".$name;
        
        return new $class($options);      
	}
}