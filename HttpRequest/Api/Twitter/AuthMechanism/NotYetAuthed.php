<?php

namespace Suburb\HttpRequest\Api\Twitter\AuthMechanism;

use Suburb\HttpRequest\Api\Twitter;

class NotYetAuthed
implements Twitter\IAuthMechanism
{
	
	private $token;
	
	public function __construct($token = null)
	{
		$this->token = $token;
	}
	
	public function getAccessToken()
	{
		return $this->token;
	}
	
	public function getAccessTokenSecret()
	{
		return '';
	}
	
}