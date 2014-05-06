<?php

namespace Suburb\HttpRequest\Api\Twitter;

interface IAuthMechanism
{
	
	public function getAccessToken();
	public function getAccessTokenSecret();
	
}