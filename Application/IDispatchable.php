<?php

namespace Suburb\Application;

use Suburb\Application;
use Suburb\Http\Response;

interface IDispatchable
{
	
	public function dispatch(Application $application, Response $response);
	
}