<?php

namespace PO\HttpRequest\Api;

use PO\HttpRequest;

class GitHub
extends HttpRequest
{
	
	public function __construct(
		/* string */				$accessToken,
		HttpRequest\ITransferMethod	$transferMethod,
		HttpRequest\Response		$response
	)
	{
		parent::__construct(
			$transferMethod,
			$response,
			'https://api.github.com',
			[
				'Accept'		=> 'application/vnd.github.v3+json',
				'User-Agent'	=> 'peteonline/PHPLibrary',
				'Authorization'	=> "token $accessToken"
			]
		);
	}
	
}
