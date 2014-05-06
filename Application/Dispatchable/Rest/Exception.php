<?php

namespace Suburb\Application\Dispatchable\Rest;

class Exception
extends \Exception
{
	
	const NO_ENDPOINT_IDENTIFIED_FROM_REQUEST_PATH_AND_METHOD	= 1;
	const ENDPOINT_CLASS_DOES_NOT_EXIST							= 2;
	const ENDPOINT_CLASS_DOES_NOT_IMPLEMENT_IENDPOINT			= 3;
	
	public function __construct($code, $message = null)
	{
		parent::__construct($message, $code);
	}
	
}