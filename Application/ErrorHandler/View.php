<?php

namespace Suburb\Application\ErrorHandler;

use Suburb\Application;
use Suburb\Http\Response;

class View
implements Application\IErrorHandler
{
	
	private $application;
	private $response;
	
	public function setup(Application $application, Response $response)
	{
		$this->application = $application;
		$this->response = $response;
		ini_set('display_errors', 0);
	}
	
	public function handleException(\Exception $exception, $recommendedResponseCode = null)
	{
		$responseCode = isset($recommendedResponseCode) ? $recommendedResponseCode : 500;
		$method = 'set' . $responseCode;
		$this->response->$method(
			(new \Suburb\View\Debug($exception, null, $responseCode))->__toString()
		);
	}
	
	
	public function handleError()
	{
		$error = error_get_last();
		$errors = [
			E_ERROR,
			E_WARNING,
			E_PARSE,
			E_COMPILE_ERROR,
			E_RECOVERABLE_ERROR,
			E_USER_ERROR,
			E_USER_WARNING
		];
		if (in_array($error['type'], $errors)) {
			echo (new \Suburb\View\Debug(null, error_get_last(), 500))->__toString();
		}
	}
	
}