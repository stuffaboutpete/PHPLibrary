<?php

namespace PO\Helper;

/**
 * Exists for testing purposes - cookies
 * cannot be set during a PHPUnit test
 */
class Cookie
{
	
	public function set(
		$name,
		$value = null,
		$expire = 0,
		$path = null,
		$domain = null,
		$secure = false,
		$httponly = false
	)
	{
		setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
	}
	
	public function revoke($name, $value)
	{
		setcookie($name, $value, 1);
	}
	
}
