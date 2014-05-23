<?php

namespace PO\Application;

use PO\Http\Response;

interface IExceptionHandler
{
	
	public function handleException(\Exception $exception, Response $response, $responseCode = 500);
	
}
