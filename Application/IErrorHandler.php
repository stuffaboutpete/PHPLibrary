<?php

namespace Suburb\Application;

use Suburb\Application;
use Suburb\Http\Response;

interface IErrorHandler
{
	
	public function setup(Application $application, Response $response);
	public function handleException(\Exception $exception, $recommendedResponseCode = null);
	public function handleError();
	
}
