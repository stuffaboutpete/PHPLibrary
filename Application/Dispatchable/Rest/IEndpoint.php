<?php

namespace Suburb\Application\Dispatchable\Rest;

use Suburb\Application;
use Suburb\Http\Response;

interface IEndpoint
{
	
	public function dispatch(Application $application, Response $response, array $routeVariables);
	
}