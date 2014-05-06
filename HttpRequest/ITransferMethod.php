<?php

namespace Suburb\HttpRequest;

interface ITransferMethod
{
	
	public function request(
		/* string */					$path,
		/* string */					$method,
		\Suburb\HttpRequest\Response	$response,
		array							$headers = null,
		/* mixed */						$data = null
	);
	
}