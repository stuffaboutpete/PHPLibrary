<?php

namespace PO\Application\ExceptionHandler;

use PO\Application\IExceptionHandler;
use PO\Http\Response;

class ErrorException
implements IExceptionHandler
{
	
	private $realExceptionHandler;
	private $response;
	
	public function __construct(IExceptionHandler $realExceptionHandler, Response $response)
	{
		
		$this->realExceptionHandler = $realExceptionHandler;
		$this->response = $response;
		
		set_error_handler([$this, 'handleError']);
		register_shutdown_function([$this, 'shutdown']);
		
	}
	
	public function handleException(\Exception $exception, Response $response, $responseCode = 500)
	{
		$this->realExceptionHandler->handleException($exception, $response, $responseCode);
	}
	
	public function handleError($errno, $errstr, $errfile, $errline, $previous)
	{
		throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
	}
	
	public function shutdown()
	{
		$error = error_get_last();
		if (!is_null($error)) {
			$exception = new \ErrorException(
				$error['message'],
				0,
				$error['type'],
				$error['file'],
				$error['line']
			);
			$this->realExceptionHandler->handleException($exception, $this->response);
			$this->response->process();
		}
	}
	
}
