<?php

namespace PO\Application;

use PO\Application;
use PO\Http\Response;

interface IDispatchable
{
	
	public function dispatch(Application $application, Response $response);
	
}