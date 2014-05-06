<?php

namespace PO\Application;

use PO\Application;
use PO\Http\Response;

interface IErrorHandler
{
	
	public function setup(Application $application, Response $response);
	public function handleException(\Exception $exception, $recommendedResponseCode = null);
	public function handleError();
	
}
