<?php

namespace PO\Application\ExceptionHandler;

use PO\Application\IExceptionHandler;
use PO\Http\Response;
use PO\View\Debug as DebugView;

class View
implements IExceptionHandler
{
	
	public function __construct()
	{
		ini_set('display_errors', false);
	}
	
	public function handleException(\Exception $exception, Response $response, $responseCode = 500)
	{
		// @todo Type check response code
		$debugView = new DebugView($exception, $responseCode);
		$method = 'set' . $responseCode;
		$response->$method($debugView->__toString());
	}
	
}
