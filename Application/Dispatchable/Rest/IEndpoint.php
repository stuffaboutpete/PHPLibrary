<?php

namespace PO\Application\Dispatchable\Rest;

use PO\Application;
use PO\Http\Response;

interface IEndpoint
{
	
	public function dispatch(Application $application, Response $response, array $routeVariables);
	
}