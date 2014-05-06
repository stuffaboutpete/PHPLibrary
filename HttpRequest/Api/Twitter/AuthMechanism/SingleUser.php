<?php

namespace Suburb\HttpRequest\Api\Twitter\AuthMechanism;

use Suburb\HttpRequest\Api\Twitter;

class SingleUser
implements Twitter\IAuthMechanism
{
	
	private $accessToken;
	private $accessTokenSecret;
	
	public function __construct(
		$accessToken,
		$accessTokenSecret
	)
	{
		$this->accessToken = $accessToken;
		$this->accessTokenSecret = $accessTokenSecret;
	}
	
	public function getAccessToken()
	{
		return $this->accessToken;
	}
	
	public function getAccessTokenSecret()
	{
		return $this->accessTokenSecret;
	}
	
}