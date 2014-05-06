<?php

namespace PO\HttpRequest;

interface ITransferMethod
{
	
	public function request(
		/* string */					$path,
		/* string */					$method,
		\PO\HttpRequest\Response	$response,
		array							$headers = null,
		/* mixed */						$data = null
	);
	
}